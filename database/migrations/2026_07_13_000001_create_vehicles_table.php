<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            // MobilId dari database logistik (LOG_BO_PROD.dbo.Mobil), bukan foreign key
            // lintas-database — hanya referensi.
            $table->uuid('mobil_id')->unique();
            $table->string('kode_mobil');
            $table->string('no_polisi');
            $table->string('kode_depo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
