@component('mail::message')
# Iscrizione al quiz approvata

Ciao **{{ $user->fullAnagraphicName() }}**,

la tua iscrizione al quiz **«{{ $quiz->title }}»** è stata **approvata**. Puoi svolgere il quiz quando vuoi dall'area "Le mie iscrizioni".

@component('mail::button', ['url' => $appUrl . '/quiz/enrollments'])
Vai alle mie iscrizioni
@endcomponent

Ricorda che per i quiz ufficiali è consentito un solo tentativo per iscrizione.

Grazie,<br>
{{ config('app.name') }}
@endcomponent
