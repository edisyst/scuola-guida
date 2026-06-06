@extends('layouts.admin')

@section('title', 'Import MIT')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Domande</p>
            <h1 class="sg-header-title"><i class="fas fa-file-import mr-2"></i> Import listato MIT</h1>
        </div>
        <a href="{{ route('admin.questions.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
            <i class="fas fa-arrow-left"></i> Indietro
        </a>
    </div>

    {{-- Errori righe saltate (mostrati dopo redirect con flash session) --}}
    @if(session('mit_import_errors'))
        <div class="sg-card sg-mb-3">
            <div class="sg-card-body" style="padding:0;">
                <div class="p-3 pb-2 font-weight-bold text-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Righe saltate durante l'import
                </div>
                <ul class="list-group list-group-flush" style="max-height:300px; overflow-y:auto;">
                    @foreach(session('mit_import_errors') as $error)
                        <li class="list-group-item list-group-item-warning small py-1">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Accordion configurazione colonne --}}
    <div class="sg-card sg-mb-3" x-data="{ open: false }">
        <div class="sg-card-body" style="padding:0.75rem 1.25rem;">
            <button type="button" class="sg-btn sg-btn-light sg-btn-sm w-100 text-left" @click="open = !open">
                <i class="fas fa-cog mr-1"></i> Configurazione attiva
                <i class="fas fa-chevron-down float-right mt-1" x-show="!open"></i>
                <i class="fas fa-chevron-up float-right mt-1" x-show="open" x-cloak></i>
            </button>

            <div x-show="open" x-transition style="margin-top:1rem;" x-cloak>
                <div class="row">
                    <div class="col-md-6 sg-mb-2">
                        <p class="small font-weight-bold mb-1">Mappatura colonne</p>
                        <table class="table table-sm table-bordered mb-0 small">
                            <thead class="thead-light">
                                <tr><th>Campo interno</th><th>Colonna Excel</th></tr>
                            </thead>
                            <tbody>
                                @foreach(config('mit_import.columns') as $field => $col)
                                    <tr>
                                        <td><code>{{ $field }}</code></td>
                                        <td>{{ $col }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6 sg-mb-2">
                        <p class="small font-weight-bold mb-1">Argomenti MIT → Categorie DB</p>
                        <div style="max-height:200px; overflow-y:auto;">
                            <table class="table table-sm table-bordered mb-0 small">
                                <thead class="thead-light">
                                    <tr><th>#</th><th>Categoria cercata</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($topicMap as $code => $name)
                                        <tr>
                                            <td>{{ $code }}</td>
                                            <td>{{ $name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <p class="small text-muted mb-0">
                    File di configurazione: <code>{{ $configPath }}</code>
                </p>
            </div>
        </div>
    </div>

    {{-- Form upload --}}
    <div class="sg-card">
        <div class="sg-card-body">
            <form method="POST" action="{{ route('admin.questions.mit-import.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="sg-mb-3">
                    <label class="form-label font-weight-bold">
                        Tipo di patente <span class="text-danger">*</span>
                    </label>
                    <select name="license_type_id" class="form-control @error('license_type_id') is-invalid @enderror" required>
                        <option value="">— Seleziona tipo di patente —</option>
                        @foreach($licenseTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ old('license_type_id', $defaultType?->id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }} ({{ $type->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('license_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="sg-mb-3">
                    <label class="form-label font-weight-bold">
                        File Excel listato MIT <span class="text-danger">*</span>
                    </label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                           accept=".xlsx,.xls,.csv" required>
                    <small class="form-text text-muted">
                        Formati: .xlsx, .xls, .csv — Max {{ config('mit_import.max_file_size_kb') / 1024 }} MB
                    </small>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="sg-mb-3">
                    <label class="form-label font-weight-bold">Filtra per argomento MIT (opzionale)</label>
                    <select name="topic_filter" class="form-control">
                        <option value="">Tutti gli argomenti ({{ count($topicMap) }})</option>
                        @foreach($topicMap as $code => $name)
                            <option value="{{ $code }}" {{ old('topic_filter') == $code ? 'selected' : '' }}>
                                {{ $code }} — {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('topic_filter')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="sg-mb-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="update_existing" value="1"
                               class="custom-control-input" id="updateExisting"
                               {{ old('update_existing') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="updateExisting">
                            Aggiorna domande esistenti (default: salta i duplicati)
                        </label>
                    </div>
                </div>

                <div class="sg-mb-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="dry_run" value="1"
                               class="custom-control-input" id="dryRun"
                               {{ old('dry_run') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="dryRun">
                            <strong>Dry run</strong> — analizza il file senza scrivere nel DB
                        </label>
                    </div>
                </div>

                <div class="d-flex align-items-center flex-wrap" style="gap:0.5rem;">
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-upload mr-1"></i> Avvia import
                    </button>
                    <a href="{{ route('admin.questions.index') }}" class="sg-btn sg-btn-light">
                        Annulla
                    </a>
                    <span class="text-muted small ml-auto">
                        Config: <code>config/mit_import.php</code>
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
