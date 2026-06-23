<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Services\LicenseTypeService;
use Illuminate\Http\Request;

class QuizTestController extends Controller
{
    public function index(Request $request)
    {
        $licenseTypeId = $request->query('license_type_id');

        $quizzes = Quiz::published()
            ->with('licenseType')
            ->withCount('questions')
            ->when($licenseTypeId, fn ($q, $v) => $q->where('license_type_id', $v))
            ->get();

        $licenseTypes = app(LicenseTypeService::class)->allForSelect();

        return view('quiz-test.index', compact('quizzes', 'licenseTypes', 'licenseTypeId'));
    }

    public function play(Quiz $quiz)
    {
        abort_unless($quiz->isPublished(), 404);

        $locale = auth()->user()->getPreferredLocale();

        $questionsJson = $quiz->questions()
            ->with('translations')
            ->get()
            ->map(fn ($q) => [
                'id'      => $q->id,
                'text'    => $q->getLocalizedText($locale),
                'image'   => $q->image ? asset('storage/' . $q->image) : null,
                'correct' => (int) $q->is_true,
            ])
            ->all();

        return view('quiz-test.play', [
            'quiz'          => $quiz,
            'timeLimit'     => $quiz->time_limit,
            'maxErrors'     => $quiz->max_errors,
            'questionsJson' => $questionsJson,
        ]);
    }
}
