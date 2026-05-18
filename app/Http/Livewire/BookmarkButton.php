<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class BookmarkButton extends Component
{
    public int $questionId;
    public bool $isBookmarked = false;
    public ?string $note = null;

    public function mount(int $questionId): void
    {
        $this->questionId = $questionId;

        if (!Auth::check() || !Auth::user()->isViewer()) {
            return;
        }

        $bookmark = Auth::user()
            ->bookmarkedQuestions()
            ->wherePivot('question_id', $this->questionId)
            ->first();

        $this->isBookmarked = $bookmark !== null;
        $this->note = $bookmark?->pivot->note;
    }

    public function toggleBookmark(): void
    {
        if (!Auth::check() || !Auth::user()->isViewer()) {
            return;
        }

        $result = Auth::user()->bookmarkedQuestions()->toggle([$this->questionId]);

        $this->isBookmarked = !empty($result['attached']);

        if (!$this->isBookmarked) {
            $this->note = null;
        }
    }

    public function saveNote(): void
    {
        if (!Auth::check() || !Auth::user()->isViewer()) {
            return;
        }

        $this->validate(['note' => 'nullable|max:500']);

        if (!$this->isBookmarked) {
            return;
        }

        Auth::user()
            ->bookmarkedQuestions()
            ->updateExistingPivot($this->questionId, ['note' => $this->note]);
    }

    public function render()
    {
        return view('livewire.bookmark-button');
    }
}
