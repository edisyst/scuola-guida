<?php

namespace App\Http\Livewire\Admin;

use App\Services\FeatureToggleService;
use App\Services\SettingService;
use Livewire\Component;

class FeatureToggles extends Component
{
    public array $toggles = [];

    public function mount(): void
    {
        $this->toggles = app(FeatureToggleService::class)->all();
    }

    public function toggle(string $key): void
    {
        abort_unless(
            in_array($key, FeatureToggleService::TOGGLES, true),
            422
        );

        $newValue = !($this->toggles[$key] ?? true);

        app(SettingService::class)->set("features.{$key}", $newValue ? '1' : '0');

        $this->toggles[$key] = $newValue;

        $this->dispatch(
            'feature-notify',
            type: $newValue ? 'success' : 'warning',
            message: $newValue ? __('features.toggled_on') : __('features.toggled_off'),
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.feature-toggles', [
            'configManaged' => app(FeatureToggleService::class)->configManaged(),
        ]);
    }
}
