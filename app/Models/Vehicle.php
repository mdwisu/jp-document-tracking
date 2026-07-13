<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mobil_id',
        'kode_mobil',
        'no_polisi',
        'kode_depo',
        'tanggal_jatuh_tempo_stnk',
        'tanggal_jatuh_tempo_kir',
        'tanggal_jatuh_tempo_pajak',
    ];

    protected $casts = [
        'tanggal_jatuh_tempo_stnk'  => 'date',
        'tanggal_jatuh_tempo_kir'   => 'date',
        'tanggal_jatuh_tempo_pajak' => 'date',
    ];

    private const EXPIRY_FIELDS = [
        'tanggal_jatuh_tempo_stnk',
        'tanggal_jatuh_tempo_kir',
        'tanggal_jatuh_tempo_pajak',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Vehicle $vehicle) {
            if (! $vehicle->isForceDeleting()) {
                $vehicle->files()->get()->each->delete();
            }
        });
    }

    public function files(): HasMany
    {
        return $this->hasMany(VehicleFile::class);
    }

    public function fileOfType(string $type): ?VehicleFile
    {
        return $this->files->firstWhere('type', $type);
    }

    public function expiryStatus(string $field): ?string
    {
        $date = $this->$field;

        if (! $date) {
            return null;
        }

        if ($date->isPast()) {
            return 'expired';
        }

        if (now()->diffInDays($date, false) <= 30) {
            return 'soon';
        }

        return 'ok';
    }

    public function worstExpiryStatus(): ?string
    {
        $statuses = collect(self::EXPIRY_FIELDS)->map(fn ($field) => $this->expiryStatus($field));

        if ($statuses->contains('expired')) {
            return 'expired';
        }

        if ($statuses->contains('soon')) {
            return 'soon';
        }

        if ($statuses->contains('ok')) {
            return 'ok';
        }

        return null;
    }
}
