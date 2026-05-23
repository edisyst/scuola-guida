<?php

namespace App\Services;

use App\Models\LearnedQuestion;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;

class ReviewErrorsService
{
    /**
     * Aggrega le domande sbagliate dell'utente negli ultimi $lastAttempts tentativi completati.
     * Esclude le domande già marcate come "imparate".
     */
    public function getErrors(User $user, ?int $categoryId = null, int $lastAttempts = 20): Collection
    {
        $attempts = $user->quizAttempts()
            ->whereNotNull('answers')
            ->where('answers', '!=', '[]')
            ->where('answers', '!=', '{}')
            ->latest()
            ->limit($lastAttempts)
            ->get();

        $errors = []; // question_id => ['count' => int, 'last_wrong_at' => Carbon|null]

        foreach ($attempts as $attempt) {
            foreach (array_keys($attempt->answers ?? []) as $rawId) {
                if ($attempt->getAnswerResult($rawId) !== 0) {
                    continue;
                }
                $qid = (int) $rawId;
                if (!isset($errors[$qid])) {
                    $errors[$qid] = ['count' => 0, 'last_wrong_at' => null];
                }
                $errors[$qid]['count']++;
                $wrongAt = $attempt->getAnsweredAt($rawId) ?? $attempt->updated_at;
                if ($errors[$qid]['last_wrong_at'] === null || $wrongAt > $errors[$qid]['last_wrong_at']) {
                    $errors[$qid]['last_wrong_at'] = $wrongAt;
                }
            }
        }

        if (empty($errors)) {
            return collect();
        }

        $learnedIds = LearnedQuestion::where('user_id', $user->id)->pluck('question_id')->all();
        foreach ($learnedIds as $id) {
            unset($errors[$id]);
        }

        if (empty($errors)) {
            return collect();
        }

        $query = Question::whereIn('id', array_keys($errors));
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
        $questions = $query->get(); // category già in $with del model

        $result = collect();
        foreach ($questions as $question) {
            $data = $errors[$question->id];
            $result->push([
                'question'      => $question,
                'error_count'   => $data['count'],
                'last_wrong_at' => $data['last_wrong_at'],
                'category'      => $question->category,
            ]);
        }

        return $result->sort(function ($a, $b) {
            if ($a['error_count'] !== $b['error_count']) {
                return $b['error_count'] <=> $a['error_count'];
            }
            return $b['last_wrong_at'] <=> $a['last_wrong_at'];
        })->values();
    }

    public function markAsLearned(User $user, int $questionId): void
    {
        LearnedQuestion::firstOrCreate(
            ['user_id' => $user->id, 'question_id' => $questionId],
            ['marked_at' => now()]
        );
    }

    public function unmarkAsLearned(User $user, int $questionId): void
    {
        LearnedQuestion::where('user_id', $user->id)
            ->where('question_id', $questionId)
            ->delete();
    }

    /**
     * Restituisce le domande marcate come imparate, opzionalmente filtrate per categoria.
     */
    public function getLearned(User $user, ?int $categoryId = null): Collection
    {
        $learnedIds = LearnedQuestion::where('user_id', $user->id)->pluck('question_id');

        $query = Question::whereIn('id', $learnedIds);
        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return $query->get(); // category già in $with del model
    }
}
