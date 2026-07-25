<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Data master depo dari database logistik (LOG_BO_PROD), read-only.
 * Dipakai untuk menampilkan nama depo, bukan cuma kode-nya.
 */
class LogboDepo extends Model
{
    protected $connection = 'logbo';

    protected $table = 'Depo';

    protected $primaryKey = 'KodeDepo';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = ['*'];

    /**
     * Peta KodeDepo => NamaSingkat untuk sekumpulan kode depo.
     */
    public static function namesByCodes(array $codes): Collection
    {
        $codes = array_values(array_filter(array_unique($codes)));

        if (empty($codes)) {
            return collect();
        }

        return static::whereIn('KodeDepo', $codes)
            ->get(['KodeDepo', 'NamaSingkat'])
            ->pluck('NamaSingkat', 'KodeDepo');
    }
}
