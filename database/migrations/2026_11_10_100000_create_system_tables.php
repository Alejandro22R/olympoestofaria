<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Último resultado de cada chequeo del sistema (respaldo, prueba de restauración, planificador…).
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->boolean('ok');
            $table->text('message')->nullable();
            $table->jsonb('details')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        // Registro de consentimientos LGPD (quién, qué, cuándo, desde dónde). Nunca se edita.
        Schema::create('consent_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);     // privacy | contact | document
            $table->boolean('granted');
            $table->string('source', 32);   // portal | checkin | panel | email_link
            $table->string('ip_hash', 64)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['customer_id', 'created_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('anonymized_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('anonymized_at'));
        Schema::dropIfExists('consent_events');
        Schema::dropIfExists('health_checks');
    }
};
