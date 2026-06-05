<?php

return [
    'title'    => 'System status',
    'subtitle' => 'Infrastructure',

    // Small boxes
    'last_backup'       => 'Last backup (:age)',
    'last_backup_never' => 'Never run',
    'backup_count'      => ':count backups available',
    'db_size'           => 'Database size',
    'db_detail'         => 'View detail',
    'media_storage'     => 'Media storage (:count files)',
    'goto_media'        => 'Go to Media Manager',
    'disk_free'         => 'Free disk space (:pct%)',
    'disk_total'        => 'Total: :size',

    // Queue card
    'queue_title'         => 'Queue status',
    'queue_no_pending'    => 'No pending jobs',
    'queue_failed_footer' => ':count failed jobs in failed_jobs',
    'queue_inspect'       => 'Inspect',
    'queue_no_failed'     => 'No failed jobs',

    // Backup card
    'backup_title'      => 'Available backups',
    'backup_run_now'    => 'Run now',
    'backup_confirm'    => 'Start a manual backup now?',
    'backup_no_files'   => 'No backups available',
    'backup_total_size' => 'Total backup size:',
    'backup_more'       => '... and :count more backups',

    // DB tables card
    'db_top_tables'  => 'Top 5 tables by size',
    'db_col_table'   => 'Table',
    'db_col_rows'    => 'Rows',
    'db_col_size'    => 'Size',
    'db_unavailable' => 'Data unavailable',

    // Disk card
    'disk_title'       => 'Disk space',
    'disk_used'        => 'Used: :size',
    'disk_free_label'  => 'Free',
    'disk_total_label' => 'Total',

    // Errors card
    'errors_title'       => 'Recent log errors',
    'errors_no_recent'   => 'No recent errors',
    'errors_col_when'    => 'When',
    'errors_col_level'   => 'Level',
    'errors_col_message' => 'Message',

    // Job table
    'jobs_col_job'    => 'Job',
    'jobs_col_queue'  => 'Queue',
    'jobs_col_failed' => 'Failed',
];
