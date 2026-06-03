<?php

namespace App\Services;

use App\Models\DiagnosticResult;
use App\Models\QuestionReview;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserBadge;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GdprExportService
{
    public function buildExport(User $user): array
    {
        $user->load([
            'quizAttempts',
            'bookmarkedQuestions.category',
            'learnedQuestions',
            'questionReports',
        ]);

        $questionReviews   = QuestionReview::where('user_id', $user->id)->get();
        $userBadges        = UserBadge::where('user_id', $user->id)->get();
        $activityLog       = UserActivityLog::where('user_id', $user->id)->orderBy('activity_date')->get();
        $diagnosticResults = DiagnosticResult::where('user_id', $user->id)->get();

        return [
            'meta' => [
                'export_date' => now()->toIso8601String(),
                'app_version' => config('app.version', '1.0'),
                'email'       => $user->email ?? '[anonimizzato]',
            ],
            'anagrafica' => [
                'id'                        => $user->id,
                'name'                      => $user->name ?? '[anonimizzato]',
                'email'                     => $user->email ?? '[anonimizzato]',
                'role'                      => $user->role,
                'email_verified_at'         => $user->email_verified_at?->toIso8601String(),
                'created_at'                => $user->created_at?->toIso8601String(),
                'first_name'                => $user->first_name ?? '[anonimizzato]',
                'last_name'                 => $user->last_name ?? '[anonimizzato]',
                'address'                   => $user->address ?? '[anonimizzato]',
                'birth_date'                => $user->birth_date?->toDateString() ?? '[anonimizzato]',
                'birth_place'               => $user->birth_place ?? '[anonimizzato]',
                'fiscal_code'               => $user->fiscal_code ?? '[anonimizzato]',
                'registration_status'       => $user->registration_status,
                'registration_submitted_at' => $user->registration_submitted_at?->toIso8601String(),
                'id_document_incluso'       => (bool) $user->id_document_path,
            ],
            'quiz_attempts' => $user->quizAttempts->map(fn ($a) => [
                'id'               => $a->id,
                'quiz_id'          => $a->quiz_id,
                'score'            => $a->score,
                'total_questions'  => $a->total_questions,
                'passed'           => $a->is_passed,
                'duration_seconds' => $a->duration,
                'created_at'       => $a->created_at?->toIso8601String(),
            ])->values()->all(),
            'saved_questions' => $user->bookmarkedQuestions->map(fn ($q) => [
                'question_id' => $q->id,
                'text'        => $q->text,
                'category'    => $q->category?->name,
                'note'        => $q->pivot->note ?? null,
                'saved_at'    => $q->pivot->created_at?->toIso8601String(),
            ])->values()->all(),
            'learned_questions' => $user->learnedQuestions->map(fn ($lq) => [
                'question_id' => $lq->question_id,
                'marked_at'   => $lq->marked_at?->toIso8601String(),
            ])->values()->all(),
            'question_flags' => $user->questionReports->map(fn ($r) => [
                'question_id' => $r->question_id,
                'type'        => $r->type,
                'body'        => $r->body,
                'status'      => $r->status,
                'created_at'  => $r->created_at?->toIso8601String(),
            ])->values()->all(),
            'diagnostic' => $diagnosticResults->map(fn ($d) => [
                'batch_id'    => $d->batch_id,
                'category_id' => $d->category_id,
                'correct'     => (bool) $d->correct,
                'taken_at'    => $d->taken_at instanceof \DateTimeInterface
                    ? \Carbon\Carbon::instance($d->taken_at)->toIso8601String()
                    : (string) $d->taken_at,
            ])->values()->all(),
            'spaced_repetition' => $questionReviews->map(fn ($r) => [
                'question_id'     => $r->question_id,
                'ease_factor'     => (float) $r->ease_factor,
                'interval_days'   => $r->interval_days,
                'repetitions'     => $r->repetitions,
                'next_review_at'  => $r->next_review_at instanceof \DateTimeInterface
                    ? \Carbon\Carbon::instance($r->next_review_at)->toIso8601String()
                    : (string) $r->next_review_at,
                'last_reviewed_at' => $r->last_reviewed_at instanceof \DateTimeInterface
                    ? \Carbon\Carbon::instance($r->last_reviewed_at)->toIso8601String()
                    : $r->last_reviewed_at,
            ])->values()->all(),
            'activity' => $activityLog->map(fn ($a) => [
                'activity_date' => (string) $a->activity_date,
                'actions_count' => $a->actions_count,
            ])->values()->all(),
            'badges' => $userBadges->map(fn ($b) => [
                'badge_code' => $b->badge_code,
                'earned_at'  => $b->earned_at?->toIso8601String(),
                'metadata'   => $b->metadata,
            ])->values()->all(),
        ];
    }

    public function generateZip(User $user): string
    {
        $exportData = $this->buildExport($user);
        $json       = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $directory = 'private/gdpr-exports';
        Storage::disk('local')->makeDirectory($directory);

        $filename = "gdpr_export_{$user->id}_" . now()->timestamp . '.zip';
        $zipPath  = Storage::disk('local')->path("{$directory}/{$filename}");

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('export.json', $json);

        if ($user->id_document_path) {
            $absolutePath = Storage::disk('public')->path($user->id_document_path);
            if (file_exists($absolutePath)) {
                $zip->addFile($absolutePath, 'files/' . basename($user->id_document_path));
            }
        }

        $zip->close();

        return $zipPath;
    }

    public function cleanupOldExports(): void
    {
        $directory = 'private/gdpr-exports';
        $cutoff    = now()->subHours(24)->timestamp;

        foreach (Storage::disk('local')->files($directory) as $file) {
            if (Storage::disk('local')->lastModified($file) < $cutoff) {
                Storage::disk('local')->delete($file);
            }
        }
    }
}
