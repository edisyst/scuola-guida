<?php

return [
    'page_title'                => 'Feature Toggle',
    'section_platform'          => 'Managed by the platform',
    'section_platform_desc'     => 'Enable or disable features at runtime. Changes are immediate and reversible.',
    'section_config'            => 'Managed by configuration',
    'section_config_desc'       => 'These flags are controlled by environment variables or configuration files. Do not modify them here.',
    'flag'                      => 'Flag',
    'current_value'             => 'Current value',
    'hint'                      => 'How to modify',
    'enabled'                   => 'Active',
    'disabled'                  => 'Inactive',
    'toggled_on'                => 'Feature enabled.',
    'toggled_off'               => 'Feature disabled.',

    // Labels for DB-managed toggles
    'gamification_enabled'       => 'Gamification (badges & streak)',
    'gamification_enabled_desc'  => 'Shows or hides badges, streak, and the progress section in the dashboard.',
    'web_push_enabled'           => 'Web Push Notifications',
    'web_push_enabled_desc'      => 'Enables push subscription and SM-2 review reminder delivery.',
    'guest_homepage_enabled'     => 'Public homepage',
    'guest_homepage_enabled_desc'=> 'If disabled, the root "/" redirects directly to login.',
    'exam_translations_enabled'  => 'Interface language selector',
    'exam_translations_enabled_desc' => 'Shows or hides the language selection menu in the interface.',
    'driving_practice_enabled'   => 'Driving practice module',
    'driving_practice_enabled_desc' => 'Enables driving module and session management (Feature 9.x).',
    'eu_categories_visible'      => 'EU categories in study',
    'eu_categories_visible_desc' => 'Shows or hides EU directive categories in study screens.',
    'study_content_enabled'      => 'Learning content (StudyContent)',
    'study_content_enabled_desc' => 'Enables the learning content viewer linked to categories and modules.',

    // Hints for config-managed flags
    'hint_two_factor'  => 'Security: only editable from <code>.env</code> (key <code>TWO_FACTOR_ENABLED</code>). Change the value and run <code>php artisan config:clear</code>.',
    'hint_messaging'   => 'Requires valid Twilio credentials. Set in <code>.env</code> (key <code>MESSAGING_ENABLED</code>) and clear the config.',
    'hint_cache'       => 'Master cache switch. Disable only for debugging; requires <code>config:clear</code>. <code>.env</code> key: <code>CACHE_ENABLED</code>.',
    'hint_debug'       => 'Never <code>true</code> in production. Only editable from <code>.env</code> (key <code>APP_DEBUG</code>).',
    'hint_queue'       => 'Changing the queue backend requires restarting workers. <code>.env</code> key: <code>QUEUE_CONNECTION</code>.',
    'hint_session'     => 'Changing the session driver disconnects active users. <code>.env</code> key: <code>SESSION_DRIVER</code>.',
];
