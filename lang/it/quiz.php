<?php

return [
    // Titoli
    'title'     => 'Gestione Quiz',
    'subtitle'  => 'Catalogo',
    'create'    => 'Nuovo quiz',
    'edit'      => 'Modifica quiz',
    'list'      => 'Elenco quiz',

    // Colonne tabella
    'col_id'        => 'ID',
    'col_title'     => 'Titolo',
    'col_status'    => 'Stato',
    'col_questions' => 'Domande',
    'col_license_type' => 'Patente',
    'col_actions'   => 'Azioni',

    // Filtri
    'filter_license_type' => 'Tipo di patente',
    'filter_license_type_all' => 'Tutti i tipi',

    // Stati ciclo di vita
    'status_draft'      => 'Bozza',
    'status_published'  => 'Pubblicato',
    'status_confirmed'  => 'Confermato',

    // Descrizioni stati
    'status_draft_desc'     => 'Quiz in preparazione. Visibile e modificabile solo da admin/editor; non giocabile dai viewer.',
    'status_published_desc' => 'Disponibile per tutti gli utenti in modalità allenamento. Si può ancora modificare o riportare in bozza.',
    'status_confirmed_desc' => 'Quiz bloccato per esame ufficiale. Non più modificabile; i viewer lo svolgono solo dopo iscrizione approvata.',

    // Legenda stati
    'states_legend' => 'Stati del quiz',

    // Azioni
    'action_new'         => 'Nuovo Quiz',
    'action_random'      => 'Quiz Random',
    'action_publish'     => 'Pubblica',
    'action_unpublish'   => 'Riporta in bozza',
    'action_confirm'     => 'Conferma (lock)',
    'action_summary'     => 'Riepilogo',
    'action_schedule'    => 'Schedulazione iscrizioni',
    'action_questions'   => 'Gestisci domande',
    'action_fill_random' => 'Aggiungi domande random',
    'action_delete'      => 'Elimina',
    'action_play'        => 'Play',

    // Tooltip disabled
    'tooltip_no_questions'           => 'Nessuna domanda nel quiz',
    'tooltip_questions_locked'       => 'Quiz confermato: domande bloccate',
    'tooltip_already_has_questions'  => 'Il quiz ha già domande',

    // Conferme JS
    'confirm_confirm_lock' => 'Una volta confermato il quiz non potrà più essere modificato. Continuare?',
    'confirm_delete'       => 'Sei sicuro?',
];
