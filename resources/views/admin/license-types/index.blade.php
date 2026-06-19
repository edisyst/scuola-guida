@extends('layouts.admin')

@section('page-title', 'Tipi di patente')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-fluid">

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title">Tipi di patente</h1>
            <p class="sg-header-subtitle sg-mt-1">Definisci le categorie di patente disponibili per le iscrizioni.</p>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('admin.license-types.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Aggiungi tipo
            </a>
        </div>
    </div>

    @if ($licenseTypes->count() === 0)
        <div class="alert alert-info">
            <i class="fas fa-info-circle fa-3x text-muted"></i>
            <p class="mt-2">Nessun tipo di patente registrato. {{ route('admin.license-types.create') }}</p>
        </div>
    @else
        <div class="sg-card">
            <div class="sg-card-body">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Codice</th>
                            <th>Nome</th>
                            <th>Categorie</th>
                            <th>Quiz</th>
                            <th>Formato esame</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($licenseTypes as $type)
                            <tr>
                                <td><strong>{{ $type->code }}</strong></td>
                                <td>{{ $type->name }}</td>
                                <td>{{ $type->categories_count ?? $type->categories()->count() }}</td>
                                <td>{{ $type->quizzes_count ?? $type->quizzes()->count() }}</td>
                                <td>
                                    @if ($type->exam_questions && $type->exam_minutes && $type->exam_max_errors)
                                        {{ $type->exam_questions }} domande / {{ $type->exam_minutes }} min / max {{ $type->exam_max_errors }} errori
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($type->is_active)
                                        <span class="badge badge-success">Attivo</span>
                                    @else
                                        <span class="badge badge-secondary">Inattivo</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.license-types.edit', $type) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.license-types.destroy', $type) }}" style="display: inline;" onsubmit="return confirm('Sei sicuro?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $licenseTypes->links() }}
    @endif

</div>
@endsection
