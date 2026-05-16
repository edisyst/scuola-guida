<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitRegistrationRequest;
use App\Services\UserRegistrationService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class RegistrationController extends Controller
{
    public function __construct(private UserRegistrationService $service) {}

    /**
     * Invio (o reinvio) dei dati anagrafici da parte del viewer.
     */
    public function submit(SubmitRegistrationRequest $request): RedirectResponse
    {
        try {
            $this->service->submit(
                $request->user(),
                $request->safe()->except('id_document'),
                $request->file('id_document'),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Richiesta di iscrizione inviata. Riceverai una notifica dopo la revisione dell\'amministratore.');
    }
}
