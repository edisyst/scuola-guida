# CLAUDE.md — Scuola Guida

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

## Stack

- Laravel 11, PHP 8.3+
- Frontend: Blade + Livewire 3 + Alpine.js + JS vanilla
- Tema: AdminLTE 3 + Bootstrap 5 — non introdurre altri framework CSS
- Database: MySQL
- Code quality: ogni Model usa il trait `Auditable` e il relativo `Observer`
- Ruoli e permessi: sistema custom con metodi `canEditQuestion()`,
  `canEditQuiz()`, `canEditCategory()`, `canEditUser()` su `User`.
  Non usare Spatie gates o `$user->hasRole()` di Spatie.

## Naming conventions

- Livewire components: `app/Http/Livewire/NomeComponente.php`
- Views Livewire: `resources/views/livewire/nome-componente.blade.php`
- Service: `app/Services/NomeEntitàService.php`
- Form Request: `app/Http/Requests/NomeAzioneNomeEntitàRequest.php`
- Migration: `verb_campo_to_tabella_table` oppure `create_tabella_table`
- Test Feature: `tests/Feature/NomeEntitàTest.php`
  — aggiungi ai file esistenti prima di crearne di nuovi
- Comandi artisan: `entità:azione` (es. `questions:import-mit`,
  `enrollments:close-expired`, `gdpr:anonymize`)

## Convenzioni sviluppo (obbligatorie per ogni PR)

### Livewire
- I componenti stanno in `app/Http/Livewire/`
- `wire:model.blur` — mai `.defer`, mai `.live`
- `#[Validate]` attribute direttamente sulle property (non il metodo `rules()`)
- `wire:loading` obbligatorio su **tutti** i bottoni che triggerano azioni
- Pattern corretto:
```blade
  <button wire:click="azione" wire:loading.attr="disabled">
      <span wire:loading.remove wire:target="azione">Testo</span>
      <span wire:loading wire:target="azione">
          <i class="fas fa-spinner fa-spin"></i>
      </span>
  </button>
```
- Per sincronizzare Alpine con stato Livewire:
  `x-show="{{ json_encode($prop) }}"` — non `wire:model` su `x-show`
- Nei componenti viewer: wrappare sempre con `@auth` + controllo ruolo

### Controller e Service
- Zero logica di business nel controller: tutto nei Service
- Autorizzazione: `abort_unless(auth()->user()->canEditXxx(), 403)`
- Validazione: sempre tramite Form Request dedicato, mai `validate()`
  inline nel controller
- Flash messages obbligatori su tutti i redirect:
  `with('success'|'warning'|'error'|'info', '...')`
- Injection del service nel costruttore o come parametro del metodo,
  coerente con il pattern già usato nel controller esistente

### View e frontend
- Nessun `<script>` inline nel body: tutto via `@push('scripts')`
- Nessun `<style>` inline: solo classi Bootstrap 5 e AdminLTE 3
- Zero CSS custom
- Layout: `@extends('layouts.app')`, `@section('page-title', '...')`
- Empty state: icona `fa-3x text-muted` + testo esplicativo + CTA
- Non riscrivere le view esistenti: aggiungi solo i blocchi necessari
  nel punto corretto, identificato leggendo la view prima di modificarla
- `Storage::url()` per tutti i path file pubblici

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

## Checklist PR — obbligatoria prima di ogni merge

- [ ] `git flow feature finish` **NON** eseguito: aspetto il comando esplicito
- [ ] Nessuna logica di business nel controller
- [ ] `#[Validate]` sulle property Livewire (non `rules()`)
- [ ] `wire:loading` su tutti i bottoni Livewire
- [ ] `wire:model.blur` (non `.defer`)
- [ ] Nessun `dd()`, `dump()`, `var_dump()`
- [ ] Flash messages su tutti i redirect
- [ ] Nessuna query N+1
- [ ] Migration con `down()` implementato
- [ ] `cascadeOnDelete` sulle FK verso `users`
- [ ] Nessun asset inline nelle view
- [ ] `README.md` aggiornato (se pertinente)
- [ ] `CHANGELOG.md` aggiornato con la voce della feature
- [ ] `php artisan test` — intera suite verde

## Known issues aperti

Non riaprire, non aggiungere workaround che li peggiorano.
Chiudere nella PR dedicata quando si lavora sull'area coinvolta.

| Issue | Da chiudere in |
|---|---|
| `View::composer('*', ...)` in `AppServiceProvider` gira su ogni view: usare layout specifici, non aggiungere altri composer su `'*'` | PR notifiche in-app |
| `ImportQuestionsRequest` non valida `max:5120` | PR Import MIT |
| Migration `drop_quiz_results_table` da creare | PR dedicata |
| `Quiz::hasQuestion()` e `QuizAttemptService::scoreAnswers()` senza type hint | prossima PR che tocca questi metodi |
| `RoleMiddleware::handle()` senza return type `Response` | prossima PR che tocca il middleware |
