<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('tanggal_jatuh_tempo_stnk')->nullable();
            $table->date('tanggal_jatuh_tempo_kir')->nullable();
            $table->date('tanggal_jatuh_tempo_pajak')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['tanggal_jatuh_tempo_stnk', 'tanggal_jatuh_tempo_kir', 'tanggal_jatuh_tempo_pajak']);
        });
    }
};
