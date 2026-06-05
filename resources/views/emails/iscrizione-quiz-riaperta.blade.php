@component('mail::message')
# {{ __('notifications.enrollment_reopened_mail_title') }}

Ciao **{{ $user->fullAnagraphicName() }}**,

{{ __('notifications.enrollment_reopened_mail_body', ['title' => $quiz->title]) }}

@component('mail::button', ['url' => $appUrl . '/quiz/enrollments'])
{{ __('notifications.enrollment_reopened_mail_cta') }}
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
