<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Журнал минтов партнёрских купонов через dual-write (Фаза D, этап 3).
 * Живёт в собственной БД ppm. См. миграцию create_minted_coupons_table.
 */
class MintedCoupon extends Model
{
    protected $fillable = [
        'partner_ref',
        'code',
        'kind',
        'value',
        'commission_percent',
        'mint_request_id',
        'joomla_written',
    ];

    protected $casts = [
        'joomla_written' => 'boolean',
    ];
}
