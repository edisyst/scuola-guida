<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionVersionSeeder extends Seeder
{
    public function run(): void
    {
        $editorIds = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_EDITOR])
            ->pluck('id')
            ->toArray();

        // Domande che hanno già la versione 1 (dalla data-migration o run precedente)
        $alreadyVersioned = QuestionVersion::where('version_number', 1)
            ->pluck('question_id')
            ->flip();

        $v1Count = 0;
        $v2Count = 0;

        Question::query()->lazy()->each(function (Question $q) use ($editorIds, $alreadyVersioned, &$v1Count, &$v2Count) {
            // Versione 1: snapshot originale (stessa logica della data-migration)
            if (!$alreadyVersioned->has($q->id)) {
                QuestionVersion::create([
                    'question_id'    => $q->id,
                    'version_number' => 1,
                    'question'       => $q->question,
                    'is_true'        => $q->is_true,
                    'image'          => $q->image,
                    'category_id'    => $q->category_id,
                    'created_by'     => null,
                    'created_at'     => $q->created_at ?? now()->subDays(30),
                ]);
                $v1Count++;
            }

            // Versione 2 per ~25% delle domande: simula una revisione testuale
            if (!empty($editorIds) && fake()->boolean(25)) {
                QuestionVersion::insertOrIgnore([
                    'question_id'    => $q->id,
                    'version_number' => 2,
                    'question'       => rtrim($q->question, '?. ') . ' (aggiornato)?',
                    'is_true'        => $q->is_true,
                    'image'          => $q->image,
                    'category_id'    => $q->category_id,
                    'created_by'     => $editorIds[array_rand($editorIds)],
                    'created_at'     => now()->subDays(fake()->numberBetween(1, 20)),
                ]);
                $v2Count++;
            }
        });

        $this->command->info("CREATI {$v1Count} versioni V1, {$v2Count} versioni V2 (Feature 6.2)");
    }
}
