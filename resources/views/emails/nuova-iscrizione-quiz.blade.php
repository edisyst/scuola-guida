@component('mail::message')
# Nuova richiesta di iscrizione a un quiz

Ciao **{{ $admin->name }}**,

l'utente **{{ $viewer->fullAnagraphicName() }}** ({{ $viewer->email }}) ha richiesto l'iscrizione al quiz **«{{ $quiz->title }}»** ed è in attesa di approvazione.

@component('mail::button', ['url' => $appUrl . '/admin/enrollments?status=pending'])
Apri le iscrizioni in attesa
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
