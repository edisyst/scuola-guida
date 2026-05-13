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

<div class="user-form-wrapper">

    {{-- ── Info base ── --}}
    <div class="card form-card mb-4">
        <div class="form-card-header">
            <span class="form-section-title">
                <i class="fas fa-id-card"></i> Dati Anagrafici
            </span>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Nome</label>
                        <input name="name" class="form-control"
                               value="{{ old('name', $user->name ?? '') }}" required>
                        @error('name') <div class="invalid-msg">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control"
                               value="{{ old('email', $user->email ?? '') }}" required>
                        @error('email') <div class="invalid-msg">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            Password
                            @isset($user)
                                <small class="text-muted">(lascia vuoto per non modificare)</small>
                            @endisset
                        </label>
                        <input name="password" type="password" class="form-control"
                               autocomplete="new-password">
                        @error('password') <div class="invalid-msg">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Ruolo</label>
                        <select name="role" id="role-select" class="form-control">
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
    <div class="card form-card mb-4">
        <div class="form-card-header">
            <span class="form-section-title">
                <i class="fas fa-key"></i> Permessi individuali
            </span>
            <span class="form-section-hint" id="perms-hint" style="{{ $isAdminRole ? '' : 'display:none' }}">
                <i class="fas fa-info-circle"></i>
                Il ruolo <strong>Admin</strong> ha tutti i permessi automaticamente
            </span>
        </div>
        <div class="card-body p-4" id="perms-section" style="{{ $isAdminRole ? 'opacity:.5; pointer-events:none' : '' }}">

            <p class="perms-intro">
                Spunta le caselle per concedere permessi <strong>aggiuntivi</strong> a questo utente
                (oltre a quelli già concessi dal ruolo).
                <a href="{{ route('admin.roles.index') }}" class="text-info">Gestisci i permessi dei ruoli →</a>
            </p>

            <div class="row">
                @foreach($entities as $entity)
                    <div class="col-md-6 mb-3">
                        <div class="perm-entity-card">
                            <div class="perm-entity-head">
                                <i class="fas {{ $icons[$entity] ?? 'fa-cube' }}"></i>
                                {{ $entityLabels[$entity] }}
                            </div>
                            <div class="perm-entity-body">
                                @foreach($actions as $action)
                                    @php $p = "{$action}_{$entity}"; @endphp
                                    <label class="perm-toggle">
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

</div>

@push('css')
<style>
    .user-form-wrapper { max-width: 1100px; margin: 0 auto; }

    .form-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,.08);
        overflow: hidden;
    }
    .form-card-header {
        background: #f8f9fa;
        padding: 14px 22px;
        border-bottom: 1px solid #ecedef;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .form-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #212529;
        text-transform: uppercase;
        letter-spacing: .8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-section-title i { color: #6c757d; }
    .form-section-hint {
        font-size: .8rem;
        color: #6f42c1;
        font-weight: 600;
    }
    .form-label {
        font-size: .8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #6c757d;
        margin-bottom: 6px;
    }
    .form-control {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 10px 14px;
    }
    .form-control:focus {
        border-color: #1a1a2e;
        box-shadow: 0 0 0 .2rem rgba(26,26,46,.1);
    }
    .invalid-msg {
        color: #dc3545;
        font-size: .8rem;
        margin-top: 4px;
    }

    .perms-intro {
        font-size: .9rem;
        color: #6c757d;
        margin-bottom: 18px;
    }

    .perm-entity-card {
        border: 1px solid #ecedef;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        height: 100%;
    }
    .perm-entity-head {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 10px 14px;
        font-weight: 700;
        font-size: .85rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .perm-entity-head i { color: #6c757d; }
    .perm-entity-body {
        padding: 8px 14px 12px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }

    .perm-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        margin: 0;
        cursor: pointer;
        transition: background .15s;
    }
    .perm-toggle:hover { background: #f8f9fa; }
    .perm-toggle input { display: none; }
    .perm-toggle .dot {
        width: 18px; height: 18px;
        border-radius: 5px;
        border: 2px solid #dee2e6;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all .15s;
        flex-shrink: 0;
    }
    .perm-toggle .dot::after {
        content: '';
        width: 5px; height: 9px;
        border: solid transparent;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg) translateY(-1px);
        transition: border-color .15s;
    }
    .perm-toggle input:checked + .dot {
        background: linear-gradient(135deg, #28a745, #20c997);
        border-color: #28a745;
    }
    .perm-toggle input:checked + .dot::after {
        border-color: #fff;
    }
    .perm-toggle .lbl {
        font-size: .85rem;
        color: #495057;
        font-weight: 500;
    }

    .btn-submit-user {
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        color: #fff;
        border: none;
        padding: 11px 30px;
        font-weight: 700;
        border-radius: 10px;
        letter-spacing: .4px;
    }
    .btn-submit-user:hover {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,.2);
    }
</style>
@endpush

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
