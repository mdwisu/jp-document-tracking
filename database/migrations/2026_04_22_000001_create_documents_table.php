<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('document_title')->nullable();
            $table->string('document_author')->nullable();
            $table->string('document_creator')->nullable();
            $table->string('document_producer')->nullable();
            $table->datetime('pdf_created_at')->nullable();
            $table->datetime('pdf_modified_at')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
