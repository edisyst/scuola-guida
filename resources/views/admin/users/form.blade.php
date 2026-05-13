@php
    $userRole = old('role', $user->role ?? \App\Models\User::ROLE_VIEWER);
    $userPerms = old('permissions', $user->permissions ?? []) ?? [];
    $isAdminRole = $userRole === \App\Models\User::ROLE_ADMIN;
    $icons = [
        'question' => 'fa-question-circle',
        'quiz'     => 'fa-clipboard-check',
        'category' => 'fa-tags',
        'user'     => 'fa-users',
    ];
@endphp

{{-- ── Info base ── --}}
<div class="sg-form-section">
    <div class="sg-form-section-header">
        <h2 class="sg-form-section-title">
            <i class="fas fa-id-card"></i> Dati anagrafici
        </h2>
    </div>
    <div class="sg-form-section-body">
        <div class="row">
            <div class="col-md-6">
                <div class="sg-form-group">
                    <label class="sg-form-label">Nome</label>
                    <input name="name" class="sg-form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name ?? '') }}" required>
                    @error('name') <div class="sg-form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="sg-form-group">
                    <label class="sg-form-label">Email</label>
                    <input name="email" type="email" class="sg-form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email ?? '') }}" required>
                    @error('email') <div class="sg-form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="sg-form-group">
                    <label class="sg-form-label">
                        Password
                        @isset($user)
                            <span class="sg-text-muted" style="font-weight:400;text-transform:none;letter-spacing:0;">(lascia vuoto per non modificare)</span>
                        @endisset
                    </label>
                    <input name="password" type="password" class="sg-form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                    @error('password') <div class="sg-form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="sg-form-group">
                    <label class="sg-form-label">Ruolo</label>
                    <select name="role" id="role-select" class="sg-form-control">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ $userRole === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Permessi ── --}}
<div class="sg-form-section">
    <div class="sg-form-section-header">
        <h2 class="sg-form-section-title">
            <i class="fas fa-key"></i> Permessi individuali
        </h2>
        <span class="sg-form-section-hint" id="perms-hint" style="{{ $isAdminRole ? '' : 'display:none' }}">
            <i class="fas fa-info-circle"></i>
            Il ruolo <strong>Admin</strong> ha tutti i permessi automaticamente
        </span>
    </div>
    <div class="sg-form-section-body" id="perms-section" style="{{ $isAdminRole ? 'opacity:.5; pointer-events:none' : '' }}">

        <p class="sg-text-muted sg-mb-3" style="font-size:.9rem;">
            Spunta le caselle per concedere permessi <strong>aggiuntivi</strong> a questo utente
            (oltre a quelli già concessi dal ruolo).
            <a href="{{ route('admin.roles.index') }}" class="sg-link">Gestisci i permessi dei ruoli →</a>
        </p>

        <div class="row">
            @foreach($entities as $entity)
                <div class="col-md-6 sg-mb-2">
                    <div class="sg-perm-entity-card">
                        <div class="sg-perm-entity-head">
                            <i class="fas {{ $icons[$entity] ?? 'fa-cube' }}"></i>
                            {{ $entityLabels[$entity] }}
                        </div>
                        <div class="sg-perm-entity-body">
                            @foreach($actions as $action)
                                @php $p = "{$action}_{$entity}"; @endphp
                                <label class="sg-perm-toggle">
                                    <input type="checkbox" name="permissions[]" value="{{ $p }}"
                                           {{ in_array($p, $userPerms) ? 'checked' : '' }}>
                                    <span class="dot"></span>
                                    <span class="lbl">{{ $actionLabels[$action] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('js')
<script>
    $(function () {
        const $select = $('#role-select');
        const $section = $('#perms-section');
        const $hint = $('#perms-hint');

        function refresh() {
            const isAdmin = $select.val() === 'admin';
            $section.css({
                opacity: isAdmin ? .5 : 1,
                pointerEvents: isAdmin ? 'none' : 'auto'
            });
            $hint.toggle(isAdmin);
        }

        $select.on('change', refresh);
        refresh();
    });
</script>
@endpush
