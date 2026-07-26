<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Http\Resources\GeneralSettingResource;
use App\Models\GeneralSetting;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
    public function __construct(private readonly SettingService $settingService) {}

    /**
     * Show the company-wide general settings page (name, currency, logo).
     */
    public function edit(): Response
    {
        return Inertia::render('settings/General', [
            'setting' => GeneralSettingResource::make(GeneralSetting::current()),
        ]);
    }

    /**
     * Update the company-wide general settings.
     */
    public function update(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $this->settingService->updateGeneral(
            $request->safe()->only(['company_name', 'currency_symbol']),
            $request->file('logo'),
        );

        return to_route('general.edit')->with('success', 'Settings updated successfully.');
    }
}
