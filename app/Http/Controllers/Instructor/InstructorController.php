<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstructorNoteRequest;
use App\Models\InstructorNote;
use App\Models\User;
use App\Services\InstructorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class InstructorController extends Controller
{
    public function __construct(private InstructorService $instructorService) {}

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

        return view('instructor.student', compact('student', 'progress', 'notes'));
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
