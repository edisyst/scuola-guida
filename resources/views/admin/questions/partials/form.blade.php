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
    <small class="text-muted">Lascia vuoto per non modificare. Le immagini si gestiscono dal Media Manager.</small>
</div>

@if(!empty($question->image))
    <div class="sg-mb-3">
        <span class="sg-label d-block mb-1">Immagine attuale</span>
        <img src="{{ asset('storage/'.$question->image) }}" width="160" class="sg-img-thumb">
        <div class="mt-2">
            <label class="text-danger" style="cursor:pointer;">
                <input type="checkbox" name="remove_image" value="1"
                       {{ old('remove_image') ? 'checked' : '' }}>
                <i class="fas fa-unlink ml-1"></i> Rimuovi immagine da questa domanda
            </label>
            <div class="text-muted small">
                Il file resta nello storage (gestito dal Media Manager).
            </div>
        </div>
    </div>
@endif
