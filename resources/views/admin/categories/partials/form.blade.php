<div class="form-group">
    <label>Nome</label>
    <input type="text" name="name"
           value="{{ old('name', $category->name ?? '') }}"
           class="form-control">
</div>
