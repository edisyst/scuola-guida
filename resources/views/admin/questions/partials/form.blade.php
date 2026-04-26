<div class="form-group">
    <label>Categoria</label>
    <select name="category_id" class="form-control">
        @foreach($categories as $id => $name)
            <option value="{{ $id }}"
                {{ old('category_id', $question->category_id ?? '') == $id ? 'selected' : '' }}>
                {{ $name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Domanda</label>
    <textarea name="question" class="form-control">{{ old('question', $question->question ?? '') }}</textarea>
</div>

<div class="form-group">
    <label>Risposta corretta</label><br>

    <input type="checkbox" name="is_true"
           {{ old('is_true', $question->is_true ?? false) ? 'checked' : '' }}
           data-bootstrap-switch
           data-on-text="Vero"
           data-off-text="Falso">
</div>

<div class="form-group">
    <label>Immagine</label>
    <input type="file" name="image" class="form-control">
</div>

@if(!empty($question->image))
    <div class="mb-2">
        <img src="{{ asset('storage/'.$question->image) }}" width="120">
    </div>
@endif
