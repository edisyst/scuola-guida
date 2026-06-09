<?php

namespace App\Services;

use App\Exceptions\DrivingModuleSequenceException;
use App\Models\DrivingModule;
use App\Models\DrivingSession;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DrivingSessionService
{
    public function record(array $data): DrivingSession
    {
        $student = User::findOrFail($data['student_id']);
        $module  = DrivingModule::findOrFail($data['driving_module_id']);
        $actor   = Auth::user();

        if ($actor !== null && ! $this->canRegisterForModule($actor, $student, $module)) {
            throw new DrivingModuleSequenceException(
                __('driving.error_sequence', ['module' => $module->name])
            );
        }

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

    /**
     * Stato completo del percorso formativo: completamento, data certificazione, prossimo modulo.
     * Zero N+1: una sola query per le sessioni, il resto in memoria.
     */
    public function getCompletionStatus(User $student, LicenseType $licenseType): array
    {
        $modules = DrivingModule::where('license_type_id', $licenseType->id)
            ->ordered()
            ->get();

        $moduleIds   = $modules->pluck('id');
        $allSessions = DrivingSession::where('student_id', $student->id)
            ->whereIn('driving_module_id', $moduleIds)
            ->orderBy('conducted_at')
            ->orderBy('id')
            ->get()
            ->groupBy('driving_module_id');

        $totalRequired        = 0.0;
        $totalCompleted       = 0.0;
        $completedModuleIds   = [];
        $nextRequiredModuleId = null;
        $moduleCompletionDates = [];
        $modulesDetail        = [];

        foreach ($modules as $module) {
            $modSessions    = $allSessions->get($module->id, collect());
            $required       = (float) $module->required_hours;
            $completedHours = round($modSessions->sum('duration_minutes') / 60, 1);
            $completed      = $completedHours >= $required;

            // Trova la sessione che ha completato questo modulo
            $moduleCompletionDate = null;
            if ($completed) {
                $runningMinutes = 0;
                foreach ($modSessions as $session) {
                    $runningMinutes += $session->duration_minutes;
                    if (round($runningMinutes / 60, 1) >= $required) {
                        $moduleCompletionDate = $session->conducted_at;
                        break;
                    }
                }
            }

            $totalRequired  += $required;
            $totalCompleted += min($completedHours, $required);

            if ($completed) {
                $completedModuleIds[] = $module->id;
                if ($moduleCompletionDate !== null) {
                    $moduleCompletionDates[] = $moduleCompletionDate;
                }
            } elseif ($nextRequiredModuleId === null) {
                $nextRequiredModuleId = $module->id;
            }

            $modulesDetail[] = [
                'module'          => $module,
                'required_hours'  => $required,
                'completed_hours' => $completedHours,
                'completed'       => $completed,
            ];
        }

        $allCompleted = $modules->isNotEmpty() && count($completedModuleIds) === $modules->count();

        $completionDate = null;
        if ($allCompleted && ! empty($moduleCompletionDates)) {
            $completionDate = collect($moduleCompletionDates)->max();
        }

        $percentage = $totalRequired > 0
            ? (int) round(($totalCompleted / $totalRequired) * 100)
            : 0;

        return [
            'all_completed'           => $allCompleted,
            'completed_modules'       => $completedModuleIds,
            'next_required_module_id' => $nextRequiredModuleId,
            'total_required_hours'    => $totalRequired,
            'total_completed_hours'   => $totalCompleted,
            'percentage'              => $percentage,
            'completion_date'         => $completionDate,
            'modules_detail'          => $modulesDetail,
        ];
    }

    /**
     * Verifica che l'actor possa registrare sessioni per il modulo specificato dello student.
     * Controlla autorizzazione e sequenzialità (decreto 294/2025).
     * Se lo student non ha un tipo patente attivo il vincolo di sequenza non si applica.
     */
    public function canRegisterForModule(User $actor, User $student, DrivingModule $module): bool
    {
        if (! $this->canRegisterForStudent($actor, $student)) {
            return false;
        }

        $licenseType = $student->activeLicenseType;
        if (! $licenseType) {
            return true;
        }

        if ($module->license_type_id !== $licenseType->id) {
            return false;
        }

        $status = $this->getCompletionStatus($student, $licenseType);

        foreach ($status['modules_detail'] as $detail) {
            if ($detail['module']->sort_order < $module->sort_order && ! $detail['completed']) {
                return false;
            }
        }

        return true;
    }

    public function canRegisterForStudent(User $actor, User $student): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return $actor->isInstructor() && $actor->hasStudent($student);
    }
}
