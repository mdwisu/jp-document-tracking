<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_settings', function (Blueprint $table) {
            $table->id();
            $table->string('password_hash');
            $table->string('create_token')->unique();
            $table->timestamps();
        });

        \DB::table('vehicle_settings')->insert([
            'password_hash' => Hash::make(env('VEHICLE_MODULE_INITIAL_PASSWORD', 'gantipassword')),
            'create_token'  => Str::random(40),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_settings');
    }
};
