<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Models\QuizEnrollment;
use App\Models\User;
use App\Services\QuizEnrollmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CloseExpiredEnrollments extends Command
{
    protected $signature = 'enrollments:close-expired';

    protected $description = 'Chiude automaticamente le iscrizioni pending sui quiz con enrollments_close_at scaduto.';

    public function __construct(private QuizEnrollmentService $enrollmentService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $systemAdmin = User::where('role', User::ROLE_ADMIN)->first();

        if (!$systemAdmin) {
            Log::error('enrollments:close-expired: nessun utente admin trovato, impossibile procedere.');
            $this->error('Nessun utente admin trovato.');
            return self::FAILURE;
        }

        $total = 0;

        Quiz::confirmed()
            ->enrollmentsClosed()
            ->whereHas('enrollments', fn ($q) => $q->where('status', QuizEnrollment::STATUS_PENDING))
            ->lazy()
            ->each(function (Quiz $quiz) use ($systemAdmin, &$total) {
                $count = 0;

                QuizEnrollment::where('quiz_id', $quiz->id)
                    ->where('status', QuizEnrollment::STATUS_PENDING)
                    ->lazy()
                    ->each(function (QuizEnrollment $enrollment) use ($systemAdmin, &$count) {
                        try {
                            $this->enrollmentService->reject(
                                $enrollment,
                                $systemAdmin,
                                'Iscrizione chiusa automaticamente per scadenza termini'
                            );
                            $count++;
                        } catch (RuntimeException $e) {
                            Log::warning('enrollments:close-expired: iscrizione non rifiutabile', [
                                'enrollment_id' => $enrollment->id,
                                'reason'        => $e->getMessage(),
                            ]);
                        }
                    });

                Log::info('enrollments:close-expired: iscrizioni chiuse', [
                    'quiz_id'              => $quiz->id,
                    'quiz_title'           => $quiz->title,
                    'closed_count'         => $count,
                    'enrollments_close_at' => $quiz->enrollments_close_at?->toIso8601String(),
                ]);

                $total += $count;
            });

        $this->info("Chiuse {$total} iscrizioni scadute.");

        return self::SUCCESS;
    }
}
