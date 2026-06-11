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

    // PDF Attestazione — Feature 9.1
    'pdf_title'             => 'Riepilogo Guide Pratiche Obbligatorie',
    'pdf_progress_summary'  => 'Riepilogo Avanzamento',
    'pdf_col_module'        => 'Modulo',
    'pdf_col_required'      => 'Ore richieste',
    'pdf_col_completed'     => 'Ore completate',
    'pdf_col_status'        => 'Stato',
    'pdf_completed'         => 'Completato',
    'pdf_sessions_detail'   => 'Dettaglio Sessioni',
    'pdf_session_date'      => 'Data',
    'pdf_session_duration'  => 'Durata',
    'pdf_session_instructor'=> 'Istruttore',
    'pdf_session_notes'     => 'Note',
    'pdf_no_sessions'       => 'Nessuna sessione registrata.',
    'pdf_instructors'       => 'Istruttori Coinvolti',
    'pdf_disclaimer_title'  => 'Avvertenza importante',
    'pdf_disclaimer_text'   => 'Questo documento è un riepilogo interno generato da :school e non costituisce attestazione ufficiale ai sensi del D.M. MIT 294/2025. È fornito a supporto della registrazione sul Portale dell\'Automobilista e della gestione amministrativa della scuola guida.',
    'pdf_signature_label'   => 'Timbro e firma autoscuola',
    'pdf_generated_by'      => 'Documento generato da :school',

    // Download attestazione
    'download_attestation'  => 'Scarica riepilogo PDF',
    'download_attestation_pending' => 'Il riepilogo PDF sarà disponibile al completamento di tutte le ore obbligatorie.',

    // Feature 9.2 — Sequenzialità e certificazione (decreto 294/2025)
    'cert_status_title'       => 'Stato Certificazione',
    'cert_unlocked'           => 'Certificazione sbloccata',
    'cert_unlocked_detail'    => 'Tutte le guide pratiche sono completate. Lo studente può ora esercitarsi con un accompagnatore privato.',
    'cert_unlocked_on'        => 'Certificazione sbloccata il :date.',
    'cert_unlocked_desc'      => 'Puoi ora esercitarti con un accompagnatore privato che abbia la patente B da almeno 10 anni.',
    'cert_in_progress'        => 'In corso',
    'cert_next_module'        => 'Prossimo modulo richiesto: :name',
    'cert_in_progress_detail' => 'In corso. Ore completate: :completed/:required (:pct%). Prossimo modulo: :module.',
    'error_sequence'          => 'Non puoi registrare una sessione del modulo :module finché i moduli precedenti non sono completati (decreto 294/2025).',
    'pdf_cert_status'         => 'Stato Certificazione',
    'pdf_cert_unlocked'       => 'CERTIFICAZIONE SBLOCCATA',
    'pdf_cert_in_progress'    => 'PERCORSO IN CORSO',
    'pdf_cert_companion_desc' => 'Il percorso formativo è completo. Lo studente è autorizzato a esercitarsi con accompagnatore privato.',

];
