@component('mail::message')
# Iscrizione al quiz riaperta

Ciao **{{ $user->fullAnagraphicName() }}**,

l'amministratore ha **riaperto** una nuova iscrizione per il quiz **«{{ $quiz->title }}»**. Puoi quindi svolgerlo nuovamente quando preferisci.

@component('mail::button', ['url' => $appUrl . '/quiz/enrollments'])
Vai alle mie iscrizioni
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
