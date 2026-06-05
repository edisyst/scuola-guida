<?php

return [
    // Registrazione approvata
    'reg_approved_subject'    => 'Iscrizione anagrafica approvata',
    'reg_approved_mail_title' => 'Iscrizione approvata',
    'reg_approved_mail_body'  => "la tua iscrizione anagrafica è stata **approvata** dall'amministratore. Da ora puoi richiedere l'iscrizione ai quiz ufficiali per la patente.",
    'reg_approved_mail_cta'   => 'Vai al catalogo quiz',
    'reg_approved_mail_closing' => 'In bocca al lupo per gli esami!',
    'reg_approved_db_title'   => 'Iscrizione approvata',
    'reg_approved_db_body'    => 'La tua iscrizione anagrafica è stata approvata: ora puoi iscriverti agli esami ufficiali.',
    'reg_approved_push_title' => 'Iscrizione approvata',
    'reg_approved_push_body'  => "La tua iscrizione è stata approvata: ora puoi iscriverti agli esami ufficiali.",
    'reg_approved_push_action'=> 'Apri dashboard',

    // Registrazione rifiutata
    'reg_rejected_subject'    => 'Iscrizione anagrafica non approvata',
    'reg_rejected_mail_title' => 'Iscrizione non approvata',
    'reg_rejected_mail_body'  => "la tua richiesta di iscrizione anagrafica non è stata approvata. Controlla il motivo nell'area personale e invia una nuova richiesta con i dati corretti.",
    'reg_rejected_mail_cta'   => 'Vai al profilo',
    'reg_rejected_db_title'   => 'Iscrizione rifiutata',
    'reg_rejected_db_body'    => "La tua richiesta di iscrizione anagrafica non è stata approvata. Controlla i motivi nella tua area personale.",

    // Iscrizione quiz approvata
    'enrollment_approved_subject'    => 'Iscrizione al quiz approvata',
    'enrollment_approved_mail_title' => 'Iscrizione quiz approvata',
    'enrollment_approved_mail_body'  => "la tua iscrizione al quiz **:title** è stata **approvata**. Ora puoi svolgere il quiz dall'area iscrizioni.",
    'enrollment_approved_mail_cta'   => 'Vai alle iscrizioni',
    'enrollment_approved_db_title'   => 'Iscrizione quiz approvata',
    'enrollment_approved_db_body'    => 'La tua iscrizione al quiz «:title» è stata approvata.',

    // Iscrizione quiz rifiutata
    'enrollment_rejected_subject'    => 'Iscrizione al quiz non approvata',
    'enrollment_rejected_mail_title' => 'Iscrizione quiz rifiutata',
    'enrollment_rejected_mail_body'  => "la tua richiesta di iscrizione al quiz **:title** non è stata approvata.",
    'enrollment_rejected_mail_cta'   => 'Vai alle iscrizioni',
    'enrollment_rejected_db_title'   => 'Iscrizione quiz rifiutata',
    'enrollment_rejected_db_body'    => 'La tua richiesta di iscrizione al quiz «:title» non è stata approvata.',

    // Iscrizione quiz riaperta
    'enrollment_reopened_subject'    => 'Iscrizione al quiz riaperta',
    'enrollment_reopened_mail_title' => 'Iscrizione quiz riaperta',
    'enrollment_reopened_mail_body'  => "la tua iscrizione al quiz **:title** è stata riaperta.",
    'enrollment_reopened_mail_cta'   => 'Vai alle iscrizioni',
    'enrollment_reopened_db_title'   => 'Iscrizione quiz riaperta',
    'enrollment_reopened_db_body'    => 'La tua iscrizione al quiz «:title» è stata riaperta.',

    // Quiz confermato
    'quiz_confirmed_subject'    => 'Quiz confermato: iscrizioni aperte',
    'quiz_confirmed_mail_title' => 'Quiz confermato',
    'quiz_confirmed_mail_body'  => 'il quiz **:title** è stato confermato e le iscrizioni sono ora aperte.',
    'quiz_confirmed_mail_cta'   => 'Iscriviti ora',
    'quiz_confirmed_db_title'   => 'Quiz confermato',
    'quiz_confirmed_db_body'    => 'Il quiz «:title» è stato confermato.',

    // Esame completato (notifica ad admin)
    'exam_completed_subject'  => 'Esame completato: :name',
    'exam_completed_db_title' => 'Esame completato',

    // Badge guadagnato
    'badge_db_title'    => 'Hai guadagnato un badge: :name',
    'badge_push_title'  => 'Nuovo badge: :name',
    'badge_push_body'   => 'Hai guadagnato un nuovo badge!',
    'badge_push_action' => 'Vedi badge',

    // Ripasso spaziato
    'sr_push_title'      => 'Ripasso intelligente',
    'sr_push_body_one'   => 'Hai 1 domanda in scadenza oggi — dedicale 2 minuti!',
    'sr_push_body_many'  => 'Hai :count domande in scadenza oggi — dedicaci qualche minuto!',
    'sr_push_action'     => 'Inizia il ripasso',

    // Ruolo aggiornato
    'role_updated_subject'  => 'Aggiornamento del tuo ruolo',
    'role_updated_db_title' => 'Ruolo aggiornato',

    // Anagrafica modificata
    'anagrafica_modified_subject'  => 'Dati anagrafici modificati',
    'anagrafica_modified_db_title' => 'Dati anagrafici modificati',

    // Nuova iscrizione (admin)
    'new_enrollment_subject'   => 'Nuova richiesta di iscrizione',
    'new_enrollment_db_title'  => 'Nuova iscrizione',
    'new_enrollment_db_body'   => ':name ha richiesto l\'iscrizione al quiz «:title».',
];
