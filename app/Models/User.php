<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // app/Models/User.php
    public function updateAvatar($file)
    {
        \Log::info('Updating avatar for user: ' . $this->id);

        try {
            // Проверяем что файл действительно загружен
            if (!$file->isValid()) {
                throw new \Exception('Invalid file upload');
            }

            // Удаляем старый аватар только если он локальный
            if ($this->avatar && !filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                $oldPath = str_replace('/storage/', '', $this->avatar);
                \Log::info('Deleting old avatar: ' . $oldPath);
                Storage::disk('public')->delete($oldPath);
            }

            // Сохраняем новый файл
            $path = $file->store('avatars', 'public');
            \Log::info('New avatar stored at: ' . $path);

            $this->avatar = $path;
            $this->save();

            return [
                'avatar_url' => Storage::url($path),
                'user' => $this->fresh()
            ];
        } catch (\Exception $e) {
            \Log::error('Avatar update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Метод для получения URL аватара
    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) return null;

        return filter_var($this->avatar, FILTER_VALIDATE_URL)
            ? $this->avatar
            : Storage::url($this->avatar);
    }
}
