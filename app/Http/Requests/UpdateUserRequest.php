<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canEditUser() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'          => 'required|string|max:255',
            'email'         => "required|email|unique:users,email,{$userId}",
            'password'      => 'nullable|min:6',
            'role'          => 'required|in:' . implode(',', array_keys(User::ROLES)),
            'permissions'   => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', User::allPermissions()),
        ];
    }
}
