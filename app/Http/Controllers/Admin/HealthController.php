<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HealthService;
use Illuminate\Support\Facades\Artisan;

class HealthController extends Controller
{
    public function __construct(private HealthService $health) {}

    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.health.index', [
            'backupStatus' => $this->health->getBackupStatus(),
            'dbSize'       => $this->health->getDatabaseSize(),
            'storageSize'  => $this->health->getStorageSize(),
            'queueStatus'  => $this->health->getQueueStatus(),
            'diskSpace'    => $this->health->getDiskSpace(),
            'recentErrors' => $this->health->getRecentErrors(),
        ]);
    }

    public function runBackupNow()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        Artisan::queue('backup:run');

        return redirect()
            ->route('admin.health.index')
            ->with('info', 'Backup avviato in background. Tornerà disponibile tra qualche minuto.');
    }
}
