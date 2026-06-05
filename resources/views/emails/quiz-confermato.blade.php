@component('mail::message')
# {{ __('notifications.quiz_confirmed_mail_title') }}

Ciao **{{ $user->fullAnagraphicName() }}**,

{{ __('notifications.quiz_confirmed_mail_body', ['title' => $quiz->title]) }}

@component('mail::button', ['url' => $appUrl . '/quiz/confirmed'])
{{ __('notifications.quiz_confirmed_mail_cta') }}
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
