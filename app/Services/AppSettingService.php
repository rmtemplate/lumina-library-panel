<?php

namespace App\Services;

use App\Models\AppSetting;

class AppSettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::query()
            ->where('setting_key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        return $this->castValue($setting->setting_value, $setting->data_type, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getDecimal(string $key, float $default = 0): float
    {
        return (float) $this->get($key, $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    protected function castValue(mixed $value, ?string $type, mixed $default = null): mixed
    {
        if ($value === null) {
            return $default;
        }

        return match ($type) {
            'int', 'integer' => (int) $value,
            'decimal', 'float', 'double' => (float) $value,
            'bool', 'boolean', 'tinyint' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }
}