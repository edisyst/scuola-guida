@extends('layouts.admin')

@section('title', __('profile.page_title'))

@section('content_header')@endsection

@section('content')
<div class="sg-wrapper-sm">

    <div class="sg-header sg-flex-between">
        <div>
            <p class="sg-header-subtitle">{{ __('profile.account_subtitle') }}</p>
            <h1 class="sg-header-title">{{ __('profile.my_profile') }}</h1>
        </div>
        <div class="sg-header-actions d-none d-sm-flex">
            <i class="fas fa-user-circle" style="font-size:2rem;opacity:.6;"></i>
        </div>
    </div>

    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">{{ __('profile.info_section') }}</h2>
        </div>
        <div class="sg-card-body">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    @if($user->requiresRegistration())
        <div class="sg-card sg-mb-3">
            <div class="sg-card-header sg-flex-between">
                <h2 class="sg-card-header-title">
                    <i class="fas fa-id-card mr-2"></i> {{ __('profile.reg_section') }}
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
            <h2 class="sg-card-header-title">{{ __('profile.password_section') }}</h2>
        </div>
        <div class="sg-card-body">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    @if($user->requiresTwoFactor())
        <div class="sg-card sg-mb-3">
            <div class="sg-card-header">
                <h2 class="sg-card-header-title">
                    <i class="fas fa-shield-alt mr-2"></i> {{ __('profile.twofa_section') }}
                </h2>
            </div>
            <div class="sg-card-body">
                @include('profile.partials.two-factor-form')
            </div>
        </div>
    @endif

    @if($user->isViewer())
    <div class="sg-card sg-mb-3"
         x-data="{ ttsEnabled: {{ $user->tts_enabled ? 'true' : 'false' }}, ttsAutoplay: {{ $user->tts_autoplay ? 'true' : 'false' }} }">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">
                <i class="fas fa-universal-access mr-2"></i> {{ __('profile.tts_title') }}
            </h2>
        </div>
        <div class="sg-card-body">
            <p class="text-muted mb-3">{{ __('profile.tts_desc') }}</p>

            <form action="{{ route('profile.accessibility.update') }}" method="POST">
                @csrf

                <div class="form-group d-flex align-items-center" style="gap:1rem;">
                    <label class="mb-0">{{ __('profile.tts_field_label') }}</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox"
                               class="custom-control-input"
                               id="tts_enabled"
                               name="tts_enabled"
                               value="1"
                               x-model="ttsEnabled"
                               {{ $user->tts_enabled ? 'checked' : '' }}>
                        <label class="custom-control-label" for="tts_enabled"></label>
                    </div>
                </div>

                <div class="form-group d-flex align-items-center mt-3" style="gap:1rem;"
                     x-show="ttsEnabled" x-cloak>
                    <label class="mb-0">{{ __('profile.tts_autoplay_field_label') }}</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox"
                               class="custom-control-input"
                               id="tts_autoplay"
                               name="tts_autoplay"
                               value="1"
                               x-model="ttsAutoplay"
                               {{ $user->tts_autoplay ? 'checked' : '' }}>
                        <label class="custom-control-label" for="tts_autoplay"></label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    <i class="fas fa-save mr-1"></i> {{ __('profile.save_prefs') }}
                </button>
            </form>
        </div>
    </div>

    <div class="sg-card sg-mb-3" x-data="pushSubscription()" x-init="init()" x-cloak>
        <div class="sg-card-header sg-flex-between">
            <h2 class="sg-card-header-title">
                <i class="fas fa-bell mr-2"></i> {{ __('profile.push_section') }}
            </h2>
            <span x-show="subscribed" class="badge badge-success">{{ __('profile.push_active') }}</span>
            <span x-show="!subscribed" class="badge badge-secondary">{{ __('profile.push_inactive') }}</span>
        </div>
        <div class="sg-card-body">
            <p class="text-muted mb-3">{{ __('profile.push_desc') }}</p>

            <div x-show="!supported" class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                {{ __('profile.push_not_supported') }}
            </div>

            <div x-show="supported">
                <button
                    x-show="!subscribed"
                    x-bind:disabled="loading"
                    @click="subscribe()"
                    class="btn btn-primary">
                    <span x-show="!loading"><i class="fas fa-bell mr-1"></i> {{ __('profile.push_subscribe') }}</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i> {{ __('profile.push_subscribing') }}</span>
                </button>

                <button
                    x-show="subscribed"
                    x-bind:disabled="loading"
                    @click="unsubscribe()"
                    class="btn btn-outline-secondary">
                    <span x-show="!loading"><i class="fas fa-bell-slash mr-1"></i> {{ __('profile.push_unsubscribe') }}</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin mr-1"></i> {{ __('profile.push_unsubscribing') }}</span>
                </button>

                <div x-show="error" class="alert alert-danger mt-2" x-text="error"></div>
            </div>
        </div>
    </div>
    @endif

    <div class="sg-card sg-mb-3">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title">
                <i class="fas fa-file-archive mr-2"></i> {{ __('profile.gdpr_section') }}
            </h2>
        </div>
        <div class="sg-card-body">
            <p class="text-muted mb-3">{{ __('profile.gdpr_desc') }}</p>
            <a href="{{ route('profile.download-data') }}" class="btn btn-outline-secondary">
                <i class="fas fa-download mr-1"></i> {{ __('profile.gdpr_download') }}
            </a>
        </div>
    </div>

    <div class="sg-card sg-card-danger">
        <div class="sg-card-header">
            <h2 class="sg-card-header-title sg-text-danger">{{ __('profile.delete_section') }}</h2>
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
                            this.error = '{{ __('profile.push_permission_denied') }}';
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
                        this.error = '{{ __('profile.push_activate_error') }}' + (e.message || e);
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
                        this.error = '{{ __('profile.push_deactivate_error') }}' + (e.message || e);
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>

    <script>
        @if (session('status') === 'profile-updated')
            toastr.success('{{ __('profile.profile_updated') }}');
        @endif

        @if (session('status') === 'password-updated')
            toastr.success('{{ __('profile.password_updated') }}');
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
