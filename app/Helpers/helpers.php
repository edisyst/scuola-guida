<?php

use Illuminate\Support\Facades\Cache;

if (!function_exists('clearAdminBadgesCache')) {

    function clearAdminBadgesCache(): void
    {
        Cache::forget('admin_badges');
    }
}

if (!function_exists('clearDashboardKpiCache')) {

    function clearDashboardKpiCache(): void
    {
        Cache::forget('dashboard_kpi');
    }
}
