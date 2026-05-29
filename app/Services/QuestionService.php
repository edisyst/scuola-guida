<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Http\UploadedFile;

class QuestionService
{
    /*
    | NOTA: i file immagine non vengono MAI cancellati dallo storage da questo
    | service. La gestione dei file (upload/rinomina/elimina) è responsabilità
    | esclusiva del Media Manager (sezione admin). Qui agiamo solo sul campo
    | image della domanda (puntatore al path).
    */

    public function create(array $data, ?UploadedFile $image = null): Question
    {
        // validated() include l'UploadedFile sotto 'image'; va rimosso prima di create().
        unset($data['image'], $data['remove_image']);

        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        return Question::create($data);
    }

    public function update(Question $question, array $data, ?UploadedFile $image = null): Question
    {
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        if ($image) {
            $data['image'] = $this->storeImage($image);
        } elseif ($removeImage) {
            $data['image'] = null;
        }

        $question->update($data);

        return $question;
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    public function bulkDelete(array $ids): int
    {
        $deleted = Question::whereIn('id', $ids)->delete();

        // whereIn()->delete() bypassa l'Observer: invalidiamo le cache manualmente.
        clearAdminBadgesCache();
        clearDashboardKpiCache();

        return $deleted;
    }

    private function storeImage(UploadedFile $file): string
    {
        $directory = config('media.directories.' . config('media.active'));

        return $file->store($directory, config('media.disk'));
    }
}
