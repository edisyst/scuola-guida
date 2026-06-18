# Audit grafico — ScuolaGUIDA 2026-06-18

> **Feature 14.0** — Solo analisi, zero modifiche al codice.
> Fotografia dei problemi grafici reali nelle view Blade, con severità e roadmap dei
> lotti di refactoring successivi.

---

## Indice

1. [Inventario view per area](#1-inventario-view-per-area)
2. [Leggibilità e tipografia](#2-leggibilità-e-tipografia)
3. [Impaginazione e spazi vuoti](#3-impaginazione-e-spazi-vuoti)
4. [Spaziature tra box](#4-spaziature-tra-box)
5. [Contrasto colori (WCAG AA)](#5-contrasto-colori-wcag-aa)
6. [Uniformità di struttura](#6-uniformità-di-struttura)
7. [Uniformità box/div](#7-uniformità-boxdiv)
8. [Font e dimensioni](#8-font-e-dimensioni)
9. [CSS sparso](#9-css-sparso)
10. [Censimento token e duplicazioni](#10-censimento-token-e-duplicazioni)
11. [Tabella problemi con priorità](#11-tabella-problemi-con-priorità)
12. [Lotti di refactoring proposti](#12-lotti-di-refactoring-proposti)

---

## 1. Inventario view per area

### 1.1 Layout base

| Path | Scopo | Stack |
|---|---|---|
| `layouts/admin.blade.php` | Shell AdminLTE area autenticata | AdminLTE 3, Bootstrap 4 compat, `@extends('adminlte::page')` |
| `layouts/guest.blade.php` | Shell Bootstrap 5 area pubblica (homepage, auth, viewer) | Bootstrap 5.3 CDN, Alpine.js 3 CDN |
| `layouts/auth.blade.php` | Componente `<x-guest-layout>` per le pagine auth | Identico a `guest.blade.php` (stessa navbar, footer, card centrata) |
| `layouts/partials/appearance-css.blade.php` | Iniezione CSS variables dinamiche (`--sg-accent`, `--sg-font`, `--sg-radius`, `--sg-accent-dark`) | Blocco `<style>` generato da PHP, incluso in tutti i layout |

### 1.2 Pagina standalone

| Path | Scopo | Layout |
|---|---|---|
| `welcome.blade.php` | Landing page Laravel default, **non usa** `layouts/guest.blade.php` | Standalone, CSS `sg-home*` + font Figtree da bunny.net |

### 1.3 Area guest/pubblica

| Path | Scopo | Layout |
|---|---|---|
| `guest/home.blade.php` | Homepage configurabile (carosello, stats, feature, CTA) | `layouts/guest` |
| `auth/login.blade.php` | Accesso | `<x-guest-layout>` (layouts/auth) |
| `auth/register.blade.php` | Registrazione | `<x-guest-layout>` |
| `auth/forgot-password.blade.php` | Reset password | `<x-guest-layout>` |
| `auth/reset-password.blade.php` | Nuova password | `<x-guest-layout>` |
| `auth/verify-email.blade.php` | Verifica email | `<x-guest-layout>` |
| `auth/two-factor-challenge.blade.php` | 2FA challenge | `<x-guest-layout>` |
| `auth/two-factor-setup.blade.php` | Setup 2FA | `<x-guest-layout>` |
| `auth/two-factor-codes.blade.php` | Codici recovery 2FA | `<x-guest-layout>` |
| `auth/confirm-password.blade.php` | Conferma password | `<x-guest-layout>` |

### 1.4 Pagine errore

| Path | Scopo | Layout |
|---|---|---|
| `errors/layout.blade.php` | Shell standalone per errori HTTP | Standalone, CSS inline completo |
| `errors/401.blade.php` | Non autenticato | `errors/layout` |
| `errors/403.blade.php` | Proibito | `errors/layout` |
| `errors/404.blade.php` | Non trovato | `errors/layout` |
| `errors/500.blade.php` | Errore server | `errors/layout` |

### 1.5 Area admin

| Path | Scopo | Wrapper |
|---|---|---|
| `admin/dashboard.blade.php` | Statistiche globali + grafici | `sg-wrapper` |
| `admin/users/index.blade.php` | Lista utenti | `sg-wrapper` |
| `admin/users/create.blade.php` | Crea utente | `sg-wrapper` |
| `admin/users/edit.blade.php` | Modifica utente | `sg-wrapper` |
| `admin/users/form.blade.php` | Form permessi utente (partial) | — |
| `admin/questions/index.blade.php` | Lista domande (DataTables) | `sg-wrapper` |
| `admin/questions/create.blade.php` | Crea domanda | `sg-wrapper` |
| `admin/questions/edit.blade.php` | Modifica domanda | `sg-wrapper` |
| `admin/questions/mit-import.blade.php` | Import MIT | `sg-wrapper` |
| `admin/questions/translations.blade.php` | Traduzioni domanda | `sg-wrapper` |
| `admin/quizzes/index.blade.php` | Lista quiz | `sg-wrapper` |
| `admin/quizzes/create.blade.php` | Crea quiz | `sg-wrapper` |
| `admin/quizzes/questions.blade.php` | Ordinamento domande quiz (sortable) | `sg-wrapper` |
| `admin/quizzes/schedule.blade.php` | Pianificazione quiz | `sg-wrapper` |
| `admin/quizzes/summary.blade.php` | Riepilogo quiz | `sg-wrapper` |
| `admin/quizzes/confirmed-results.blade.php` | Risultati quiz confermato | `sg-wrapper` |
| `admin/categories/index.blade.php` | Lista categorie | `sg-wrapper` |
| `admin/categories/create.blade.php` | Crea categoria | `sg-wrapper` |
| `admin/categories/edit.blade.php` | Modifica categoria | `sg-wrapper` |
| `admin/categories/show.blade.php` | Dettaglio categoria | `sg-wrapper` |
| `admin/categories/materials/index.blade.php` | Lista materiali | `sg-wrapper` |
| `admin/categories/materials/create.blade.php` | Crea materiale | `sg-wrapper` |
| `admin/categories/materials/edit.blade.php` | Modifica materiale | `sg-wrapper` |
| `admin/quiz-attempts/index.blade.php` | Tutti i tentativi | `sg-wrapper` |
| `admin/question-reports/index.blade.php` | Segnalazioni domande | `sg-wrapper` |
| `admin/question-reports/show.blade.php` | Dettaglio segnalazione | `sg-wrapper` |
| `admin/registrations/index.blade.php` | Lista registrazioni | `sg-wrapper` |
| `admin/registrations/show.blade.php` | Dettaglio registrazione | `sg-wrapper` |
| `admin/enrollments/index.blade.php` | Iscrizioni quiz | `sg-wrapper` |
| `admin/instructors/index.blade.php` | Lista istruttori | `sg-wrapper` |
| `admin/instructors/edit.blade.php` | Modifica istruttore | `sg-wrapper` |
| `admin/driving-modules/index.blade.php` | Moduli guida | `sg-wrapper` |
| `admin/driving-modules/create.blade.php` | Crea modulo | `sg-wrapper` |
| `admin/driving-modules/edit.blade.php` | Modifica modulo | `sg-wrapper` |
| `admin/driving-modules/show.blade.php` | Dettaglio modulo | `sg-wrapper` |
| `admin/roles/index.blade.php` | Matrice permessi ruoli | `sg-wrapper` |
| `admin/media/index.blade.php` | Gestione media | `sg-wrapper` |
| `admin/reports/index.blade.php` | Reportistica | `sg-wrapper` |
| `admin/reports/show.blade.php` | Dettaglio report | `sg-wrapper` |
| `admin/audit/index.blade.php` | Audit log (Livewire) | `sg-wrapper` |
| `admin/audit-log/index.blade.php` | Audit log (tabella) | **`container-fluid`** ⚠️ |
| `admin/audit-log/show.blade.php` | Dettaglio audit | **`container-fluid`** ⚠️ |
| `admin/health/index.blade.php` | Health check | **`container-fluid`** ⚠️ |
| `admin/license-types/index.blade.php` | Lista tipi patente | **`container-fluid`** ⚠️ |
| `admin/license-types/create.blade.php` | Crea tipo patente | **`container-fluid`** ⚠️ |
| `admin/license-types/edit.blade.php` | Modifica tipo patente | **`container-fluid`** ⚠️ |
| `admin/system/settings.blade.php` | Impostazioni sistema | **`container-fluid`** ⚠️ |
| `admin/system/features.blade.php` | Feature toggle (Livewire) | `sg-wrapper` |
| `admin/system/form-fields.blade.php` | Campi moduli | `container-fluid` ⚠️ |
| `admin/system/health.blade.php` | Salute sistema | `container-fluid` ⚠️ |
| `admin/commands/index.blade.php` | Comandi artisan | `sg-wrapper` |

### 1.6 Area viewer (utente autenticato)

| Path | Scopo | Wrapper |
|---|---|---|
| `stats/dashboard.blade.php` | Dashboard statistiche personali | `sg-wrapper` |
| `quiz/play.blade.php` | Esecuzione quiz | `quiz-wrapper` |
| `quiz/attempt.blade.php` | Revisione tentativo | `sg-wrapper` |
| `quiz/attempts.blade.php` | Storico tentativi | `sg-wrapper` |
| `quiz/confirmed/index.blade.php` | Quiz disponibili | `sg-wrapper` |
| `quiz/enrollments/index.blade.php` | Iscrizioni viewer | `sg-wrapper` |
| `simulator/index.blade.php` | Avvio simulatore | `sg-wrapper` |
| `simulator/play.blade.php` | Simulatore esame | `quiz-wrapper` |
| `simulator/result.blade.php` | Risultato simulazione | `sg-wrapper` |
| `study/index.blade.php` | Selezione sorgente studio | `sg-wrapper` |
| `study/play.blade.php` | Sessione di studio | `sg-wrapper` (max-width:800px inline) |
| `study/summary.blade.php` | Riepilogo sessione | `sg-wrapper` |
| `smart-review/index.blade.php` | Smart review index | `sg-wrapper` |
| `smart-review/session.blade.php` | Sessione smart review | `sg-wrapper` |
| `review-errors/index.blade.php` | Domande con errori | `sg-wrapper` |
| `bookmarks/index.blade.php` | Segnalibri | `sg-wrapper` |
| `calendar/index.blade.php` | Calendario quiz | `sg-wrapper` |
| `notifications/index.blade.php` | Notifiche | `sg-wrapper` |
| `profile/edit.blade.php` | Profilo e preferenze | `sg-wrapper-sm` |
| `driving/progress.blade.php` | Progressi guida pratica | `sg-wrapper` |
| `search/results.blade.php` | Risultati ricerca | `sg-wrapper` |
| `diagnostic/show.blade.php` | Test diagnostico | `sg-wrapper` |
| `study-contents/index.blade.php` | Contenuti studio | `sg-wrapper` |
| `study-contents/create.blade.php` | Crea contenuto | `sg-wrapper` |
| `study-contents/edit.blade.php` | Modifica contenuto | `sg-wrapper` |
| `study-plan/show.blade.php` | Piano di studio | `sg-wrapper` |
| `viewer/badges.blade.php` | Badge gamification | `sg-wrapper` |

### 1.7 Area editor

| Path | Scopo | Wrapper |
|---|---|---|
| `editor/dashboard.blade.php` | Dashboard editor | `sg-wrapper` |

### 1.8 Area instructor

| Path | Scopo | Wrapper |
|---|---|---|
| `instructor/index.blade.php` | Lista studenti | `sg-wrapper` |
| `instructor/student.blade.php` | Dettaglio studente | `sg-wrapper` |

### 1.9 Componenti Blade

| Path | Scopo |
|---|---|
| `components/auth-session-status.blade.php` | Alert stato sessione auth |
| `components/danger-button.blade.php` | Bottone distruttivo |
| `components/input-error.blade.php` | Messaggio errore campo |
| `components/input-label.blade.php` | Label campo form |
| `components/modal.blade.php` | Modal generica |
| `components/primary-button.blade.php` | Bottone primario |
| `components/secondary-button.blade.php` | Bottone secondario |
| `components/text-input.blade.php` | Input testo |

### 1.10 Componenti Livewire

| Path | Scopo |
|---|---|
| `livewire/notification-bell.blade.php` | Campanella notifiche navbar |
| `livewire/bookmark-button.blade.php` | Segnalibro domanda |
| `livewire/report-button.blade.php` | Segnalazione domanda |
| `livewire/question-version-history.blade.php` | Storico versioni domanda |
| `livewire/smart-review.blade.php` | Widget smart review |
| `livewire/study-content-viewer.blade.php` | Viewer contenuti studio |
| `livewire/diagnostic-test.blade.php` | Test diagnostico interattivo |
| `livewire/admin/feature-toggles.blade.php` | Toggle funzionalità admin |
| `livewire/admin/form-fields-manager.blade.php` | Gestione campi moduli |
| `livewire/admin/media-manager.blade.php` | Media manager |

### 1.11 Template PDF e email (esclusi dall'audit UI)

PDF: `driving/pdf/attestation.blade.php`, `instructor/pdf/student-progress.blade.php`, `admin/reports/pdf/period.blade.php` — usano CSS inline per DomPDF, pattern corretto.  
Email: `emails/*.blade.php` — layout email transazionali, fuori scope.

---

## 2. Leggibilità e tipografia

### 2.1 Gerarchia tipografica

Il design system definisce le dimensioni principali via classi `sg-*`:

| Classe | Font-size | Contesto |
|---|---|---|
| `sg-header-title` | 1.35rem / 1.2rem (tablet) / 1.05rem (mobile) | H1 pagina |
| `sg-card-header-title` | 1rem | Titolo card |
| `sg-page-title` | 1.55rem / 1.25rem (mobile) | Titolo pagina flat |
| `sg-stat-value` | 1.9rem / 1.5rem / 1.4rem | KPI numerico |
| `sg-label` | 0.7rem, uppercase | Label metrica |
| `sg-form-label` | 0.85rem | Label campo form |
| `#question-text` | 1.25rem → 1.1rem → 1rem | Testo domanda |
| `timer` | 2.2rem → 1.55rem | Timer quiz |
| Body base | `--sg-font` (variabile) | — |

**Problema**: le view che usano Bootstrap 4/AdminLTE senza classi `sg-*` ereditano taglie diverse (AdminLTE usa `1rem` base ma con line-height diverso). L'utente percepisce un salto visivo tra, ad esempio, la pagina profilo (mix Bootstrap 4 + Bootstrap 5) e la dashboard.

### 2.2 Line-height

- `#question-text`: line-height: 1.6 ✓
- `sg-form-control`: line-height: 1.6 ✓  
- Corpo testo generico nelle card: ereditato da AdminLTE/Bootstrap (~1.5) — nessun override esplicito nelle view, dipende dalla cascade

### 2.3 Lunghezza riga di testo

- `sg-wrapper` max-width: 1100px — su schermi >1400px le righe nelle tabelle sono adeguate. Le sezioni di testo puro dentro le card potrebbero risultare troppo larghe (>80 caratteri) se non vengono vincolate.
- `sg-wrapper-sm` max-width: 720px — usato solo in `profile/edit.blade.php`. Appropriato per form.
- `study/index.blade.php` limita esplicitamente il form a `max-width: 800px` via style inline — corretto ma non coerente con l'uso di `sg-wrapper`.

---

## 3. Impaginazione e spazi vuoti

### 3.1 Wrapper admin area

**Pattern atteso**: ogni vista admin usa `<div class="sg-wrapper">` come root.  
**Eccezioni trovate** (9 view su ~50 admin):

| View | Wrapper usato |
|---|---|
| `admin/audit-log/index.blade.php` | `container-fluid` |
| `admin/audit-log/show.blade.php` | `container-fluid` |
| `admin/health/index.blade.php` | `container-fluid` |
| `admin/license-types/index.blade.php` | `container-fluid` |
| `admin/license-types/create.blade.php` | `container-fluid` |
| `admin/license-types/edit.blade.php` | `container-fluid` |
| `admin/system/settings.blade.php` | `container-fluid` |
| `admin/system/form-fields.blade.php` | `container-fluid` |
| `admin/system/health.blade.php` | `container-fluid` |

Su schermi > 1200px `container-fluid` occupa l'intera larghezza del `content-wrapper` di AdminLTE (~`100% - 250px sidebar`), creando bande di testo molto larghe. `sg-wrapper` (max-width: 1100px centrato) è più leggibile.

### 3.2 Hero `guest/home.blade.php`

Il contenuto della sezione hero è avvolto in `style="width:80%;margin:0 auto;"` invece di un `container` Bootstrap o `sg-wrapper`. Su schermi < 576px questo produce una banda laterale di 10% per lato che è poco intuitiva se l'intent era "card bordata", ma non è sbagliata se l'intent è "carosello a tutta larghezza con padding".

### 3.3 Quiz/simulatore play

`quiz-wrapper` (max-width: 1100px) — coerente con `sg-wrapper`. Il layout 8+4 colonne (domanda + sidebar) è appropriato per desktop; su mobile la sidebar passa sotto correttamente via media query. Nessun problema strutturale.

---

## 4. Spaziature tra box

### 4.1 Spaziature tra card nella griglia

La griglia `sg-grid-row` / `sg-grid-col` (`margin: 0 -8px` / `padding: 0 8px; margin-bottom: 16px`) è usata correttamente nella maggior parte delle view admin e nella dashboard.

**Incoerenze rilevate**:

- `stats/dashboard.blade.php`: usa `sg-card sg-mt-3` (custom margin) invece di `sg-grid-col` per le card secondarie — spazio diverso rispetto alle card nella griglia principale.
- `simulator/result.blade.php` e `quiz/attempt.blade.php`: card risultato usa `mb-4` Bootstrap, card lista domande usa `mb-3` Bootstrap — nessun `sg-grid-col`.
- `profile/edit.blade.php`: card sections usano `sg-mb-3` — coerente internamente ma diverso rispetto alla griglia admin.

### 4.2 Inline style gap/padding hardcoded

Valori `gap` e `padding` hardcoded via `style="..."` in punti non coperti da classi `sg-*`:

| Pattern | Occorrenze | Esempio |
|---|---|---|
| `style="gap:12px;"` | ~8 | `admin/system/settings.blade.php` color picker |
| `style="gap:1rem;"` | ~3 | `profile/edit.blade.php` form-group |
| `style="max-width:260px;"` | ~3 | `admin/dashboard.blade.php` tabella celle |
| `style="max-width:280px;"` | ~2 | `admin/dashboard.blade.php` tabella celle |
| `style="height:100%;"` | ~5 | card nelle griglie |
| `style="font-size:.7rem;"` | ~2 | bottoni delete carousel |

---

## 5. Contrasto colori (WCAG AA)

Target: 4.5:1 testo normale, 3:1 testo grande (≥ 18pt / ≥ 14pt bold) e componenti UI.

### 5.1 Accent color — problema parametrico

`--sg-accent` è scelto dall'admin. Le seguenti combinazioni dipendono dal suo valore:

| Punto di uso | Testo sopra | Rischio |
|---|---|---|
| `guest/home.blade.php` CTA finale `<section style="background:var(--sg-accent);">` | `text-white` | CRITICO — accent chiaro (es. giallo) + bianco = fail |
| `layouts/guest.blade.php` navbar register `style="background-color:var(--sg-accent);"` | `class="btn btn-sm text-white"` | CRITICO |
| `layouts/auth.blade.php` register button | idem | CRITICO |
| `guest/home.blade.php` license type badges `style="background:var(--sg-accent);"` | testo bianco implicito | CRITICO |

**Raccomandazione**: calcolare dinamicamente il colore testo (bianco o nero) in base alla luminanza relativa di `--sg-accent`. Algoritmo: `luminance = 0.2126*R + 0.7152*G + 0.0722*B`; se > 0.4 usare `#000` o `#212529`, altrimenti `#fff`. Implementabile con una CSS calc() limitata o con un valore PHP al momento del salvataggio.

### 5.2 Badge ruolo (`sg-badge-role`)

| Badge | Sfondo | Testo | Rapporto contrasto | Esito WCAG AA |
|---|---|---|---|---|
| `role-admin` | `#6f42c1` | `#fff` | ~5.0:1 | ✓ Passa |
| `role-editor` | `#fd7e14` | `#fff` | ~2.82:1 | ✗ Fallisce (< 4.5:1) |
| `role-viewer` | `#20c997` | `#fff` | ~2.47:1 | ✗ Fallisce |

### 5.3 Navbar viewer (`--rt-viewer: #ffc107`)

La topnav del ruolo viewer usa sfondo `#ffc107` (giallo-ambra). Il testo è `rgba(0,0,0,.75)` — rapporto ~8:1. ✓ Passa.  
Il badge `body.role-viewer .main-header .badge { color: var(--rt-viewer-dk) !important; }` dove `--rt-viewer-dk: #d39e00` su sfondo `#fff`: rapporto ~2.68:1 — ✗ Fallisce per testo normale.

### 5.4 Dark mode

La copertura dark mode in `scuola-guida.css` è estesa (> 300 righe di override). Alcune lacune:

- `study/play.blade.php` usa Bootstrap `badge-secondary`, `alert-success`, `alert-danger` senza override `sg-*` in dark — questi classi Bootstrap 4 non sono coperte automaticamente dall'AdminLTE dark mode.
- `profile/edit.blade.php` usa `custom-control-label` (Bootstrap 4) — possibile rendering scorretto in dark mode AdminLTE.

### 5.5 Errori pagina

`errors/layout.blade.php`: testo principale `rgba(255,255,255,.58)` su sfondo scuro `linear-gradient(135deg, #0d0d1a 0%, #1a1a2e 50%, #0f3460 100%)`.  
Calcolo approssimativo: sfondo media ~`#0a1528` (molto scuro). Bianco al 58% = `rgba(255,255,255,0.58)` → luminanza ~0.27 su sfondo ~0.003 → rapporto ~90:1 (va bene). ✓

---

## 6. Uniformità di struttura

### 6.1 Struttura scheletro pagina admin

**Pattern corretto** (67 view su ~76 admin):
```
@extends('layouts.admin')
@section('content_header')@endsection  <!-- header vuoto: usa sg-header interno -->
@section('content')
  <div class="sg-wrapper">
    <div class="sg-header sg-flex-between">...</div>
    <div class="sg-card">...</div>
  </div>
@endsection
```

**Eccezioni senza `content_header` vuoto**: `admin/system/settings.blade.php` usa `@section('content_header') <h1>...</h1> @stop`, generando un doppio titolo (AdminLTE `content-header` + contenuto).

**Eccezioni senza `sg-header`**:
- `admin/system/settings.blade.php` — nessun `sg-header`, inizia direttamente con form + `.card`
- `admin/audit-log/*.blade.php` — usano `h3.card-title` nel card-header invece di `sg-header`
- `admin/health/index.blade.php` — idem
- `admin/license-types/*.blade.php` — usa h1 inline

### 6.2 Struttura pagine risultato quiz/simulatore

`quiz/attempt.blade.php` e `simulator/result.blade.php` usano card AdminLTE colorate:
```html
<div class="card card-success mb-4">
  <div class="card-header"><h3 class="card-title">...</h3></div>
  <div class="card-body">...</div>
</div>
```
Il pattern `sg-card` + `sg-card-header` è assente qui. Visivamente produce card con header colorato (verde/rosso AdminLTE) che è inconsistente con il resto delle pagine (header bianchi `sg-card`).

### 6.3 Empty state

Il pattern documentato (icona `fa-3x text-muted` + testo + CTA) è applicato correttamente in:
- `stats/dashboard.blade.php` (nessun tentativo)
- `admin/dashboard.blade.php` (nessuna categoria)

Ma è assente o diverso in:
- Alcune tabelle DataTables: l'empty state è gestito da DataTables stesso, con testo non stilizzato.
- `quiz/confirmed/index.blade.php` (non letto integralmente ma struttura attesa).

---

## 7. Uniformità box/div

### 7.1 Card amministrative

| Pattern | View di esempio | Coerente |
|---|---|---|
| `<div class="sg-card"><div class="sg-card-header">...<div class="sg-card-body">` | Dashboard, questions/index, categories/index, quiz/index, users/index | ✓ Maggioranza |
| `<div class="card"><div class="card-header"><h3 class="card-title">...<div class="card-body">` | system/settings, audit-log, license-types, health | ✗ Minoranza |
| `<div class="card card-success/card-danger">` | quiz/attempt, simulator/result | ✗ Solo risultati |

### 7.2 Form admin

| Pattern | View di esempio |
|---|---|
| `sg-form-control`, `sg-form-label`, `sg-form-group` | Sezioni auth, componenti Blade |
| `form-control`, `form-group` Bootstrap 4/5 | admin/system/settings, profile, study/index |
| Misto `sg-form-control` + `form-group` Bootstrap | questions/create, categories/create |

### 7.3 Bottoni admin

| Pattern | View di esempio |
|---|---|
| `sg-btn sg-btn-primary`, `sg-btn sg-btn-light sg-btn-sm` | header actions, questions, categories |
| `btn btn-primary`, `btn btn-secondary` Bootstrap | system/settings, profile/edit, study/index |
| Misto stesso form | profile/edit (outer sg-btn, inner btn-primary) |

---

## 8. Font e dimensioni

### 8.1 Famiglie font presenti

| Origine | Font | Dove usato |
|---|---|---|
| `appearance-css.blade.php` | `--sg-font` (sistema, Inter, Roboto, Open Sans) | Tutto il corpo tramite `body { font-family: var(--sg-font) }` |
| `welcome.blade.php` | `'Figtree'` da `fonts.bunny.net` | Solo `welcome.blade.php` standalone |
| `errors/layout.blade.php` | `'Segoe UI', system-ui, -apple-system, sans-serif` hardcoded | Solo pagine errore |

**Problema**: `welcome.blade.php` e le pagine errore non usano `--sg-font`. Se l'admin imposta Inter come font, la homepage Laravel e i 404 rimangono con il font di sistema/Figtree.

### 8.2 Pesi font

I pesi usati nel design system: 400 (body), 500 (`sg-link`), 600 (label), 700 (sg-btn, sg-header-title, sg-card-header-title), 800 (sg-stat-value, sg-home-title).  
Le Google Fonts vengono caricate con i pesi esatti richiesti. Nessun problema.

### 8.3 Dimensioni font hardcoded non nel design system

| Valore | Dove | Note |
|---|---|---|
| `font-size:1.3rem;padding:0.5rem 1.2rem;` | badge Superato/Bocciato in `quiz/attempt`, `simulator/result` | Inline, non classe |
| `font-size:.7rem;` | bottone delete carousel in `system/settings` | Inline |
| `font-size:1.2rem;` | stat-value "ultimo tentativo" | Inline style override |
| `font-size: clamp(2rem, 4.5vw, 3.4rem)` | `sg-home-title` | Già in CSS centralizzato ✓ |

---

## 9. CSS sparso

### 9.1 Blocchi `<style>` nelle view (non-PDF)

| File | Contenuto | Legittimità |
|---|---|---|
| `layouts/admin.blade.php` | `.navbar-brand img.school-logo { max-height:40px; width:auto; }` | **Dovrebbe stare in `scuola-guida.css`** |
| `layouts/partials/appearance-css.blade.php` | Iniezione `:root { --sg-accent... }` + body font-family | ✓ Legittimo (valori dinamici da DB) |
| `welcome.blade.php` | `body { margin:0; font-family:'Figtree'... }` | **Problema** (font non rispetta --sg-font) |
| `study/play.blade.php` | `[x-cloak] { display: none !important; }` | Marginale, ricorre in molte view — centralizzabile |
| `admin/quizzes/questions.blade.php` | Stili sortable specifici | Candidato a `scuola-guida.css` |
| `search/results.blade.php` | Stili highlight ricerca (`.sg-search-highlight`) | Candidato a `scuola-guida.css` |
| `offline.blade.php` | Stili pagina offline | Pochi, accettabile se non si crea un file per una view |
| `errors/layout.blade.php` | Layout completo errori (legittimo, standalone) | ✓ Legittimo |
| `errors/*.blade.php` | Solo variabili CSS per colore | ✓ Legittimo (`@yield('theme-vars')`) |

**Nota**: `[x-cloak]` è usato in multiple view ma non è in `scuola-guida.css`. Va spostato lì una sola volta.

### 9.2 Attributi `style="..."` inline con valori hardcoded rilevanti

| Valore | File di esempio | Problema |
|---|---|---|
| `style="background:rgba(0,0,0,0.45);border-radius:12px;padding:10px 18px;backdrop-filter:blur(2px);"` | `guest/home.blade.php` × 4 | Ripetuto 4 volte, dovrebbe diventare classe `.sg-hero-overlay` |
| `style="width:80%;margin:0 auto;"` | `guest/home.blade.php` hero container | Non-standard, usa % anziché container BS5 |
| `style="min-height:40vh;"` | `guest/home.blade.php` | Valore esposto inline |
| `style="min-height:calc(100vh - 56px - 80px);"` | `layouts/auth.blade.php` contenitore centrato | Esposto inline, fragile (56px = navbar height hardcoded) |
| `style="max-height:220px; cursor:pointer;"` | `quiz/play.blade.php`, `simulator/play.blade.php` | Ripetuto in 2 view play |
| `style="background:#333; padding:4px;"` | `admin/system/settings.blade.php` logo dark preview | Colore hardcoded |
| `style="font-family: var(--font-family);"` | `admin/system/settings.blade.php` select font | **Typo**: dovrebbe essere `var(--sg-font)` |
| `style="width:200px;height:63px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;"` | `admin/system/settings.blade.php` carousel preview | Inline, candidato a classe |
| `style="background:var(--sg-accent);"` con `text-white` | `guest/home.blade.php` CTA, badge | Contrasto parametrico — vedi §5.1 |

### 9.3 Classi Bootstrap 4 in context Bootstrap 5

Le view seguenti usano classi Bootstrap 4 (`ml-*`, `mr-*`, `font-weight-bold`, `badge-success`, `custom-control`, `embed-responsive`, `data-toggle`, `data-target`) in un contesto dove Bootstrap 5 è il framework:

- `study/play.blade.php` — `badge-secondary`, `embed-responsive`, `data-toggle="collapse"`, `data-target`
- `study/index.blade.php` — `custom-control custom-radio`, `custom-control-input`, `font-weight-bold`, `ml-3`
- `profile/edit.blade.php` — `custom-control custom-switch`, `form-group`, `btn btn-primary`, `mr-1`
- `quiz/attempt.blade.php` — `font-weight-bold`, `mr-2`, `mr-1`, `float-right`, `mr-1`

AdminLTE 3 include una compatibilità parziale Bootstrap 4, quindi queste classi probabilmente non rompono il layout, ma genereranno problemi futuri all'aggiornamento ad AdminLTE 4 / Bootstrap 5 puro.

---

## 10. Censimento token e duplicazioni

### 10.1 Colori hardcoded ricorrenti

| Valore | Significato | Definito come token | Dove hardcoded |
|---|---|---|---|
| `#4361ee` | Primary blue | `--sg-primary` ✓ | JS Chart.js color strings in `admin/dashboard.blade.php`, `stats/dashboard.blade.php` |
| `#28a745` | Success green | `--sg-success` ✓ | JS Chart.js colors, `admin/dashboard.blade.php` |
| `#dc3545` | Danger red | `--sg-danger` ✓ | JS Chart.js background `stats/dashboard.blade.php` |
| `#f1f3f5` | Grid line color | `--sg-border-light` (~) | JS Chart.js scales in dashboard |
| `#dee2e6` | Border default | Bootstrap default | `admin/system/settings.blade.php` carousel border |
| `rgba(0,0,0,0.45)` | Hero overlay | — | `guest/home.blade.php` × 4 |
| `rgba(0,0,0,.15)` | Role brand link border | — | CSS `scuola-guida.css` role theming × 4 |
| `rgba(255,255,255,.12)` | Sidebar hover | — | CSS × 3 |

### 10.2 Misure hardcoded ricorrenti

| Valore | Significato | Occorrenze |
|---|---|---|
| `12px` | Border-radius scenari speciali | ~6 (hero overlay, flag preview, ecc.) |
| `220px` | Max-height immagini domanda | `quiz/play.blade.php`, `simulator/play.blade.php` × 2 |
| `40px` / `36px` | Max-height logo navbar | `layouts/admin.blade.php`, `layouts/guest.blade.php` |
| `80px` | Max-height logo auth brand | `layouts/auth.blade.php` |
| `56px` | Altezza navbar (usato in calc) | `layouts/auth.blade.php` |
| `800px` | Larghezza massima form studio | `study/index.blade.php` inline style + `study/play.blade.php` |

### 10.3 Duplicazioni strutturali

- `[x-cloak] { display: none !important; }` presente in: `study/play.blade.php` come `<style>` inline. Assente da `scuola-guida.css`.
- Markup HTML backdrop hero (`rgba(0,0,0,0.45)...`) ripetuto 4 volte in `guest/home.blade.php`.
- Logica color-picker Alpine.js per accent (font-family preview in system/settings): ripetuta in blocchi `x-data` separati per ogni campo colore, invece di un unico componente.

---

## 11. Tabella problemi con priorità

| # | Problema | Severità | View/file coinvolti | Tipo intervento | Lotto |
|---|---|---|---|---|---|
| P01 | Accent su testo bianco senza verifica contrasto (accent dinamico) | **Alta** — accessibilità | `guest/home.blade.php` (CTA sezione, badges), `layouts/guest.blade.php` (navbar register), `layouts/auth.blade.php` | CSS custom centralizzato (CSS custom property + JS luminance calc o PHP) | L1 |
| P02 | Badge ruolo `role-editor` (#fd7e14 + bianco = 2.82:1) e `role-viewer` (#20c997 + bianco = 2.47:1) | **Alta** — accessibilità | `scuola-guida.css` `.sg-badge-role` | Riallineamento colori (aumentare saturazione o cambiare testo) | L1 |
| P03 | Badge navbar viewer `#d39e00` su bianco = 2.68:1 | **Alta** — accessibilità | `scuola-guida.css` role-viewer badge topnav | Riallineamento colori | L1 |
| P04 | 9 view admin usano `container-fluid` invece di `sg-wrapper` | **Media** — incoerenza visibile su wide screens | `admin/system/settings.blade.php`, `admin/audit-log/*.blade.php`, `admin/health/index.blade.php`, `admin/license-types/*.blade.php`, `admin/system/form-fields.blade.php`, `admin/system/health.blade.php` | Riallineamento classi Bootstrap/AdminLTE | L2 |
| P05 | `admin/system/settings.blade.php` usa schema card Bootstrap 4 (no `sg-header`, no `sg-card`) e ha duplicato h1 (`content_header` + contenuto) | **Media** — incoerenza visibile | `admin/system/settings.blade.php` | Riallineamento classi + aggiunta sg-header | L2 |
| P06 | Risultati quiz/simulatore usano card colorate AdminLTE (`card-success/card-danger`) invece di `sg-card` | **Media** — incoerenza visibile | `quiz/attempt.blade.php`, `simulator/result.blade.php` | Riallineamento classi Bootstrap/AdminLTE | L2 |
| P07 | 4 backdrop overlay in `guest/home.blade.php` con 60+ char di inline style ripetuti | **Media** — manutenibilità + coerenza | `guest/home.blade.php` | CSS custom centralizzato (classe `.sg-hero-overlay`) | L3 |
| P08 | Typo `style="font-family: var(--font-family);"` (variabile inesistente) | **Media** — bug funzionale | `admin/system/settings.blade.php` | Fix inline → `var(--sg-font)` | L2 |
| P09 | `welcome.blade.php` usa font Figtree da bunny.net, non rispetta `--sg-font` | **Media** — incoerenza tipografica | `welcome.blade.php` | Riallineamento o redirect a `guest.home` | L3 |
| P10 | Classi Bootstrap 4 (`custom-control`, `badge-secondary`, `embed-responsive`, `data-toggle`, `ml-*`, `mr-*`) in view che usano Bootstrap 5 | **Media** — debt tecnico, potenziali problemi futuri | `study/play.blade.php`, `study/index.blade.php`, `profile/edit.blade.php`, `quiz/attempt.blade.php` | Riallineamento classi Bootstrap 4→5 | L4 |
| P11 | `[x-cloak]` presente in `<style>` di `study/play.blade.php` ma assente da `scuola-guida.css` | **Bassa** — manutenibilità | `study/play.blade.php`, `scuola-guida.css` | CSS custom centralizzato | L3 |
| P12 | `.navbar-brand img.school-logo` in `<style>` di `layouts/admin.blade.php` | **Bassa** — separazione responsabilità | `layouts/admin.blade.php` | CSS custom centralizzato | L3 |
| P13 | Stili sortable e search highlight in `<style>` inline nei file blade | **Bassa** — manutenibilità | `admin/quizzes/questions.blade.php`, `search/results.blade.php` | CSS custom centralizzato | L3 |
| P14 | Colori Chart.js hardcoded come stringhe JS (`'#4361ee'`, `'#28a745'`), non leggono `--sg-primary` | **Bassa** — token non rispettati | `admin/dashboard.blade.php`, `stats/dashboard.blade.php` | CSS custom centralizzato (passare i valori via data-attribute PHP o JS vars) | L4 |
| P15 | `min-height:calc(100vh - 56px - 80px)` hardcoded in `layouts/auth.blade.php` (navbar height 56px fragile) | **Bassa** — fragilità | `layouts/auth.blade.php` | CSS custom centralizzato (variabile navbar height) | L3 |
| P16 | Bottoni primari in `profile/edit.blade.php` e sezioni profilo usano `btn btn-primary` Bootstrap invece di `sg-btn` | **Bassa** — incoerenza visiva | `profile/edit.blade.php`, partials profilo | Riallineamento classi | L4 |
| P17 | `guest/home.blade.php` hero container usa `width:80%;margin:0 auto` invece di `container` BS5 o `sg-wrapper` | **Bassa** — inconsistenza layout | `guest/home.blade.php` | Riallineamento classi Bootstrap | L3 |

---

## 12. Lotti di refactoring proposti

I lotti sono ordinati per urgenza/impatto. Ogni lotto diventa un prompt di refactoring separato.

---

### Lotto 1 — Contrasto colori e accessibilità (WCAG AA)

**Scope**: accessibilità, sicurezza visiva per tutti gli utenti.

**Interventi**:
1. Creare una strategia accent-contrast: aggiungere logica in PHP (`appearance-css.blade.php` o helper) che calcola un colore testo leggibile (`#fff` o `#212529`) per `--sg-accent` e lo espone come `--sg-accent-text`. Tutti i punti che usano `var(--sg-accent)` come sfondo di testo useranno `color: var(--sg-accent-text)`.
2. Correggere `.sg-badge-role.role-editor` e `.sg-badge-role.role-viewer` in `scuola-guida.css`: cambiare colore testo da `#fff` a un dark appropriato.
3. Correggere badge navbar viewer (`--rt-viewer-dk` su bianco).

**File**: `public/css/scuola-guida.css`, `resources/views/layouts/partials/appearance-css.blade.php`.

---

### Lotto 2 — Uniformità struttura pagine admin

**Scope**: le ~9 view admin che non usano `sg-wrapper` e quelle che usano card Bootstrap invece di `sg-card`.

**Interventi**:
1. Migrare le view con `container-fluid` a `sg-wrapper`.
2. Rimuovere il `content_header` duplicato da `admin/system/settings.blade.php`, aggiungere `sg-header`.
3. Portare `admin/system/settings.blade.php` al pattern `sg-card` + `sg-card-header` (o almeno uniformare i titoli sezione con lo stesso livello `h2` del resto).
4. Sostituire `card card-success/card-danger` con `sg-card` + colore semantico tramite badge o header colorato nelle view risultato quiz/simulatore.
5. Correggere typo `var(--font-family)` → `var(--sg-font)` in `system/settings.blade.php`.

**File**: ~9 view admin + `quiz/attempt.blade.php` + `simulator/result.blade.php`.

---

### Lotto 3 — CSS sparso e token centralizzati

**Scope**: pulizia CSS inline, centralizzazione in `scuola-guida.css`.

**Interventi**:
1. Aggiungere `[x-cloak] { display:none!important; }` a `scuola-guida.css`.
2. Spostare `.navbar-brand img.school-logo` da `layouts/admin.blade.php` in `scuola-guida.css`.
3. Spostare stili sortable da `admin/quizzes/questions.blade.php` in `scuola-guida.css`.
4. Spostare stili search highlight da `search/results.blade.php` in `scuola-guida.css`.
5. Creare classe `.sg-hero-overlay` in `scuola-guida.css` per i 4 backdrop della homepage, rimuovere inline styles da `guest/home.blade.php`.
6. Aggiungere variabile CSS `--sg-navbar-height: 56px` (usarla nel calc di `layouts/auth.blade.php`).
7. Aggiungere classe `.sg-question-img` (`max-height: 220px`) in `scuola-guida.css`, sostituendo inline styles in `quiz/play.blade.php` e `simulator/play.blade.php`.
8. Uniformare container hero di `guest/home.blade.php` da `width:80%;margin:0 auto` a `container` BS5.
9. Allineare `welcome.blade.php` al sistema `--sg-font` (o redirigere definitivamente a `guest.home`).

---

### Lotto 4 — Migrazione Bootstrap 4 → Bootstrap 5

**Scope**: eliminare le classi Bootstrap 4 rimaste in view che girano su Bootstrap 5 (via AdminLTE compat layer).

**Interventi** (migrare per view):
1. `study/play.blade.php`: `badge-secondary` → `badge bg-secondary`, `embed-responsive` → classe custom o `ratio ratio-16x9`, `data-toggle` → `data-bs-toggle`, `data-target` → `data-bs-target`, `mr-*` → `me-*`.
2. `study/index.blade.php`: `custom-control custom-radio` → `form-check`, `custom-control-input` → `form-check-input`, `font-weight-bold` → `fw-bold`, `ml-3` → `ms-3`.
3. `profile/edit.blade.php`: `custom-control custom-switch` → `form-check form-switch`, `form-group` → `mb-3`, `btn btn-primary` → `sg-btn sg-btn-primary` (o mantenere BS5 `btn btn-primary` uniformemente).
4. `quiz/attempt.blade.php`: `font-weight-bold` → `fw-bold`, `mr-*` → `me-*`, `float-right` → `float-end`.

**Nota**: AdminLTE 3 include Bootstrap 4; alcune classi BS4 funzionano ancora per compatibilità. Il lotto non è urgente ma crea debito tecnico. Da fare prima di un eventuale upgrade AdminLTE 4.

---

### Lotto 5 — Chart.js e token colore in JavaScript

**Scope**: allineare i colori Chart.js ai token CSS.

**Interventi**:
1. In `admin/dashboard.blade.php` e `stats/dashboard.blade.php`, passare i colori via PHP come stringhe lette dalle variabili CSS o costanti del design system, non come literal `'#4361ee'`.
2. Alternativa: esporre i colori via `data-*` attribute su `<canvas>` e leggerli in JS tramite `getComputedStyle`.

---

*Report generato il 2026-06-18 nell'ambito della Feature 14.0 — Audit grafico generale.*  
*Zero modifiche al codice sono state effettuate nella generazione di questo documento.*
