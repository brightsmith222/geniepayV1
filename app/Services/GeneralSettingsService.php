<?php

namespace App\Services;

use App\Models\GeneralSettings;

class GeneralSettingsService
{
    /**
     * Check if a specific setting is enabled.
     *
     * @param string $name
     * @return bool
     */
    public function isSettingEnabled(string $name): bool
    {
        $setting = GeneralSettings::where('name', $name)->first();
        return $setting && $setting->is_enabled;
    }

    /**
     * Get the value of a specific setting.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getSettingValue(string $name, $default = null)
    {
        $setting = GeneralSettings::where('name', $name)->first();
        return $setting ? $setting->value : $default;
    }
}