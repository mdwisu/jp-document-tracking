<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VehicleSetting extends Model
{
    protected $fillable = [
        'password_hash',
        'create_token',
    ];

    protected $casts = [
        'password_hash' => 'hashed',
    ];

    public static function current(): self
    {
        return static::first() ?? static::create([
            'password_hash' => Str::random(12),
            'create_token'  => Str::random(40),
        ]);
    }
}
