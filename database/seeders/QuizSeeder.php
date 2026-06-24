<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Category;
use App\Models\LicenseType;
use App\Models\User;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $questions = Question::all();

        if ($questions->isEmpty()) {
            $categories = Category::all();

            if ($categories->isEmpty()) {
                $categories = Category::factory(5)->create();
            }

            $questions = Question::factory(100)->recycle($categories)->create();
        }

        $admin = User::first();

        $licenseTypes = LicenseType::whereIn('code', ['A', 'B', 'C'])->get();

        $drafts     = Quiz::factory(3)->state(['status' => Quiz::STATUS_DRAFT])->create();
        $published  = Quiz::factory(4)->state(['status' => Quiz::STATUS_PUBLISHED])->create();
        $confirmed  = Quiz::factory(3)->state(fn () => [
            'status'       => Quiz::STATUS_CONFIRMED,
            'confirmed_at' => now()->subDays(rand(1, 15)),
            'confirmed_by' => $admin?->id,
        ])->create();

        // Date iscrizione sui quiz confermati: upcoming / open / closed
        if ($confirmed->count() >= 3) {
            $confirmed->get(0)->update([
                'enrollments_open_at'  => now()->addDays(5),
                'enrollments_close_at' => now()->addDays(15),
            ]);
            $confirmed->get(1)->update([
                'enrollments_open_at'  => now()->subDays(3),
                'enrollments_close_at' => now()->addDays(7),
            ]);
            $confirmed->get(2)->update([
                'enrollments_open_at'  => now()->subDays(20),
                'enrollments_close_at' => now()->subDays(5),
            ]);
        }

        $quizzes = $drafts->merge($published)->merge($confirmed);

        foreach ($quizzes as $quiz) {
            if ($quiz->status !== Quiz::STATUS_DRAFT && $licenseTypes->isNotEmpty()) {
                $quiz->license_type_id = $licenseTypes->random()->id;
                $quiz->save();
            }

            $count    = min($quiz->max_questions, $questions->count());
            $selected = $questions->random($count);

            $pivot = $selected->values()->mapWithKeys(
                fn ($question, $index) => [$question->id => ['order' => $index + 1]]
            );

            $quiz->questions()->attach($pivot);
        }

        $this->command->info('CREATI 10 QUIZ (3 bozze, 4 pubblicati, 3 esami con date iscrizione) CON DOMANDE ASSOCIATE');
    }
}
