<div class="form-group">
    <label>Max Domande</label>
    <input type="number" name="max_questions"
           class="form-control"
           value="{{ old('max_questions', $quiz->max_questions ?? 30) }}">
</div>

<div class="form-group">
    <label>Titolo</label>
    <input name="title"
           class="form-control"
           value="{{ old('title', $quiz->title ?? '') }}">
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

    <select name="questions[]" class="form-control" multiple size="10">

        @foreach($questions as $q)
            <option value="{{ $q->id }}"
                    @if(isset($quiz) && $quiz->questions->contains($q->id)) selected @endif
            >
                {{ Str::limit($q->question, 60) }}
            </option>
        @endforeach

    </select>

    <small class="text-muted">Tieni premuto CTRL per selezione multipla</small>
</div>
