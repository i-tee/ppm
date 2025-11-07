<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccessLevel extends Model
{
    protected $fillable = [
        'user_id',
        'access_level_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Доступ к данным уровня доступа из конфига
    public function getAccessLevelAttribute(): ?array
    {
        $settings = \App\Helpers\Partners::getSettings();
        return $settings['access_levels'][$this->access_level_id] ?? null;
    }

    /**
     * Быстрая проверка, является ли уровень админским (1 или 2).
     * Используй в scope или коллекциях: $accessLevels->contains(fn($level) => $level->isAdminLevel())
     */
    public function isAdminLevel(): bool
    {
        return in_array($this->access_level_id, [1, 2]);
    }
}