@extends('layouts.admin')

@section('page-title', __('study_content.title_index'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between mb-3">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-book mr-2"></i> {{ __('study_content.title_index') }}
            </h1>
            <p class="sg-header-subtitle sg-mt-1">Gestisci i contenuti di studio disponibili per gli iscritti.</p>
        </div>
        <div>
            <a href="{{ route('study-contents.create') }}" class="sg-btn sg-btn-primary">
                <i class="fas fa-plus mr-1"></i> {{ __('study_content.btn_new') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if($contents->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-book fa-3x text-muted mb-3 d-block"></i>
            <p class="text-muted mb-3">{{ __('study_content.empty') }}</p>
            <a href="{{ route('study-contents.create') }}" class="sg-btn sg-btn-primary">
                <i class="fas fa-plus mr-1"></i> {{ __('study_content.btn_new') }}
            </a>
        </div>
    @else
        <div class="sg-card">
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>{{ __('study_content.col_title') }}</th>
                            <th>{{ __('study_content.col_studyable') }}</th>
                            <th>{{ __('study_content.col_order') }}</th>
                            <th>{{ __('study_content.col_published') }}</th>
                            <th>{{ __('study_content.col_author') }}</th>
                            <th>{{ __('study_content.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contents as $content)
                            <tr>
                                <td>{{ $content->title }}</td>
                                <td>
                                    @if($content->studyable)
                                        <span class="sg-badge sg-badge-secondary">
                                            {{ class_basename($content->studyable_type) }}
                                        </span>
                                        {{ $content->studyable->name ?? $content->studyable->title ?? '—' }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $content->order }}</td>
                                <td>
                                    @if($content->is_published)
                                        <span class="badge badge-success">{{ __('study_content.published') }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ __('study_content.draft') }}</span>
                                    @endif
                                </td>
                                <td>{{ $content->creator?->name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('study-contents.edit', $content) }}"
                                       class="sg-btn sg-btn-info sg-btn-sm mr-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST"
                                          action="{{ route('study-contents.destroy', $content) }}"
                                          style="display:inline"
                                          onsubmit="return confirm('{{ __('study_content.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sg-btn sg-btn-danger sg-btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
