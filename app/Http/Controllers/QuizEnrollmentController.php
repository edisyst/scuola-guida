<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizEnrollment;
use App\Models\User;
use App\Services\QuizEnrollmentService;
use Illuminate\Http\Request;
use RuntimeException;

class QuizEnrollmentController extends Controller
{
    public function __construct(private QuizEnrollmentService $service) {}

    /*
    |--------------------------------------------------------------------------
    | VIEWER
    |--------------------------------------------------------------------------
    */

    /**
     * Catalogo dei quiz confermati con lo stato dell'iscrizione del viewer.
     */
    public function catalog()
    {
        $user = auth()->user();

        $quizzes = Quiz::confirmed()
            ->withCount('questions')
            ->orderByDesc('confirmed_at')
            ->get();

        $enrollments = QuizEnrollment::where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->latest('id')
            ->get()
            ->groupBy('quiz_id');

        $canEnroll = !$user->isViewer() || $user->canEnrollOfficialExams();

        return view('quiz.confirmed.index', compact('quizzes', 'enrollments', 'canEnroll', 'user'));
    }

    /**
     * Il viewer richiede l'iscrizione a un quiz confermato.
     */
    public function store(Quiz $quiz)
    {
        try {
            $this->service->request($quiz, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('flash.enrollment_requested'));
    }

    /**
     * Il viewer visualizza le proprie iscrizioni.
     */
    public function myEnrollments()
    {
        $enrollments = QuizEnrollment::with(['quiz', 'reviewer'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('quiz.enrollments.index', compact('enrollments'));
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    /**
     * Vista admin di tutte le iscrizioni (filtrabili per stato).
     */
    public function adminIndex(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $status = $request->query('status');

        $enrollments = QuizEnrollment::with(['quiz', 'user', 'reviewer'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = QuizEnrollment::pending()->count();

        return view('admin.enrollments.index', compact('enrollments', 'status', 'pendingCount'));
    }

    public function approve(QuizEnrollment $enrollment)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $this->service->approve($enrollment, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Iscrizione approvata.');
    }

    public function reject(QuizEnrollment $enrollment)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $this->service->reject($enrollment, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Iscrizione rifiutata.');
    }

    public function reopen(Quiz $quiz, User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        try {
            $this->service->reopen($quiz, $user, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Iscrizione riaperta per ' . $user->name . '.');
    }
}
