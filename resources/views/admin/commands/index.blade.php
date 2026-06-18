@extends('layouts.admin')

@section('title', 'Comandi utili')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header">
        <p class="sg-header-subtitle">Strumenti amministrativi</p>
        <h1 class="sg-header-title"><i class="fas fa-terminal mr-2"></i> Comandi utili</h1>
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-body">
            <p class="sg-text-muted mb-0">
                Esegue da web una selezione di comandi <code>php artisan</code>. I comandi long-running
                (come <code>queue:work</code>) sono lanciati con <code>--stop-when-empty</code>: processano
                i job in coda e terminano. La pagina attende il termine dell'esecuzione e mostra l'output qui sotto.
            </p>
        </div>
    </div>

    @if($result)
        <div class="sg-card sg-mb-3">
            <div class="sg-card-header sg-flex-between">
                <h2 class="sg-card-header-title">
                    <i class="fas {{ $result['ok'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} mr-2"></i>
                    Output: {{ $result['label'] }}
                </h2>
                <span class="sg-badge {{ $result['ok'] ? 'sg-badge-success' : 'sg-badge-danger' }}">
                    exit {{ $result['exit_code'] ?? '—' }} · {{ $result['duration_ms'] }} ms
                </span>
            </div>
            <div class="sg-card-body">
                <div class="sg-text-muted mb-2">
                    <code>{{ $result['command_str'] }}</code>
                    <span class="ml-2">— {{ $result['ran_at'] }}</span>
                </div>
                <pre class="sg-pre" style="max-height:420px; overflow:auto; word-break:break-all; white-space:pre-wrap;">{{ $result['output'] }}</pre>
            </div>
        </div>
    @endif

    @foreach($grouped as $groupName => $commands)
        <div class="sg-card sg-mb-3">
            <div class="sg-card-header">
                <h2 class="sg-card-header-title">{{ $groupName }}</h2>
            </div>
            <div class="sg-card-body">
                <div class="row">
                    @foreach($commands as $cmd)
                        <div class="col-12 col-md-6 col-lg-4 mb-3">
                            <div class="sg-cmd-tile h-100 p-3" style="border:1px solid #e9ecef; border-radius:10px; display:flex; flex-direction:column; gap:10px;">
                                <div>
                                    <h3 style="font-size:1rem; margin:0 0 4px;">
                                        <i class="{{ $cmd['icon'] ?? 'fas fa-cog' }} mr-2"></i>{{ $cmd['label'] }}
                                    </h3>
                                    <p class="sg-text-muted" style="margin:0; font-size:.85rem; white-space:pre-line;">{{ $cmd['description'] }}</p>
                                </div>
                                <form method="POST"
                                      action="{{ route('admin.commands.run', $cmd['slug']) }}"
                                      style="margin-top:auto;"
                                      @if(!empty($cmd['danger']))
                                          onsubmit="return confirm('Sei sicuro? Questa operazione è distruttiva.');"
                                      @endif>
                                    @csrf
                                    @if(!empty($cmd['inputs']))
                                        @foreach($cmd['inputs'] as $inputName => $inputSpec)
                                            <div class="form-group mb-2">
                                                <label for="{{ $cmd['slug'] }}_{{ $inputName }}" class="sg-text-muted" style="font-size:.8rem; margin-bottom:4px;">
                                                    {{ $inputSpec['label'] }}@if(!empty($inputSpec['required'])) <span class="text-danger">*</span>@endif
                                                </label>
                                                <input
                                                    type="{{ $inputSpec['type'] ?? 'text' }}"
                                                    id="{{ $cmd['slug'] }}_{{ $inputName }}"
                                                    name="{{ $inputName }}"
                                                    class="form-control form-control-sm @error($inputName) is-invalid @enderror"
                                                    @if(isset($inputSpec['min'])) min="{{ $inputSpec['min'] }}" @endif
                                                    @if(!empty($inputSpec['placeholder'])) placeholder="{{ $inputSpec['placeholder'] }}" @endif
                                                    @if(!empty($inputSpec['required'])) required @endif>
                                                @error($inputName)
                                                    <small class="text-danger d-block">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        @endforeach
                                    @endif
                                    <button type="submit"
                                            class="sg-btn sg-btn-sm sg-btn-block {{ !empty($cmd['danger']) ? 'sg-btn-danger' : 'sg-btn-primary' }}">
                                        <i class="fas fa-play mr-1"></i> Esegui
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

</div>
@endsection
