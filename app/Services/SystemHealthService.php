<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemHealthService
{
    public function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'label' => 'Connesso'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'label' => 'Non connesso', 'detail' => $e->getMessage()];
        }
    }

    public function checkRedis(): array
    {
        try {
            Redis::connection()->ping();

            return ['status' => 'ok', 'label' => 'Connesso'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'label' => 'Non connesso', 'detail' => $e->getMessage()];
        }
    }

    public function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();

            if ($failed > 0) {
                return [
                    'status'  => 'warning',
                    'label'   => 'Warning',
                    'pending' => $pending,
                    'failed'  => $failed,
                ];
            }

            return [
                'status'  => 'ok',
                'label'   => 'OK',
                'pending' => $pending,
                'failed'  => 0,
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'label' => 'Errore', 'detail' => $e->getMessage()];
        }
    }

    public function checkStorage(): array
    {
        try {
            Storage::disk('public')->exists('.');

            return ['status' => 'ok', 'label' => 'Accessibile'];
        } catch (Throwable $e) {
            return ['status' => 'error', 'label' => 'Non accessibile', 'detail' => $e->getMessage()];
        }
    }

    public function checkMail(): array
    {
        $driver = config('mail.default', '');
        $host   = config("mail.mailers.{$driver}.host", '');

        if (in_array($driver, ['log', 'array'])) {
            return ['status' => 'warning', 'label' => 'Driver dev (' . $driver . ')', 'driver' => $driver];
        }

        if (empty($host)) {
            return ['status' => 'error', 'label' => 'Non configurato', 'driver' => $driver];
        }

        return ['status' => 'ok', 'label' => 'Configurato', 'driver' => $driver, 'host' => $host];
    }

    public function checkTwilio(): array
    {
        $sid     = config('services.twilio.sid', env('TWILIO_ACCOUNT_SID', ''));
        $token   = config('services.twilio.token', env('TWILIO_AUTH_TOKEN', ''));
        $enabled = (bool) env('MESSAGING_ENABLED', false);

        if (empty($sid) || empty($token)) {
            return ['status' => 'error', 'label' => 'Non configurato'];
        }

        if (! $enabled) {
            return ['status' => 'warning', 'label' => 'Configurato (disabilitato)', 'enabled' => false];
        }

        return ['status' => 'ok', 'label' => 'Configurato e abilitato', 'enabled' => true];
    }
}
