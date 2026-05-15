<?php

return [

    /*
    | Disco Laravel utilizzato per le immagini (default: 'public').
    | Cambia MEDIA_DISK nel .env per usare S3 o altri driver.
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    | Cartella relativa al disco dove vengono salvate le immagini delle domande.
    | Valori consigliati:
    |   local/test  -> questions/images/test
    |   production  -> questions/images
    */
    'directory' => env('MEDIA_IMAGE_DIR', 'questions/images/test'),

];
