<?php

return [

    /*
    | Disco Laravel utilizzato per le immagini (default: 'public').
    | Cambia MEDIA_DISK nel .env per usare S3 o altri driver.
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    | Cartelle gestite dal Media Manager.
    |   test       -> immagini usate dai seeder in locale
    |   production -> immagini reali utilizzate in produzione
    */
    'directories' => [
        'test'       => 'questions/images/test',
        'production' => 'questions/images/production',
    ],

    /*
    | Cartella attiva: quella in cui finiscono le immagini caricate dal form
    | di creazione/modifica domanda. In locale conviene 'test', in produzione 'production'.
    */
    'active' => env('MEDIA_ACTIVE_DIR', 'test'),

];
