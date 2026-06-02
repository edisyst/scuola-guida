<?php

return [
    /*
     | Abilita o disabilita il 2FA sull'intera piattaforma.
     | Quando false: il middleware non fa enforce, la sezione profilo è nascosta.
     | Quando true (default): admin e editor devono configurare e usare il 2FA.
     */
    'enabled' => env('TWO_FACTOR_ENABLED', true),
];
