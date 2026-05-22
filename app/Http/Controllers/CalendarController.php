<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        $user   = auth()->user();
        $userId = $user->id;

        $canEnroll = !$user->isViewer() || $user->canEnrollOfficialExams();

        $upcoming = Quiz::confirmed()
            ->enrollmentsUpcoming()
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('enrollments_open_at')
            ->get();

        $open = Quiz::confirmed()
            ->enrollmentsOpen()
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('enrollments_close_at')
            ->get();

        $closed = Quiz::confirmed()
            ->enrollmentsClosed()
            ->with(['enrollments' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('enrollments_close_at', 'desc')
            ->limit(10)
            ->get();

        return view('calendar.index', compact('upcoming', 'open', 'closed', 'canEnroll', 'user'));
    }
}
