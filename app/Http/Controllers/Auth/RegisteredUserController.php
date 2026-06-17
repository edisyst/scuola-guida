<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\User;
use App\Services\FormFieldService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'extraFields' => app(FormFieldService::class)->getRegistrationFields(),
        ]);
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        $data = [
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ];

        foreach (app(FormFieldService::class)->getRegistrationFields() as $field) {
            if ($request->filled($field['key'])) {
                $data[$field['key']] = $request->input($field['key']);
            }
        }

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
