<?php

namespace App\Http\Livewire;

use App\Models\StudyContent;
use App\Services\StudyContentService;
use Illuminate\Support\Collection;
use Livewire\Component;

class StudyContentViewer extends Component
{
    public string $studyableType;
    public int    $studyableId;

    /** @var array<int, bool> Map content id → isRead */
    public array $readMap = [];

    public function mount(string $studyableType, int $studyableId): void
    {
        $this->studyableType = $studyableType;
        $this->studyableId   = $studyableId;
        $this->buildReadMap();
    }

    public function markRead(int $contentId): void
    {
        $content = StudyContent::findOrFail($contentId);
        app(StudyContentService::class)->markAsRead($content, auth()->user());
        $this->readMap[$contentId] = true;
    }

    public function render()
    {
        $contents = app(StudyContentService::class)
            ->getForStudyable($this->studyableType, $this->studyableId, publishedOnly: true);

        return view('livewire.study-content-viewer', compact('contents'));
    }

    private function buildReadMap(): void
    {
        if (!auth()->check()) {
            return;
        }

        $ids = app(StudyContentService::class)
            ->getForStudyable($this->studyableType, $this->studyableId, publishedOnly: true)
            ->pluck('id');

        $readIds = auth()->user()
            ->studyContentProgress()
            ->whereIn('study_content_id', $ids)
            ->whereNotNull('study_content_user.read_at')
            ->pluck('study_content_id')
            ->toArray();

        foreach ($ids as $id) {
            $this->readMap[$id] = in_array($id, $readIds, true);
        }
    }
}
