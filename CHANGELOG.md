# Changelog

Tutte le modifiche significative a questo progetto sono documentate in questo file.
Formato seguente [Keep a Changelog](https://keepachangelog.com/it/1.0.0/).

## [Unreleased]

## [2026-05-16] — Iscrizioni anagrafica

### Added
- **Iscrizione anagrafica viewer** con approvazione admin: nuova interfaccia per la gestione delle iscrizioni con workflow di approvazione

### Fixed
- Ricerca navbar apre risultati in nuova scheda (fix comportamento clic)

### Changed
- Test: aggiorna redirect atteso dopo login da admin

---

## [2026-05-10] — Dark Mode & Media Manager

### Added
- **Dark mode completo**: migliora contrasto su tutte le views, copertura completa

### Changed
- Media manager: spaziatura verticale, classi CSS mancanti, dark mode completa
- UI: migliora grafica media manager con spaziamento e dimensioni corretti
- Branding: favicon volante, logo ScuolaGUIDA, rimozione riferimenti AdminLTE
- README: aggiorna documentazione con funzionalità, architettura e ciclo di vita quiz attuali

### Fixed
- Media manager: rinomina `upload()` in `save()` per evitare alias riservato Livewire 3

---

## [2026-05-01] — Media Manager & Dashboard Refactor

### Added
- **Media manager completo**: tab multi-cartella, griglia immagini, gestione file separata dallo storage
- Seeder di produzione separato per domande reali

### Changed
- Rinomina dashboard e stats: `/dashboard` è la homepage utente, `/admin/stats` è la panoramica admin
- Riordino menu laterale con sezioni e separatori per ruolo
- Pagine di errore personalizzate (404, 401, 403, 500)

---

## [2026-04-20] — Iscrizioni & Quiz Management

### Added
- **Iscrizioni ai quiz**: gestione completa del workflow di iscrizione

### Changed
- Refactoring edit e manage quiz: migliora interfaccia e UX
- Fix interfaccia edit e manage quiz

---

## [2026-04-10] — Business Logic Refactor

### Changed
- **Refactor business logic**: estrai logica da controller in Services, FormRequests, Observers e DataTables
- README: riscrivi documentazione con istruzioni di installazione e flusso business logic

### Added
- Permessi `read_xxx` e `bulk_xxx` per entità
- Permessi granulari per ruolo in controller e viste admin

### Fixed
- Fix DB seeder: risolve problemi di integrità dei dati

---

## [2026-03-25] — Quiz Features & Search

### Added
- **Ricerca globale dalla navbar**: ricerca domande e categorie
- **Dark mode toggle**: pulsante nella navbar per attivare/disattivare dark mode
- Dashboard con statistiche per utente (con cache)
- **Design system unificato**: ispirato alla schermata quiz/play

### Fixed
- Disabilita CSRF middleware nei test per risolvere errori 419
- Fix logica play quiz: storico tentativi e restyling UI
- Fix permessi dashboard

---

## [2026-03-15] — Infrastructure & CI/CD

### Changed
- CI: consolida workflow di test in un unico file
- CI: allinea requisito PHP a 8.3
- Migrations: consolida in una per tabella

---

## [2026-03-01] — Initial Setup

### Added
- Setup iniziale del progetto Laravel con AdminLTE
- Autenticazione base
- Modelli e migrazioni principali (User, Quiz, Question, QuizAttempt)
- Controllers resource per gestione quiz
- Viste Blade template per admin e user

### Changed
- Dependencies: aggiorna composer packages all'ultima versione stabile
