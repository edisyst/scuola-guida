@if ($errors->any())
    <div class="alert alert-danger" style="border-radius:var(--sg-radius-sm);border:none;">
        <strong>Ci sono errori nel form:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="sg-form-group">
    <label class="sg-form-label">Titolo</label>
    <input name="title"
           class="sg-form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $quiz->title ?? '') }}">
    @error('title')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror
</div>

@if(auth()->user()->isAdmin())
<div class="sg-form-group">
    <label class="sg-form-label">Stato iniziale</label>
    <select name="status" class="sg-form-control">
        @foreach([\App\Models\Quiz::STATUS_DRAFT => 'Bozza', \App\Models\Quiz::STATUS_PUBLISHED => 'Pubblicato'] as $value => $label)
            <option value="{{ $value }}"
                @selected(old('status', $quiz->status ?? \App\Models\Quiz::STATUS_DRAFT) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <small class="sg-form-hint">La conferma può essere applicata solo dopo la creazione, dalla lista.</small>
</div>
@endif

<div class="sg-form-group">
    <label class="sg-form-label">Domande</label>

    <select name="questions[]"
            class="sg-form-control @error('questions') is-invalid @enderror"
            multiple size="20">
        @foreach($questions as $q)
            <option value="{{ $q->id }}"
                    @if(isset($quiz) && $quiz->questions->contains($q->id)) selected @endif>
                {{ Str::limit($q->question, 60) }}
            </option>
        @endforeach
    </select>

    @error('questions')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror

    <small class="sg-form-hint">Tieni premuto CTRL per selezione multipla</small>
</div>

<div class="sg-form-group">
    <label class="sg-form-label">Max domande</label>

    <input type="number"
           id="max_questions"
           name="max_questions"
           class="sg-form-control @error('max_questions') is-invalid @enderror"
           value="{{ old('max_questions', $quiz->max_questions ?? 30) }}">

    @error('max_questions')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror

    <small class="sg-form-hint">
        Domande attuali: {{ $questionsCount ?? 0 }}
    </small>

    <div id="max-warning" class="sg-form-error" style="display:none;"></div>
</div>


@section('js')
    @parent
    <script>
        $(document).ready(function () {
            const currentCount = {{ $questionsCount ?? 0 }};
            const input = $('#max_questions');
            const warning = $('#max-warning');

            function validateMax() {
                const value = parseInt(input.val());

                if (value < currentCount) {
                    warning
                        .text(`⚠ Il limite non può essere inferiore a ${currentCount}`)
                        .show();

                    input.addClass('is-invalid');

                } else {
                    warning.hide();
                    input.removeClass('is-invalid');
                }
            }

            input.on('input', validateMax);
            validateMax();

            $('form').on('submit', function (e) {
                const value = parseInt($('#max_questions').val());

                if (value < {{ $questionsCount ?? 0 }}) {
                    e.preventDefault();
                    toastr.error('Correggi il limite massimo prima di salvare');
                }
            });
        });
    </script>
@stop
