<?php

namespace App\Http\Livewire;

use App\Models\QuestionReview;
use App\Services\ReviewErrorsService;
use App\Services\SpacedRepetitionService;
use Livewire\Component;

class SmartReview extends Component
{
    public array $reviewIds        = [];
    public int   $currentIndex     = 0;
    public bool  $showFeedback     = false;
    public bool  $lastAnswerCorrect = false;
    public array $sessionStats     = ['correct' => 0, 'wrong' => 0];
    public int   $lastIntervalDays = 1;
    public ?int  $categoryId       = null;

    public function mount(?int $categoryId = null): void
    {
        $this->categoryId = $categoryId;

        $this->reviewIds = app(SpacedRepetitionService::class)
            ->getDueQuestions(auth()->user(), $categoryId)
            ->pluck('id')
            ->toArray();
    }

    public function answer(int $userAnswer): void
    {
        // Evita doppio submit se il feedback è già visibile
        if ($this->showFeedback) {
            return;
        }

        $review = QuestionReview::find($this->reviewIds[$this->currentIndex] ?? null);

        if (!$review || !$review->question) {
            return;
        }

        $isCorrect = $userAnswer === (int) $review->question->is_true;

        $updated = app(SpacedRepetitionService::class)
            ->recordAnswer(auth()->user(), $review->question_id, $isCorrect);

        $this->lastIntervalDays  = $updated->interval_days;
        $this->lastAnswerCorrect = $isCorrect;

        if ($isCorrect) {
            $this->sessionStats['correct']++;
        } else {
            $this->sessionStats['wrong']++;
        }

        $this->showFeedback = true;
    }

    public function nextQuestion(): void
    {
        $this->showFeedback = false;
        $this->currentIndex++;
    }

    public function markCurrentAsLearned(): void
    {
        $review = QuestionReview::find($this->reviewIds[$this->currentIndex] ?? null);

        if ($review) {
            app(ReviewErrorsService::class)->markAsLearned(auth()->user(), $review->question_id);
        }

        $this->nextQuestion();
    }

    public function render(): \Illuminate\View\View
    {
        $currentReview   = null;
        $currentQuestion = null;
        $isFinished      = $this->currentIndex >= count($this->reviewIds);

        if (!$isFinished && isset($this->reviewIds[$this->currentIndex])) {
            $currentReview   = QuestionReview::with('question.category.translations', 'question.translations')
                ->find($this->reviewIds[$this->currentIndex]);
            $currentQuestion = $currentReview?->question;
        }

        $locale        = auth()->user()->getPreferredLocale();
        $localizedText = $currentQuestion?->getLocalizedText($locale);

        return view('livewire.smart-review', [
            'currentReview'   => $currentReview,
            'currentQuestion' => $currentQuestion,
            'localizedText'   => $localizedText,
            'total'           => count($this->reviewIds),
            'isFinished'      => $isFinished,
        ]);
    }
}
