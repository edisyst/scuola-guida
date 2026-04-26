<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Storage;

class QuestionService
{
    public function create(array $data, $request): Question
    {
        $data['is_true'] = $request->has('is_true');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('questions', 'public');
        }

        return Question::create($data);
    }

    public function update(Question $question, array $data, $request): Question
    {
        $data['is_true'] = $request->has('is_true');

        if ($request->hasFile('image')) {

            if ($question->image) {
                Storage::disk('public')->delete($question->image);
            }

            $data['image'] = $request->file('image')
                ->store('questions', 'public');
        }

        $question->update($data);

        return $question;
    }

    public function delete(Question $question): void
    {
        if ($question->image) {
            Storage::disk('public')->delete($question->image);
        }

        $question->delete();
    }
}
