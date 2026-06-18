<?php

use Illuminate\Support\Facades\Cache;

if (!function_exists('setting')) {

    function setting(string $key, mixed $default = null): mixed
    {
        return app(\App\Services\SettingService::class)->get($key, $default);
    }
}

if (!function_exists('feature')) {

    function feature(string $name): bool
    {
        return app(\App\Services\FeatureToggleService::class)->isEnabled($name);
    }
}

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
