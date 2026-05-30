# Modalità Studio & Simulatore Esame

I due strumenti di esercitazione del viewer:
- **Modalità Studio** — allenamento libero senza punteggio
- **Simulatore Esame** — riproduzione fedele del formato ministeriale (30 domande, 20 minuti, max 3 errori)

Condividono il model `QuizAttempt` per la persistenza ma usano flussi e service distinti.

---

## Modalità Studio

Il `StudyService` gestisce una sessione di allenamento interamente in `$_SESSION` (nessuna tabella dedicata).

### Sorgenti delle domande

| Sorgente | Origine | Note |
|---|---|---|
| `quiz` | Domande di un quiz `published` o `confirmed` | Selezione dal dropdown |
| `category` | Tutte le domande di una categoria | Ordine casuale |
| `random` | Domande casuali dall'intero database | Cap `RANDOM_LIMIT = 30` |
| `flagged` | Sole domande marcate "da ripassare" nella sessione precedente | Disponibile solo dal riepilogo |
| `bookmarks` | Sole domande salvate dal viewer (bookmark permanenti) | Disponibile dalla pagina "Domande salvate" |

Sorgente `bookmarks`: tramite il pulsante "Studia le domande salvate" (POST `/study/start` con `source=bookmarks`). La lista domande viene costruita da `auth()->user()->bookmarkedQuestions()->pluck('questions.id')`. Se la lista è vuota, `StudyController::start()` reindirizza a `GET /bookmarks` con flash `warning`.

### Chiavi di sessione PHP

| Chiave | Contenuto |
|---|---|
| `study_questions` | Array degli ID domanda nell'ordine della sessione |
| `study_index` | Indice corrente (0-based), clampato all'intervallo valido |
| `study_flagged` | Array degli ID marcati come "da ripassare" |
| `study_answers` | Map `question_id => 0|1` per il conteggio nel riepilogo |
| `study_source` | Sorgente (`quiz`, `category`, `random`, `flagged`, `bookmarks`) |

### Materiale didattico

Prima di ogni domanda viene mostrata (se presente) una **card collassabile** con il materiale didattico della categoria: PDF scaricabili, video YouTube incorporati, link esterni e note testuali. La card è chiusa di default (Bootstrap collapse).

`StudyController::play()` esegue eager loading di `category` e poi `materials` (ordered) sul modello `Question` corrente, per evitare query N+1 nella view.

### Feedback e interazione

- Feedback inline immediato per ogni risposta (corretta/errata, Alpine.js, nessun round-trip).
- Navigazione libera avanti/indietro via URL `?index=N`.
- Marcatura "da ripassare" via AJAX al flag endpoint (stato in sessione PHP, niente DB).
- Bookmark permanente con il pulsante apposito (Livewire `BookmarkButton`, persistente su DB).

### Integrazione con altri sottosistemi

- **Spaced repetition** — `StudyController::flag()` chiama `SpacedRepetitionService::recordAnswer()` dopo ogni risposta, alimentando l'algoritmo SM-2.
- **Gamification** — `StudyController::flag()` chiama anche `StreakService::recordActivity()` e `BadgeService::checkAllBadges()`.
- **Offline (PWA)** — la modalità studio è l'unica funzionalità con supporto offline. Vedi [docs/07-pwa.md](07-pwa.md).

---

## Simulatore Esame

Il `SimulatorService` riproduce il formato ufficiale dell'esame teorico patente B vigente dal **20 dicembre 2021** (DM MIT 27/10/2021): **30 domande**, **20 minuti**, **max 3 errori**. I parametri sono configurabili in `config/simulator.php` insieme alla distribuzione per categoria — nessun valore hardcoded.

### Distribuzione per categoria

Il simulatore costruisce la lista di domande secondo la mappa `distribution` definita in `config/simulator.php`:
- Per ogni **categoria fondamentale** vengono estratte 2 domande casuali
- Per ogni **categoria integrativa** 1 domanda
- Totale target: 12 fondamentali × 2 + 6 integrative × 1 = 30

Il nome della categoria viene confrontato con `LOWER(name) LIKE '%nome%'` per resistere a piccole differenze ortografiche (è comunque consigliato allineare i nomi). Se una categoria della config non esiste nel DB, viene saltata e registrata con `Log::warning()`. Se il totale delle estratte è inferiore al target configurato, il pool viene integrato con domande casuali da altre categorie.

### Flusso

```
GET /simulator                  → pagina introduttiva (info-box 30 / 20 min / 3)
POST /simulator/start           → buildQuestionList() + QuizAttempt(quiz_id=null) + redirect /play
GET /simulator/play             → renderizza domande in JSON, timer JS, navigatore sidebar
PUT /simulator/{attempt}/autosave → autosave debounced 1s (jQuery), aggiorna answers+score
POST /simulator/submit          → finalize + clearSession + redirect /result/{attempt}
GET /simulator/result/{attempt} → riepilogo dedicato con criterio "max 3 errori"
DELETE /simulator/session       → abbandono esplicito senza salvare il risultato
```

### Sessione

Tutto in `$_SESSION` (nessuna tabella dedicata):

| Chiave | Contenuto |
|---|---|
| `simulator_questions` | Array degli ID domanda nell'ordine estratto |
| `simulator_attempt_id` | ID del `QuizAttempt` creato all'avvio |

### Persistenza e separazione dai quiz ufficiali

Il tentativo simulatore è salvato come `QuizAttempt` con **`quiz_id = null`** (migration `make_quiz_id_nullable_in_quiz_attempts_table`). La relazione `QuizAttempt::quiz()` usa `withDefault(['title' => 'Simulatore Esame'])` per evitare NPE nelle view condivise.

Il flusso di autosave **non passa** da `QuizAttemptService::updateAttempt()` perché quel metodo dipende da `$attempt->quiz->questions`: il `SimulatorService` ha un proprio `updateAttempt()` che ricostruisce la mappa `question_id => is_true` direttamente da `Question::whereIn($questionIds)`.

### Esito (criterio reale, non 60%)

A differenza dei quiz ufficiali (che valutano in percentuale), la pagina `/simulator/result/{attempt}` calcola l'esito con il criterio del MIT:

> **Promosso se** `wrong + not_answered ≤ max_errors`

Le risposte non date contano come errori al momento del submit. La view è dedicata (`simulator/result.blade.php`) per non inquinare la pagina di dettaglio tentativo quiz.

### Integrazione gamification

`SimulatorController::submit()`:
1. Chiama `StreakService::recordActivity()`.
2. Se promosso (`total_questions - score <= max_errors`), chiama `BadgeService::awardIfEligible(..., 'first_pass', ...)`.
3. Chiama `BadgeService::checkAllBadges()` per gli altri badge.

---

## Struttura dati — `QuizAttempt.answers`

Il campo `answers` su `quiz_attempts` è un JSON indicizzato per `question_id`. Dal formato flat originale (`{ "12": 1 }`) è stato migrato al formato esteso:

```json
{
  "12": { "correct": 1, "answered_at": 1747123456, "time_spent_seconds": null, "position": 1, "question_version_id": 7 },
  "15": { "correct": 0, "answered_at": 1747123470, "time_spent_seconds": null, "position": 2, "question_version_id": 12 }
}
```

| Campo | Tipo | Note |
|---|---|---|
| `correct` | `int` 0\|1 | Risposta corretta (1) o errata (0). Obbligatorio. |
| `answered_at` | `int` Unix | Momento della risposta. Obbligatorio per le nuove risposte. |
| `time_spent_seconds` | `int\|null` | Secondi sulla domanda (opzionale). |
| `position` | `int\|null` | Posizione nella sequenza mostrata all'utente (utile dopo shuffle). |
| `question_version_id` | `int\|null` | FK verso `question_versions.id` — versione della domanda attiva al momento della risposta. `null` per tentativi pre-versionamento: in quel caso le view fanno fallback al `Question` corrente. |

### Compatibilità legacy

Il formato flat (`{ "12": 1 }`) è ancora accettato dal service durante la transizione:

- `QuizAttempt::getAnswerResult($questionId)` — metodo da usare per leggere il risultato (gestisce entrambi i formati).
- `QuizAttempt::getAnsweredAt($questionId): ?Carbon` — timestamp della risposta o `null` per formato flat.
- `QuizAttempt::getTimeSpent($questionId): ?int` — secondi sulla domanda o `null` per formato flat.
- `QuizAttempt::getAnswerPosition($questionId): ?int` — posizione progressiva o `null` per formato flat.
- `QuizAttempt::getAnswerVersionId($questionId): ?int` — `question_version_id` della risposta o `null` per formato flat/legacy.
- `QuizAttemptService::normalizeAnswers()` — converte flat → esteso prima di ogni scrittura su DB.
- `QuizAttemptService::scoreAnswers()` — calcola lo score leggendo `$answer['correct']` se array, `(int) $answer` se scalare.

La migration `2026_05_17_220000_migrate_quiz_attempts_answers_to_extended_format` ha convertito i record storici in modo non-distruttivo (con `down()` di rollback). Usare sempre gli accessori del model, mai accedere direttamente alle chiavi `$rawEntry['position']` o `$rawEntry['time_spent_seconds']`.

### Versionamento domande e integrità storica

`QuizAttemptService::injectVersionIds()` inietta automaticamente il `question_version_id` nelle risposte al momento della registrazione (singola query batch). Su autosave, i version_id già presenti vengono preservati: si registra la versione attiva alla **prima** risposta, non all'ultimo salvataggio.

Per le view che mostrano il dettaglio di un tentativo usare `QuizAttemptService::getAttemptDetail()`, che restituisce per ogni domanda i campi:
- `version` — `QuestionVersion|null` (la versione storica referenziata)
- `is_historical` — `bool` — `true` se la versione storica differisce dallo stato corrente della domanda
- `correct_answer` — viene dalla versione storica se disponibile (risposta corretta al momento del tentativo)
