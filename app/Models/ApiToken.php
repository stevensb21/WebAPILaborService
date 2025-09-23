<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'description',
        'last_used_at',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    protected $hidden = [
        'token'
    ];

    /**
     * Создать новый API токен
     */
    public static function createToken($name, $description = null, $expiresAt = null)
    {
        return self::create([
            'name' => $name,
            'token' => Str::random(64),
            'description' => $description,
            'expires_at' => $expiresAt,
            'is_active' => true
        ]);
    }

    /**
     * Проверить, активен ли токен
     */
    public function isActive()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Обновить время последнего использования
     */
    public function updateLastUsed()
    {
        $this->update(['last_used_at' => now()]);
    }
}
