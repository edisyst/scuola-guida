@extends('layouts.admin')

@section('title', 'Assegnazioni — ' . $instructor->name)
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header sg-flex-between">
        <div>
            <h1 class="sg-header-title">
                <i class="fas fa-user-graduate mr-2"></i> Studenti di {{ $instructor->name }}
            </h1>
            <p class="sg-header-subtitle sg-mt-1">Gestisci gli studenti assegnati a questo istruttore</p>
        </div>
        <div class="sg-header-actions">
            <a href="{{ route('admin.instructors.index') }}" class="sg-btn sg-btn-light sg-btn-sm">
                <i class="fas fa-arrow-left"></i> Torna alla lista
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">
        {{-- Studenti assegnati --}}
        <div class="col-md-7">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-users mr-1"></i> Studenti assegnati</h3>
                </div>
                @if($assigned->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Nessuno studente assegnato.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="sg-table">
                            <thead>
                                <tr>
                                    <th>Studente</th>
                                    <th>Email</th>
                                    <th class="text-right" style="width:80px;">Rimuovi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assigned as $student)
                                    <tr>
                                        <td>
                                            <span class="sg-user-avatar">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                            <strong>{{ $student->name }}</strong>
                                        </td>
                                        <td class="sg-text-muted">{{ $student->email }}</td>
                                        <td class="sg-actions-cell">
                                            <form method="POST"
                                                  action="{{ route('admin.instructors.unassign', [$instructor, $student]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="sg-btn-icon delete"
                                                        title="Rimuovi studente"
                                                        onclick="return confirm('Rimuovere {{ $student->name }} da {{ $instructor->name }}?')">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Aggiungi studenti --}}
        <div class="col-md-5">
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3 class="sg-card-title"><i class="fas fa-user-plus mr-1"></i> Aggiungi studenti</h3>
                </div>
                @if($viewers->isEmpty())
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-muted mb-0">Tutti i viewer sono già assegnati.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.instructors.assign', $instructor) }}">
                        @csrf
                        <div class="p-3">
                            <div class="form-group mb-3">
                                <label for="student_ids" class="font-weight-bold">Seleziona studenti</label>
                                <select name="student_ids[]" id="student_ids"
                                        class="form-control @error('student_ids') is-invalid @enderror"
                                        multiple size="10">
                                    @foreach($viewers as $viewer)
                                        <option value="{{ $viewer->id }}">{{ $viewer->name }} ({{ $viewer->email }})</option>
                                    @endforeach
                                </select>
                                @error('student_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Tieni premuto Ctrl/Cmd per selezione multipla.</small>
                            </div>
                            <button type="submit" class="sg-btn sg-btn-primary w-100">
                                <i class="fas fa-user-plus"></i> Assegna selezionati
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
