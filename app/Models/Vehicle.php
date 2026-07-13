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
    ];

    private const EXPIRY_TYPES = ['stnk', 'kir', 'pajak'];

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

    /**
     * Baris VehicleFile terbaru untuk jenis dokumen tertentu (versi yang berlaku saat ini).
     */
    public function currentFileOfType(string $type): ?VehicleFile
    {
        return $this->files
            ->where('type', $type)
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * Semua versi dokumen jenis tertentu, terbaru duluan (untuk tampilan riwayat).
     */
    public function historyOfType(string $type)
    {
        return $this->files
            ->where('type', $type)
            ->sortByDesc('created_at')
            ->values();
    }

    public function expiryStatus(string $type): ?string
    {
        $date = $this->currentFileOfType($type)?->expiry_date;

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
        $statuses = collect(self::EXPIRY_TYPES)->map(fn ($type) => $this->expiryStatus($type));

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
