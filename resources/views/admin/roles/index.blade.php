@extends('layouts.admin')

@section('title', 'Ruoli & Permessi')
@section('header', 'Ruoli & Permessi')
@section('content_header')@endsection

@section('css')
    @parent
    <style>
        .perm-wrapper { max-width: 1200px; margin: 0 auto; }

        .perm-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 22px 28px;
            margin-bottom: 24px;
            color: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,.12);
        }
        .perm-header h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: .3px;
        }
        .perm-header .subtitle {
            color: rgba(255,255,255,.7);
            font-size: .85rem;
            margin-top: 4px;
        }

        .perm-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .perm-card-header {
            background: #f8f9fa;
            padding: 14px 22px;
            border-bottom: 1px solid #ecedef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .perm-entity-title {
            font-size: 1rem;
            font-weight: 700;
            color: #212529;
            text-transform: uppercase;
            letter-spacing: .8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .perm-entity-title i {
            color: #6c757d;
        }

        .perm-table {
            width: 100%;
            margin-bottom: 0;
        }
        .perm-table th, .perm-table td {
            padding: 12px 16px;
            vertical-align: middle;
            border-top: 1px solid #f1f3f5;
        }
        .perm-table thead th {
            background: #fff;
            color: #adb5bd;
            text-transform: uppercase;
            font-size: .7rem;
            letter-spacing: .8px;
            font-weight: 700;
            border-bottom: 1px solid #ecedef;
            border-top: none;
        }
        .perm-table .role-name {
            font-weight: 600;
            font-size: .95rem;
            color: #212529;
        }
        .perm-table .role-name .badge-role {
            font-size: .65rem;
            padding: 3px 9px;
            border-radius: 20px;
            margin-left: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .badge-admin  { background: #6f42c1; color: #fff; }
        .badge-editor { background: #fd7e14; color: #fff; }
        .badge-viewer { background: #20c997; color: #fff; }

        /* checkbox custom */
        .perm-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
        }
        .perm-check input { display: none; }
        .perm-check .box {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .15s ease;
            color: transparent;
        }
        .perm-check:hover .box {
            border-color: #adb5bd;
            transform: scale(1.08);
        }
        .perm-check input:checked + .box {
            background: linear-gradient(135deg, #28a745, #20c997);
            border-color: #28a745;
            color: #fff;
            box-shadow: 0 4px 10px rgba(40,167,69,.3);
        }
        .perm-check.disabled {
            cursor: not-allowed;
            opacity: .6;
        }
        .perm-check.disabled input:checked + .box {
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
            border-color: #6f42c1;
            box-shadow: 0 4px 10px rgba(111,66,193,.3);
        }

        .col-action {
            text-align: center;
            width: 130px;
        }

        .save-bar {
            position: sticky;
            bottom: 20px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 -2px 20px rgba(0,0,0,.1);
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #ecedef;
            z-index: 10;
        }
        .save-bar .hint {
            color: #6c757d;
            font-size: .85rem;
        }
        .btn-save-perms {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #fff;
            border: none;
            padding: 10px 24px;
            font-weight: 700;
            border-radius: 10px;
            letter-spacing: .4px;
            transition: transform .12s;
        }
        .btn-save-perms:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 6px 16px rgba(0,0,0,.18);
        }

        .legend {
            display: flex;
            gap: 18px;
            margin-top: 10px;
            flex-wrap: wrap;
            font-size: .8rem;
            color: rgba(255,255,255,.85);
        }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        .legend-dot {
            width: 14px; height: 14px; border-radius: 4px;
        }
    </style>
@endsection

@section('content')
<div class="perm-wrapper">

    <div class="perm-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1><i class="fas fa-user-shield mr-2"></i> Ruoli & Permessi</h1>
                <div class="subtitle">
                    Definisci quali azioni può eseguire ogni ruolo per ciascuna entità.
                </div>
                <div class="legend">
                    <span class="legend-item">
                        <span class="legend-dot" style="background: linear-gradient(135deg,#28a745,#20c997)"></span>
                        Concesso
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot" style="background: linear-gradient(135deg,#6f42c1,#5a32a3)"></span>
                        Admin (sempre attivo)
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot" style="background:#fff;border:2px solid #dee2e6"></span>
                        Negato
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.roles.update') }}">
        @csrf
        @method('PUT')

        @foreach($entities as $entity)
            @php $icons = [
                'question' => 'fa-question-circle',
                'quiz'     => 'fa-clipboard-check',
                'category' => 'fa-tags',
                'user'     => 'fa-users',
            ]; @endphp

            <div class="card perm-card">
                <div class="perm-card-header">
                    <span class="perm-entity-title">
                        <i class="fas {{ $icons[$entity] ?? 'fa-cube' }}"></i>
                        {{ $entityLabels[$entity] }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="perm-table">
                        <thead>
                            <tr>
                                <th>Ruolo</th>
                                @foreach($actions as $action)
                                    <th class="col-action">{{ $actionLabels[$action] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $roleKey => $roleLabel)
                                <tr>
                                    <td>
                                        <span class="role-name">
                                            {{ $roleLabel }}
                                            <span class="badge-role badge-{{ $roleKey }}">{{ $roleKey }}</span>
                                        </span>
                                    </td>
                                    @foreach($actions as $action)
                                        @php
                                            $perm   = "{$action}_{$entity}";
                                            $isAdmin = $roleKey === $adminRole;
                                            $checked = $matrix[$roleKey][$perm] ?? false;
                                        @endphp
                                        <td class="col-action">
                                            <label class="perm-check {{ $isAdmin ? 'disabled' : '' }}">
                                                <input type="checkbox"
                                                       name="matrix[{{ $roleKey }}][{{ $perm }}]"
                                                       value="1"
                                                       {{ $checked ? 'checked' : '' }}
                                                       {{ $isAdmin ? 'disabled' : '' }}>
                                                <span class="box">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="save-bar">
            <span class="hint">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>manage</strong> concede automaticamente create/edit/delete sull'entità.
            </span>
            <button type="submit" class="btn btn-save-perms">
                <i class="fas fa-save mr-1"></i> Salva permessi
            </button>
        </div>
    </form>

</div>
@endsection
