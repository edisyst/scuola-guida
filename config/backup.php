<?php

return [

    'backup' => [
        'name' => env('APP_NAME', 'laravel-backup'),

        'source' => [
            'files' => [
                'include' => [
                    storage_path('app/public'),
                ],

                'exclude' => [
                    storage_path('app/backup-temp'),
                ],

                'follow_links' => false,

                'ignore_unreadable_directories' => false,

                'relative_path' => null,
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 9,

            'filename_prefix' => '',

            'disks' => [
                env('BACKUP_DISK', 'backups'),
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        'tries' => 1,

        'retry_delay' => 0,
    ],

    /*
     * Le notifiche di fallimento backup sono gestite dall'event listener
     * SendBackupFailedNotification che integra il sistema notifiche del progetto
     * (canali database + mail). Le notifiche native di spatie sono disabilitate
     * per evitare duplicati.
     */
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class      => [],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class     => [],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class  => [],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL') ?: env('MAIL_FROM_ADDRESS', 'admin@example.com'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name'    => env('MAIL_FROM_NAME', 'ScuolaGUIDA'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username'    => '',
            'avatar_url'  => '',
        ],
    ],

    'monitor_backups' => [
        [
            'name'  => env('APP_NAME', 'laravel-backup'),
            'disks' => [env('BACKUP_DISK', 'backups')],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class         => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days'               => env('BACKUP_KEEP_ALL_DAYS', 7),
            'keep_daily_backups_for_days'             => env('BACKUP_KEEP_DAILY', 16),
            'keep_weekly_backups_for_weeks'           => env('BACKUP_KEEP_WEEKLY', 8),
            'keep_monthly_backups_for_months'         => env('BACKUP_KEEP_MONTHLY', 4),
            'keep_yearly_backups_for_years'           => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],

        'tries'       => 1,
        'retry_delay' => 0,
    ],

];
