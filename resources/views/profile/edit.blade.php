@extends('layouts.admin')

@section('title', 'Profilo')

@section('content_header')
    <h1>Profilo</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8 col-lg-6">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informazioni Profilo</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aggiorna Password</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title">Elimina Account</h3>
                </div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
@stop

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
