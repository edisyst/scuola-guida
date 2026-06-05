<?php

return [
    'title'    => 'Stato sistema',
    'subtitle' => 'Infrastruttura',

    // Small boxes
    'last_backup'       => 'Ultimo backup (:age)',
    'last_backup_never' => 'Mai eseguito',
    'backup_count'      => ':count backup disponibili',
    'db_size'           => 'Dimensione database',
    'db_detail'         => 'Vedi dettaglio',
    'media_storage'     => 'Media storage (:count file)',
    'goto_media'        => 'Vai a Media Manager',
    'disk_free'         => 'Spazio disco libero (:pct%)',
    'disk_total'        => 'Totale: :size',

    // Card code queue
    'queue_title'         => 'Stato code',
    'queue_no_pending'    => 'Nessun job in coda',
    'queue_failed_footer' => ':count job falliti in failed_jobs',
    'queue_inspect'       => 'Ispeziona',
    'queue_no_failed'     => 'Nessun job fallito',

    // Card backup
    'backup_title'    => 'Backup disponibili',
    'backup_run_now'  => 'Esegui ora',
    'backup_confirm'  => 'Avviare un backup manuale ora?',
    'backup_no_files' => 'Nessun backup disponibile',
    'backup_total_size' => 'Spazio totale backup:',
    'backup_more'     => '... e altri :count backup',

    // Card DB tables
    'db_top_tables'  => 'Top 5 tabelle per dimensione',
    'db_col_table'   => 'Tabella',
    'db_col_rows'    => 'Righe',
    'db_col_size'    => 'Dim.',
    'db_unavailable' => 'Dati non disponibili',

    // Card disk
    'disk_title'       => 'Spazio disco',
    'disk_used'        => 'Usato: :size',
    'disk_free_label'  => 'Libero',
    'disk_total_label' => 'Totale',

    // Card errors
    'errors_title'     => 'Ultimi errori dal log',
    'errors_no_recent' => 'Nessun errore recente',
    'errors_col_when'  => 'Quando',
    'errors_col_level' => 'Livello',
    'errors_col_message' => 'Messaggio',

    // Job table
    'jobs_col_job'    => 'Job',
    'jobs_col_queue'  => 'Queue',
    'jobs_col_failed' => 'Fallito',
];
