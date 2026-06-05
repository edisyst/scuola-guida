@component('mail::message')
# {{ __('notifications.reg_rejected_mail_title') }}

Ciao **{{ $user->fullAnagraphicName() }}**,

{{ __('notifications.reg_rejected_mail_body') }}

@if (!empty($motivazione))
**Motivazione:**

> {{ $motivazione }}
@endif

@component('mail::button', ['url' => $appUrl . '/profile'])
{{ __('notifications.reg_rejected_mail_cta') }}
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
