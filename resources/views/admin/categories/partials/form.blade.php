<div class="sg-form-group">
    <label class="sg-form-label">Nome</label>
    <input type="text" name="name"
           value="{{ old('name', $category->name ?? '') }}"
           class="sg-form-control @error('name') is-invalid @enderror"
           required autofocus>
    @error('name')
        <div class="sg-form-error">{{ $message }}</div>
    @enderror
</div>
