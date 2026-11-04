<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogos editables del check-in (listas propias, no JSON dentro de ajustes).
        Schema::create('damage_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->jsonb('name');
            $table->string('color_hex', 7)->default('#D64545');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->jsonb('name');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('mileage_km')->nullable();
            $table->string('fuel_level', 16);
            $table->jsonb('items_present')->default('[]');
            $table->text('observations')->nullable();
            $table->string('silhouette', 32);
            // Sin firma del cliente: se registra el consentimiento LGPD y el envío del acta.
            $table->timestamp('lgpd_consent_at');
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_disk', 32)->nullable();
            $table->string('report_path')->nullable();
            $table->timestamp('report_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->string('zone', 40);
            // Posición relativa en el diagrama (0–100 %).
            $table->decimal('x', 5, 2);
            $table->decimal('y', 5, 2);
            $table->foreignId('damage_type_id')->constrained()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->jsonb('photos')->default('[]');
            $table->timestamps();
        });

        // Registro de envíos del acta: quién, cuándo, por qué canal y a quién.
        Schema::create('inspection_report_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 16);
            $table->string('recipient');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_report_sends');
        Schema::dropIfExists('inspection_marks');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('damage_types');
    }
};
