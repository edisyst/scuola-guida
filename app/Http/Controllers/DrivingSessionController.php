<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDrivingSessionRequest;
use App\Models\DrivingSession;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\DrivingSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DrivingSessionController extends Controller
{
    public function __construct(private readonly DrivingSessionService $service) {}

    /**
     * Avanzamento guide pratiche di uno studente (visto da admin/instructor o dal viewer stesso).
     */
    public function index(User $student): View
    {
        abort_unless(
            auth()->user()->canRegisterDrivingSession() || auth()->id() === $student->id,
            403
        );

        $lt = $student->activeLicenseType ?? LicenseType::first();

        $progress = $lt
            ? $this->service->getProgress($student, $lt)
            : null;

        // Ultime 10 sessioni con eager loading per evitare N+1
        $sessions = DrivingSession::where('student_id', $student->id)
            ->with(['drivingModule', 'instructor', 'recorder'])
            ->orderByDesc('conducted_at')
            ->limit(10)
            ->get();

        return view('driving.progress', compact('student', 'progress', 'sessions', 'lt'));
    }

    /**
     * Registra una nuova sessione di guida pratica per uno studente.
     */
    public function store(StoreDrivingSessionRequest $request, User $student): RedirectResponse
    {
        abort_unless(auth()->user()->canRegisterDrivingSession(), 403);
        abort_unless($this->service->canRegisterForStudent(auth()->user(), $student), 403);

        $data               = $request->validated();
        $data['student_id'] = $student->id;

        // Istruttore loggato viene impostato automaticamente se il ruolo è instructor
        if (auth()->user()->isInstructor()) {
            $data['instructor_id'] = auth()->id();
        }

        $this->service->record($data);

        return redirect()
            ->route('instructor.students.show', $student)
            ->with('success', __('flash.driving_session_recorded'));
    }

    /**
     * Elimina una sessione di guida pratica.
     */
    public function destroy(User $student, DrivingSession $session): RedirectResponse
    {
        abort_unless(auth()->user()->canRegisterDrivingSession(), 403);
        abort_unless($this->service->canRegisterForStudent(auth()->user(), $student), 403);

        $this->service->delete($session);

        return back()->with('success', __('flash.driving_session_deleted'));
    }

    /**
     * Avanzamento del viewer autenticato (area personale).
     */
    public function progress(): View
    {
        /** @var User $student */
        $student = auth()->user();

        $lt = $student->activeLicenseType ?? LicenseType::first();

        $progress = $lt
            ? $this->service->getProgress($student, $lt)
            : null;

        $sessions = DrivingSession::where('student_id', $student->id)
            ->with(['drivingModule', 'instructor', 'recorder'])
            ->orderByDesc('conducted_at')
            ->limit(10)
            ->get();

        return view('driving.progress', compact('student', 'progress', 'sessions', 'lt'));
    }
}
