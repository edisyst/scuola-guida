<?php

namespace App\Http\Controllers;

use App\Models\LicenseType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (!auth()->check() && !feature('guest_homepage_enabled')) {
            return redirect()->route('login');
        }

        if (auth()->check()) {
            $user = auth()->user();

            if ($user->isAdmin()) {
                return redirect()->route('admin.stats');
            }

            if ($user->isEditor()) {
                return redirect()->route('editor.dashboard');
            }

            return redirect()->route('dashboard');
        }

        $stats = [
            'quiz_count'           => Quiz::count(),
            'question_count'       => Question::count(),
            'license_types_count'  => LicenseType::active()->count(),
        ];

        $licenseTypes = LicenseType::active()->orderBy('sort_order')->get();

        return view('guest.home', compact('stats', 'licenseTypes'));
    }
}
