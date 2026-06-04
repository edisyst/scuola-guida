@extends('layouts.admin')

@section('title', 'Riepilogo studio')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper" style="max-width: 800px; margin: 0 auto;">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('viewer.study.session_ended') }}</p>
        <h1 class="sg-header-title"><i class="fas fa-flag-checkered mr-2"></i> {{ __('viewer.study.summary_title') }}</h1>
    </div>

    {{-- mb-3 sulle colonne: su mobile (col-12) le info-box si impilano e si toccano senza margine --}}
    <div class="row sg-mb-3">
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('viewer.study.total_questions') }}</span>
                    <span class="info-box-number">{{ $summary['total'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-pen"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('viewer.study.answers_given') }}</span>
                    <span class="info-box-number">{{ $summary['answered'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-bookmark"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('viewer.study.to_review') }}</span>
                    <span class="info-box-number">{{ $summary['flagged_count'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="sg-card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('viewer.study.marked_for_review') }}</h5>
        </div>
        <div class="card-body">
            @if($summary['flagged']->isEmpty())
                <p class="text-muted mb-0">{{ __('viewer.study.no_marked') }}</p>
            @else
                <ol class="mb-3">
                    @foreach($summary['flagged'] as $q)
                        <li class="mb-2">
                            {{ \Illuminate\Support\Str::limit($q->question, 140) }}
                            @if($q->category)
                                <span class="badge badge-secondary ml-1">{{ $q->category->getLocalizedName() }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <form action="{{ route('study.start') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="source" value="flagged">
                    <button class="sg-btn sg-btn-primary">
                        <i class="fas fa-redo"></i> {{ __('viewer.study.review_marked') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between sg-gap-2 sg-mt-3">
        <form action="{{ route('study.destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <button class="sg-btn sg-btn-outline">
                <i class="fas fa-times"></i> {{ __('viewer.study.close_session') }}
            </button>
        </form>

        <a href="{{ route('study.index') }}" class="sg-btn sg-btn-dark">
            <i class="fas fa-graduation-cap"></i> {{ __('viewer.study.new_session') }}
        </a>
    </div>
</div>
@endsection
