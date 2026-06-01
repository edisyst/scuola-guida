<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InstructorService;
use Illuminate\Http\Request;

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

        return view('instructor.student', compact('student', 'progress'));
    }
}
