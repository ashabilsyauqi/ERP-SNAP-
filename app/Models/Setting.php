<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        try {
            $setting = static::where('key', $key)->first();
            if ($setting && !is_null($setting->value) && $setting->value !== '') {
                return $setting->value;
            }
        } catch (\Throwable $e) {
            // Table might not exist yet during migration
        }

        return env(strtoupper($key), $default);
    }

    public static function set(string $key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
