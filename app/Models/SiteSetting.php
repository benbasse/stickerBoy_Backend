<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static int $cacheTtl = 3600; // 1 heure

    /**
     * Récupérer une valeur de configuration
     */
    public static function get(string $key, $default = null): mixed
    {
        return Cache::remember("site_setting_{$key}", static::$cacheTtl, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Définir une valeur de configuration
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("site_setting_{$key}");
    }

    /**
     * Récupérer plusieurs valeurs de configuration
     */
    public static function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key => $default) {
            if (is_int($key)) {
                $result[$default] = static::get($default);
            } else {
                $result[$key] = static::get($key, $default);
            }
        }
        return $result;
    }

    /**
     * Définir plusieurs valeurs de configuration
     */
    public static function setMultiple(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    /**
     * Vider le cache pour une clé
     */
    public static function clearCache(string $key): void
    {
        Cache::forget("site_setting_{$key}");
    }

    /**
     * Vider tout le cache des settings
     */
    public static function clearAllCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("site_setting_{$setting->key}");
        }
    }
}
