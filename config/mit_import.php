<?php

return [
    /*
     | Se il file ha una riga di intestazione, imposta has_header_row = true
     | e usa i nomi header come chiave nei 'columns' qui sotto.
     | Se il file non ha intestazione, usa indici numerici (0, 1, 2...).
     */
    'has_header_row' => true,

    /*
     | Mappatura colonne foglio MIT → campi applicazione.
     | Valori: nome colonna Excel (se has_header_row = true) oppure indice 0-based.
     | Modifica questo file per adattarlo al formato del file MIT ricevuto
     | senza toccare il codice PHP.
     */
    'columns' => [
        'mit_code'   => 'Codice',    // ID univoco MIT — usato per deduplicazione
        'topic_code' => 'Argomento', // intero 1-25
        'question'   => 'Domanda',   // testo della domanda
        'answer'     => 'Risposta',  // "V"/"F", "VERO"/"FALSO", "1"/"0", "TRUE"/"FALSE"
        'image_code' => 'Immagine',  // nome file immagine, nullable
    ],

    /*
     | Valori accettati come risposta VERA. Confronto case-insensitive.
     | Tutto il resto viene interpretato come FALSO.
     */
    'true_values' => ['v', 'vero', '1', 'true', 's', 'si', 'sì'],

    /*
     | Mappatura argomento MIT (1-25) → nome categoria nel DB.
     | Il service cerca la categoria con str_contains (case-insensitive).
     | Se la categoria non viene trovata, la domanda viene saltata e loggata.
     | Adatta i nomi a quelli reali presenti nel DB.
     */
    'topic_map' => [
        1  => 'Definizioni generali',
        2  => 'Segnali di pericolo',
        3  => 'Segnali di divieto',
        4  => 'Segnali di obbligo',
        5  => 'Segnali di precedenza',
        6  => 'Segnaletica orizzontale',
        7  => 'Semafori',
        8  => 'Norme di comportamento',
        9  => 'Precedenze',
        10 => 'Velocità',
        11 => 'Sorpasso',
        12 => 'Distanza di sicurezza',
        13 => 'Uso delle corsie',
        14 => 'Sosta e fermata',
        15 => 'Autostrade',
        16 => 'Carico e sagoma',
        17 => 'Trasporto persone',
        18 => 'Luci e dispositivi',
        19 => 'Cinture e dispositivi sicurezza',
        20 => 'Patenti e documenti',
        21 => 'Incidenti stradali',
        22 => 'Alcol droga e farmaci',
        23 => 'Manutenzione veicolo',
        24 => 'Assicurazione',
        25 => 'Tutela ambiente',
    ],

    /*
     | Numero massimo di righe per import. Il listato MIT completo ha ~7.184 righe.
     */
    'max_rows' => 10000,

    /*
     | Dimensione massima file in KB per la validazione del Form Request.
     */
    'max_file_size_kb' => 10240,
];
