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
