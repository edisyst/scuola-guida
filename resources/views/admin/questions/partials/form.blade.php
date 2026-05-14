<div class="sg-form-group">
    <label class="sg-form-label">Categoria</label>
    <select name="category_id" class="sg-form-control">
        @foreach($categories as $id => $name)
            <option value="{{ $id }}"
                {{ old('category_id', $question->category_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </select>
</div>

<div class="sg-form-group">
    <label class="sg-form-label">Domanda</label>
    <textarea name="question" rows="4" class="sg-form-control">{{ old('question', $question->question ?? '') }}</textarea>
</div>

<div class="sg-form-group">
    <label class="sg-form-label">Risposta corretta</label>
    <div>
        <input type="checkbox" name="is_true"
               {{ old('is_true', $question->is_true ?? false) ? 'checked' : '' }}
               data-bootstrap-switch
               data-on-text="Vero"
               data-off-text="Falso"
               data-on-color="success"
               data-off-color="danger">
    </div>
</div>

<div class="sg-form-group">
    <label class="sg-form-label">Immagine</label>
    <input type="file" name="image" class="sg-form-control">
</div>

@if(!empty($question->image))
    <div class="sg-mb-2">
        <span class="sg-label">Immagine attuale</span>
        <img src="{{ asset('storage/'.$question->image) }}" width="160" style="border-radius:var(--sg-radius-sm);box-shadow:var(--sg-shadow-card);">
    </div>
@endif
