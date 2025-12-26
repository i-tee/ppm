<?php

namespace App\Models;

use App\Models\UserAccessLevel;
use App\Models\PartnerApplication;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifyComplateNotification;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\ResetPasswordNotification;
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

    public function partnerApplications()
    {
        return $this->hasMany(PartnerApplication::class);
    }

    public function responsibleApplications()
    {
        return $this->hasMany(PartnerApplication::class, 'responsible_user_id');
    }

    public function accessLevels()
    {
        return $this->hasMany(UserAccessLevel::class);
    }

    public function getEffectiveAccessLevelsAttribute(): array
    {
        if (!$this->relationLoaded('accessLevels')) {
            $this->load('accessLevels');
        }

        $accessLevelIds = $this->accessLevels->pluck('access_level_id')->filter()->unique()->values()->all();

        if (empty($accessLevelIds)) {
            return $this->hasVerifiedEmail() ? [0] : [-1];
        }

        return $accessLevelIds;
    }

    public function hasAccessLevel(int $levelId): bool
    {
        return $this->accessLevels()->where('access_level_id', $levelId)->exists();
    }


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

        try {
            // Проверяем что файл действительно загружен
            if (!$file->isValid()) {
                throw new \Exception('Invalid file upload');
            }

            // Удаляем старый аватар только если он локальный
            if ($this->avatar && !filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                $oldPath = str_replace('/storage/', '', $this->avatar);
                Storage::disk('public')->delete($oldPath);
            }

            // Сохраняем новый файл
            $path = $file->store('avatars', 'public');

            $this->avatar = $path;
            $this->save();

            return [
                'avatar_url' => Storage::url($path),
                'user' => $this->fresh()
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected $appends = ['avatar_url', 'effective_access_levels']; // Добавляем аксессор в JSON

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return asset('storage/avatars/avatar.png'); // файл в public/avatars
        }

        // Если это URL (начинается с http/https)
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        // Для локальных файлов
        return asset('storage/' . ltrim($this->avatar, '/'));
    }

    public function sendVerificationCompleteNotification()
    {
        $this->notify(new VerifyComplateNotification());
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new EmailVerificationNotification());
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Отношение к заявкам на вывод (один пользователь — много заявок)
     */
    public function payoutRequests()
    {
        return $this->hasMany(PayoutRequest::class, 'user_id');
    }

    /**
     * Возвращает первую (самую новую) заявку, требующую загрузки чека от пользователя.
     * Если таких нет — null.
     *
     * @return PayoutRequest|null
     */
    public function pendingTicketPayout(): ?PayoutRequest
    {
        return $this->payoutRequests()
            ->where('status', \App\Models\PayoutRequest::STATUS_PAID_WHAIT_TICKET)
            ->where('is_active', true) // на всякий случай только активные
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Булева проверка: есть ли такая заявка?
     *
     * @return bool
     */
    public function hasPendingTicketPayout(): bool
    {
        return $this->pendingTicketPayout() !== null;
    }
}
