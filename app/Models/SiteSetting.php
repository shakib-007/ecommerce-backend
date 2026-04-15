<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'value', 'group'];

    // Handy static helper: SiteSetting::get('site_logo')
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    // SiteSetting::set('site_logo', 'https://...')
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}