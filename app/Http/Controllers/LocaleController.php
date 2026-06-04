<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchLocaleRequest;

class LocaleController extends Controller
{
    public function switch(SwitchLocaleRequest $request): \Illuminate\Http\RedirectResponse
    {
        $locale = $request->validated('locale');

        $request->session()->put('app_locale', $locale);

        // Persiste la preferenza lingua nel profilo utente (Feature 7.1):
        // la bandierina diventa l'unico punto di scelta per il viewer.
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        return redirect()->back()->with('info', __('menu.locale_changed'));
    }
}
