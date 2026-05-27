<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncAnswersRequest;
use App\Models\Question;
use App\Models\QuestionReview;
use App\Services\BadgeService;
use App\Services\SpacedRepetitionService;
use App\Services\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OfflineController extends Controller
{
    public function __construct(
        private SpacedRepetitionService $spacedRepetition,
        private StreakService            $streak,
        private BadgeService             $badge,
    ) {}

    /**
     * Returns up to 100 recently-reviewed questions for offline caching.
     * Throttled to 1 request per 5 minutes per user.
     */
    public function questions(): JsonResponse
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $user = auth()->user();

        // Questions the user has recently interacted with via spaced repetition
        $questions = QuestionReview::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_reviewed_at')
            ->with(['question', 'question.category'])
            ->limit(100)
            ->get()
            ->pluck('question')
            ->filter()
            ->map(fn (Question $q) => [
                'id'          => $q->id,
                'question'    => $q->question,
                'is_true'     => (int) $q->is_true,
                'image'       => $q->image ? \Illuminate\Support\Facades\Storage::url($q->image) : null,
                'category_id' => $q->category_id,
                'category'    => $q->category ? [
                    'id'   => $q->category->id,
                    'name' => $q->category->name,
                ] : null,
            ])
            ->values();

        return response()->json([
            'questions'  => $questions,
            'count'      => $questions->count(),
            'fetched_at' => now()->toISOString(),
        ]);
    }

    /**
     * Processes answers recorded while offline and applies them to the server.
     * Each answer triggers SpacedRepetitionService and StreakService (once).
     */
    public function syncAnswers(SyncAnswersRequest $request): JsonResponse
    {
        abort_unless(auth()->user()->isViewer(), 403);

        $user    = auth()->user();
        $answers = $request->validated('answers');
        $synced  = [];

        DB::transaction(function () use ($user, $answers, &$synced) {
            $activityRecorded = false;

            foreach ($answers as $answer) {
                $questionId = (int) $answer['question_id'];
                $isCorrect  = (bool) $answer['is_correct'];

                $question = Question::find($questionId);
                if (!$question) {
                    continue;
                }

                $this->spacedRepetition->recordAnswer($user, $questionId, $isCorrect);

                if (!$activityRecorded) {
                    $this->streak->recordActivity($user);
                    $activityRecorded = true;
                }

                $synced[] = (int) $answer['id'];
            }

            if ($activityRecorded) {
                $this->badge->checkAllBadges($user);
            }
        });

        return response()->json([
            'synced_ids' => $synced,
            'count'      => count($synced),
        ]);
    }
}
