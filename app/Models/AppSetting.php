<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function value(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return match (true) {
            is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOL),
            is_int($default) => (int) $value,
            is_float($default) => (float) $value,
            default => $value,
        };
    }
}
