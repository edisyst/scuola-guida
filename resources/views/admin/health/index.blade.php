@extends('adminlte::page')

@section('title', 'Stato sistema')

@section('content_header')
    <h1><i class="fas fa-heartbeat mr-2"></i>Stato sistema</h1>
@endsection

@section('content')
<div class="container-fluid">

    {{-- ── SMALL BOXES: metriche principali ─────────────────────────────── --}}
    <div class="row">

        {{-- Ultimo backup --}}
        @php
            $backupAt  = $backupStatus['last_backup_at'] ?? null;
            $healthy   = $backupStatus['is_healthy'] ?? false;
            $boxColor  = $healthy ? 'success' : 'danger';
            $backupAge = $backupAt ? $backupAt->diffForHumans() : 'Mai eseguito';
        @endphp
        <div class="col-lg-3 col-6">
            <div class="small-box bg-{{ $boxColor }}">
                <div class="inner">
                    <h3>{{ $backupAt ? $backupAt->format('d/m H:i') : '—' }}</h3>
                    <p>Ultimo backup ({{ $backupAge }})</p>
                </div>
                <div class="icon"><i class="fas fa-database"></i></div>
                <a href="{{ route('admin.health.index') }}" class="small-box-footer">
                    {{ $backupStatus['count'] ?? 0 }} backup disponibili
                    <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- Dimensione DB --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ \App\Services\HealthService::formatBytes($dbSize['total_bytes'] ?? 0) }}</h3>
                    <p>Dimensione database</p>
                </div>
                <div class="icon"><i class="fas fa-server"></i></div>
                <a href="#card-db" class="small-box-footer">
                    Vedi dettaglio <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- Media storage --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ \App\Services\HealthService::formatBytes($storageSize['size_bytes'] ?? 0) }}</h3>
                    <p>Media storage ({{ number_format($storageSize['file_count'] ?? 0) }} file)</p>
                </div>
                <div class="icon"><i class="fas fa-images"></i></div>
                <a href="{{ route('admin.media.index') }}" class="small-box-footer">
                    Vai a Media Manager <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        {{-- Spazio disco --}}
        @php
            $freePct  = $diskSpace['free_pct'] ?? 100;
            $diskColor = $freePct > 20 ? 'success' : ($freePct > 10 ? 'warning' : 'danger');
        @endphp
        <div class="col-lg-3 col-6">
            <div class="small-box bg-{{ $diskColor }}">
                <div class="inner">
                    <h3>{{ \App\Services\HealthService::formatBytes($diskSpace['free_bytes'] ?? 0) }}</h3>
                    <p>Spazio disco libero ({{ $diskSpace['free_pct'] ?? 0 }}%)</p>
                </div>
                <div class="icon"><i class="fas fa-hdd"></i></div>
                <a href="#card-disk" class="small-box-footer">
                    Totale: {{ \App\Services\HealthService::formatBytes($diskSpace['total_bytes'] ?? 0) }}
                    <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ── RIGA 2: Code + Backup disponibili ────────────────────────────── --}}
    <div class="row">

        {{-- Card: Stato code --}}
        <div class="col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i>Stato code</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            @if(!empty($queueStatus['pending_by_queue']))
                                @foreach($queueStatus['pending_by_queue'] as $queue => $count)
                                <tr>
                                    <td><code>{{ $queue }}</code></td>
                                    <td>
                                        <span class="badge badge-{{ $count > 0 ? 'warning' : 'success' }}">
                                            {{ $count }} pending
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">
                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                        Nessun job in coda
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if(($queueStatus['failed_count'] ?? 0) > 0)
                <div class="card-footer bg-danger text-white">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    <strong>{{ $queueStatus['failed_count'] }}</strong> job falliti in <code>failed_jobs</code>
                    <button class="btn btn-sm btn-outline-light float-right" type="button"
                            data-toggle="collapse" data-target="#failed-jobs-list">
                        Ispeziona
                    </button>
                </div>
                <div class="collapse" id="failed-jobs-list">
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr>
                                <th>Job</th><th>Queue</th><th>Fallito</th>
                            </tr></thead>
                            <tbody>
                                @foreach($queueStatus['recent_failed'] as $job)
                                <tr>
                                    <td><small>{{ class_basename($job['job_class']) }}</small></td>
                                    <td><code>{{ $job['queue'] }}</code></td>
                                    <td><small>{{ \Carbon\Carbon::parse($job['failed_at'])->diffForHumans() }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="card-footer bg-light text-muted">
                    <i class="fas fa-check-circle text-success mr-1"></i>
                    Nessun job fallito
                </div>
                @endif
            </div>
        </div>

        {{-- Card: Backup disponibili --}}
        <div class="col-md-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-archive mr-1"></i>Backup disponibili</h3>
                    <div class="card-tools">
                        <form method="POST" action="{{ route('admin.health.backup-now') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success"
                                    onclick="return confirm('Avviare un backup manuale ora?')">
                                <i class="fas fa-play mr-1"></i>Esegui ora
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(empty($backupStatus['files']))
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-database fa-3x mb-2"></i>
                            <p>Nessun backup disponibile</p>
                        </div>
                    @else
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr>
                                <th>Data</th><th>Dimensione</th>
                            </tr></thead>
                            <tbody>
                                @foreach($backupStatus['files'] as $file)
                                <tr>
                                    <td>
                                        <small>
                                            {{ \Carbon\Carbon::createFromTimestamp($file['last_modified'])->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ \App\Services\HealthService::formatBytes($file['size']) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if(($backupStatus['count'] ?? 0) > 10)
                            <div class="card-footer text-muted text-sm">
                                ... e altri {{ $backupStatus['count'] - 10 }} backup
                            </div>
                        @endif
                    @endif
                </div>
                <div class="card-footer text-muted">
                    Spazio totale backup:
                    <strong>{{ \App\Services\HealthService::formatBytes($backupStatus['total_size_bytes'] ?? 0) }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- ── RIGA 3: DB top tables + Disco + Errori recenti ─────────────────── --}}
    <div class="row">

        {{-- Card: Top 5 tabelle DB --}}
        <div class="col-md-4" id="card-db">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-table mr-1"></i>Top 5 tabelle per dimensione</h3>
                </div>
                <div class="card-body p-0">
                    @if(empty($dbSize['top_tables']))
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-exclamation-circle mr-1"></i>Dati non disponibili
                        </div>
                    @else
                        <table class="table table-sm mb-0">
                            <thead><tr>
                                <th>Tabella</th><th>Righe</th><th>Dim.</th>
                            </tr></thead>
                            <tbody>
                                @foreach($dbSize['top_tables'] as $table)
                                <tr>
                                    <td><code>{{ $table['name'] }}</code></td>
                                    <td><small>{{ number_format($table['rows']) }}</small></td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ \App\Services\HealthService::formatBytes($table['size_bytes']) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card: Spazio disco --}}
        <div class="col-md-4" id="card-disk">
            <div class="card card-outline card-{{ $diskColor }}">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hdd mr-1"></i>Spazio disco</h3>
                </div>
                <div class="card-body">
                    @php $usedPct = $diskSpace['used_pct'] ?? 0; @endphp
                    <div class="d-flex justify-content-between mb-1">
                        <span>Usato: {{ \App\Services\HealthService::formatBytes($diskSpace['used_bytes'] ?? 0) }}</span>
                        <span>{{ $usedPct }}%</span>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-{{ $diskColor }}"
                             style="width: {{ $usedPct }}%"
                             role="progressbar"
                             aria-valuenow="{{ $usedPct }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>Totale</th>
                            <td>{{ \App\Services\HealthService::formatBytes($diskSpace['total_bytes'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <th>Libero</th>
                            <td class="text-{{ $diskColor }}">
                                {{ \App\Services\HealthService::formatBytes($diskSpace['free_bytes'] ?? 0) }}
                                ({{ $diskSpace['free_pct'] ?? 0 }}%)
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Card: Ultimi errori --}}
        <div class="col-md-4">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bug mr-1"></i>Ultimi errori dal log</h3>
                </div>
                <div class="card-body p-0">
                    @if(empty($recentErrors))
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                            <p>Nessun errore recente</p>
                        </div>
                    @else
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm mb-0">
                                <thead><tr>
                                    <th>Quando</th><th>Livello</th><th>Messaggio</th>
                                </tr></thead>
                                <tbody>
                                    @foreach($recentErrors as $err)
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($err['timestamp'])->format('d/m H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $err['level'] === 'ERROR' ? 'danger' : 'dark' }}">
                                                {{ $err['level'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <small title="{{ $err['message'] }}">
                                                {{ Str::limit($err['message'], 60) }}
                                            </small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- /.row --}}

</div>{{-- /.container-fluid --}}
@endsection

@push('js')
<script>
    // Refresh automatico ogni 60 secondi (dati infrastrutturali cambiano lentamente)
    setTimeout(function () { location.reload(); }, 60000);
</script>
@endpush
