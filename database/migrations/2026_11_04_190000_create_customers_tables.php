<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 20)->index();
            $table->string('document_type', 8)->nullable();
            // Número cifrado en reposo; el hash permite unicidad y búsqueda exacta sin descifrar.
            $table->text('document_number')->nullable();
            $table->string('document_hash', 64)->nullable()->unique();
            $table->timestamp('lgpd_consent_at')->nullable();
            $table->string('locale', 8)->default('pt_BR');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Un único documento de identidad por cliente, cifrado en disco privado.
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('disk', 32);
            $table->string('path');
            $table->string('mime_type', 64);
            $table->unsignedInteger('size_bytes');
            $table->string('original_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consent_at');
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plate', 10)->unique();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('mileage_km')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('customers');
    }
};
