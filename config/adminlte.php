<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'ScuolaGUIDA',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => true,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '<b>Scuola</b>GUIDA',
    'logo_img' => 'img/logo.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'ScuolaGUIDA Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => true,
        'img' => [
            'path' => 'img/logo.png',
            'alt' => 'ScuolaGUIDA Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'img/logo.png',
            'alt' => 'ScuolaGUIDA',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => 'dashboard',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => 'profile',
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [
        // Navbar items:
        [
            'type'        => 'navbar-search',
            'text'        => 'cerca_placeholder',
            'topnav_right' => true,
            'href'        => '/search',
            'input_name'  => 'q',
            'method'      => 'get',
        ],
        [
            'type' => 'darkmode-widget',
            'topnav_right' => true,
        ],
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],

        // ── MENU UTENTE (dropdown sotto il nome, in alto a destra) ───────────
        // Voci personali/account spostate qui dalla sidebar per alleggerirla.
        // I gate 'can' restano invariati: cambia solo il contenitore, non la
        // visibilità né le funzionalità.
        [
            'text'         => 'profilo',
            'url'          => 'profile',
            'icon'         => 'fas fa-user-circle',
            'topnav_user'  => true,
        ],
        [
            'text'         => 'i_miei_badge',
            'url'          => 'profile/badges',
            'icon'         => 'fas fa-award',
            'key'          => 'profile-badges',
            'can'          => 'exam-participant',
            'topnav_user'  => true,
        ],
        [
            'text'         => 'notifiche',
            'url'          => 'notifications',
            'icon'         => 'far fa-bell',
            'key'          => 'notifications',
            'topnav_user'  => true,
        ],

        // ── DROPDOWN BARRA IN ALTO (sinistra) ────────────────────────────────
        // Sezioni admin-only spostate dalla sidebar alla navbar come menu a
        // tendina. Rotte, key e gate 'can' restano invariati: cambia solo il
        // contenitore, non la visibilità né le funzionalità.
        [
            'text'    => 'iscrizioni',
            'icon'    => 'fas fa-id-card',
            'topnav'  => true,
            'can'     => 'admin-only',
            'submenu' => [
                [
                    'text' => 'iscrizioni_anagrafiche',
                    'url'  => 'admin/registrations',
                    'icon' => 'fas fa-id-card',
                    'can'  => 'admin-only',
                    'key'  => 'registrations',
                ],
                [
                    'text' => 'iscrizioni_quiz',
                    'url'  => 'admin/enrollments',
                    'icon' => 'fas fa-user-check',
                    'can'  => 'admin-only',
                    'key'  => 'enrollments',
                ],
            ],
        ],
        [
            'text'    => 'esiti_statistiche',
            'icon'    => 'fas fa-chart-bar',
            'topnav'  => true,
            'can'     => 'admin-only',
            'submenu' => [
                [
                    'text' => 'esiti_confermati',
                    'url'  => 'admin/confirmed-results',
                    'icon' => 'fas fa-trophy',
                    'can'  => 'admin-only',
                    'key'  => 'confirmed-results',
                ],
                [
                    'text' => 'statistiche',
                    'url'  => 'admin/stats',
                    'icon' => 'fas fa-chart-bar',
                    'can'  => 'admin-only',
                ],
                [
                    'text' => 'report',
                    'url'  => 'admin/reports',
                    'icon' => 'fas fa-chart-pie',
                    'can'  => 'admin-only',
                    'key'  => 'reports',
                ],
            ],
        ],
        [
            'text'    => 'sistema',
            'icon'    => 'fas fa-server',
            'topnav'  => true,
            'can'     => 'admin-only',
            'submenu' => [
                [
                    'text' => 'media_manager',
                    'url'  => 'admin/media',
                    'icon' => 'fas fa-images',
                    'can'  => 'admin-only',
                    'key'  => 'media',
                ],
                [
                    'text' => 'audit_log',
                    'url'  => 'admin/audit-logs',
                    'icon' => 'fas fa-history',
                    'can'  => 'admin-only',
                    'key'  => 'audit',
                ],
                [
                    'text' => 'comandi_utili',
                    'url'  => 'admin/commands',
                    'icon' => 'fas fa-terminal',
                    'can'  => 'admin-only',
                    'key'  => 'commands',
                ],
                [
                    'text' => 'stato_sistema',
                    'url'  => 'admin/health',
                    'icon' => 'fas fa-heartbeat',
                    'can'  => 'admin-only',
                    'key'  => 'health',
                ],
            ],
        ],
        [
            'text'    => 'utenti_ruoli',
            'icon'    => 'fas fa-users-cog',
            'topnav'  => true,
            'can'     => 'admin-only',
            'submenu' => [
                [
                    'text' => 'utenti',
                    'url'  => 'admin/users',
                    'icon' => 'fas fa-users',
                    'can'  => 'manage-users-menu',
                    'key'  => 'users',
                ],
                [
                    'text' => 'ruoli_permessi',
                    'url'  => 'admin/roles',
                    'icon' => 'fas fa-user-shield',
                    'can'  => 'admin-only',
                ],
            ],
        ],

        // Sidebar items:
        [
            'type' => 'sidebar-menu-search',
            'text' => 'search',
        ],

        // ── AREA PERSONALE (tutti i ruoli) ──────────────────────────────────
        ['header' => 'area_personale'],
        [
            'text'  => 'dashboard',
            'url'   => 'dashboard',
            'icon'  => 'fas fa-tachometer-alt',
            'key'   => 'dashboard',
        ],

        // ── STUDIO (allenamento libero, solo viewer/exam-participant) ────────
        ['header' => 'studio', 'can' => 'exam-participant'],
        [
            'text'  => 'modalita_studio',
            'url'   => 'study',
            'icon'  => 'fas fa-graduation-cap',
            'key'   => 'study',
            'can'   => 'exam-participant',
        ],
        [
            'text'  => 'simulatore_esame',
            'url'   => 'simulator',
            'icon'  => 'fas fa-stopwatch',
            'key'   => 'simulator',
            'can'   => 'exam-participant',
        ],
        [
            'text'        => 'domande_salvate',
            'url'         => 'bookmarks',
            'icon'        => 'fas fa-bookmark',
            'key'         => 'bookmarks',
            'can'         => 'exam-participant',
            // TODO: aggiungere label con contatore bookmark dell'utente via View Composer
        ],
        [
            'text' => 'revisione_errori',
            'url'  => 'review-errors',
            'icon' => 'fas fa-exclamation-triangle',
            'key'  => 'review-errors',
            'can'  => 'exam-participant',
        ],
        [
            'text' => 'piano_studio',
            'url'  => 'study-plan',
            'icon' => 'fas fa-route',
            'key'  => 'study-plan',
            'can'  => 'exam-participant',
        ],
        [
            'text' => 'ripasso_intelligente',
            'url'  => 'smart-review',
            'icon' => 'fas fa-brain',
            'key'  => 'smart-review',
            'can'  => 'exam-participant',
        ],

        // ── ESAMI UFFICIALI (viewer partecipa, admin/editor sola lettura) ───
        ['header' => 'esami', 'can' => 'viewer-quiz-area'],
        [
            'text' => 'quiz_disponibili',
            'url'  => 'quiz/confirmed',
            'icon' => 'fas fa-clipboard-check',
            'can'  => 'viewer-quiz-area',
            'key'  => 'quiz-confirmed',
        ],
        [
            'text' => 'calendario_sessioni',
            'url'  => 'calendar',
            'icon' => 'fas fa-calendar-alt',
            'can'  => 'viewer-quiz-area',
            'key'  => 'calendar',
        ],
        [
            'text' => 'le_mie_iscrizioni',
            'url'  => 'quiz/enrollments',
            'icon' => 'fas fa-list-check',
            'can'  => 'exam-participant',
            'key'  => 'quiz-enrollments-mine',
        ],
        [
            'text'  => 'i_miei_tentativi',
            'url'   => 'quiz/attempts',
            'icon'  => 'fas fa-history',
            'can'   => 'exam-participant',
            'key'   => 'quiz-attempts',
        ],

        // ── CATALOGO (admin, editor, viewer) ────────────────────────────────
        ['header' => 'catalogo', 'can' => 'view-admin'],
        [
            'text' => 'produzione_contenuti',
            'url'  => 'editor/dashboard',
            'icon' => 'fas fa-pen-fancy',
            'can'  => 'content-editor',
            'key'  => 'editor-dashboard',
        ],
        [
            'text' => 'categorie',
            'url'  => 'admin/categories',
            'icon' => 'fas fa-tags',
            'can'  => 'view-admin',
            'key'  => 'categories',
        ],
        [
            'text' => 'tipi_patente',
            'url'  => 'admin/license-types',
            'icon' => 'fas fa-id-card',
            'can'  => 'admin-only',
            'key'  => 'license-types',
        ],
        [
            'text' => 'domande',
            'url'  => 'admin/questions',
            'icon' => 'fas fa-question-circle',
            'can'  => 'view-admin',
            'key'  => 'questions',
        ],
        [
            'text'        => 'segnalazioni',
            'url'         => 'admin/question-reports',
            'icon'        => 'fas fa-flag',
            'can'         => 'view-question-reports',
            'key'         => 'question-reports',
        ],

        // ── QUIZ (admin, editor, viewer) ────────────────────────────────────
        ['header' => 'quiz', 'can' => 'view-admin'],
        [
            'text' => 'quizzes',
            'url'  => 'admin/quizzes',
            'icon' => 'fas fa-clipboard-list',
            'can'  => 'view-admin',
            'key'  => 'quizzes',
        ],

        // ── ISCRIZIONI · ESITI & STATISTICHE · SISTEMA · UTENTI & RUOLI ──────
        // Spostate nella barra in alto come dropdown (vedi 'topnav' all'inizio
        // dell'array 'menu'). Rotte e gate invariati.

        // ── ISTRUTTORE (istruttore + admin per supervisione) ─────────────────
        ['header' => 'istruttore', 'can' => 'instructor-area'],
        [
            'text' => 'i_miei_studenti',
            'url'  => 'instructor/students',
            'icon' => 'fas fa-user-graduate',
            'can'  => 'instructor-area',
            'key'  => 'instructor-students',
        ],

        // ── GESTIONE ISTRUTTORI (solo admin) ────────────────────────────────
        [
            'text' => 'gestione_istruttori',
            'url'  => 'admin/instructors',
            'icon' => 'fas fa-chalkboard-teacher',
            'can'  => 'admin-only',
            'key'  => 'instructors',
        ],

        // ── ACCOUNT ──────────────────────────────────────────────────────────
        // Le voci personali (Profilo, I miei badge, Notifiche) sono ora nel
        // menu a tendina sotto il nome utente (vedi 'topnav_user' in alto).
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
