@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Ci sono errori nel form:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-group">
    <label>Titolo</label>
    <input name="title"
           class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $quiz->title ?? '') }}">

    @error('title')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label>
        <input type="checkbox" name="is_active"
            {{ old('is_active', $quiz->is_active ?? true) ? 'checked' : '' }}>
        Attivo
    </label>
</div>

<div class="form-group">
    <label>Domande</label>

    <select name="questions[]"
            class="form-control @error('questions') is-invalid @enderror"
            multiple size="20">

        @foreach($questions as $q)
            <option value="{{ $q->id }}"
                    @if(isset($quiz) && $quiz->questions->contains($q->id)) selected @endif
            >
                {{ Str::limit($q->question, 60) }}
            </option>
        @endforeach

    </select>

    @error('questions')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror

    <small class="text-muted">Tieni premuto CTRL per selezione multipla</small>
</div>

<div class="form-group">
    <label>Max Domande</label>

    <input type="number"
           id="max_questions"
           name="max_questions"
           class="form-control @error('max_questions') is-invalid @enderror"
           value="{{ old('max_questions', $quiz->max_questions ?? 30) }}">

    @error('max_questions')
        <div class="text-danger">{{ $message }}</div>
    @enderror

    <small class="text-muted">
        Domande attuali: {{ $questionsCount ?? 0 }}
    </small>

    <div id="max-warning" class="text-danger mt-1" style="display:none;"></div>
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

            // 🔥 live mentre scrivi
            input.on('input', validateMax);
            // 🔥 al load (se old())
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
