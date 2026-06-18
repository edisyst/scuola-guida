@extends('layouts.admin')

@section('title', $category->name)
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between mb-3">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-tag mr-2"></i> {{ $category->name }}
                @if($category->is_eu_directive)
                    <span class="badge badge-info ml-2">EU</span>
                @endif
            </h1>
        </div>
        <div>
            <a href="{{ route('admin.categories.index') }}" class="sg-btn sg-btn-secondary sg-btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> {{ __('common.back') }}
            </a>
            @if(auth()->user()->canEditCategory())
                <a href="{{ route('admin.categories.edit', $category) }}"
                   class="sg-btn sg-btn-info sg-btn-sm ml-1">
                    <i class="fas fa-edit mr-1"></i> {{ __('common.edit') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Lista domande --}}
    <div class="sg-card mb-4">
        <div class="sg-card-header">
            <h3 class="sg-card-title">{{ __('categories.col_questions') }} ({{ $category->questions_count }})</h3>
        </div>
        <div class="card-body p-0">
            @if($category->questions->isEmpty())
                <div class="text-center py-4">
                    <i class="fas fa-question-circle fa-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted">{{ __('categories.no_questions') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('questions.col_text') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->questions->take(20) as $question)
                                <tr>
                                    <td class="sg-text-muted">{{ $question->id }}</td>
                                    <td>{{ Str::limit($question->text, 80) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Contenuti formativi ADAS/EU (solo se categoria EU) --}}
    @if($category->is_eu_directive)
        <div class="sg-card mb-4">
            <div class="sg-card-header d-flex justify-content-between align-items-center">
                <h3 class="sg-card-title">{{ __('study_content.section_title') }}</h3>
                @if(auth()->user()->canEditStudyContent())
                    <a href="{{ route('study-contents.create') }}?studyable_type={{ urlencode(\App\Models\Category::class) }}&studyable_id={{ $category->id }}"
                       class="sg-btn sg-btn-primary sg-btn-sm">
                        <i class="fas fa-plus mr-1"></i> {{ __('study_content.btn_new') }}
                    </a>
                @endif
            </div>
            <div class="card-body">
                <livewire:study-content-viewer
                    :studyable-type="\App\Models\Category::class"
                    :studyable-id="$category->id" />
            </div>
        </div>
    @endif

</div>
@endsection
