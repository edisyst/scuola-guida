<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Services\SettingService;
use App\Services\SystemHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function __construct(
        private SettingService $settingService,
        private SystemHealthService $healthService,
    ) {}

    public function health(): Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return response()->view('admin.system.health', [
            'checks' => [
                'database' => $this->healthService->checkDatabase(),
                'redis'    => $this->healthService->checkRedis(),
                'queue'    => $this->healthService->checkQueue(),
                'storage'  => $this->healthService->checkStorage(),
                'mail'     => $this->healthService->checkMail(),
                'twilio'   => $this->healthService->checkTwilio(),
            ],
        ]);
    }

    public function settings(): Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return response()->view('admin.system.settings', [
            'school'     => $this->settingService->getGroup('school'),
            'appearance' => $this->settingService->getGroup('appearance'),
        ]);
    }

    public function updateSettings(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = [
            'school.name'          => $request->input('school_name'),
            'school.tagline'       => $request->input('school_tagline'),
            'school.address'       => $request->input('school_address'),
            'school.phone'         => $request->input('school_phone'),
            'school.email'         => $request->input('school_email'),
            'school.license_number'=> $request->input('school_license_number'),
        ];

        if ($request->input('accent_color')) {
            $data['appearance.accent_color'] = $request->input('accent_color');
        }

        if ($request->hasFile('logo')) {
            $oldPath = $this->settingService->get('school.logo_path');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('school', 'public');
            $data['school.logo_path'] = $path;
        }

        if ($request->hasFile('logo_dark')) {
            $oldPath = $this->settingService->get('school.logo_dark_path');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo_dark')->store('school', 'public');
            $data['school.logo_dark_path'] = $path;
        }

        if ($request->hasFile('carousel_images')) {
            $existing = json_decode($this->settingService->get('school.carousel_images', '[]'), true) ?? [];
            $slots    = max(0, 4 - count($existing));

            foreach (array_slice($request->file('carousel_images'), 0, $slots) as $file) {
                $existing[] = $file->store('school/carousel', 'public');
            }

            $data['school.carousel_images'] = json_encode(array_values($existing));
        }

        $this->settingService->setMany($data);

        return redirect()
            ->route('admin.system.settings')
            ->with('success', __('system.settings_saved'));
    }

    public function deleteCarouselImage(int $index): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $images = json_decode($this->settingService->get('school.carousel_images', '[]'), true) ?? [];

        if (isset($images[$index])) {
            Storage::disk('public')->delete($images[$index]);
            array_splice($images, $index, 1);
            $this->settingService->setMany(['school.carousel_images' => json_encode(array_values($images))]);
        }

        return redirect()
            ->route('admin.system.settings')
            ->with('success', __('system.carousel_image_deleted'));
    }
}
