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

        if (!$this->completed && isset($this->questionIds[$this->currentIndex])) {
            $currentQuestion = Question::find($this->questionIds[$this->currentIndex]);
        }

        return view('livewire.diagnostic-test', [
            'currentQuestion' => $currentQuestion,
            'total'           => count($this->questionIds),
        ]);
    }
}
