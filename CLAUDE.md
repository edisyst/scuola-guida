# CLAUDE.md — Scuola Guida

> Linee guida operative per Claude Code. Convenzioni architetturali, stilistiche
> e di sviluppo per il progetto. Fonte di verità insieme a `README.md` e
> `CHANGELOG.md` su GitHub.

---

## Stack tecnologico

| Layer            | Tecnologia                                |
|------------------|-------------------------------------------|
| Framework PHP    | Laravel 11, PHP 8.3+                      |
| Template engine  | Blade                                     |
| Reattività UI    | Livewire 3                                |
| JS leggero       | Alpine.js + JS vanilla                    |
| CSS Framework    | Bootstrap 5 (no altri framework)          |
| Admin theme      | AdminLTE 3                                |
| Database         | MySQL                                     |
| Asset build      | Vite (`laravel-vite-plugin`)              |
| Audit            | Trait `Auditable` + Observer su ogni Model |
| Ruoli e permessi | Sistema custom (no Spatie)                |

Per i ruoli usare i metodi su `User`: `isAdmin()`, `isEditor()`, `isViewer()`,
`isInstructor()`. Per i permessi: `canEditQuestion()`, `canEditQuiz()`,
`canEditCategory()`, `canEditUser()`. **Non** usare Spatie gates né
`$user->hasRole()` di Spatie.
Il ruolo `instructor` è read-only: `canEditXxx()` ritorna sempre `false`.

---

## Git Flow

- Branch permanenti: `master` e `develop`
- Ogni nuova feature: `git flow feature start <nome>` → sviluppa e committa
- **NON eseguire mai `git flow feature finish` autonomamente**: aspetta il
  comando esplicito dell'utente. Il finish è il merge, e il merge è una
  decisione umana.
- Quando l'utente dà il via al merge: `git flow feature finish <nome>`
  (merge su develop, cancella il branch locale, no push automatico su remote)
- Hotfix urgenti su master: `git flow hotfix start <nome>` →
  `git flow hotfix finish <nome>` (merge su master e develop)
- Release: `git flow release start <versione>` →
  `git flow release finish <versione>`
- Mai committare direttamente su `master` o `develop`

---

## Documentazione

Aggiorna **sempre** entrambi i file a ogni feature, prima del merge:

- `CHANGELOG.md` — segui il formato Keep a Changelog. La voce va scritta
  come parte della feature stessa, non dopo il merge. Usa le sezioni
  `Added`, `Changed`, `Fixed`, `Removed`, `Security` solo se pertinenti.
  La data nella voce è quella del merge previsto, non della scrittura.
- `README.md` — aggiorna se cambiano: funzionalità esposte all'utente,
  istruzioni di installazione, dipendenze, struttura del progetto,
  comandi artisan rilevanti, variabili `.env` richieste.

Entrambi gli aggiornamenti fanno parte della checklist PR: la PR non è
completa senza di essi.

---

## Struttura directory

```
app/
  Http/
    Controllers/          # Controller snelli, zero business logic
    Livewire/             # Classi PHP dei componenti Livewire
    Requests/             # Form Request per validazione
  Models/                 # Eloquent models con trait Auditable
  Observers/              # Observer dei model (audit, side effect)
  Services/               # Business logic (9 service)
resources/
  views/
    layouts/              # app.blade.php, partials
    livewire/             # View dei componenti Livewire
    components/           # Blade components riutilizzabili
config/                   # File di config dedicati per feature configurabili
database/
  migrations/             # Sempre con down() reversibile
  seeders/
  factories/
tests/
  Feature/                # Test feature (aggiungere ai file esistenti)
```

---

## Naming conventions

| Cosa                 | Pattern                                                   |
|----------------------|-----------------------------------------------------------|
| Livewire class       | `app/Http/Livewire/NomeComponente.php`                    |
| Livewire view        | `resources/views/livewire/nome-componente.blade.php`      |
| Service              | `app/Services/NomeEntitàService.php`                      |
| Form Request         | `app/Http/Requests/NomeAzioneNomeEntitàRequest.php`       |
| Migration            | `create_tabella_table` o `verb_campo_to_tabella_table`    |
| Test Feature         | `tests/Feature/NomeEntitàTest.php`                        |
| Comando artisan      | `entità:azione` (es. `questions:import-mit`)              |

Per i test: aggiungi ai file esistenti prima di crearne di nuovi.

---

## Convenzioni sviluppo (obbligatorie per ogni PR)

### Controller e Service

- Zero logica di business nel controller: tutto nei Service
- Autorizzazione: `abort_unless(auth()->user()->canEditXxx(), 403)`
- Validazione: sempre tramite Form Request dedicato, mai `validate()`
  inline nel controller
- Flash messages obbligatori su tutti i redirect:
  `with('success'|'warning'|'error'|'info', '...')`
- Injection del service nel costruttore o come parametro del metodo,
  coerente con il pattern già usato nel controller esistente

Pattern corretto:

```php
public function store(StoreQuestionRequest $request, QuestionService $service)
{
    abort_unless(auth()->user()->canEditQuestion(), 403);

    $service->create($request->validated());

    return redirect()
        ->route('questions.index')
        ->with('success', 'Domanda creata con successo.');
}
```

Pattern sbagliato (validazione inline + logica nel controller):

```php
public function store(Request $request)
{
    $request->validate([...]);
    // 50 righe di logica...
}
```

### Livewire

- I componenti stanno in `app/Http/Livewire/`
- `wire:model.blur` — mai `.defer`, mai `.live`
- `#[Validate]` attribute direttamente sulle property (non il metodo `rules()`)
- `wire:loading` obbligatorio su **tutti** i bottoni che triggerano azioni
- `wire:confirm="..."` per azioni distruttive (delete, reset)
- Eventi tra componenti: `$this->dispatch('nome-evento')`
- Per sincronizzare Alpine con stato Livewire:
  `x-show="{{ json_encode($prop) }}"` — non `wire:model` su `x-show`
- Nei componenti viewer: wrappare sempre con `@auth` + controllo ruolo

Pattern corretto del bottone:

```blade
<button wire:click="azione" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="azione">Testo</span>
    <span wire:loading wire:target="azione">
        <i class="fas fa-spinner fa-spin"></i>
    </span>
</button>
```

**Quando usare Livewire** — interazioni UI localizzate che richiedono
reattività senza ricaricare la pagina: tabelle con ricerca/filtro/ordinamento,
form multi-step, modali di conferma con azioni, upload con anteprima,
autocomplete, polling di notifiche o stato job.

**Quando NON usare Livewire** — intere pagine che funzionano bene con un
redirect, logica gestibile con un normale form POST, sostituzione di jQuery
per click handler banali.

### View e frontend

- Nessun `<script>` inline nel body: tutto via `@push('scripts')`
- Nessun `<style>` inline: solo classi Bootstrap 5 e AdminLTE 3
- Zero CSS custom
- Layout: `@extends('layouts.app')`, `@section('page-title', '...')`
- Empty state: icona `fa-3x text-muted` + testo esplicativo + CTA
- Non riscrivere le view esistenti: aggiungi solo i blocchi necessari
  nel punto corretto, identificato leggendo la view prima di modificarla
- `Storage::url()` per tutti i path file pubblici
- Output sempre escaped con `{{ }}`. Usare `{!! !!}` solo per HTML fidato
  generato dal codice, mai per input utente

### Migration

- Sempre includere `down()` implementato e reversibile
- `cascadeOnDelete()` obbligatorio su tutte le FK verso `users`
  (requisito GDPR: l'anonimizzazione deve propagarsi automaticamente)
- Usare `->nullable()->after('campo_esistente')` per colonne aggiunte
  a tabelle esistenti
- Non modificare migration già eseguite: creare sempre una nuova migration

### Query e performance

- Zero query N+1: eager loading obbligatorio con `with()` o `load()`
- Pre-caricare in memoria prima dei loop:
  `Model::whereNotNull('codice')->pluck('id', 'codice')` — non query dentro loop
- `->lazy()` per loop su dataset grandi (migration dati, import)
- `->limit()` nei componenti Livewire con polling per non caricare tabelle intere

### Pattern architetturali

- Notifiche: dispatch nei Service, mai nei controller
- Notifiche: `->onQueue('emails')` (fire-and-forget, non bloccano il workflow)
- Deduplicazione: pre-caricare i codici esistenti in memoria prima del loop
- Feature configurabili via file `config/`: zero hardcoding di valori
  configurabili nel codice (vedi `config/simulator.php`,
  `config/mit_import.php` come riferimento)
- Retrocompatibilità formati JSON: usare accessor/metodo dedicato sul model
  che gestisce entrambi i formati (es. `getAnswerResult()` su `QuizAttempt`)

### Testing

- Ogni feature deve avere almeno un Feature Test in `tests/Feature/`
- `RefreshDatabase` nei test che toccano il database
- Testare i componenti Livewire con `Livewire::test()`
- Usare factories per generare dati di test

Esempio Livewire test:

```php
Livewire::test(QuestionSearch::class)
    ->set('search', 'precedenza')
    ->assertSee('Diritto di precedenza')
    ->assertDontSee('Distanza di sicurezza');
```

---

## Comandi artisan frequenti

```bash
# Suite di test
php artisan test

# Scaffolding risorsa completo (model + migration + factory + seeder + controller)
php artisan make:model Foo -mfsc

# Livewire
php artisan make:livewire NomeComponente

# Cache produzione
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Comandi custom del progetto
php artisan questions:import-mit
php artisan enrollments:close-expired
php artisan gdpr:anonymize
```

---

## Checklist PR — obbligatoria prima di ogni merge

- [ ] `git flow feature finish` **NON** eseguito: aspetto il comando esplicito
- [ ] Nessuna logica di business nel controller
- [ ] `#[Validate]` sulle property Livewire (non `rules()`)
- [ ] `wire:loading` su tutti i bottoni Livewire
- [ ] `wire:model.blur` (non `.defer`, non `.live`)
- [ ] Nessun `dd()`, `dump()`, `var_dump()`
- [ ] Flash messages su tutti i redirect
- [ ] Nessuna query N+1
- [ ] Migration con `down()` implementato
- [ ] `cascadeOnDelete` sulle FK verso `users`
- [ ] Nessun asset inline nelle view
- [ ] Almeno un Feature Test per la funzionalità introdotta
- [ ] `README.md` aggiornato (se pertinente)
- [ ] `CHANGELOG.md` aggiornato con la voce della feature
- [ ] `php artisan test` — intera suite verde

---

## Known issues aperti

Non riaprire, non aggiungere workaround che li peggiorano.
Chiudere nella PR dedicata quando si lavora sull'area coinvolta.

| Issue                                                                                                                                  | Da chiudere in                                |
|----------------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------|
| `View::composer('*', ...)` in `AppServiceProvider` gira su ogni view: usare layout specifici, non aggiungere altri composer su `'*'`   | PR notifiche in-app                           |
| ~~`ImportQuestionsRequest` non valida `max:5120`~~ — **già presente, issue chiuso**                                                    | ~~PR Import MIT~~                             |
| ~~Migration `drop_quiz_results_table` da creare~~ — **CHIUSO in Refactor 7.2**                                                        | ~~PR dedicata~~                               |
| ~~`Quiz::hasQuestion()` e `QuizAttemptService::scoreAnswers()` senza type hint~~ — **CHIUSO in Refactor 7.2**                          | ~~prossima PR che tocca questi metodi~~       |
| ~~`RoleMiddleware::handle()` senza return type `Response`~~ — **CHIUSO in Feature 6.6**                                                | ~~prossima PR che tocca il middleware~~       |
