<?php

use Illuminate\Support\Facades\Cache;

if (!function_exists('clearAdminBadgesCache')) {

    function clearAdminBadgesCache(): void
    {
        Cache::forget('admin_badges');
    }
}
