@component('mail::message')
# {{ __('notifications.enrollment_rejected_mail_title') }}

Ciao **{{ $user->fullAnagraphicName() }}**,

{{ __('notifications.enrollment_rejected_mail_body', ['title' => $quiz->title]) }}

@if (!empty($motivazione))
**Motivazione:**

> {{ $motivazione }}
@endif

@component('mail::button', ['url' => $appUrl . '/quiz/enrollments'])
{{ __('notifications.enrollment_rejected_mail_cta') }}
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
