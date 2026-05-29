<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Depo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'password',
        'register_token',
    ];

    protected $hidden = [
        'password',
        'register_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (Depo $depo) {
            if (empty($depo->register_token)) {
                $depo->register_token = Str::random(40);
            }
        });

        // Cascade soft delete ke karyawan di dalamnya
        static::deleting(function (Depo $depo) {
            if (! $depo->isForceDeleting()) {
                $depo->employees()->get()->each->delete();
            }
        });
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
