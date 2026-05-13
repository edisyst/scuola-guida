@extends('layouts.admin')

@section('title', 'Utenti')
@section('header', 'Utenti')
@section('content_header')@endsection

@section('css')
    @parent
    <style>
        .users-wrapper { max-width: 1200px; margin: 0 auto; }

        .users-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 22px 28px;
            margin-bottom: 24px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.12);
        }
        .users-header h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: .3px;
        }
        .users-header .subtitle {
            color: rgba(255,255,255,.7);
            font-size: .85rem;
            margin-top: 4px;
        }
        .btn-new-user {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 700;
            letter-spacing: .4px;
            transition: transform .12s;
        }
        .btn-new-user:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 6px 16px rgba(40,167,69,.35);
        }
        .btn-roles {
            background: rgba(255,255,255,.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,.18);
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-roles:hover {
            background: rgba(255,255,255,.22);
            color: #fff;
        }

        .users-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .users-table { width: 100%; margin-bottom: 0; }
        .users-table thead th {
            background: #f8f9fa;
            color: #adb5bd;
            text-transform: uppercase;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .8px;
            padding: 14px 18px;
            border: none;
        }
        .users-table tbody td {
            padding: 14px 18px;
            vertical-align: middle;
            border-top: 1px solid #f1f3f5;
        }
        .user-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
        }
        .badge-role {
            display: inline-block;
            font-size: .65rem;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 700;
        }
        .badge-admin  { background: #6f42c1; color: #fff; }
        .badge-editor { background: #fd7e14; color: #fff; }
        .badge-viewer { background: #20c997; color: #fff; }

        .actions-cell .btn { border-radius: 8px; }
    </style>
@endsection

@section('content')
<div class="users-wrapper">

    <div class="users-header">
        <div>
            <h1><i class="fas fa-users mr-2"></i> Gestione Utenti</h1>
            <div class="subtitle">Crea utenti, assegna ruoli e permessi individuali</div>
        </div>
        <div class="d-flex gap-2" style="gap: 10px;">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-roles">
                <i class="fas fa-user-shield mr-1"></i> Ruoli & Permessi
            </a>
            @if(auth()->user()->canCreateUser())
                <a href="{{ route('admin.users.create') }}" class="btn btn-new-user">
                    <i class="fas fa-plus mr-1"></i> Nuovo utente
                </a>
            @endif
        </div>
    </div>

    <div class="card users-card">
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>Permessi extra</th>
                        <th class="text-end" style="width: 200px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                <span class="user-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                <strong>{{ $u->name }}</strong>
                            </td>
                            <td>{{ $u->email }}</td>
                            <td>
                                <span class="badge-role badge-{{ $u->role }}">{{ $u->role }}</span>
                            </td>
                            <td>
                                @if($u->role !== \App\Models\User::ROLE_ADMIN && !empty($u->permissions))
                                    <span class="badge badge-info">
                                        +{{ count($u->permissions) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end actions-cell">
                                @if(auth()->user()->canEditUser())
                                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif

                                @if(auth()->user()->canDeleteUser() && $u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          style="display:inline;" onsubmit="return confirm('Eliminare {{ $u->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-3 border-top">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
