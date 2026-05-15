<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canCreateUser() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'role'          => 'required|in:' . implode(',', array_keys(User::ROLES)),
            'permissions'   => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', User::allPermissions()),
        ];
    }
}
