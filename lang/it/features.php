<?php

return [
    'page_title'                => 'Feature Toggle',
    'section_platform'          => 'Gestite dalla piattaforma',
    'section_platform_desc'     => 'Attiva o disattiva funzionalità a caldo. La modifica è immediata e reversibile.',
    'section_config'            => 'Gestite da configurazione',
    'section_config_desc'       => 'Questi flag sono controllati da variabili d\'ambiente o file di configurazione. Non modificarli da qui.',
    'flag'                      => 'Flag',
    'current_value'             => 'Valore attuale',
    'hint'                      => 'Come modificare',
    'enabled'                   => 'Attivo',
    'disabled'                  => 'Disattivo',
    'toggled_on'                => 'Funzionalità attivata.',
    'toggled_off'               => 'Funzionalità disattivata.',

    // Labels dei toggle DB-gestiti
    'gamification_enabled'       => 'Gamification (badge e streak)',
    'gamification_enabled_desc'  => 'Mostra o nasconde badge, streak e la sezione progressi nella dashboard.',
    'web_push_enabled'           => 'Notifiche Web Push',
    'web_push_enabled_desc'      => 'Abilita la sottoscrizione push e l\'invio di promemoria ripasso SM-2.',
    'guest_homepage_enabled'     => 'Homepage pubblica',
    'guest_homepage_enabled_desc'=> 'Se disattivata, la root "/" reindirizza direttamente al login.',
    'exam_translations_enabled'  => 'Selezione lingua interfaccia',
    'exam_translations_enabled_desc' => 'Mostra o nasconde il menu di selezione lingua nell\'interfaccia.',
    'driving_practice_enabled'   => 'Modulo guide pratiche',
    'driving_practice_enabled_desc' => 'Abilita la gestione dei moduli e delle sessioni di guida pratica (Feature 9.x).',
    'eu_categories_visible'      => 'Categorie EU in studio',
    'eu_categories_visible_desc' => 'Mostra o nasconde le categorie EU direttiva nelle schermate di studio.',
    'study_content_enabled'      => 'Contenuti formativi (StudyContent)',
    'study_content_enabled_desc' => 'Abilita il visualizzatore di contenuti formativi collegati a categorie e moduli.',

    // Hint per i flag config-gestiti
    'hint_two_factor'  => 'Sicurezza: modificabile solo da <code>.env</code> (chiave <code>TWO_FACTOR_ENABLED</code>). Cambia il valore e lancia <code>php artisan config:clear</code>.',
    'hint_messaging'   => 'Richiede credenziali Twilio valide. Imposta in <code>.env</code> (chiave <code>MESSAGING_ENABLED</code>) e svuota la config.',
    'hint_cache'       => 'Master switch cache. Disattivare solo per debug; richiede <code>config:clear</code>. Chiave <code>.env</code>: <code>CACHE_ENABLED</code>.',
    'hint_debug'       => 'Mai <code>true</code> in produzione. Modificabile solo da <code>.env</code> (chiave <code>APP_DEBUG</code>).',
    'hint_queue'       => 'Cambiare il backend code richiede riavvio dei worker. Chiave <code>.env</code>: <code>QUEUE_CONNECTION</code>.',
    'hint_session'     => 'Cambiare il driver sessioni disconnette gli utenti attivi. Chiave <code>.env</code>: <code>SESSION_DRIVER</code>.',
    'hint_simulator_questions' => 'Numero domande per esame. Modificabile in <code>config/simulator.php</code>. Default: 30.',
    'hint_simulator_time_limit' => 'Tempo limite in minuti per l\'esame. Modificabile in <code>config/simulator.php</code>. Default: 20.',
    'hint_simulator_max_errors' => 'Errori massimi consentiti. Modificabile in <code>config/simulator.php</code>. Default: 3.',
];
