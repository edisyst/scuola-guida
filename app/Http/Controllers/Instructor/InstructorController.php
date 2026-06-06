<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstructorNoteRequest;
use App\Models\DrivingSession;
use App\Models\InstructorNote;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\DrivingSessionService;
use App\Services\InstructorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class InstructorController extends Controller
{
    public function __construct(
        private InstructorService $instructorService,
        private DrivingSessionService $drivingSessionService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user->isInstructor() || $user->isAdmin(), 403);

        $instructor = $user->isAdmin() ? $user : $user;

        $overview = $this->instructorService->getInstructorOverview($instructor);

        return view('instructor.index', compact('overview', 'instructor'));
    }

    public function showStudent(Request $request, User $student)
    {
        $user = $request->user();

        abort_unless($user->isInstructor() || $user->isAdmin(), 403);

        if ($user->isInstructor()) {
            abort_unless($user->hasStudent($student), 403, 'Studente non assegnato a questo istruttore.');
        }

        $progress = $this->instructorService->getStudentProgress($student);
        $notes    = $this->instructorService->getNotesForStudent($user, $student);

        // Dati guide pratiche — eager loading per evitare N+1
        $lt              = $student->activeLicenseType ?? LicenseType::first();
        $drivingProgress = $lt
            ? $this->drivingSessionService->getProgress($student, $lt)
            : ['modules' => [], 'total_required' => 0, 'total_completed' => 0, 'percentage' => 0, 'all_completed' => false];

        $drivingSessions = DrivingSession::where('student_id', $student->id)
            ->with(['drivingModule', 'instructor', 'recorder'])
            ->orderByDesc('conducted_at')
            ->limit(20)
            ->get();

        $drivingModules = $lt
            ? \App\Models\DrivingModule::where('license_type_id', $lt->id)->ordered()->get()
            : collect();

        // Considera "teoria superata" se lo studente ha almeno un tentativo completato con score non nullo
        $hasPassedTheory = $student->quizAttempts()
            ->whereNotNull('score')
            ->where('score', '>', 0)
            ->exists();

        return view('instructor.student', compact(
            'student', 'progress', 'notes',
            'drivingProgress', 'drivingSessions', 'drivingModules', 'hasPassedTheory', 'lt'
        ));
    }

    public function storeNote(StoreInstructorNoteRequest $request, User $student): RedirectResponse
    {
        $instructor = $request->user();

        abort_unless($instructor->isInstructor() || $instructor->isAdmin(), 403);
        abort_unless($instructor->hasStudent($student), 403, 'Studente non assegnato a questo istruttore.');

        $this->instructorService->addNote($instructor, $student, $request->validated()['body']);

        return redirect()
            ->route('instructor.students.show', $student)
            ->with('success', __('flash.instructor_note_added'));
    }

    public function destroyNote(Request $request, User $student, InstructorNote $note): RedirectResponse
    {
        $instructor = $request->user();

        abort_unless($instructor->isInstructor() || $instructor->isAdmin(), 403);
        abort_unless($note->instructor_id === $instructor->id, 403, 'Non puoi eliminare questa nota.');

        $this->instructorService->deleteNote($instructor, $note);

        return redirect()
            ->route('instructor.students.show', $student)
            ->with('success', __('flash.instructor_note_deleted'));
    }

    public function exportStudentPdf(Request $request, User $student): Response
    {
        $instructor = $request->user();

        abort_unless($instructor->isInstructor() || $instructor->isAdmin(), 403);
        abort_unless($instructor->hasStudent($student) || $instructor->canEditUser(), 403);

        $data = $this->instructorService->prepareStudentExportData($instructor, $student);

        $pdf = Pdf::loadView('instructor.pdf.student-progress', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'progressi-' . Str::slug($student->name) . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
