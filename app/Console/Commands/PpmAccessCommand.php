<?php

namespace App\Console\Commands;

use App\Models\PartnerApplication;
use App\Models\PayoutRequest;
use App\Models\Requisite;
use App\Models\User;
use App\Models\UserAccessLevel;
use Illuminate\Console\Command;

/**
 * Выдача и снятие ролей сотрудников. Экрана для этого нет (решение
 * владельца, этап 1.3) — только эта команда.
 */
class PpmAccessCommand extends Command
{
    protected $signature = 'ppm:access {email?} {role?} {--revoke} {--list}';
    protected $description = 'Выдать/снять роль сотрудника (superadmin|admin|accountant) или показать список сотрудников';

    private const ROLES = [
        'superadmin' => User::LEVEL_SUPERADMIN,
        'admin' => User::LEVEL_ADMIN,
        'accountant' => User::LEVEL_ACCOUNTANT,
    ];

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listStaff();
        }

        $email = $this->argument('email');
        $role = $this->argument('role');

        if (!$email || !$role) {
            $this->error('Использование: ppm:access {email} {role} [--revoke] или ppm:access --list');
            return 1;
        }

        if (!array_key_exists($role, self::ROLES)) {
            $this->error("Неизвестная роль «{$role}». Доступны: " . implode(', ', array_keys(self::ROLES)));
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Пользователь с email {$email} не найден");
            return 1;
        }

        $levelId = self::ROLES[$role];

        if ($this->option('revoke')) {
            UserAccessLevel::where('user_id', $user->id)
                ->where('access_level_id', $levelId)
                ->delete();
            $this->info("Роль «{$role}» снята с {$email}");
        } else {
            $this->warnIfHasPartnerData($user);

            UserAccessLevel::firstOrCreate([
                'user_id' => $user->id,
                'access_level_id' => $levelId,
            ]);
            $this->info("Роль «{$role}» выдана {$email}");
        }

        $user->load('accessLevels');
        $levels = $user->accessLevels->pluck('access_level_id')->sort()->values()->all();
        $this->info('Текущие уровни доступа: ' . (empty($levels) ? '(нет)' : implode(', ', $levels)));

        return 0;
    }

    private function warnIfHasPartnerData(User $user): void
    {
        $applications = PartnerApplication::where('user_id', $user->id)->count();
        $payouts = PayoutRequest::where('user_id', $user->id)->count();
        $requisites = Requisite::where('user_id', $user->id)->count();

        if ($applications || $payouts || $requisites) {
            $this->warn(
                "Внимание: у {$user->email} есть партнёрские данные — " .
                "заявок: {$applications}, выплат: {$payouts}, реквизитов: {$requisites}"
            );
        }
    }

    private function listStaff(): int
    {
        $staff = User::whereHas('accessLevels')
            ->with('accessLevels')
            ->get()
            ->filter(fn (User $user) => $user->isStaff());

        if ($staff->isEmpty()) {
            $this->info('Сотрудников нет');
            return 0;
        }

        foreach ($staff as $user) {
            $roles = $user->accessLevels
                ->pluck('access_level_id')
                ->map(fn (int $id) => array_search($id, self::ROLES, true) ?: $id)
                ->implode(', ');

            $this->line("{$user->email} — {$roles}");
        }

        return 0;
    }
}
