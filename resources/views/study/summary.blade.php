@extends('layouts.admin')

@section('title', 'Riepilogo studio')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper" style="max-width: 800px; margin: 0 auto;">

    <div class="sg-header">
        <p class="sg-header-subtitle">Sessione conclusa</p>
        <h1 class="sg-header-title"><i class="fas fa-flag-checkered mr-2"></i> Riepilogo studio</h1>
    </div>

    <div class="row sg-mb-3">
        <div class="col-md-4">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Domande totali</span>
                    <span class="info-box-number">{{ $summary['total'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-pen"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Risposte date</span>
                    <span class="info-box-number">{{ $summary['answered'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-bookmark"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Da ripassare</span>
                    <span class="info-box-number">{{ $summary['flagged_count'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="sg-card">
        <div class="card-header">
            <h5 class="mb-0">Domande marcate da ripassare</h5>
        </div>
        <div class="card-body">
            @if($summary['flagged']->isEmpty())
                <p class="text-muted mb-0">Non hai marcato nessuna domanda per il ripasso.</p>
            @else
                <ol class="mb-3">
                    @foreach($summary['flagged'] as $q)
                        <li class="mb-2">
                            {{ \Illuminate\Support\Str::limit($q->question, 140) }}
                            @if($q->category)
                                <span class="badge badge-secondary ml-1">{{ $q->category->name }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <form action="{{ route('study.start') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="source" value="flagged">
                    <button class="sg-btn sg-btn-primary">
                        <i class="fas fa-redo"></i> Ripassa le domande marcate
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-between sg-mt-3">
        <form action="{{ route('study.destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <button class="sg-btn sg-btn-outline">
                <i class="fas fa-times"></i> Chiudi sessione
            </button>
        </form>

        <a href="{{ route('study.index') }}" class="sg-btn sg-btn-dark">
            <i class="fas fa-graduation-cap"></i> Nuova sessione
        </a>
    </div>
</div>
@endsection
