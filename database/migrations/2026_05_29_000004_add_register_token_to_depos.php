<?php

use App\Models\Depo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depos', function (Blueprint $table) {
            $table->string('register_token', 64)->nullable()->after('password');
        });

        Depo::whereNull('register_token')->get()->each(function (Depo $depo) {
            $depo->update(['register_token' => Str::random(40)]);
        });

        Schema::table('depos', function (Blueprint $table) {
            $table->string('register_token', 64)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('depos', function (Blueprint $table) {
            $table->dropUnique(['register_token']);
            $table->dropColumn('register_token');
        });
    }
};
