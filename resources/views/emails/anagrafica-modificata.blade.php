@component('mail::message')
# Anagrafica modificata — richiesta nuova revisione

Ciao **{{ $admin->name }}**,

l'utente **{{ $viewer->fullAnagraphicName() }}** (`{{ $viewer->email }}`), già approvato in precedenza, ha modificato i propri dati anagrafici. Lo stato è tornato a **in attesa** e l'iscrizione agli esami ufficiali è temporaneamente sospesa fino a una nuova approvazione.

@component('mail::button', ['url' => $appUrl . '/admin/registrations/' . $viewer->id])
Rivedi la richiesta
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
