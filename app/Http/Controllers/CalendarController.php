<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizEnrollment;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $upcoming = Quiz::confirmed()
            ->enrollmentsUpcoming()
            ->orderBy('enrollments_open_at')
            ->get();

        $open = Quiz::confirmed()
            ->enrollmentsOpen()
            ->orderBy('enrollments_open_at', 'desc')
            ->get();

        $closed = Quiz::confirmed()
            ->enrollmentsClosed()
            ->orderBy('enrollments_close_at', 'desc')
            ->limit(10)
            ->get();

        $userEnrollmentQuizIds = QuizEnrollment::where('user_id', $user->id)
            ->pluck('quiz_id')
            ->toArray();

        // Stessa logica del catalogo quiz confermati
        $canEnroll = !$user->isViewer() || $user->canEnrollOfficialExams();

        return view('calendar.index', compact(
            'upcoming', 'open', 'closed', 'userEnrollmentQuizIds', 'canEnroll', 'user'
        ));
    }
}
