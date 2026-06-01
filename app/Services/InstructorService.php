<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Facades\DB;

class InstructorService
{
    public function __construct(
        private UserStatsService $statsService,
        private StreakService $streakService,
    ) {}

    public function assignStudent(User $instructor, User $student, User $assignedBy): void
    {
        if (!$instructor->isInstructor()) {
            throw new \InvalidArgumentException('L\'utente assegnato come istruttore non ha il ruolo instructor.');
        }

        if (!$student->isViewer()) {
            throw new \InvalidArgumentException('Lo studente deve avere il ruolo viewer.');
        }

        DB::table('instructor_student')->insertOrIgnore([
            'instructor_id' => $instructor->id,
            'student_id'    => $student->id,
            'assigned_at'   => now(),
            'assigned_by'   => $assignedBy->id,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function unassignStudent(User $instructor, User $student): void
    {
        DB::table('instructor_student')
            ->where('instructor_id', $instructor->id)
            ->where('student_id', $student->id)
            ->delete();
    }

    public function getStudentProgress(User $student): array
    {
        $stats = $this->statsService->get($student);
        $streak = $this->streakService->getStats($student);

        $badges = UserBadge::where('user_id', $student->id)
            ->orderByDesc('earned_at')
            ->get()
            ->map(fn (UserBadge $b) => [
                'badge_code' => $b->badge_code,
                'earned_at'  => $b->earned_at?->toDateTimeString(),
                'metadata'   => $b->metadata,
            ])
            ->toArray();

        return [
            'student'  => [
                'id'          => $student->id,
                'name'        => $student->name,
                'email'       => $student->email,
                'last_seen'   => $student->updated_at?->toDateTimeString(),
            ],
            'stats'    => $stats,
            'streak'   => $streak,
            'badges'   => $badges,
        ];
    }

    public function getInstructorOverview(User $instructor): array
    {
        $students = $instructor->students()
            ->with([])
            ->get();

        if ($students->isEmpty()) {
            return [];
        }

        $studentIds = $students->pluck('id')->toArray();

        $lastAttempts = DB::table('quiz_attempts')
            ->whereIn('user_id', $studentIds)
            ->select('user_id', DB::raw('MAX(created_at) as last_attempt_at'))
            ->groupBy('user_id')
            ->pluck('last_attempt_at', 'user_id');

        $lastScores = DB::table('quiz_attempts as qa')
            ->joinSub(
                DB::table('quiz_attempts')
                    ->whereIn('user_id', $studentIds)
                    ->select('user_id', DB::raw('MAX(id) as max_id'))
                    ->groupBy('user_id'),
                'latest',
                fn ($join) => $join->on('qa.id', '=', 'latest.max_id')
            )
            ->select('qa.user_id', 'qa.score', 'qa.total_questions')
            ->pluck(null, 'user_id')
            ->map(fn ($row) => [
                'score'           => $row->score,
                'total_questions' => $row->total_questions,
                'pct'             => $row->total_questions > 0
                    ? round($row->score * 100 / $row->total_questions, 1)
                    : null,
            ]);

        $streakCurrents = DB::table('user_activity_log')
            ->whereIn('user_id', $studentIds)
            ->where('activity_date', today()->toDateString())
            ->pluck('user_id')
            ->flip();

        return $students->map(function (User $student) use ($lastAttempts, $lastScores, $streakCurrents) {
            $streak = $this->streakService->getCurrentStreak($student);

            return [
                'id'              => $student->id,
                'name'            => $student->name,
                'email'           => $student->email,
                'last_attempt_at' => $lastAttempts[$student->id] ?? null,
                'last_score'      => $lastScores[$student->id] ?? null,
                'streak'          => $streak,
                'active_today'    => isset($streakCurrents[$student->id]),
            ];
        })->toArray();
    }
}
