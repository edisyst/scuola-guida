<?php

namespace App\DataTables;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class QuizQuestionsDataTable
{
    public function response(Request $request, Quiz $quiz): JsonResponse
    {
        $query = Question::with('category')->select('questions.*');

        if ($quiz->license_type_id) {
            $query->whereHas('category.licenseTypes', fn ($q) => $q->where('license_types.id', $quiz->license_type_id));
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $quizQuestionIds = $quiz->questions()->pluck('questions.id')->toArray();

        return DataTables::of($query)
            ->addColumn('category', fn (Question $q) => $q->category->name ?? '-')
            ->addColumn('is_in_quiz', fn (Question $q) => in_array($q->id, $quizQuestionIds) ? 'added' : 'pending')
            ->addColumn('status', function (Question $q) use ($quizQuestionIds) {
                return in_array($q->id, $quizQuestionIds)
                    ? '<span class="badge badge-success">✔ Nel quiz</span>'
                    : '<span class="badge badge-secondary">Non presente</span>';
            })
            ->addColumn('action', function (Question $q) use ($quizQuestionIds) {
                $exists       = in_array($q->id, $quizQuestionIds);
                $questionText = htmlspecialchars($q->question, ENT_QUOTES, 'UTF-8');

                if ($exists) {
                    return '<button class="btn btn-sm btn-danger btn-remove"
                                data-id="' . $q->id . '"
                                data-text="' . $questionText . '">
                                Rimuovi
                            </button>';
                }

                return '<button class="btn btn-sm btn-success btn-add"
                            data-id="' . $q->id . '"
                            data-text="' . $questionText . '">
                            Aggiungi
                        </button>';
            })
            ->addColumn('in_quiz', fn (Question $q) => in_array($q->id, $quizQuestionIds))
            ->rawColumns(['status', 'action'])
            ->make(true);
    }
}
