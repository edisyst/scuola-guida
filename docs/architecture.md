# Architettura — scuola-guida

Questa sezione descrive i flussi architetturali principali del progetto attraverso quattro diagrammi.

---

## 1. Laravel Request Lifecycle

Ciclo di vita di una HTTP request nel contesto di scuola-guida: dall'entry point `public/index.php` attraverso il Kernel, la Global Middleware Pipeline (auth, throttle, verified, CORS), il Route Middleware con `RoleMiddleware`, fino al Controller con `FormRequest`, il Service Layer e il pattern Observer sui Model per audit log e invalidazione cache.

![Laravel Request Lifecycle](diagrams/01-request-lifecycle.svg)

---

## 2. Livewire 3 Components

I quattro componenti Livewire 3 del progetto e i loro flussi di stato:

| Componente | Scopo | Pattern chiave |
|---|---|---|
| `MediaManager` | Upload, rename, delete immagini domande | `wire:loading`, storage pubblico |
| `NotificationBell` | Badge notifiche non lette | `wire:poll.30s`, no composer load |
| `QuestionReport` | Segnalazione errori sulle domande | `#[Validate]`, anti-spam 3 pending |
| `Bookmarks` | Salva/rimuovi domande preferite | Toggle con unique constraint su pivot |

![Livewire Component Flow](diagrams/02-livewire-flow.svg)

---

## 3. Role-based Access Control

Sistema di ruoli custom (no Spatie). I permessi granulari sono salvati come JSON nel campo `permissions` di ogni utente e configurabili da **Admin → Ruoli & Permessi**. I check nei Controller usano sempre `abort_unless(auth()->user()->canEditXxx(), 403)`.

| Ruolo | Accesso | Anagrafica richiesta |
|---|---|---|
| `admin` | Full: CRUD, publish/confirm quiz, audit log, gestione utenti | No |
| `editor` | CRUD domande/categorie/quiz (no publish, no confirm) | No |
| `viewer` | Quiz confermati, studio, simulatore, bookmark, segnalazioni | Sì |

![Role-based Access Control](diagrams/03-roles-pipeline.svg)

---

## 4. Quiz Lifecycle & Enrollment Workflow

Due workflow paralleli che si intersecano:

- **Quiz**: `draft` → `published` → `confirmed` (solo admin può publish e confirm)
- **Anagrafica viewer**: `none` → `pending` → `approved` / `rejected`
- **Enrollment**: `pending` → `approved` / `rejected` — disponibile solo dopo approvazione anagrafica

Lo scheduler `enrollments:close-expired` (dailyAt `00:05`) chiude automaticamente le iscrizioni pending rimaste oltre la data di chiusura e invia la notifica `IscrizioneQuizRifiutata` fire-and-forget sulla coda `emails`.

![Quiz Lifecycle and Enrollment Workflow](diagrams/04-quiz-lifecycle.svg)
