<?php

namespace App\Http\Livewire;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Services\QuestionVersionService;
use Illuminate\Support\Collection;
use Livewire\Component;

class QuestionVersionHistory extends Component
{
    public int $questionId;

    public bool $showModal    = false;
    public ?int $modalVersion = null;

    public function mount(int $questionId): void
    {
        $this->questionId = $questionId;
    }

    public function openModal(int $versionId): void
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);
        $this->modalVersion = $versionId;
        $this->showModal    = true;
    }

    public function closeModal(): void
    {
        $this->showModal    = false;
        $this->modalVersion = null;
    }

    public function restoreVersion(int $versionId): void
    {
        abort_unless(auth()->user()->canEditQuestion(), 403);

        $question = Question::findOrFail($this->questionId);
        $version  = QuestionVersion::where('question_id', $this->questionId)->findOrFail($versionId);

        app(QuestionVersionService::class)->restoreVersion($question, $version);

        $this->showModal    = false;
        $this->modalVersion = null;

        session()->flash('success', "Versione {$version->version_number} ripristinata con successo.");
        $this->dispatch('version-restored');
    }

    public function render(): \Illuminate\View\View
    {
        $question = Question::findOrFail($this->questionId);

        /** @var Collection<QuestionVersion> $versions */
        $versions = QuestionVersion::where('question_id', $this->questionId)
            ->with('creator:id,name')
            ->orderByDesc('version_number')
            ->get();

        // Per ogni versione calcola un diff sintetico rispetto alla precedente.
        $withDiff = $versions->map(function (QuestionVersion $v, int $index) use ($versions) {
            $prev   = $versions->get($index + 1); // versione precedente (ordine desc)
            $fields = [];

            if ($prev) {
                if ($v->question !== $prev->question) {
                    $fields[] = 'testo';
                }
                if ((bool) $v->is_true !== (bool) $prev->is_true) {
                    $fields[] = 'risposta';
                }
                if ($v->image !== $prev->image) {
                    $fields[] = 'immagine';
                }
                if ($v->category_id !== $prev->category_id) {
                    $fields[] = 'categoria';
                }
            }

            return [
                'version'        => $v,
                'changed_fields' => $fields,
            ];
        });

        $modalVersionModel = $this->modalVersion
            ? $versions->firstWhere('id', $this->modalVersion)
            : null;

        return view('livewire.question-version-history', [
            'question'          => $question,
            'versionsWithDiff'  => $withDiff,
            'modalVersionModel' => $modalVersionModel,
        ]);
    }
}
