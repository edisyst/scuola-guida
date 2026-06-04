<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchLocaleRequest;

class LocaleController extends Controller
{
    public function switch(SwitchLocaleRequest $request): \Illuminate\Http\RedirectResponse
    {
        $request->session()->put('app_locale', $request->validated('locale'));

        return redirect()->back()->with('info', __('menu.locale_changed'));
    }
}
