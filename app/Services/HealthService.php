<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthService
{
    public function getBackupStatus(): array
    {
        try {
            $disk = Storage::disk('backups');
            $appName = config('app.name');

            $allFiles = collect($disk->allFiles($appName))
                ->filter(fn(string $f) => str_ends_with($f, '.zip'))
                ->values();

            if ($allFiles->isEmpty()) {
                return [
                    'last_backup_at'    => null,
                    'last_backup_size'  => null,
                    'count'             => 0,
                    'total_size_bytes'  => 0,
                    'is_healthy'        => false,
                    'files'             => [],
                ];
            }

            $files = $allFiles->map(function (string $path) use ($disk) {
                return [
                    'path'         => $path,
                    'size'         => $disk->size($path),
                    'last_modified' => $disk->lastModified($path),
                ];
            })->sortByDesc('last_modified')->values();

            $latest      = $files->first();
            $lastBackupAt = \Carbon\Carbon::createFromTimestamp($latest['last_modified']);
            $isHealthy   = $lastBackupAt->diffInHours(now()) < 26;

            return [
                'last_backup_at'   => $lastBackupAt,
                'last_backup_size' => $latest['size'],
                'count'            => $files->count(),
                'total_size_bytes' => $files->sum('size'),
                'is_healthy'       => $isHealthy,
                'files'            => $files->take(10)->toArray(),
            ];
        } catch (Throwable $e) {
            Log::warning('HealthService::getBackupStatus failed', ['error' => $e->getMessage()]);

            return [
                'last_backup_at'   => null,
                'last_backup_size' => null,
                'count'            => 0,
                'total_size_bytes' => 0,
                'is_healthy'       => false,
                'files'            => [],
                'error'            => true,
            ];
        }
    }

    public function getDatabaseSize(): array
    {
        try {
            $dbName = DB::connection()->getDatabaseName();

            $total = DB::select("
                SELECT ROUND(SUM(data_length + index_length)) AS size_bytes
                FROM information_schema.tables
                WHERE table_schema = ? AND table_type = 'BASE TABLE'
            ", [$dbName]);

            $topTables = DB::select("
                SELECT table_name,
                       ROUND(data_length + index_length) AS size_bytes,
                       table_rows
                FROM information_schema.tables
                WHERE table_schema = ? AND table_type = 'BASE TABLE'
                ORDER BY table_rows DESC
                LIMIT 5
            ", [$dbName]);

            return [
                'total_bytes' => (int) ($total[0]->size_bytes ?? 0),
                'top_tables'  => collect($topTables)->map(fn($t) => [
                    'name'       => $t->table_name,
                    'size_bytes' => (int) $t->size_bytes,
                    'rows'       => (int) $t->table_rows,
                ])->toArray(),
            ];
        } catch (Throwable $e) {
            Log::warning('HealthService::getDatabaseSize failed', ['error' => $e->getMessage()]);

            return ['total_bytes' => 0, 'top_tables' => [], 'error' => true];
        }
    }

    public function getStorageSize(): array
    {
        try {
            $path  = storage_path('app/public');
            $bytes = 0;
            $count = 0;

            if (!is_dir($path)) {
                return ['size_bytes' => 0, 'file_count' => 0];
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $bytes += $file->getSize();
                    $count++;
                }
            }

            return ['size_bytes' => $bytes, 'file_count' => $count];
        } catch (Throwable $e) {
            Log::warning('HealthService::getStorageSize failed', ['error' => $e->getMessage()]);

            return ['size_bytes' => 0, 'file_count' => 0, 'error' => true];
        }
    }

    public function getQueueStatus(): array
    {
        try {
            $pendingByQueue = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->get()
                ->mapWithKeys(fn($row) => [$row->queue => (int) $row->count])
                ->toArray();

            $failedCount = DB::table('failed_jobs')->count();

            $recentFailed = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(5)
                ->get(['id', 'queue', 'failed_at', 'payload'])
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);

                    return [
                        'id'          => $job->id,
                        'queue'       => $job->queue,
                        'failed_at'   => $job->failed_at,
                        'job_class'   => $payload['displayName'] ?? 'unknown',
                    ];
                })
                ->toArray();

            return [
                'pending_total'    => array_sum($pendingByQueue),
                'pending_by_queue' => $pendingByQueue,
                'failed_count'     => $failedCount,
                'recent_failed'    => $recentFailed,
            ];
        } catch (Throwable $e) {
            Log::warning('HealthService::getQueueStatus failed', ['error' => $e->getMessage()]);

            return [
                'pending_total'    => 0,
                'pending_by_queue' => [],
                'failed_count'     => 0,
                'recent_failed'    => [],
                'error'            => true,
            ];
        }
    }

    public function getDiskSpace(): array
    {
        try {
            $path  = storage_path();
            $free  = disk_free_space($path);
            $total = disk_total_space($path);
            $used  = $total - $free;
            $pct   = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            return [
                'total_bytes' => (int) $total,
                'used_bytes'  => (int) $used,
                'free_bytes'  => (int) $free,
                'used_pct'    => $pct,
                'free_pct'    => round(100 - $pct, 1),
            ];
        } catch (Throwable $e) {
            Log::warning('HealthService::getDiskSpace failed', ['error' => $e->getMessage()]);

            return [
                'total_bytes' => 0,
                'used_bytes'  => 0,
                'free_bytes'  => 0,
                'used_pct'    => 0,
                'free_pct'    => 0,
                'error'       => true,
            ];
        }
    }

    public function getRecentErrors(int $limit = 10): array
    {
        try {
            $logFile = $this->resolveLogPath();

            if (!$logFile || !file_exists($logFile)) {
                return [];
            }

            $lines = $this->tailFile($logFile, 500);
            $errors = [];
            $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): (.+)/';

            foreach ($lines as $line) {
                if (preg_match($pattern, $line, $m)) {
                    $errors[] = [
                        'timestamp' => $m[1],
                        'level'     => $m[2],
                        'message'   => mb_substr($m[3], 0, 200),
                    ];
                }
            }

            return array_slice(array_reverse($errors), 0, $limit);
        } catch (Throwable $e) {
            Log::warning('HealthService::getRecentErrors failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function resolveLogPath(): ?string
    {
        $channel = config('logging.default', 'stack');
        $stack   = config('logging.channels.stack.channels', ['single']);

        // Se usa stack, guarda il primo canale concreto
        if ($channel === 'stack' && is_array($stack)) {
            $channel = $stack[0] ?? 'single';
        }

        if ($channel === 'single') {
            return storage_path('logs/laravel.log');
        }

        if ($channel === 'daily') {
            return storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
        }

        return storage_path('logs/laravel.log');
    }

    private function tailFile(string $path, int $lines): array
    {
        $file   = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $total = $file->key();

        $start = max(0, $total - $lines);
        $file->seek($start);

        $result = [];
        while (!$file->eof()) {
            $result[] = rtrim($file->fgets());
        }

        return $result;
    }

    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $b = (float) $bytes;

        while ($b >= 1024 && $i < count($units) - 1) {
            $b /= 1024;
            $i++;
        }

        return round($b, $precision) . ' ' . $units[$i];
    }
}
