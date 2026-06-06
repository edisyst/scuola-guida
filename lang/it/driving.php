<?php

return [

    // Titoli sezione
    'title_modules'         => 'Moduli guida pratica',
    'title_sessions'        => 'Sessioni registrate',
    'title_progress'        => 'Avanzamento guide pratiche',

    // Card istruttore
    'card_title'            => 'Guide pratiche',

    // Avvisi
    'theory_warning'        => 'Lo studente non ha ancora superato l\'esame teorico. Le guide pratiche non sono ancora disponibili.',
    'no_license_type'       => 'Nessun tipo di patente associato allo studente. Contatta un amministratore.',

    // Moduli — colonne tabella
    'col_code'              => 'Codice',
    'col_name'              => 'Nome',
    'col_license_type'      => 'Tipo patente',
    'col_required_hours'    => 'Ore richieste',
    'col_sessions'          => 'N° sessioni',
    'col_actions'           => 'Azioni',

    // Moduli — form campi
    'field_license_type'    => 'Tipo patente',
    'field_code'            => 'Codice (max 5 caratteri)',
    'field_name'            => 'Nome modulo',
    'field_description'     => 'Descrizione',
    'field_required_hours'  => 'Ore richieste',
    'field_sort_order'      => 'Ordinamento',

    // Moduli — azioni
    'btn_new_module'        => 'Nuovo modulo',
    'btn_save'              => 'Salva',
    'btn_cancel'            => 'Annulla',
    'btn_edit'              => 'Modifica',
    'btn_delete'            => 'Elimina',

    // Moduli — empty state
    'modules_empty'         => 'Nessun modulo configurato per questo tipo di patente.',
    'modules_empty_hint'    => 'Crea il primo modulo per iniziare a strutturare le guide pratiche.',

    // Moduli — conferme
    'module_delete_confirm' => 'Sei sicuro di voler eliminare questo modulo? Tutte le sessioni associate saranno eliminate.',

    // Moduli — filtro
    'filter_all_types'      => 'Tutti i tipi',
    'filter_label'          => 'Filtra per tipo patente',

    // Moduli — titoli pagina
    'create_title'          => 'Nuovo modulo guida pratica',
    'edit_title'            => 'Modifica modulo',

    // Sessioni — campi form
    'field_module'          => 'Modulo',
    'field_conducted_at'    => 'Data sessione',
    'field_duration'        => 'Durata (min)',
    'field_notes'           => 'Note',

    // Sessioni — azioni
    'register_session'      => 'Registra sessione',
    'session_delete_confirm'=> 'Eliminare questa sessione?',

    // Sessioni — colonne
    'session_date'          => 'Data',
    'session_module'        => 'Modulo',
    'session_duration'      => 'Durata',
    'session_notes'         => 'Note',

    // Sessioni — stato
    'session_none'          => 'Nessuna sessione registrata.',

    // Progresso
    'progress_title'        => 'Completamento',
    'progress_completed'    => 'Completato',
    'progress_sessions'     => 'sessioni',
    'progress_empty'        => 'Nessun modulo disponibile. Contatta il tuo istruttore o la scuola guida.',
    'progress_all_done'     => 'Complimenti! Hai completato tutte le ore di guida pratica richieste.',
    'progress_hours'        => ':completed / :required ore',
    'progress_pct'          => ':pct% completato',

    // Errori service
    'module_has_sessions'   => 'Impossibile eliminare il modulo: esistono sessioni registrate. Elimina prima le sessioni.',

    // Titoli alternativi (alias per template flessibili)
    'title_create'          => 'Nuovo modulo',
    'title_edit'            => 'Modifica modulo',

];
