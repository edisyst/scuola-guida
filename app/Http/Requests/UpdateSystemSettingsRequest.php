<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'school_name'          => ['nullable', 'string', 'max:150'],
            'school_tagline'       => ['nullable', 'string', 'max:255'],
            'school_address'       => ['nullable', 'string', 'max:255'],
            'school_phone'         => ['nullable', 'string', 'max:20'],
            'school_email'         => ['nullable', 'email', 'max:150'],
            'school_license_number'=> ['nullable', 'string', 'max:50'],
            'logo'                    => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
            'logo_dark'               => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
            'accent_color'            => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color_dark'       => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family'             => ['nullable', 'string', 'in:system,inter,roboto,open-sans'],
            'border_radius'           => ['nullable', 'string', 'in:square,default,rounded'],
            'sidebar_skin_admin'      => ['nullable', 'string', Rule::in($this->sidebarSkins())],
            'sidebar_skin_editor'     => ['nullable', 'string', Rule::in($this->sidebarSkins())],
            'sidebar_skin_viewer'     => ['nullable', 'string', Rule::in($this->sidebarSkins())],
            'sidebar_skin_instructor' => ['nullable', 'string', Rule::in($this->sidebarSkins())],
            'carousel_images'         => ['nullable', 'array', 'max:4'],
            'carousel_images.*'       => ['file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Valid AdminLTE 3 sidebar skin classes offered in the appearance panel.
     *
     * @return list<string>
     */
    public static function sidebarSkins(): array
    {
        return [
            'sidebar-dark-primary',
            'sidebar-dark-danger',
            'sidebar-dark-success',
            'sidebar-dark-warning',
            'sidebar-dark-info',
            'sidebar-dark-indigo',
            'sidebar-dark-navy',
            'sidebar-light-primary',
            'sidebar-light-danger',
            'sidebar-light-success',
            'sidebar-light-warning',
            'sidebar-light-info',
        ];
    }
}
