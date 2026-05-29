# UI Patterns — ScuolaGUIDA

Riferimento per chi modifica le view. Descrive le convenzioni grafiche adottate
nel tema AdminLTE 3 e i pattern Livewire in uso nel progetto.

---

## Indice

1. [Design system e variabili CSS](#1-design-system-e-variabili-css)
2. [Layout di pagina](#2-layout-di-pagina)
3. [Componenti di contenuto](#3-componenti-di-contenuto)
4. [Bottoni](#4-bottoni)
5. [Form](#5-form)
6. [Tabelle](#6-tabelle)
7. [Badge e label](#7-badge-e-label)
8. [Pattern Livewire](#8-pattern-livewire)
9. [Sidebar e tema ruoli](#9-sidebar-e-tema-ruoli)
10. [Dark mode](#10-dark-mode)
11. [Cosa NON fare](#11-cosa-non-fare)

---

## 1. Design system e variabili CSS

Il file `public/css/scuola-guida.css` definisce tutte le variabili CSS del
progetto sotto il selettore `:root`. Usarle sempre al posto di valori hardcoded.

### Palette colori

| Variabile            | Valore      | Uso principale                    |
|----------------------|-------------|-----------------------------------|
| `--sg-primary`       | `#4361ee`   | Accento blu (link, focus ring)    |
| `--sg-dark-1`        | `#1a1a2e`   | Sfondo scuro, btn-primary         |
| `--sg-dark-2`        | `#16213e`   | Sfondo scuro secondario           |
| `--sg-success`       | `#28a745`   | Azioni positive, risposte giuste  |
| `--sg-danger`        | `#dc3545`   | Errori, eliminazione              |
| `--sg-warning`       | `#ffc107`   | Attenzione                        |
| `--sg-info`          | `#17a2b8`   | Informazioni neutrali             |
| `--sg-text`          | `#212529`   | Testo principale                  |
| `--sg-text-muted`    | `#6c757d`   | Testo secondario                  |
| `--sg-text-soft`     | `#adb5bd`   | Label uppercase, etichette soft   |
| `--sg-border`        | `#e9ecef`   | Bordi standard                    |
| `--sg-border-light`  | `#f1f3f5`   | Divisori interni card             |
| `--sg-bg-soft`       | `#f8f9fb`   | Sfondo thead, sezioni grigio soft |

### Gradienti

| Variabile               | Uso                                      |
|-------------------------|------------------------------------------|
| `--sg-gradient-dark`    | Header di pagina, sfondo auth/home       |
| `--sg-gradient-success` | Progress bar, icone KPI verdi            |
| `--sg-gradient-blue`    | Icone KPI blu                            |
| `--sg-gradient-orange`  | Icone KPI arancio                        |
| `--sg-gradient-red`     | Icone KPI rosse                          |

### Raggi e ombre

| Variabile            | Valore  | Uso                            |
|----------------------|---------|--------------------------------|
| `--sg-radius`        | `14px`  | Card principali                |
| `--sg-radius-sm`     | `10px`  | Bottoni, form control, modali  |
| `--sg-radius-pill`   | `20px`  | Badge, pill                    |
| `--sg-shadow-card`   | —       | Card a riposo                  |
| `--sg-shadow-hover`  | —       | Card / bottoni al hover        |

---

## 2. Layout di pagina

### Struttura base

```blade
@extends('layouts.admin')

@section('title', 'Titolo pagina')
@section('content_header')@endsection   {{-- svuotato: usiamo sg-header --}}

@section('content')
<div class="sg-wrapper">
    ...contenuto...
</div>
@endsection
```

`@section('content_header')@endsection` va sempre svuotato: il titolo di pagina
viene gestito dall'`sg-header` dentro `sg-wrapper`, non dall'header di AdminLTE.

### Contenitore principale

```html
<div class="sg-wrapper">        <!-- max-width 1100px, centrato -->
<div class="sg-wrapper-sm">     <!-- max-width 720px, centrato — per form stretti -->
```

### Header di pagina (`sg-header`)

Sostituisce il classico `<h1>` libero. Sfondo gradiente scuro, testo bianco.

```blade
{{-- Solo titolo --}}
<div class="sg-header">
    <p class="sg-header-subtitle">Sottosezione</p>
    <h1 class="sg-header-title">Titolo pagina</h1>
</div>

{{-- Titolo + azioni a destra --}}
<div class="sg-header sg-flex-between">
    <div>
        <p class="sg-header-subtitle">Sottosezione</p>
        <h1 class="sg-header-title"><i class="fas fa-users mr-2"></i> Titolo</h1>
    </div>
    <div class="sg-header-actions">
        <a href="..." class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-plus"></i> Nuova
        </a>
    </div>
</div>

{{-- Versione compatta (es. all'interno di una card) --}}
<div class="sg-header compact"> ... </div>
```

### Griglia interna

Usare le classi Bootstrap 5 per il layout a colonne, con le classi di
spaziatura del design system per gap coerenti:

```html
<div class="row sg-grid-row">
    <div class="col-lg-3 col-6 sg-grid-col">...</div>
</div>
```

`sg-grid-row` / `sg-grid-col` danno padding e gap standard senza override manuali.

---

## 3. Componenti di contenuto

### Card (`sg-card`)

```html
<div class="sg-card">
    <div class="sg-card-header">
        <h2 class="sg-card-header-title">Titolo sezione</h2>
        <!-- eventuale azione in alto a destra -->
    </div>
    <div class="sg-card-body">...</div>
</div>

<!-- Variante: corpo diviso in sezioni con divisore -->
<div class="sg-card">
    <div class="sg-card-section">Sezione A</div>
    <div class="sg-card-section">Sezione B</div>   <!-- nessun bordo sull'ultima -->
</div>

<!-- Variante: card con accento rosso (es. zona pericolo) -->
<div class="sg-card sg-card-danger">...</div>

<!-- Corpo più stretto (es. filtri sopra tabelle) -->
<div class="sg-card-body-tight">...</div>
```

### KPI / Stat card (`sg-stat-card`)

Usate nella dashboard e nelle pagine di riepilogo:

```html
<div class="sg-stat-card">
    <div class="sg-stat-icon grad-blue">
        <i class="fas fa-users"></i>
    </div>
    <div>
        <div class="sg-stat-value">{{ $count }}</div>
        <div class="sg-stat-label">Utenti</div>
    </div>
</div>
```

Varianti icona: `grad-blue`, `grad-green`, `grad-orange`, `grad-red`, `grad-dark`.

Se la stat-card è cliccabile, wrappare in `<a href="..." class="sg-stat-card">`.

### Form admin a sezioni (`sg-form-section`)

Per i form di creazione/modifica con più blocchi logici (es. utenti, domande):

```html
<div class="sg-form-section">
    <div class="sg-form-section-header">
        <h2 class="sg-form-section-title">
            <i class="fas fa-id-card"></i> Dati anagrafici
        </h2>
        <!-- eventuale hint a destra -->
        <span class="sg-form-section-hint">Testo informativo</span>
    </div>
    <div class="sg-form-section-body">
        ...campi form...
    </div>
</div>
```

### Save bar sticky

Per i form lunghi con salvataggio in fondo alla pagina:

```html
<div class="sg-save-bar">
    <span class="hint">Testo hint opzionale</span>
    <div class="d-flex" style="gap:8px;">
        <a href="..." class="sg-btn sg-btn-secondary">Annulla</a>
        <button type="submit" class="sg-btn sg-btn-primary">Salva</button>
    </div>
</div>
```

### Modal

```html
<div class="modal fade" id="myModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="sg-modal-content modal-content">
            <div class="modal-header sg-modal-header-dark">
                <h5 class="modal-title">Titolo</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">...</div>
            <div class="modal-footer">...</div>
        </div>
    </div>
</div>
```

### Empty state

Ogni lista/tabella vuota deve avere un empty state esplicito:

```html
<div class="text-center py-5 text-muted">
    <i class="fas fa-inbox fa-3x mb-3"></i>
    <p>Nessun elemento trovato.</p>
    <a href="..." class="sg-btn sg-btn-primary sg-btn-sm">Crea il primo</a>
</div>
```

---

## 4. Bottoni

Usare sempre le classi `sg-btn` — mai i `btn btn-*` di Bootstrap/AdminLTE nudi
all'interno di `content-wrapper` (sono stati sovrascritti parzialmente).

```html
<!-- Varianti principali -->
<button class="sg-btn sg-btn-primary">Salva</button>
<button class="sg-btn sg-btn-success">Conferma</button>
<button class="sg-btn sg-btn-danger">Elimina</button>
<button class="sg-btn sg-btn-outline">Annulla</button>
<button class="sg-btn sg-btn-light">Azione neutra</button>
<button class="sg-btn sg-btn-secondary">Secondario</button>

<!-- Dimensione ridotta (header, barre azioni) -->
<button class="sg-btn sg-btn-primary sg-btn-sm">Piccolo</button>

<!-- Full width -->
<button class="sg-btn sg-btn-primary sg-btn-block">Pieno</button>
```

### Bottoni azione nelle tabelle (`sg-btn-icon`)

Per le colonne azioni nelle tabelle — icone senza testo:

```html
<td class="sg-actions-cell">
    <a href="..." class="sg-btn-icon edit" title="Modifica">
        <i class="fas fa-edit"></i>
    </a>
    <button class="sg-btn-icon delete" title="Elimina">
        <i class="fas fa-trash"></i>
    </button>
    <a href="..." class="sg-btn-icon info" title="Dettaglio">
        <i class="fas fa-eye"></i>
    </a>
</td>
```

Varianti: `edit` (giallo), `delete` (rosso), `info` (azzurro).

---

## 5. Form

### Struttura campo

```html
<div class="sg-form-group">
    <label class="sg-form-label">
        Nome campo
        <span class="sg-text-muted sg-form-label-note">(opzionale)</span>
    </label>
    <input class="sg-form-control @error('campo') is-invalid @enderror"
           name="campo" value="{{ old('campo') }}">
    @error('campo')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror
    <div class="sg-form-hint">Testo di aiuto descrittivo.</div>
</div>
```

### Validazione nei componenti Livewire

Usare `#[Validate]` direttamente sulle property — mai il metodo `rules()`:

```php
use Livewire\Attributes\Validate;

#[Validate('required|string|max:255')]
public string $name = '';
```

Mostrare l'errore in view con `@error('name') ... @enderror` come sopra.

---

## 6. Tabelle

### Tabella custom (`sg-table`)

```html
<div class="table-responsive">
    <table class="sg-table">
        <thead>
            <tr>
                <th>Colonna A</th>
                <th>Colonna B</th>
                <th class="sg-actions-cell">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>{{ $item->field }}</td>
                <td>{{ $item->other }}</td>
                <td class="sg-actions-cell">
                    @include('admin.xxx.partials.actions', ['item' => $item])
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="sg-table-empty">
                    <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                    Nessun elemento.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
```

### Tabelle server-side con DataTables

Per tabelle grandi (domande, utenti): usare DataTables via endpoint JSON.
Il CSS e JS di DataTables è caricato direttamente in `layouts/admin.blade.php`
(`datatables.net 1.13.6`) — non usare la versione nel config plugin AdminLTE
(è disabilitata e punta a una versione più vecchia).

---

## 7. Badge e label

### Badge di stato (`sg-badge`)

```html
<span class="sg-badge">Neutro</span>
<span class="sg-badge sg-badge-success">Attivo</span>
<span class="sg-badge sg-badge-danger">Errore</span>
<span class="sg-badge sg-badge-warning">Attenzione</span>
<span class="sg-badge sg-badge-info">Info</span>
```

### Badge ruolo (`sg-badge-role`)

```html
<span class="sg-badge-role role-admin">Admin</span>
<span class="sg-badge-role role-editor">Editor</span>
<span class="sg-badge-role role-viewer">Viewer</span>
```

Colori ruolo: Admin = viola `#6f42c1`, Editor = arancio `#fd7e14`,
Viewer = verde `#20c997`.

### Label sezione (`sg-label`)

Etichetta uppercase sopra a un gruppo di elementi (non un campo form):

```html
<span class="sg-label"><i class="fas fa-filter"></i> Filtri attivi</span>
```

---

## 8. Pattern Livewire

### Bottone con stato di caricamento

Regola obbligatoria: `wire:loading` su **tutti** i bottoni che triggerano azioni.

```blade
<button wire:click="salva" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="salva">
        <i class="fas fa-save mr-1"></i> Salva
    </span>
    <span wire:loading wire:target="salva">
        <i class="fas fa-spinner fa-spin mr-1"></i> Salvataggio...
    </span>
</button>
```

### Azioni distruttive

```blade
<button wire:click="elimina"
        wire:confirm="Sei sicuro di voler eliminare questo elemento?"
        wire:loading.attr="disabled"
        class="sg-btn sg-btn-danger sg-btn-sm">
    <i class="fas fa-trash mr-1"></i> Elimina
</button>
```

### Input binding

Usare sempre `wire:model.blur` — mai `.defer` né `.live`:

```blade
<input wire:model.blur="search" class="sg-form-control" type="text">
```

### Comunicazione tra componenti

```php
// Emettere
$this->dispatch('nome-evento', payload: $data);

// Ascoltare (nel componente destinatario)
#[On('nome-evento')]
public function handleEvento($payload): void { ... }
```

### Polling limitato

I componenti con polling devono usare `->limit()` per non caricare tabelle intere:

```php
// Corretto
public function loadNotifications(): void
{
    $this->notifications = auth()->user()
        ->notifications()
        ->latest()
        ->limit(10)
        ->get();
}
```

### Componente Livewire full-page

```blade
{{-- route --}}
Route::get('/my-page', MyComponent::class)->name('my-page');

{{-- layout nel componente --}}
public function render(): View
{
    return view('livewire.my-component')
        ->layout('layouts.admin', ['title' => 'Titolo']);
}
```

---

## 9. Sidebar e tema ruoli

Il colore dello sfondo della sidebar cambia in base al ruolo dell'utente.
La logica è in `resources/views/vendor/adminlte/partials/sidebar/left-sidebar.blade.php`
(override pubblicato del vendor — non toccare il file originale nel vendor).

| Ruolo  | Classe AdminLTE          | Colore  |
|--------|--------------------------|---------|
| Admin  | `sidebar-dark-primary`   | Blu     |
| Editor | `sidebar-dark-danger`    | Rosso   |
| Viewer | `sidebar-dark-primary`   | Blu     |

Per cambiare il colore di un ruolo modificare solo quel file, usando
esclusivamente le classi AdminLTE 3 built-in (`sidebar-dark-*`). Zero CSS custom.

### Configurazione AdminLTE (`config/adminlte.php`)

Scelte adottate rilevanti:

| Chiave                  | Valore                          | Note                                    |
|-------------------------|---------------------------------|-----------------------------------------|
| `classes_sidebar`       | `sidebar-dark-primary elevation-4` | Default; sovrascritto per ruolo        |
| `classes_topnav`        | `navbar-white navbar-light`     | Navbar chiara                           |
| `classes_auth_card`     | `card-outline card-primary`     | Login/register con bordo primario       |
| `classes_auth_btn`      | `btn-flat btn-primary`          | Bottone submit auth                     |
| `sidebar_mini`          | `'lg'`                          | Sidebar collassa in icone da `lg`       |
| `sidebar_nav_accordion` | `true`                          | Un solo menu aperto alla volta          |
| `preloader`             | abilitato, `animation__shake`   | Logo animato al caricamento             |
| `right_sidebar`         | `false`                         | Sidebar destra disabilitata             |
| `livewire`              | `false`                         | Assets Livewire gestiti manualmente     |

---

## 10. Dark mode

AdminLTE aggiunge la classe `dark-mode` al `<body>` quando l'utente attiva
la modalità scura (toggle in navbar).

Tutti i componenti del design system (`sg-card`, `sg-form-control`, `sg-table`, ecc.)
hanno già le regole `body.dark-mode .sg-*` in `scuola-guida.css`.

**Regola:** ogni nuovo componente custom deve avere la sua regola dark mode
nel CSS nello stesso file, nella sezione `DARK MODE` dedicata.

---

## 11. Cosa NON fare

| ❌ Sbagliato | ✓ Corretto |
|---|---|
| `<style>` inline ovunque | Solo classi CSS esistenti |
| `<script>` nel body | `@push('scripts')` |
| `btn btn-primary` di Bootstrap nudo | `sg-btn sg-btn-primary` |
| `wire:model` (live) | `wire:model.blur` |
| `wire:model.defer` | `wire:model.blur` |
| Metodo `rules()` in Livewire | Attributo `#[Validate]` sulla property |
| Bottone Livewire senza `wire:loading` | Sempre `wire:loading` + `wire:loading.attr="disabled"` |
| `$user->hasRole('admin')` (Spatie) | `$user->isAdmin()` |
| Query dentro loop | Pre-caricare con `pluck()` prima del loop |
| Titolo pagina via `@section('content_header')` | `sg-header` dentro `sg-wrapper` |
