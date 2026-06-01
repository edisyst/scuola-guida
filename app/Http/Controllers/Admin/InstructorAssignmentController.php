<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStudentRequest;
use App\Models\User;
use App\Services\InstructorService;
use Illuminate\Http\Request;

class InstructorAssignmentController extends Controller
{
    public function __construct(private InstructorService $instructorService) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->canEditUser(), 403);

        $instructors = User::where('role', User::ROLE_INSTRUCTOR)
            ->withCount('students')
            ->orderBy('name')
            ->get();

        return view('admin.instructors.index', compact('instructors'));
    }

    public function edit(Request $request, User $instructor)
    {
        abort_unless($request->user()->canEditUser(), 403);
        abort_unless($instructor->isInstructor(), 404);

        $assigned = $instructor->students()->orderBy('name')->get();

        $viewers = User::where('role', User::ROLE_VIEWER)
            ->whereNotIn('id', $assigned->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('admin.instructors.edit', compact('instructor', 'assigned', 'viewers'));
    }

    public function assign(AssignStudentRequest $request, User $instructor)
    {
        abort_unless($instructor->isInstructor(), 404);

        $assignedBy = $request->user();
        $errors = [];

        foreach ($request->validated('student_ids') as $studentId) {
            $student = User::find($studentId);
            if (!$student || !$student->isViewer()) {
                $errors[] = $studentId;
                continue;
            }
            $this->instructorService->assignStudent($instructor, $student, $assignedBy);
        }

        if (!empty($errors)) {
            return redirect()
                ->route('admin.instructors.edit', $instructor)
                ->with('warning', 'Alcuni studenti non sono stati assegnati (ID non validi o non viewer).');
        }

        return redirect()
            ->route('admin.instructors.edit', $instructor)
            ->with('success', 'Studenti assegnati con successo.');
    }

    public function unassign(Request $request, User $instructor, User $student)
    {
        abort_unless($request->user()->canEditUser(), 403);
        abort_unless($instructor->isInstructor(), 404);

        $this->instructorService->unassignStudent($instructor, $student);

        return redirect()
            ->route('admin.instructors.edit', $instructor)
            ->with('success', 'Studente rimosso dall\'istruttore.');
    }
}
