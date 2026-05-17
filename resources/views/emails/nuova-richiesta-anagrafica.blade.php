@component('mail::message')
# Nuova richiesta di iscrizione anagrafica

Ciao **{{ $admin->name }}**,

l'utente **{{ $viewer->fullAnagraphicName() }}** ({{ $viewer->email }}) ha inviato i propri dati anagrafici ed è in attesa di revisione.

@component('mail::button', ['url' => $appUrl . '/admin/registrations/' . $viewer->id])
Apri la richiesta
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
