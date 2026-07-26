<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    /**
     * Update the singleton general settings row, replacing the stored logo
     * file (and deleting the old one) only when a new file is uploaded.
     */
    public function updateGeneral(array $data, ?UploadedFile $logo): GeneralSetting
    {
        $setting = GeneralSetting::current();

        if ($logo) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $data['logo_path'] = $logo->store('logos', 'public');
        }

        $setting->update($data);

        return $setting;
    }
}
