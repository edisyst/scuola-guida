{{--
    Appearance customization (Feature 13.1).
    Injects the configurable CSS variables read from system_settings (group
    `appearance`) and, when a non-system font is selected, the matching Google
    Fonts stylesheet. No hardcoded values — everything comes from setting().
--}}
@php
    $accent      = setting('appearance.accent_color', '#3c8dbc');
    $accentDark  = setting('appearance.accent_color_dark', '#4aa3d4');
    $accentText     = readableTextColor($accent);
    $accentDarkText = readableTextColor($accentDark);
    $fontFamily  = setting('appearance.font_family', 'system');
    $radiusKey   = setting('appearance.border_radius', 'default');

    $fontStacks = [
        'system'    => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        'inter'     => "'Inter', sans-serif",
        'roboto'    => "'Roboto', sans-serif",
        'open-sans' => "'Open Sans', sans-serif",
    ];

    $googleFonts = [
        'inter'     => 'Inter:wght@400;500;600;700',
        'roboto'    => 'Roboto:wght@400;500;700',
        'open-sans' => 'Open+Sans:wght@400;600;700',
    ];

    $radiusValues = [
        'square'  => '0',
        'default' => '.25rem',
        'rounded' => '.5rem',
    ];

    $fontStack = $fontStacks[$fontFamily] ?? $fontStacks['system'];
    $radius    = $radiusValues[$radiusKey] ?? $radiusValues['default'];
@endphp

@if($fontFamily !== 'system' && isset($googleFonts[$fontFamily]))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family={{ $googleFonts[$fontFamily] }}&display=swap">
@endif

<style>
    :root {
        --sg-accent: {{ $accent }};
        --sg-accent-dark: {{ $accentDark }};
        --sg-accent-text: {{ $accentText }};
        --sg-accent-dark-text: {{ $accentDarkText }};
        --sg-font: {{ $fontStack }};
        --sg-radius: {{ $radius }};
    }
    body {
        font-family: var(--sg-font);
    }
    .card, .btn, .form-control, .modal-content, .alert {
        border-radius: var(--sg-radius);
    }
</style>
