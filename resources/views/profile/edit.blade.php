@extends('layouts.admin')

@section('title', 'Profilo')

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">Account</p>
            <h1 class="sg-header-title">Il mio profilo</h1>
        </div>
        <div class="sg-header-actions">
            <i class="fas fa-user-circle" style="font-size:2rem;opacity:.6;"></i>
        </div>
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Informazioni profilo</h2>
        </div>
        <div class="sg-card-body">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    @if($user->requiresRegistration())
        <div class="sg-card sg-mb-3">
            <div class="sg-card-header sg-flex-between">
                <h2 class="sg-card-header-title">
                    <i class="fas fa-id-card mr-2"></i> Iscrizione esami ufficiali
                </h2>
                @include('profile.partials.registration-status-badge', ['user' => $user])
            </div>
            <div class="sg-card-body">
                @include('profile.partials.registration-form')
            </div>
        </div>
    @endif

    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">Aggiorna password</h2>
        </div>
        <div class="sg-card-body">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="sg-card sg-card-danger">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title" style="color:var(--sg-danger);">Elimina account</h2>
        </div>
        <div class="sg-card-body">
            @include('profile.partials.delete-user-form')
        </div>
    </div>

</div>
@endsection

@section('js')
    @parent

    <script>
        @if (session('status') === 'profile-updated')
            toastr.success('Profilo aggiornato con successo.');
        @endif

        @if (session('status') === 'password-updated')
            toastr.success('Password aggiornata con successo.');
        @endif

        @if ($errors->userDeletion->isNotEmpty())
            $('#confirmDeletionModal').modal('show');
        @endif
    </script>
@stop
