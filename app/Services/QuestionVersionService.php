<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\QuizAttempt;

class QuestionVersionService
{
    private const VERSIONABLE_FIELDS = ['question', 'is_true', 'image', 'category_id'];

    /**
     * Crea una nuova versione con lo stato CORRENTE (dopo la modifica) se almeno
     * un campo versionabile è cambiato rispetto agli attributi originali passati.
     *
     * Semantica "snapshot del dopo": la versione rappresenta lo stato che i viewer
     * hanno effettivamente visto durante il periodo in cui quella versione era attiva.
     * Quando un tentativo registra il question_version_id, punta sempre all'ultima
     * versione, che coincide con lo stato corrente della domanda in quel momento.
     */
    public function snapshotIfChanged(Question $question, array $originalAttributes): ?QuestionVersion
    {
        foreach (self::VERSIONABLE_FIELDS as $field) {
            if (!array_key_exists($field, $originalAttributes)) {
                continue;
            }

            $oldVal = (string) ($originalAttributes[$field] ?? '');
            $newVal = (string) ($question->$field ?? '');

            if ($oldVal !== $newVal) {
                return $question->createVersion();
            }
        }

        return null;
    }

    /**
     * Restituisce la QuestionVersion referenziata dalla risposta del tentativo
     * per la domanda specificata, con fallback alla Question corrente se il
     * tentativo non registra version_id (retrocompatibilità pre-versionamento).
     */
    public function getVersionForAttempt(QuizAttempt $attempt, int $questionId): QuestionVersion|Question
    {
        $versionId = $attempt->getAnswerVersionId($questionId);

        if ($versionId !== null) {
            $version = QuestionVersion::find($versionId);
            if ($version) {
                return $version;
            }
        }

        return $attempt->quiz->questions()->find($questionId)
            ?? Question::findOrFail($questionId);
    }

    /**
     * Costruisce una mappa [question_id => QuestionVersion] per tutte le domande
     * di un tentativo che hanno un question_version_id registrato.
     * Una singola query batch per tutti gli ID — nessun N+1.
     *
     * @param  int[]  $questionIds
     * @return array<int, QuestionVersion>
     */
    public function buildVersionMapForAttempt(QuizAttempt $attempt, array $questionIds): array
    {
        $versionIdsByQuestion = [];

        foreach ($questionIds as $qid) {
            $vId = $attempt->getAnswerVersionId($qid);
            if ($vId !== null) {
                $versionIdsByQuestion[$qid] = $vId;
            }
        }

        if (empty($versionIdsByQuestion)) {
            return [];
        }

        $versions = QuestionVersion::whereIn('id', array_values($versionIdsByQuestion))
            ->get()
            ->keyBy('id');

        $map = [];
        foreach ($versionIdsByQuestion as $qid => $vId) {
            if (isset($versions[$vId])) {
                $map[$qid] = $versions[$vId];
            }
        }

        return $map;
    }

    /**
     * Restituisce una mappa [question_id => latest_version_id] per un insieme
     * di domande. Usata da QuizAttemptService per iniettare il version_id
     * nelle risposte al momento della registrazione.
     *
     * @param  int[]  $questionIds
     * @return array<int, int>
     */
    public function latestVersionIdMap(array $questionIds): array
    {
        if (empty($questionIds)) {
            return [];
        }

        return QuestionVersion::selectRaw('question_id, MAX(id) as latest_id')
            ->whereIn('question_id', $questionIds)
            ->groupBy('question_id')
            ->pluck('latest_id', 'question_id')
            ->mapWithKeys(fn ($vId, $qId) => [(int) $qId => (int) $vId])
            ->all();
    }

    /**
     * Ripristina una versione storica creando un nuovo stato corrente.
     * Non sovrascrive la storia: la situazione precedente al ripristino
     * diventa la versione più recente, e la domanda viene aggiornata al
     * contenuto della versione ripristinata.
     */
    public function restoreVersion(Question $question, QuestionVersion $version): Question
    {
        $originalAttributes = $question->only(self::VERSIONABLE_FIELDS);

        $question->update([
            'question'    => $version->question,
            'is_true'     => $version->is_true,
            'image'       => $version->image,
            'category_id' => $version->category_id,
        ]);

        // Crea snapshot del "prima del ripristino" solo se lo stato cambia.
        $this->snapshotIfChanged($question->fresh(), $originalAttributes);

        return $question->fresh();
    }

    /**
     * Indica se la versione storica differisce dallo stato corrente della domanda
     * in almeno uno dei campi versionabili.
     */
    public function isHistoricalVersion(QuestionVersion $version, Question $question): bool
    {
        return $version->question !== $question->question
            || (bool) $version->is_true !== (bool) $question->is_true
            || $version->image !== $question->image
            || $version->category_id !== $question->category_id;
    }
}
