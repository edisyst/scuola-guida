<?php

return [
    // Stati iscrizione
    'reg_approved'          => 'Sei abilitato a iscriverti agli <strong>esami ufficiali</strong> per la patente.',
    'reg_approved_on'       => 'Approvata il :date',
    'reg_approved_by'       => 'da :name',
    'reg_resubmit_warn'     => "Se modifichi i dati anagrafici e li reinvii, dovrai essere nuovamente abilitato dall'amministratore prima di poter partecipare a nuovi esami.",
    'reg_pending'           => "La tua richiesta è <strong>in attesa di approvazione</strong>.",
    'reg_pending_sent'      => 'Inviata il :date',
    'reg_pending_practice'  => 'Puoi comunque <strong>esercitarti liberamente con i quiz</strong> in attesa della revisione.',
    'reg_rejected'          => "La tua richiesta è stata <strong>rifiutata</strong>.",
    'reg_rejected_reason'   => 'Motivo:',
    'reg_rejected_fix'      => 'Correggi i dati e invia nuovamente la richiesta.',
    'reg_none'              => 'Per iscriverti agli <strong>esami ufficiali</strong> della patente devi prima inviare i tuoi dati anagrafici e attendere l\'approvazione dell\'amministratore.',
    'reg_practice_meanwhile'=> 'Nel frattempo puoi sempre <strong>esercitarti con i quiz</strong> a piacere.',

    // Campi form
    'field_first_name'      => 'Nome',
    'field_last_name'       => 'Cognome',
    'field_address'         => 'Indirizzo di residenza',
    'field_address_ph'      => 'Via, numero civico, città, CAP',
    'field_birth_date'      => 'Data di nascita',
    'field_birth_place'     => 'Luogo di nascita',
    'field_fiscal_code'     => 'Codice fiscale',
    'field_document'        => 'Documento di identità',
    'document_uploaded'     => 'Documento caricato',
    'document_replace'      => 'carica un nuovo file solo se vuoi sostituirlo',
    'document_formats'      => 'Formati ammessi: PDF, JPG, PNG. Dimensione massima 5 MB.',

    // Pulsanti submit
    'submit_first'          => 'Invia richiesta di iscrizione',
    'submit_update_pending' => 'Aggiorna richiesta in attesa',
    'submit_rejected'       => 'Reinvia richiesta',
    'submit_reapprove'      => 'Reinvia dati (richiede nuova approvazione)',

    // Confirm dialogs
    'confirm_first_send'    => "Confermi l'invio dei dati per l'iscrizione agli esami ufficiali?",
    'confirm_reapprove'     => "Reinviando i dati perderai temporaneamente l'abilitazione agli esami fino alla riapprovazione dell'amministratore. Procedere?",

    // TTS / Accessibilità
    'tts_title'             => 'Accessibilità',
    'tts_enabled_label'     => 'Attiva sintesi vocale (TTS)',
    'tts_autoplay_label'    => 'Avvio automatico voce',

    // Scelta patente in studio (Feature 8.1)
    'license_type_title'    => 'Patente in studio',
    'license_type_desc'     => 'Seleziona il tipo di patente per cui stai studiando. Studio, simulatore e revisione verranno filtrati per le domande di questo tipo.',
    'license_type_field_label' => 'Patente',
    'license_type_select'   => 'Scegli una patente...',

    // Pagina profilo — sezioni e titoli
    'page_title'            => 'Profilo',
    'account_subtitle'      => 'Account',
    'my_profile'            => 'Il mio profilo',
    'info_section'          => 'Informazioni profilo',
    'reg_section'           => 'Iscrizione esami ufficiali',
    'password_section'      => 'Aggiorna password',
    'twofa_section'         => 'Autenticazione a due fattori',
    'tts_desc'              => "Attiva la lettura audio automatica delle domande per replicare il supporto DSA previsto dall'esame ministeriale (D.Lgs. 62/2017).",
    'tts_field_label'       => 'Lettura audio delle domande',
    'tts_autoplay_field_label' => 'Avvio automatico ad ogni domanda',
    'save_prefs'            => 'Salva preferenze',
    'push_section'          => 'Notifiche push',
    'push_active'           => 'Attive',
    'push_inactive'         => 'Non attive',
    'push_desc'             => 'Ricevi notifiche native anche a app chiusa (badge guadagnati, approvazione iscrizione, promemoria ripasso SM-2).',
    'push_not_supported'    => 'Il tuo browser non supporta le notifiche push oppure il sito non è servito via HTTPS.',
    'push_subscribe'        => 'Attiva notifiche push',
    'push_subscribing'      => 'Attivazione…',
    'push_unsubscribe'      => 'Disattiva notifiche push',
    'push_unsubscribing'    => 'Disattivazione…',
    'push_permission_denied'=> "Permesso negato. Abilita le notifiche nelle impostazioni del browser.",
    'push_activate_error'   => "Errore durante l'attivazione: ",
    'push_deactivate_error' => "Errore durante la disattivazione: ",
    'gdpr_section'          => 'Portabilità dei dati',
    'gdpr_desc'             => "Scarica un archivio ZIP con tutti i tuoi dati personali in formato JSON (GDPR art. 20 — diritto alla portabilità). Il file include quiz, bookmark, badge, attività e, se caricato, il documento d'identità.",
    'gdpr_download'         => 'Scarica i miei dati',
    'delete_section'        => 'Elimina account',
    'profile_updated'       => 'Profilo aggiornato con successo.',
    'password_updated'      => 'Password aggiornata con successo.',

    // Form aggiornamento profilo base
    'name_label'          => 'Nome',
    'email_label'         => 'Email',
    'email_unverified'    => 'Il tuo indirizzo email non è verificato.',
    'send_verification'   => 'Clicca qui per inviare nuovamente l\'email di verifica.',
    'verification_sent'   => 'Un nuovo link di verifica è stato inviato al tuo indirizzo email.',

    // Form aggiornamento password
    'current_password'    => 'Password attuale',
    'new_password'        => 'Nuova password',
    'confirm_password'    => 'Conferma password',
    'update_password_btn' => 'Aggiorna password',

    // Elimina account
    'delete_account'             => 'Elimina account',
    'delete_account_desc'        => 'Una volta eliminato l\'account, tutte le risorse e i dati saranno cancellati definitivamente. Prima di procedere, scarica eventuali dati che desideri conservare.',
    'delete_account_confirm_title' => 'Vuoi davvero eliminare il tuo account?',
    'delete_account_confirm_desc'  => 'Una volta eliminato l\'account, tutte le risorse e i dati saranno cancellati definitivamente. Inserisci la password per confermare l\'eliminazione.',

    // 2FA
    'twofa_active_since'      => '2FA attivo dal :date.',
    'twofa_disable_btn'       => 'Disabilita 2FA',
    'twofa_regenerate_btn'    => 'Rigenera codici di recupero',
    'twofa_disable_title'     => 'Disabilita autenticazione a due fattori',
    'twofa_disable_desc'      => 'Inserisci la password corrente per confermare la disabilitazione del 2FA.',
    'twofa_regen_title'       => 'Rigenera codici di recupero',
    'twofa_regen_desc'        => 'I codici attuali verranno invalidati. Inserisci la password per procedere.',
    'twofa_regen_btn'         => 'Rigenera codici',
    'twofa_not_enabled'       => 'Il 2FA non è ancora abilitato sul tuo account. È obbligatorio per accedere all\'area admin.',
    'twofa_enable_btn'        => 'Abilita 2FA',
    'twofa_platform_disabled' => 'Il 2FA è attualmente disabilitato sulla piattaforma.',

    // Badge stato iscrizione anagrafica
    'status_approved_badge' => 'Approvata',
    'status_pending_badge'  => 'In attesa di approvazione',
    'status_rejected_badge' => 'Rifiutata',
    'status_none_badge'     => 'Da compilare',
];
