<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lingua di default e fallback
    |--------------------------------------------------------------------------
    | Deve corrispondere al valore di APP_LOCALE in .env e config/app.php.
    */
    'default' => env('APP_LOCALE', 'it'),

    /*
    |--------------------------------------------------------------------------
    | Lingue supportate dall'interfaccia
    |--------------------------------------------------------------------------
    | Aggiungere qui una nuova entry e creare il relativo file lang/{code}/menu.php.
    | 'flag' è il nome del file SVG in public/images/language_flags/.
    */
    'supported' => [
        'it' => [
            'label' => 'Italiano',
            'flag'  => 'it.svg',
        ],
        'en' => [
            'label' => 'English',
            'flag'  => 'en.svg',
        ],
    ],

];
