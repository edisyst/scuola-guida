@extends('layouts.admin')

@section('title', 'Gestione Istruttori')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title"><i class="fas fa-chalkboard-teacher mr-2"></i> Gestione Istruttori</h1>
            <p class="sg-header-subtitle sg-mt-1">Assegna studenti agli istruttori per il monitoraggio dei progressi</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="sg-card">
        @if($instructors->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nessun utente con ruolo istruttore trovato.</p>
                <a href="{{ route('admin.users.create') }}" class="sg-btn sg-btn-primary sg-btn-sm">
                    <i class="fas fa-plus"></i> Crea un istruttore
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>Istruttore</th>
                            <th>Email</th>
                            <th>Studenti assegnati</th>
                            <th class="text-right" style="width:120px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($instructors as $instructor)
                            <tr>
                                <td>
                                    <span class="sg-user-avatar">{{ strtoupper(substr($instructor->name, 0, 1)) }}</span>
                                    <strong>{{ $instructor->name }}</strong>
                                </td>
                                <td class="sg-text-muted">{{ $instructor->email }}</td>
                                <td>
                                    <span class="sg-badge sg-badge-info">{{ $instructor->students_count }}</span>
                                </td>
                                <td class="sg-actions-cell">
                                    <a href="{{ route('admin.instructors.edit', $instructor) }}"
                                       class="sg-btn sg-btn-primary sg-btn-sm" title="Gestisci assegnazioni">
                                        <i class="fas fa-user-plus"></i> Assegnazioni
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
