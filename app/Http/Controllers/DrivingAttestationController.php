<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\DrivingAttestationService;
use App\Services\DrivingSessionService;

class DrivingAttestationController extends Controller
{
    public function __construct(
        private DrivingAttestationService $attestationService,
        private DrivingSessionService $sessionService,
    ) {}

    public function download(User $student)
    {
        // Resolve active license type
        $licenseType = $student->activeLicenseType;
        if (!$licenseType) {
            abort(422, __('driving.no_license_type'));
        }

        // Authorization
        $isAdmin = auth()->user()->isAdmin() || auth()->user()->canEditUser();
        $isOwnInstructor = auth()->user()->isInstructor()
            && auth()->user()->hasStudent($student);
        $isOwner = auth()->id() === $student->id;

        abort_unless($isAdmin || $isOwnInstructor || $isOwner, 403);

        // Viewer: check completion
        if ($isOwner && !$isAdmin && !$isOwnInstructor) {
            $progress = $this->sessionService->getProgress($student, $licenseType);
            abort_unless($progress['all_completed'], 403);
        }

        // Generate PDF
        $path = $this->attestationService->generatePdf($student, $licenseType);

        // Audit log
        AuditLog::create([
            'user_id'     => $student->id,
            'event'       => 'export_driving_attestation',
            'model_type'  => 'User',
            'model_id'    => $student->id,
            'old_values'  => [],
            'new_values'  => [
                'license_type_id' => $licenseType->id,
                'exported_by'     => auth()->id(),
            ],
        ]);

        return response()->download($path, 'riepilogo-guide-' . $student->id . '.pdf')
            ->deleteFileAfterSend(true);
    }
}
