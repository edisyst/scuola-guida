<?php

namespace App\Services;

use App\Models\DrivingModule;
use App\Models\DrivingSession;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DrivingSessionService
{
    public function record(array $data): DrivingSession
    {
        $data['recorded_by'] = Auth::id();
        return DrivingSession::create($data);
    }

    public function delete(DrivingSession $session): void
    {
        $session->delete();
    }

    public function getProgress(User $student, LicenseType $lt): array
    {
        $modules = DrivingModule::where('license_type_id', $lt->id)
            ->ordered()
            ->get();

        // Una sola query per tutte le sessioni dello studente in questo tipo di patente
        $moduleIds = $modules->pluck('id');
        $sessions  = DrivingSession::where('student_id', $student->id)
            ->whereIn('driving_module_id', $moduleIds)
            ->get()
            ->groupBy('driving_module_id');

        $totalRequired  = 0.0;
        $totalCompleted = 0.0;
        $modulesData    = [];

        foreach ($modules as $module) {
            $modSessions      = $sessions->get($module->id, collect());
            $completedMinutes = $modSessions->sum('duration_minutes');
            $completedHours   = round($completedMinutes / 60, 1);
            $required         = (float) $module->required_hours;
            $completed        = $completedHours >= $required;

            $totalRequired  += $required;
            $totalCompleted += min($completedHours, $required);

            $modulesData[] = [
                'module'            => $module,
                'required_hours'    => $required,
                'completed_hours'   => $completedHours,
                'sessions_count'    => $modSessions->count(),
                'completed'         => $completed,
                'last_session_date' => $modSessions->sortByDesc('conducted_at')->first()?->conducted_at,
            ];
        }

        $percentage = $totalRequired > 0
            ? (int) round(($totalCompleted / $totalRequired) * 100)
            : 0;

        return [
            'modules'         => $modulesData,
            'total_required'  => $totalRequired,
            'total_completed' => $totalCompleted,
            'percentage'      => $percentage,
            'all_completed'   => $totalRequired > 0 && $totalCompleted >= $totalRequired,
        ];
    }

    public function canRegisterForStudent(User $actor, User $student): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return $actor->isInstructor() && $actor->hasStudent($student);
    }
}
