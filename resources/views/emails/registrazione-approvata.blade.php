@component('mail::message')
# {{ __('notifications.reg_approved_mail_title') }}

Ciao **{{ $user->fullAnagraphicName() }}**,

{{ __('notifications.reg_approved_mail_body') }}

@component('mail::button', ['url' => $appUrl . '/quiz/confirmed'])
{{ __('notifications.reg_approved_mail_cta') }}
@endcomponent

{{ __('notifications.reg_approved_mail_closing') }}

Grazie,<br>
{{ config('app.name') }}
@endcomponent
