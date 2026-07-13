<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = ['stnk', 'kir', 'pajak'];

    public function up(): void
    {
        Schema::table('vehicle_files', function (Blueprint $table) {
            $table->date('expiry_date')->nullable();
        });

        Schema::table('vehicle_files', function (Blueprint $table) {
            $table->dropUnique('vehicle_files_vehicle_id_type_unique');
            $table->index(['vehicle_id', 'type']);
        });

        foreach (self::TYPES as $type) {
            $column = "tanggal_jatuh_tempo_{$type}";

            DB::table('vehicles')
                ->whereNotNull($column)
                ->orderBy('id')
                ->each(function ($vehicle) use ($type, $column) {
                    DB::table('vehicle_files')
                        ->where('vehicle_id', $vehicle->id)
                        ->where('type', $type)
                        ->update(['expiry_date' => $vehicle->$column]);
                });
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['tanggal_jatuh_tempo_stnk', 'tanggal_jatuh_tempo_kir', 'tanggal_jatuh_tempo_pajak']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->date('tanggal_jatuh_tempo_stnk')->nullable();
            $table->date('tanggal_jatuh_tempo_kir')->nullable();
            $table->date('tanggal_jatuh_tempo_pajak')->nullable();
        });

        foreach (self::TYPES as $type) {
            $column = "tanggal_jatuh_tempo_{$type}";

            DB::table('vehicle_files')
                ->where('type', $type)
                ->whereNotNull('expiry_date')
                ->orderByDesc('created_at')
                ->get(['vehicle_id', 'expiry_date'])
                ->unique('vehicle_id')
                ->each(function ($file) use ($column) {
                    DB::table('vehicles')
                        ->where('id', $file->vehicle_id)
                        ->update([$column => $file->expiry_date]);
                });
        }

        Schema::table('vehicle_files', function (Blueprint $table) {
            $table->dropIndex(['vehicle_id', 'type']);
            $table->dropColumn('expiry_date');
        });

        Schema::table('vehicle_files', function (Blueprint $table) {
            $table->unique(['vehicle_id', 'type']);
        });
    }
};
