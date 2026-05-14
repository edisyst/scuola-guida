<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class QuestionService
{
    private const IMAGE_DISK = 'public';
    private const IMAGE_DIR  = 'questions';

    public function create(array $data, ?UploadedFile $image = null): Question
    {
        // validated() include l'UploadedFile sotto 'image'; va rimosso prima di create().
        unset($data['image']);

        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        return Question::create($data);
    }

    public function update(Question $question, array $data, ?UploadedFile $image = null): Question
    {
        unset($data['image']); // stessa ragione di create()

        if ($image) {
            $this->deleteImage($question);
            $data['image'] = $this->storeImage($image);
        }

        $question->update($data);

        return $question;
    }

    public function delete(Question $question): void
    {
        $this->deleteImage($question);
        $question->delete();
    }

    public function bulkDelete(array $ids): int
    {
        $questions = Question::whereIn('id', $ids)->get();

        foreach ($questions as $question) {
            $this->deleteImage($question);
        }

        return Question::whereIn('id', $ids)->delete();
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store(self::IMAGE_DIR, self::IMAGE_DISK);
    }

    private function deleteImage(Question $question): void
    {
        if ($question->image && !str_starts_with($question->image, 'http')) {
            Storage::disk(self::IMAGE_DISK)->delete($question->image);
        }
    }
}
