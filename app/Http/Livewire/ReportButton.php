<?php

namespace App\Http\Livewire;

use App\Models\QuestionReport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ReportButton extends Component
{
    public int $questionId;
    public bool $submitted = false;
    public bool $open      = false;

    #[Validate('required|string|in:risposta_errata,testo_ambiguo,immagine_mancante,contenuto_obsoleto,altro')]
    public string $type = 'altro';

    #[Validate('required|string|min:10|max:1000')]
    public string $body = '';

    public function mount(int $questionId): void
    {
        $this->questionId = $questionId;
    }

    /**
     * Aggiornamento dinamico della domanda corrente (usato dalle view play
     * dove le domande sono renderizzate via JS senza ricaricare la pagina).
     * Reset di stato per evitare che un report scritto per la domanda X
     * venga inviato per la domanda Y.
     */
    #[On('report-button-set-question')]
    public function setCurrentQuestion(int $id): void
    {
        if ($id === $this->questionId) {
            return;
        }
        $this->questionId = $id;
        $this->open       = false;
        $this->submitted  = false;
        $this->reset(['body']);
        $this->type = 'altro';
        $this->resetErrorBag();
    }

    public function toggleForm(): void
    {
        $this->open = !$this->open;

        if (!$this->open) {
            $this->reset(['type', 'body', 'submitted']);
            $this->type = 'altro';
            $this->resetErrorBag();
        }
    }

    public function sendReport(): void
    {
        if (!Auth::check() || !Auth::user()->isViewer()) {
            return;
        }

        $this->validate();

        $pendingCount = QuestionReport::where('question_id', $this->questionId)
            ->where('user_id', Auth::id())
            ->pending()
            ->count();

        if ($pendingCount >= 3) {
            $this->addError('body', 'Hai già inviato troppe segnalazioni per questa domanda. Attendi che vengano esaminate.');
            return;
        }

        QuestionReport::create([
            'question_id' => $this->questionId,
            'user_id'     => Auth::id(),
            'type'        => $this->type,
            'body'        => $this->body,
        ]);

        $this->submitted = true;
        $this->open      = false;
        $this->reset(['type', 'body']);
        $this->type = 'altro';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.report-button');
    }
}
