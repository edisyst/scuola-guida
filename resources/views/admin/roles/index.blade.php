@extends('layouts.admin')

@section('title', 'Ruoli & Permessi')
@section('content_header')@endsection

@section('content')
<div class="sg-wrapper">

    <div class="sg-header">
        <h1 class="sg-header-title"><i class="fas fa-user-shield mr-2"></i> Ruoli & Permessi</h1>
        <p class="sg-header-subtitle sg-mt-1">
            Definisci quali azioni può eseguire ogni ruolo per ciascuna entità.
        </p>
        <div class="sg-legend">
            <span class="sg-legend-item">
                <span class="sg-legend-dot sg-legend-dot--granted"></span>
                Concesso
            </span>
            <span class="sg-legend-item">
                <span class="sg-legend-dot sg-legend-dot--admin"></span>
                Admin (sempre attivo)
            </span>
            <span class="sg-legend-item">
                <span class="sg-legend-dot sg-legend-dot--denied"></span>
                Negato
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.roles.update') }}">
        @csrf
        @method('PUT')

        @php $icons = [
            'question' => 'fa-question-circle',
            'quiz'     => 'fa-clipboard-check',
            'category' => 'fa-tags',
            'user'     => 'fa-users',
        ]; @endphp

        @foreach($entities as $entity)
            <div class="sg-form-section">
                <div class="sg-form-section-header">
                    <h2 class="sg-form-section-title">
                        <i class="fas {{ $icons[$entity] ?? 'fa-cube' }}"></i>
                        {{ $entityLabels[$entity] }}
                    </h2>
                </div>

                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>Ruolo</th>
                                @foreach($actions as $action)
                                    <th class="sg-text-center" style="width:130px;">{{ $actionLabels[$action] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $roleKey => $roleLabel)
                                <tr>
                                    <td>
                                        <strong>{{ $roleLabel }}</strong>
                                        <span class="sg-badge-role role-{{ $roleKey }} ml-2">{{ $roleKey }}</span>
                                    </td>
                                    @foreach($actions as $action)
                                        @php
                                            $perm   = "{$action}_{$entity}";
                                            $isAdmin = $roleKey === $adminRole;
                                            $checked = $matrix[$roleKey][$perm] ?? false;
                                        @endphp
                                        <td class="sg-text-center">
                                            <label class="sg-perm-check {{ $isAdmin ? 'disabled' : '' }}">
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

        <div class="sg-save-bar">
            <span class="hint">
                <i class="fas fa-info-circle"></i>
                <strong>manage</strong> concede automaticamente tutte le azioni (read, create, edit, delete, bulk) sull'entità.
            </span>
            <button type="submit" class="sg-btn sg-btn-primary">
                <i class="fas fa-save"></i> Salva permessi
            </button>
        </div>
    </form>
</div>
@endsection
