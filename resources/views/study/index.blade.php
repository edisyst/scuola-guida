@extends('layouts.admin')

@section('title', 'Modalità Studio')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <p class="sg-header-subtitle">{{ __('viewer.study.subtitle') }}</p>
        <h1 class="sg-header-title"><i class="fas fa-graduation-cap mr-2"></i> {{ __('viewer.study.title') }}</h1>
    </div>

    @if($hasSession)
        {{-- Su mobile alert + azioni vanno in colonna (flex-wrap) --}}
        <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-md-center sg-gap-2 sg-mb-3">
            <div>
                <i class="fas fa-info-circle"></i>
                {{ __('viewer.study.session_in_progress') }}
            </div>
            <div class="d-flex flex-wrap sg-gap-2">
                <a href="{{ route('study.play') }}" class="sg-btn sg-btn-primary sg-btn-sm">
                    <i class="fas fa-play"></i> {{ __('viewer.study.resume') }}
                </a>
                <form action="{{ route('study.destroy') }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="sg-btn sg-btn-outline sg-btn-sm"
                            onclick="return confirm('{{ __('viewer.study.end_session_confirm') }}');">
                        <i class="fas fa-times"></i> {{ __('viewer.study.end') }}
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="sg-card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-body p-4">
            <h5 class="mb-3">{{ __('viewer.study.choose_source') }}</h5>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('study.start') }}" method="POST">
                @csrf

                {{-- ── Quiz specifico ───────────────────────────────── --}}
                <div class="form-group">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="source-quiz" name="source" value="quiz"
                               class="custom-control-input"
                               {{ old('source') === 'quiz' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="source-quiz">
                            {{ __('viewer.study.from_quiz') }}
                        </label>
                    </div>
                    <div class="mt-2 ml-3">
                        <select name="quiz_id" class="form-control"
                                onfocus="document.getElementById('source-quiz').checked = true;">
                            <option value="">{{ __('viewer.study.select_quiz') }}</option>
                            @foreach($quizzes as $quiz)
                                <option value="{{ $quiz->id }}"
                                    {{ (int) old('quiz_id') === $quiz->id ? 'selected' : '' }}>
                                    {{ $quiz->title }} ({{ $quiz->questions_count }} domande)
                                </option>
                            @endforeach
                        </select>
                        @if($quizzes->isEmpty())
                            <small class="text-muted">{{ __('viewer.study.no_quizzes') }}</small>
                        @endif
                    </div>
                </div>

                <hr>

                {{-- ── Categoria ────────────────────────────────────── --}}
                <div class="form-group">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="source-category" name="source" value="category"
                               class="custom-control-input"
                               {{ old('source') === 'category' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="source-category">
                            {{ __('viewer.study.from_category') }}
                        </label>
                    </div>
                    <div class="mt-2 ml-3">
                        <select name="category_id" class="form-control"
                                onfocus="document.getElementById('source-category').checked = true;">
                            <option value="">{{ __('viewer.study.select_category') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ (int) old('category_id') === $category->id ? 'selected' : '' }}>
                                    {{ $category->getLocalizedName() }} ({{ $category->questions_count }} {{ __('viewer.study.questions_unit') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                {{-- ── Random ───────────────────────────────────────── --}}
                <div class="form-group">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="source-random" name="source" value="random"
                               class="custom-control-input"
                               {{ old('source', 'random') === 'random' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="source-random">
                            {{ __('viewer.study.random') }}
                        </label>
                    </div>
                    <small class="text-muted ml-3">
                        {{ __('viewer.study.random_limit_text', ['limit' => \App\Services\StudyService::RANDOM_LIMIT]) }}
                    </small>
                </div>

                <div class="d-grid d-sm-flex justify-content-sm-end mt-4">
                    <button type="submit" class="sg-btn sg-btn-primary">
                        <i class="fas fa-play"></i> {{ __('viewer.study.start') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
