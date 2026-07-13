<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['vehicle_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_files');
    }
};
