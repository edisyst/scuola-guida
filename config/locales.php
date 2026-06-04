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
        'es' => [
            'label' => 'Español',
            'flag'  => 'es.svg',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lingue d'esame (traduzione testo domande — Feature 7.1)
    |--------------------------------------------------------------------------
    | Concetto distinto da 'supported': qui non si traduce l'interfaccia ma solo
    | il TESTO delle domande, per l'esame teorico MIT (accessibilità linguistica).
    | 'it' è la fonte di verità (testo originale); le altre sono layer opzionali
    | con fallback garantito all'italiano. Aggiungere qui una nuova lingua è
    | l'unico punto da toccare per renderla disponibile a editor e viewer.
    */
    'exam' => [
        'it' => 'Italiano',
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'es' => 'Español',
    ],

];
