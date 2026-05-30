<?php

use App\Models\Question;
use App\Models\QuestionVersion;
use Illuminate\Database\Migrations\Migration;

// Data-migration idempotente: crea la versione 1 per ogni domanda esistente
// che non ha ancora versioni. Deve girare dopo 100001_create_question_versions_table.
return new class extends Migration
{
    public function up(): void
    {
        // Carica gli ID delle domande già versionate per evitare duplicati.
        $alreadyVersioned = QuestionVersion::where('version_number', 1)
            ->pluck('question_id')
            ->flip();

        Question::query()->lazy()->each(function (Question $q) use ($alreadyVersioned) {
            if ($alreadyVersioned->has($q->id)) {
                return;
            }

            QuestionVersion::create([
                'question_id'    => $q->id,
                'version_number' => 1,
                'question'       => $q->question,
                'is_true'        => $q->is_true,
                'image'          => $q->image,
                'category_id'    => $q->category_id,
                'created_by'     => null,
                'created_at'     => $q->created_at ?? now(),
            ]);
        });
    }

    public function down(): void
    {
        // Rimuove solo le versioni create da questa data-migration (version_number = 1,
        // created_by = null). Le versioni create dall'applicazione non vengono toccate.
        QuestionVersion::where('version_number', 1)
            ->whereNull('created_by')
            ->delete();
    }
};
