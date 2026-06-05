@component('mail::message')
# {{ __('notifications.enrollment_approved_mail_title') }}

Ciao **{{ $user->fullAnagraphicName() }}**,

{{ __('notifications.enrollment_approved_mail_body', ['title' => $quiz->title]) }}

@component('mail::button', ['url' => $appUrl . '/quiz/enrollments'])
{{ __('notifications.enrollment_approved_mail_cta') }}
@endcomponent

Ricorda che per i quiz ufficiali è consentito un solo tentativo per iscrizione.

Grazie,<br>
{{ config('app.name') }}
@endcomponent
