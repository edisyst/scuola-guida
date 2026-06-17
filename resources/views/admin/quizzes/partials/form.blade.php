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

<div class="sg-form-group">
    <label class="sg-form-label">Titolo <span class="text-danger">*</span></label>
    <input name="title"
           class="sg-form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $quiz->title ?? '') }}">
    @error('title')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror
</div>

<div class="sg-form-group">
    <label class="sg-form-label">Tipo di patente</label>
    <select id="license_type_id" name="license_type_id" class="sg-form-control @error('license_type_id') is-invalid @enderror">
        <option value="">— Nessuno —</option>
        @foreach($licenseTypes as $lt)
            <option value="{{ $lt->id }}"
                @selected(old('license_type_id', $quiz->license_type_id ?? '') == $lt->id)>
                {{ $lt->name }} ({{ $lt->code }})
            </option>
        @endforeach
    </select>
    @error('license_type_id')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror
    <small class="sg-form-hint">Il filtro categorie nella pagina domande verrà ristretto al tipo selezionato.</small>
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

@if(auth()->user()->isAdmin())
<div class="sg-form-group">
    <label class="sg-form-label">Apertura iscrizioni</label>
    <input type="datetime-local"
           name="enrollments_open_at"
           class="sg-form-control @error('enrollments_open_at') is-invalid @enderror"
           value="{{ old('enrollments_open_at', optional($quiz->enrollments_open_at ?? null)->format('Y-m-d\TH:i')) }}">
    @error('enrollments_open_at')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror
    <small class="sg-form-hint">Prima di questa data il pulsante "Richiedi iscrizione" sarà nascosto.</small>
</div>

<div class="sg-form-group">
    <label class="sg-form-label">Chiusura iscrizioni</label>
    <input type="datetime-local"
           name="enrollments_close_at"
           class="sg-form-control @error('enrollments_close_at') is-invalid @enderror"
           value="{{ old('enrollments_close_at', optional($quiz->enrollments_close_at ?? null)->format('Y-m-d\TH:i')) }}">
    @error('enrollments_close_at')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror
    <small class="sg-form-hint">Dopo questa data le iscrizioni pending verranno chiuse automaticamente.</small>
</div>
@endif

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
