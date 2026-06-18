<?php

namespace App\Services;

class FeatureToggleService
{
    public const TOGGLES = [
        'gamification_enabled',
        'web_push_enabled',
        'guest_homepage_enabled',
        'exam_translations_enabled',
        'driving_practice_enabled',
        'eu_categories_visible',
        'study_content_enabled',
    ];

    private const CONFIG_MANAGED = [
        'TWO_FACTOR_ENABLED' => [
            'config'   => null,
            'hint_key' => 'features.hint_two_factor',
        ],
        'MESSAGING_ENABLED' => [
            'config'   => null,
            'hint_key' => 'features.hint_messaging',
        ],
        'CACHE_ENABLED' => [
            'config'   => null,
            'hint_key' => 'features.hint_cache',
        ],
        'APP_DEBUG' => [
            'config'   => 'app.debug',
            'hint_key' => 'features.hint_debug',
        ],
        'QUEUE_CONNECTION' => [
            'config'   => 'queue.default',
            'hint_key' => 'features.hint_queue',
        ],
        'SESSION_DRIVER' => [
            'config'   => 'session.driver',
            'hint_key' => 'features.hint_session',
        ],
    ];

    public function isEnabled(string $feature): bool
    {
        return filter_var(setting("features.{$feature}", true), FILTER_VALIDATE_BOOLEAN);
    }

    public function all(): array
    {
        return array_combine(
            self::TOGGLES,
            array_map(fn($t) => $this->isEnabled($t), self::TOGGLES)
        );
    }

    public function configManaged(): array
    {
        $result = [];
        foreach (self::CONFIG_MANAGED as $flag => $meta) {
            $value = $meta['config'] !== null ? config($meta['config']) : env($flag);
            $result[] = [
                'flag'     => $flag,
                'value'    => $value,
                'hint_key' => $meta['hint_key'],
            ];
        }
        return $result;
    }
}
