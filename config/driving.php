<?php

/*
 * NOTA (Feature 11.0): I valori di questa configurazione sono stati migrati
 * nella tabella `system_settings` come fonte di verità.
 * Questo file è mantenuto SOLO come fallback per DrivingAttestationService.
 * Leggere i dati della scuola via helper `setting('school.*')`.
 */

return [
    'school_name'    => env('DRIVING_SCHOOL_NAME', 'Scuola Guida'),
    'school_address' => env('DRIVING_SCHOOL_ADDRESS', ''),
    'school_phone'   => env('DRIVING_SCHOOL_PHONE', ''),
    'school_email'   => env('DRIVING_SCHOOL_EMAIL', ''),
    'school_license' => env('DRIVING_SCHOOL_LICENSE', ''), // n. autorizzazione MIT
];
