@component('mail::message')
# Iscrizione al quiz rifiutata

Ciao **{{ $user->fullAnagraphicName() }}**,

la tua richiesta di iscrizione al quiz **«{{ $quiz->title }}»** è stata **rifiutata** dall'amministratore.

@if (!empty($motivazione))
**Motivazione:**

> {{ $motivazione }}
@endif

Puoi consultare l'elenco delle tue iscrizioni dal portale.

@component('mail::button', ['url' => $appUrl . '/quiz/enrollments'])
Vai alle mie iscrizioni
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
