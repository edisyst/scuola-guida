<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Models\QuizEnrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseExpiredEnrollments extends Command
{
    protected $signature = 'enrollments:close-expired';

    protected $description = 'Chiude automaticamente le iscrizioni pending sui quiz con enrollments_close_at scaduto.';

    public function handle(): int
    {
        $now = now();

        $quizzes = Quiz::confirmed()
            ->whereNotNull('enrollments_close_at')
            ->where('enrollments_close_at', '<=', $now)
            ->whereHas('enrollments', fn ($q) => $q->where('status', QuizEnrollment::STATUS_PENDING))
            ->with(['enrollments' => fn ($q) => $q->where('status', QuizEnrollment::STATUS_PENDING)])
            ->get();

        $total = 0;

        foreach ($quizzes as $quiz) {
            $count = $quiz->enrollments->count();

            QuizEnrollment::where('quiz_id', $quiz->id)
                ->where('status', QuizEnrollment::STATUS_PENDING)
                ->update([
                    'status'      => QuizEnrollment::STATUS_REJECTED,
                    'reviewed_at' => $now,
                ]);

            Log::info('Iscrizioni scadute chiuse automaticamente', [
                'quiz_id'              => $quiz->id,
                'quiz_title'           => $quiz->title,
                'closed_count'         => $count,
                'enrollments_close_at' => $quiz->enrollments_close_at?->toIso8601String(),
                'reason'               => 'Iscrizioni scadute automaticamente',
            ]);

            $total += $count;
        }

        $this->info("Chiuse {$total} iscrizioni scadute su {$quizzes->count()} quiz.");

        return self::SUCCESS;
    }
}
