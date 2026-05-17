@component('mail::message')
# Iscrizione anagrafica rifiutata

Ciao **{{ $user->fullAnagraphicName() }}**,

l'amministratore ha **rifiutato** la tua richiesta di iscrizione anagrafica.

@if (!empty($motivazione))
**Motivazione:**

> {{ $motivazione }}
@endif

Puoi correggere i dati dal tuo profilo e reinviare la richiesta per una nuova revisione.

@component('mail::button', ['url' => $appUrl . '/profile'])
Aggiorna i tuoi dati
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
