<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tutoriales guiados ya vistos por cada persona (no se vuelven a abrir solos).
        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('completed_tours')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('completed_tours'));
    }
};
