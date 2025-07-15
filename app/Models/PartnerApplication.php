<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerApplication extends Model
{
    protected $fillable = [
        'user_id',
        'responsible_user_id',
        'full_name',
        'phone',
        'email',
        'cooperation_type_id',
        'partner_type_id',
        'status_id',
        'company_name',
        'experience',
        'comment',
    ];

    // Автор заявки
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ответственный
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    // Тип сотрудничества из хелпера
    public function getCooperationTypeAttribute(): ?array
    {
        $settings = \App\Helpers\Partners::getSettings();
        return collect($settings['cooperation_types'])->firstWhere('id', $this->cooperation_type_id);
    }

    // Тип партнёра из хелпера
    public function getPartnerTypeAttribute(): ?array
    {
        $settings = \App\Helpers\Partners::getSettings();
        return collect($settings['partner_types'])->firstWhere('id', $this->partner_type_id);
    }

    // Название статуса
    public function getStatusNameAttribute(): string
    {
        return match ($this->status_id) {
            0 => 'new',
            1 => 'in_progress',
            2 => 'accepted',
            3 => 'rejected',
            default => 'unknown',
        };
    }
}
