<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data master mobil dari database logistik (LOG_BO_PROD), read-only.
 * Aplikasi ini tidak pernah menulis ke tabel ini.
 */
class Mobil extends Model
{
    protected $connection = 'logbo';

    protected $table = 'Mobil';

    protected $primaryKey = 'MobilId';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = ['*'];
}
