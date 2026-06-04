<?php

namespace App\Http\Livewire;

use App\Models\Question;
use App\Services\DiagnosticService;
use Illuminate\View\View;
use Livewire\Component;

class DiagnosticTest extends Component
{
    public array $questionIds = [];
    public int   $currentIndex = 0;
    public array $answers = [];
    public bool  $completed = false;

    public function mount(DiagnosticService $service): void
    {
        $this->questionIds = $service->generateQuestions(auth()->user())
            ->pluck('id')
            ->toArray();
    }

    public function submitAnswer(int $answer): void
    {
        $questionId = $this->questionIds[$this->currentIndex] ?? null;

        if ($questionId === null) {
            return;
        }

        $this->answers[(string) $questionId] = $answer;

        if ($this->currentIndex >= count($this->questionIds) - 1) {
            app(DiagnosticService::class)->saveResults(auth()->user(), $this->answers);
            $this->completed = true;
        } else {
            $this->currentIndex++;
        }
    }

    public function render(): View
    {
        $currentQuestion = null;
        $localizedText   = null;

        if (!$this->completed && isset($this->questionIds[$this->currentIndex])) {
            // Eager-load translations (Feature 7.1): localizzazione testo senza N+1.
            $currentQuestion = Question::with(['category', 'translations'])
                ->find($this->questionIds[$this->currentIndex]);

            if ($currentQuestion) {
                $locale        = auth()->user()->getPreferredLocale();
                $localizedText = $currentQuestion->getLocalizedText($locale);
            }
        }

        return view('livewire.diagnostic-test', [
            'currentQuestion' => $currentQuestion,
            'localizedText'   => $localizedText,
            'total'           => count($this->questionIds),
        ]);
    }
}
