<?php

namespace App\Services;

use App\Models\DrivingSession;
use App\Models\LicenseType;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DrivingAttestationService
{
    public function __construct(
        private DrivingSessionService $sessionService,
    ) {}

    public function buildData(User $student, LicenseType $lt): array
    {
        $progress = $this->sessionService->getProgress($student, $lt);

        // Zero N+1: precarica sessioni con relazioni
        $sessions = DrivingSession::where('student_id', $student->id)
            ->whereIn('driving_module_id', collect($progress['modules'])->pluck('module.id'))
            ->with('drivingModule', 'instructor')
            ->orderBy('conducted_at')
            ->get()
            ->groupBy('driving_module_id');

        // Estrai istruttori unici (non null)
        $instructors = DrivingSession::where('student_id', $student->id)
            ->whereIn('driving_module_id', collect($progress['modules'])->pluck('module.id'))
            ->whereNotNull('instructor_id')
            ->distinct('instructor_id')
            ->with('instructor')
            ->get()
            ->map(fn($s) => $s->instructor)
            ->unique('id')
            ->values();

        return [
            'school'    => config('driving'),
            'student'   => [
                'id'    => $student->id,
                'name'  => $student->name,
                'email' => $student->email,
                'dob'   => $student->birth_date,
            ],
            'license_type' => $lt->name,
            'generated_at' => now(),
            'progress'     => $progress,
            'sessions'     => $sessions,
            'instructors'  => $instructors,
        ];
    }

    public function generatePdf(User $student, LicenseType $lt): string
    {
        $data = $this->buildData($student, $lt);

        $pdf = Pdf::loadView('driving.pdf.attestation', $data);

        $filename = sprintf(
            'attestazione_%d_%s_%s.pdf',
            $student->id,
            $lt->code,
            now()->format('YmdHis')
        );

        $path = 'private/driving-attestations/' . $filename;
        Storage::disk('local')->put($path, $pdf->output());

        return Storage::disk('local')->path($path);
    }
}
