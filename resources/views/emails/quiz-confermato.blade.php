@component('mail::message')
# Nuovo quiz disponibile

Ciao **{{ $user->fullAnagraphicName() }}**,

il quiz **«{{ $quiz->title }}»** è stato confermato dall'amministratore ed è ora aperto alle iscrizioni.

@component('mail::button', ['url' => $appUrl . '/quiz/confirmed'])
Vai ai quiz disponibili
@endcomponent

Iscriviti per partecipare alla prossima sessione d'esame.

Grazie,<br>
{{ config('app.name') }}
@endcomponent
