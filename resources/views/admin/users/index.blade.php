@extends('layouts.admin')

@section('title', 'Utenti')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title"><i class="fas fa-users mr-2"></i> Gestione Utenti</h1>
            <p class="sg-header-subtitle sg-mt-1">Crea utenti, assegna ruoli e permessi individuali</p>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('admin.roles.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-user-shield"></i> Ruoli & Permessi
            </a>
            @if(auth()->user()->canCreateUser())
                <a href="{{ route('admin.users.create') }}" class="sg-btn sg-btn-success sg-btn-sm">
                    <i class="fas fa-plus"></i> Nuovo utente
                </a>
            @endif
        </div>
    </div>

    <div class="sg-card">
        <div class="table-responsive">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>Permessi extra</th>
                        <th class="text-right" style="width:160px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                <span class="sg-user-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                <strong>{{ $u->name }}</strong>
                            </td>
                            <td class="sg-text-muted">{{ $u->email }}</td>
                            <td>
                                <span class="sg-badge-role role-{{ $u->role }}">{{ $u->role }}</span>
                            </td>
                            <td>
                                @if($u->role !== \App\Models\User::ROLE_ADMIN && !empty($u->permissions))
                                    <span class="sg-badge sg-badge-info">+{{ count($u->permissions) }}</span>
                                @else
                                    <span class="sg-text-muted">—</span>
                                @endif
                            </td>
                            <td class="sg-actions-cell">
                                <a href="{{ route('admin.users.stats', $u) }}" class="sg-btn-icon" title="Statistiche">
                                    <i class="fas fa-chart-line"></i>
                                </a>

                                @if(auth()->user()->canEditUser())
                                    <a href="{{ route('admin.users.edit', $u) }}" class="sg-btn-icon edit" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif

                                @if(auth()->user()->canDeleteUser() && $u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                          class="d-inline" onsubmit="return confirm('Eliminare {{ $u->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="sg-btn-icon delete" title="Elimina">
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
            <div class="sg-card-section">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
