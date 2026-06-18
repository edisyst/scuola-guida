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

if (!function_exists('readableTextColor')) {

    /**
     * Restituisce '#ffffff' o '#212529' a seconda della luminanza relativa
     * dell'hex di sfondo, per garantire contrasto WCAG AA ≥ 4.5:1.
     * Soglia 0.179: punto di equivalenza del contrasto tra bianco e nero.
     */
    function readableTextColor(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // Correzione gamma sRGB
        $toLinear = fn(float $c): float => $c <= 0.03928
            ? $c / 12.92
            : (($c + 0.055) / 1.055) ** 2.4;

        $L = 0.2126 * $toLinear($r)
           + 0.7152 * $toLinear($g)
           + 0.0722 * $toLinear($b);

        return $L > 0.179 ? '#212529' : '#ffffff';
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
