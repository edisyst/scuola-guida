<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookmarkSeeder extends Seeder
{
    private const NOTES = [
        null,
        'Spesso sbaglio questa.',
        'Da ripassare prima dell\'esame.',
        'Tricky — ricordare l\'eccezione.',
        'Risposta controintuitiva.',
        'Importante per la segnaletica.',
        null,
        null,
    ];

    public function run(): void
    {
        $viewers   = User::where('role', User::ROLE_VIEWER)->get();
        $questions = Question::inRandomOrder()->take(100)->pluck('id');

        if ($viewers->isEmpty() || $questions->isEmpty()) {
            $this->command->warn('Viewer o domande mancanti: BookmarkSeeder saltato.');
            return;
        }

        $count = 0;
        foreach ($viewers as $viewer) {
            // Ogni viewer segna 5-20 domande
            $bookmarked = $questions->shuffle()->take(fake()->numberBetween(5, 20));

            foreach ($bookmarked as $questionId) {
                $createdAt = now()->subDays(fake()->numberBetween(1, 30));

                DB::table('question_user_bookmarks')->insertOrIgnore([
                    'user_id'     => $viewer->id,
                    'question_id' => $questionId,
                    'note'        => fake()->randomElement(self::NOTES),
                    'created_at'  => $createdAt,
                    'updated_at'  => $createdAt,
                ]);
                $count++;
            }
        }

        $this->command->info("CREATI {$count} SEGNALIBRI DOMANDE (BookmarkSeeder)");
    }
}
