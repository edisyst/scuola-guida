<?php

return [
    /*
     | Formato esame patente B vigente dal 20/12/2021 (DM MIT 27/10/2021).
     | 30 domande vero/falso, 20 minuti, max 3 errori.
     */
    'questions'  => 30,
    'time_limit' => 20,     // minuti
    'max_errors' => 3,

    /*
     | Distribuzione per categoria.
     | I nomi devono corrispondere esattamente al campo `name` nella tabella categories.
     | Se una categoria non viene trovata nel DB, viene saltata con Log::warning().
     | 12 categorie × 2 domande + 6 categorie × 1 domanda = 30 domande totali.
     */
    'distribution' => [
        'Segnali di pericolo'                                           => 2,
        'Segnali di precedenza'                                         => 2,
        'Segnali di divieto'                                            => 2,
        'Segnali di obbligo'                                            => 2,
        'Semafori, vigile e strisce'                                    => 2,
        'Velocità e distanze'                                           => 2,
        'Posizione veicoli e manovre'                                   => 2,
        'Norme sulla precedenza'                                        => 2,
        'Sorpasso'                                                      => 2,
        'Arresto Fermata e Sosta, uso del triangolo e carico'           => 2,
        'Norme varie'                                                   => 2,
        'Incidenti, Assicurazioni e Primo soccorso'                     => 2,
        // Categorie integrative (1 domanda)
        'Veicoli e Strade'                                              => 1,
        'Segnali di indicazione'                                        => 1,
        'Segnali temporanei e complementari e pannelli integrativi'     => 1,
        'Luci, specchietti, autostrade e strade extraurbane principali' => 1,
        'Veicoli e inquinamento'                                        => 1,
        'Patenti'                                                       => 1,
    ],
];
