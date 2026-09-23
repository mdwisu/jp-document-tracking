<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('jabatan')->nullable()->after('tanggal_mulai_kerja');
            $table->string('divisi')->nullable()->after('jabatan');
            $table->string('no_rekening')->nullable()->after('divisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['jabatan', 'divisi', 'no_rekening']);
        });
    }
};
