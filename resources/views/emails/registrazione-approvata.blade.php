@component('mail::message')
# Iscrizione approvata

Ciao **{{ $user->fullAnagraphicName() }}**,

la tua iscrizione anagrafica è stata **approvata** dall'amministratore. Da ora puoi richiedere l'iscrizione ai quiz ufficiali per la patente.

@component('mail::button', ['url' => $appUrl . '/quiz/confirmed'])
Vai al catalogo quiz
@endcomponent

In bocca al lupo per gli esami!

Grazie,<br>
{{ config('app.name') }}
@endcomponent
