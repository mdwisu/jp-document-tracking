<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index unik di level DB tidak mengecualikan baris soft-deleted, jadi mobil yang
     * pernah dihapus (soft delete) tidak bisa ditambahkan lagi meski secara aplikasi
     * seharusnya boleh (unique check aplikasi sudah whereNull('deleted_at')).
     * Turunkan jadi index biasa, uniqueness cukup dijaga di level validasi
     * (pola yang sama dipakai untuk employees.ktp_number).
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique('vehicles_mobil_id_unique');
            $table->index('mobil_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['mobil_id']);
            $table->unique('mobil_id');
        });
    }
};
