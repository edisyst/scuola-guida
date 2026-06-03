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
        <div class="sg-header-actions d-none d-sm-flex">
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

    @if($user->requiresTwoFactor())
        <div class="sg-card sg-mb-3">
            <div class="sg-card-header">
                <h2 class="sg-card-header-title">
                    <i class="fas fa-shield-alt mr-2"></i> Autenticazione a due fattori
                </h2>
            </div>
            <div class="sg-card-body">
                @include('profile.partials.two-factor-form')
            </div>
        </div>
    @endif

    @if($user->isViewer())
    <div class="sg-card sg-mb-3" x-data="pushSubscription()" x-init="init()" x-cloak>
        <div class="sg-card-header sg-flex-between">
            <h2 class="sg-card-header-title">
                <i class="fas fa-bell mr-2"></i> Notifiche push
            </h2>
            <span x-show="subscribed" class="badge badge-success">Attive</span>
            <span x-show="!subscribed" class="badge badge-secondary">Non attive</span>
        </div>
        <div class="sg-card-body">
            <p class="text-muted mb-3">
                Ricevi notifiche native anche a app chiusa (badge guadagnati, approvazione iscrizione,
                promemoria ripasso SM-2).
            </p>

            <div x-show="!supported" class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Il tuo browser non supporta le notifiche push oppure il sito non è servito via HTTPS.
            </div>

            <div x-show="supported">
                <button
                    x-show="!subscribed"
                    x-bind:disabled="loading"
                    @click="subscribe()"
                    class="btn btn-primary">
                    <span x-show="!loading"><i class="fas fa-bell mr-1"></i> Attiva notifiche push</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i> Attivazione…</span>
                </button>

                <button
                    x-show="subscribed"
                    x-bind:disabled="loading"
                    @click="unsubscribe()"
                    class="btn btn-outline-secondary">
                    <span x-show="!loading"><i class="fas fa-bell-slash mr-1"></i> Disattiva notifiche push</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i> Disattivazione…</span>
                </button>

                <div x-show="error" class="alert alert-danger mt-2" x-text="error"></div>
            </div>
        </div>
    </div>
    @endif

    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">
                <i class="fas fa-file-archive mr-2"></i> Portabilità dei dati
            </h2>
        </div>
        <div class="sg-card-body">
            <p class="text-muted mb-3">
                Scarica un archivio ZIP con tutti i tuoi dati personali in formato JSON
                (GDPR art. 20 — diritto alla portabilità). Il file include quiz, bookmark,
                badge, attività e, se caricato, il documento d'identità.
            </p>
            <a href="{{ route('profile.download-data') }}" class="btn btn-outline-secondary">
                <i class="fas fa-download mr-1"></i> Scarica i miei dati
            </a>
        </div>
    </div>

    <div class="sg-card sg-card-danger">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title sg-text-danger">Elimina account</h2>
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
        function pushSubscription() {
            return {
                supported:   ('serviceWorker' in navigator) && ('PushManager' in window),
                subscribed:  false,
                loading:     false,
                error:       null,
                _sub:        null,

                async init() {
                    if (!this.supported) return;
                    try {
                        const reg = await navigator.serviceWorker.ready;
                        this._sub = await reg.pushManager.getSubscription();
                        this.subscribed = !!this._sub;
                    } catch {}
                },

                async subscribe() {
                    this.loading = true;
                    this.error   = null;
                    try {
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') {
                            this.error = 'Permesso negato. Abilita le notifiche nelle impostazioni del browser.';
                            return;
                        }
                        const reg    = await navigator.serviceWorker.ready;
                        const vapid  = document.querySelector('meta[name="vapid-public-key"]').content;
                        const appKey = Uint8Array.from(atob(vapid.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));

                        this._sub = await reg.pushManager.subscribe({
                            userVisibleOnly:      true,
                            applicationServerKey: appKey,
                        });

                        const subJson = this._sub.toJSON();
                        await fetch('{{ route('push-subscriptions.store') }}', {
                            method:  'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                endpoint:        subJson.endpoint,
                                keys:            subJson.keys,
                                contentEncoding: 'aesgcm',
                            }),
                        });

                        this.subscribed = true;
                    } catch (e) {
                        this.error = 'Errore durante l\'attivazione: ' + (e.message || e);
                    } finally {
                        this.loading = false;
                    }
                },

                async unsubscribe() {
                    this.loading = true;
                    this.error   = null;
                    try {
                        const endpoint = this._sub?.endpoint;
                        await this._sub?.unsubscribe();

                        await fetch('{{ route('push-subscriptions.destroy') }}', {
                            method:  'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ endpoint }),
                        });

                        this._sub      = null;
                        this.subscribed = false;
                    } catch (e) {
                        this.error = 'Errore durante la disattivazione: ' + (e.message || e);
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>

    <script>
        @if (session('status') === 'profile-updated')
            toastr.success('Profilo aggiornato con successo.');
        @endif

        @if (session('status') === 'password-updated')
            toastr.success('Password aggiornata con successo.');
        @endif

        @if (session('success'))
            toastr.success(@json(session('success')));
        @endif

        @if (session('warning'))
            toastr.warning(@json(session('warning')));
        @endif

        @if ($errors->userDeletion->isNotEmpty())
            $('#confirmDeletionModal').modal('show');
        @endif

        @if ($errors->twoFactorDisable->isNotEmpty())
            $('#disableTwoFactorModal').modal('show');
        @endif

        @if ($errors->twoFactorRegenerate->isNotEmpty())
            $('#regenerateCodesModal').modal('show');
        @endif
    </script>
@stop
