<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quiz_attempts')
            ->whereNotNull('answers')
            ->orderBy('id')
            ->lazy()
            ->each(function ($attempt) {
                $old = json_decode($attempt->answers, true);
                if (!is_array($old)) return;

                $firstValue = reset($old);
                if (is_array($firstValue)) return;

                $new      = [];
                $position = 1;
                foreach ($old as $questionId => $correct) {
                    $new[$questionId] = [
                        'correct'            => (int) $correct,
                        'answered_at'        => null,
                        'time_spent_seconds' => null,
                        'position'           => $position++,
                    ];
                }

                DB::table('quiz_attempts')
                    ->where('id', $attempt->id)
                    ->update(['answers' => json_encode($new)]);
            });
    }

    public function down(): void
    {
        DB::table('quiz_attempts')
            ->whereNotNull('answers')
            ->orderBy('id')
            ->lazy()
            ->each(function ($attempt) {
                $data = json_decode($attempt->answers, true);
                if (!is_array($data)) return;

                $firstValue = reset($data);
                if (!is_array($firstValue)) return;

                $flat = [];
                foreach ($data as $questionId => $meta) {
                    $flat[$questionId] = $meta['correct'] ?? 0;
                }

                DB::table('quiz_attempts')
                    ->where('id', $attempt->id)
                    ->update(['answers' => json_encode($flat)]);
            });
    }
};
