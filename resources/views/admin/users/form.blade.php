<div class="form-group">
    <label>Nome</label>
    <input name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}">
</div>

<div class="form-group">
    <label>Email</label>
    <input name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}">
</div>

<div class="form-group">
    <label>Password</label>
    <input name="password" type="password" class="form-control">
</div>

<div class="form-group">
    <label>Ruolo</label>
    <select name="role" class="form-control" @if(old('role', $user->role ?? '') === 'admin') disabled @endif>
        <option value="admin">Admin</option>
        <option value="editor">Editor</option>
        <option value="viewer">Viewer</option>
    </select>
</div>

<hr>

<h5>Permessi</h5>

@foreach($permissions as $perm)
    <div>
        <label>
            <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                {{ isset($user) && in_array($perm, $user->permissions ?? []) ? 'checked' : '' }}
                @if(old('role', $user->role ?? '') === 'admin') disabled @endif>
            {{ $perm }}
        </label>
    </div>
@endforeach
