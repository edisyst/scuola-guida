@component('mail::message')
# {{ __('notifications.exam_completed_db_title') }}

Ciao **{{ $admin->name }}**,

l'utente **{{ $viewer->fullAnagraphicName() }}** (`{{ $viewer->email }}`) ha completato il quiz ufficiale **«{{ $quiz->title }}»**.

| Esito | Valore |
|---|---|
| Punteggio | **{{ $attempt->score }} / {{ $attempt->total_questions }}** |
| Errori | {{ $attempt->total_questions - $attempt->score }} |
@if($attempt->duration)
| Durata | {{ gmdate('i:s', $attempt->duration) }} |
@endif

@component('mail::button', ['url' => $appUrl . '/admin/confirmed-results'])
Vedi esiti confermati
@endcomponent

Grazie,<br>
{{ config('app.name') }}
@endcomponent
