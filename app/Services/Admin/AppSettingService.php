<?php

namespace App\Services\Admin;

use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;

class AppSettingService
{
    public function allKeyed()
    {
        return AppSetting::orderBy('setting_key')->get()->keyBy('setting_key');
    }

    public function updateMany(array $settings, ?string $actorId = null): void
    {
        DB::transaction(function () use ($settings, $actorId) {
            foreach ($settings as $key => $value) {
                AppSetting::where('setting_key', $key)->update([
                    'setting_value' => is_array($value) ? json_encode($value) : (string) $value,
                    'updated_by' => $actorId,
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
