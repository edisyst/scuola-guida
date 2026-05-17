@component('mail::message')
# Ruolo aggiornato

Ciao **{{ $user->name }}**,

un amministratore ha aggiornato il tuo ruolo nel sistema.

| Prima | Adesso |
|---|---|
| {{ $oldLabel }} | **{{ $newLabel }}** |

Alcune sezioni del pannello potrebbero ora essere accessibili (o non più) in base al nuovo ruolo.

@component('mail::button', ['url' => $appUrl . '/dashboard'])
Vai alla dashboard
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
