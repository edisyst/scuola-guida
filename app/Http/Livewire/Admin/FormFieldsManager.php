<?php

namespace App\Http\Livewire\Admin;

use App\Services\SettingService;
use Livewire\Component;

class FormFieldsManager extends Component
{
    public string $activeTab = 'registration';

    public array $regFields   = [];
    public array $enrollFields = [];

    public function mount(): void
    {
        $this->loadFields();
    }

    public function toggle(string $flow, int $index, string $prop): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if (! in_array($prop, ['enabled', 'required'], true)) {
            return;
        }

        $target = $flow === 'registration' ? 'regFields' : 'enrollFields';

        $this->$target[$index][$prop] = ! $this->$target[$index][$prop];

        if ($prop === 'enabled' && ! $this->$target[$index]['enabled']) {
            $this->$target[$index]['required'] = false;
        }

        if ($prop === 'required' && ! $this->$target[$index]['enabled']) {
            $this->$target[$index]['required'] = false;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        app(SettingService::class)->setMany([
            'forms.registration_fields' => json_encode(array_values($this->regFields)),
            'forms.enrollment_fields'   => json_encode(array_values($this->enrollFields)),
        ]);

        session()->flash('success', __('flash.form_fields_saved'));
    }

    public function render()
    {
        return view('livewire.admin.form-fields-manager');
    }

    private function loadFields(): void
    {
        $this->regFields   = json_decode(setting('forms.registration_fields', '[]'), true) ?: [];
        $this->enrollFields = json_decode(setting('forms.enrollment_fields', '[]'), true) ?: [];
    }
}
