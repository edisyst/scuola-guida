<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class SettingService
{
    private const TTL = 3600;
    private const PREFIX = 'settings:';

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $cached = Redis::get(self::PREFIX . $key);
            if ($cached !== null) {
                return $cached === '__null__' ? null : $cached;
            }
        } catch (Throwable) {
            // Redis down — read from DB directly
            return $this->fromDb($key, $default);
        }

        $value = $this->fromDb($key, $default);

        try {
            Redis::setex(self::PREFIX . $key, self::TTL, $value ?? '__null__');
        } catch (Throwable) {
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        SystemSetting::where('key', $key)->update(['value' => $value]);

        try {
            Redis::del(self::PREFIX . $key);
        } catch (Throwable) {
        }

        Log::info("Setting updated: {$key}");
    }

    public function getGroup(string $group): Collection
    {
        return SystemSetting::where('group', $group)->get();
    }

    public function all(): Collection
    {
        return SystemSetting::all();
    }

    public function setMany(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);

                try {
                    Redis::del(self::PREFIX . $key);
                } catch (Throwable) {
                }
            }
        });

        Log::info('Settings bulk updated', ['keys' => array_keys($data)]);
    }

    private function fromDb(string $key, mixed $default): mixed
    {
        try {
            $setting = SystemSetting::where('key', $key)->first();

            return $setting ? $setting->getCastedValue() : $default;
        } catch (Throwable) {
            return $default;
        }
    }
}
