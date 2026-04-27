# CLAUDE.md — Linee Guida per Progetti Web Laravel

> Questo file definisce le convenzioni architetturali, stilistiche e di sviluppo per tutti i progetti web basati su **Laravel + Blade + Bootstrap + AdminLTE + Livewire**. Nessun frontend separato (no Vue SPA, no React, no Inertia). Le pagine sono servite direttamente da Blade.

---

## 1. Stack Tecnologico

| Layer | Tecnologia |
|---|---|
| Framework PHP | Laravel (ultima LTS) |
| Template engine | Blade |
| CSS Framework | Bootstrap 5 |
| Admin theme | AdminLTE 3 |
| Reattività UI | Livewire 3 |
| Database | MySQL / PostgreSQL |
| Asset build | Vite (con `laravel-vite-plugin`) |
| Auth scaffolding | Laravel Breeze (Blade) oppure manuale |

---

## 2. Struttura delle Directory

```
app/
  Http/
    Controllers/          # Solo controller classici (resource o invokable)
    Livewire/             # Classi PHP dei componenti Livewire
  Models/                 # Eloquent models
  Services/               # Business logic estratta dai controller
  Policies/               # Autorizzazione per ogni model

resources/
  views/
    layouts/
      app.blade.php        # Layout principale (AdminLTE shell)
      auth.blade.php       # Layout per le pagine di login/registrazione
      partials/
        sidebar.blade.php
        navbar.blade.php
        footer.blade.php
    pages/                 # Viste delle singole pagine (index, show, create, edit)
    components/            # Blade components riutilizzabili (x-alert, x-card, ecc.)
    livewire/              # Viste dei componenti Livewire

routes/
  web.php                  # Tutte le route web
  auth.php                 # Route di autenticazione

database/
  migrations/
  seeders/
  factories/
```

---

## 3. Convenzioni Laravel

### Controller
- Usare sempre **Resource Controller** quando si gestisce un CRUD: `php artisan make:controller FooController --resource`.
- I controller devono essere **snelli**: nessuna business logic al loro interno.
- La logica complessa va nei **Service** in `app/Services/`.
- Preferire **Form Request** per la validazione: `php artisan make:request StoreFooRequest`.

```php
// ✅ Corretto
public function store(StoreFooRequest $request, FooService $service)
{
    $service->create($request->validated());
    return redirect()->route('foo.index')->with('success', 'Creato con successo.');
}

// ❌ Sbagliato — logica nel controller
public function store(Request $request)
{
    $request->validate([...]);
    // 50 righe di logica...
}
```

### Model
- Definire sempre `$fillable` o `$guarded`.
- Usare **scope** per query riutilizzabili.
- Usare **accessor/mutator** (nuova sintassi `Attribute::make()`) per trasformazioni di dati.
- Relazioni sempre tipizzate con return type `HasMany`, `BelongsTo`, ecc.

### Route
- Usare `Route::resource()` e `Route::apiResource()` dove possibile.
- Raggruppare le route per middleware e prefisso:

```php
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('posts', PostController::class);
});
```

- Niente logica nelle route. Solo invocazione di controller o Livewire.

---

## 4. Blade — Template e Layout

### Layout principale (AdminLTE)
Il layout `layouts/app.blade.php` deve integrare correttamente AdminLTE:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    @include('layouts.partials.navbar')
    @include('layouts.partials.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <h1 class="m-0">@yield('page-title')</h1>
                @yield('breadcrumb')
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    @include('layouts.partials.footer')
</div>
@livewireScripts
@stack('scripts')
</body>
</html>
```

### Blade Components
Creare componenti riutilizzabili in `resources/views/components/`:

```bash
php artisan make:component Alert --view   # Solo view, senza classe PHP
php artisan make:component DataTable      # Con classe PHP
```

Esempi di componenti da standardizzare nel progetto:
- `<x-alert type="success" :message="$message" />`
- `<x-card title="Titolo" footer="..."> ... </x-card>`
- `<x-form-input name="email" label="Email" :value="old('email')" />`
- `<x-page-header title="Utenti" :breadcrumbs="[...]" />`

### Convenzioni Blade
- Usare `@section` / `@yield` per i blocchi del layout.
- Usare `@push` / `@stack` per CSS e JS specifici di pagina.
- Evitare logica PHP complessa nelle viste: usare **View Composer** o **Blade Component class** se servono dati.
- Usare `{{ }}` per output escaped e `{!! !!}` **solo** quando strettamente necessario (contenuto HTML fidato).

---

## 5. AdminLTE — Utilizzo e Personalizzazione

### Installazione
```bash
composer require jeroennoten/laravel-adminlte
php artisan adminlte:install
```

### Configurazione
Il file `config/adminlte.php` è la fonte di verità per:
- Voci del menu sidebar
- Logo e nome dell'applicazione
- Plugin jQuery/DataTables/Select2 abilitati

### Regole d'uso
- **Non sovrascrivere** i file vendor di AdminLTE. Usare `@push('styles')` e `@push('scripts')` per personalizzazioni locali.
- Gli override CSS globali vanno in `resources/css/app.css` (importato via Vite).
- Usare le classi Bootstrap 5 native — AdminLTE 3 è compatibile con BS5.
- Usare le **box AdminLTE** (`card`, `info-box`, `small-box`) per i widget della dashboard.
- I colori di stato seguono la palette AdminLTE: `primary`, `secondary`, `success`, `danger`, `warning`, `info`.

---

## 6. Livewire — Quando e Come Usarlo

### Quando usare Livewire
Livewire è riservato a interazioni UI **localizzate** che richiedono reattività senza ricaricare la pagina:

✅ **Usare Livewire per:**
- Tabelle con ricerca/filtro/ordinamento in tempo reale
- Form multi-step
- Modali di conferma con azioni (es. elimina con conferma)
- Counter, toggle, switch interattivi
- Upload file con anteprima
- Autocomplete / ricerca live
- Polling di dati (es. notifiche, stato job)

❌ **Non usare Livewire per:**
- Intere pagine che funzionano bene con un semplice redirect
- Logica che può essere gestita con un normale form POST
- Sostituire jQuery per click handler banali

### Struttura di un componente Livewire

```bash
php artisan make:livewire Users/UserSearch
```

Genera:
- `app/Http/Livewire/Users/UserSearch.php`
- `resources/views/livewire/users/user-search.blade.php`

```php
// app/Http/Livewire/Users/UserSearch.php
namespace App\Http\Livewire\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class UserSearch extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    protected $queryString = ['search', 'sortField', 'sortDirection'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.users.user-search', [
            'users' => User::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate(15),
        ]);
    }
}
```

### Convenzioni Livewire
- Un componente = una responsabilità singola.
- Usare `wire:model.live` con cautela (preferire `wire:model.lazy` o `wire:model.blur` per evitare troppe request).
- Usare `#[Validate]` attribute (Livewire 3) direttamente sulle property.
- Emettere eventi con `$this->dispatch('nome-evento')` per comunicare tra componenti.
- Usare `wire:loading` per feedback visivo durante le operazioni asincrone.
- Usare `wire:confirm` per conferme distruttive prima di azioni delete.

```blade
{{-- Esempio: bottone con loading state --}}
<button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
    <span wire:loading.remove>Salva</span>
    <span wire:loading><i class="fas fa-spinner fa-spin"></i> Salvataggio...</span>
</button>
```

---

## 7. JavaScript — Approccio

Niente framework JS pesanti. Lo stack JS è **minimalista**:

- **Alpine.js** (opzionale, consigliato) per comportamenti UI leggeri che non richiedono un round-trip server (toggle menu, dropdown, tabs locali).
- **jQuery** solo se già richiesto da plugin AdminLTE/DataTables/Select2.
- **Niente Vue, React, o altri framework SPA.**

```bash
npm install alpinejs
```

```js
// resources/js/app.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

---

## 8. Asset Management con Vite

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

- Tutti gli asset vengono importati tramite `@vite()` nel layout.
- AdminLTE e Bootstrap vengono installati via npm e importati in `app.css` / `app.js`.
- **Non usare** `mix()` o `asset()` per i file compilati da Vite.

---

## 9. Autenticazione e Autorizzazione

- Usare **Laravel Breeze** con il preset Blade per lo scaffolding iniziale.
- Definire una **Policy** per ogni Model: `php artisan make:policy FooPolicy --model=Foo`.
- Usare `$this->authorize()` nei controller o `@can` nelle viste.
- Raggruppare le route protette con il middleware `auth`.
- I ruoli vengono gestiti tramite **Spatie Laravel Permission** (`spatie/laravel-permission`).

---

## 10. Convenzioni di Naming

| Cosa | Convenzione | Esempio |
|---|---|---|
| Model | PascalCase, singolare | `User`, `BlogPost` |
| Controller | PascalCase + Controller | `UserController` |
| Livewire class | PascalCase, nella sottocartella | `Users/UserSearch` |
| Migration | snake_case, descrittiva | `create_blog_posts_table` |
| View (pagina) | snake_case | `users/index.blade.php` |
| View (component) | snake_case | `components/alert.blade.php` |
| Route name | snake_case con dot notation | `admin.users.index` |
| CSS class custom | kebab-case | `.user-avatar-lg` |

---

## 11. Gestione Errori e Flash Messages

Standardizzare i messaggi flash nel layout:

```blade
{{-- layouts/partials/flash.blade.php --}}
@foreach (['success', 'error', 'warning', 'info'] as $type)
    @if (session($type))
        <div class="alert alert-{{ $type }} alert-dismissible fade show" role="alert">
            {{ session($type) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach
```

Nei controller:
```php
return redirect()->route('users.index')->with('success', 'Utente creato con successo.');
return back()->with('error', 'Si è verificato un errore.')->withInput();
```

---

## 12. Testing

- Ogni feature deve avere almeno un **Feature Test** in `tests/Feature/`.
- Usare `RefreshDatabase` nei test che toccano il database.
- Testare le **Livewire component** con `Livewire::test()`.
- Usare **factories** per generare dati di test.

```php
// Esempio test Livewire
Livewire::test(UserSearch::class)
    ->set('search', 'Mario')
    ->assertSee('Mario Rossi')
    ->assertDontSee('Luigi Verdi');
```

---

## 13. Comandi Artisan Frequenti

```bash
# Crea lo scaffolding completo per una risorsa
php artisan make:model Foo -mfsc              # Model, Migration, Factory, Seeder, Controller

# Livewire
php artisan make:livewire NomeComponente

# AdminLTE
php artisan adminlte:install
php artisan adminlte:plugins install

# Cache (produzione)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 14. Checklist Prima di Ogni PR

- [ ] Nessuna logica di business nel controller (usa Service)
- [ ] Validazione tramite Form Request
- [ ] Policy definita e usata per le azioni protette
- [ ] Nessun `dd()`, `dump()`, `var_dump()` rimasto nel codice
- [ ] Flash messages impostati dopo ogni redirect
- [ ] `wire:loading` presente su tutti i bottoni Livewire
- [ ] Niente query N+1 (usare `with()` per eager loading)
- [ ] Migrations con `down()` implementato correttamente
- [ ] Almeno un Feature Test per la funzionalità introdotta
- [ ] Nessun asset inline (`<style>` o `<script>` inline nelle viste, tranne casi eccezionali via `@push`)
