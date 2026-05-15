<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Category;

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

        Quiz::factory(10)->recycle($questions)->create();
    }
}
