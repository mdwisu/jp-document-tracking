<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'depo_id',
        'name',
        'ktp_number',
        'kk_number',
        'address',
        'phone',
        'email',
    ];

    protected static function booted(): void
    {
        // Cascade soft delete ke berkas karyawan
        static::deleting(function (Employee $employee) {
            if (! $employee->isForceDeleting()) {
                $employee->files()->get()->each->delete();
            }
        });
    }

    public function depo(): BelongsTo
    {
        return $this->belongsTo(Depo::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(EmployeeFile::class);
    }

    public function fileOfType(string $type): ?EmployeeFile
    {
        return $this->files->firstWhere('type', $type);
    }
}
