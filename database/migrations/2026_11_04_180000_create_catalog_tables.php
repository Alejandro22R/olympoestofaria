<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tipos de servicio (asientos, volante, puertas, techo, piso, tinta, reforma…).
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->unsignedBigInteger('base_price_cents')->default(0);
            $table->string('pricing_unit', 16);
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('material_m2', 6, 2)->nullable()->comment('m² de material estimados por unidad');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->string('supplier')->nullable();
            $table->string('origin')->nullable();
            $table->unsignedBigInteger('cost_per_m2_cents')->default(0);
            $table->unsignedBigInteger('price_per_m2_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('material_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->jsonb('name');
            $table->string('color_hex', 7);
            $table->string('image_path')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Catálogo marca/modelo/año con plantilla SVG de interior y zonas tapizables.
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->string('body_type', 32)->nullable();
            $table->string('interior_svg_path')->nullable();
            $table->jsonb('zones')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('material_variants');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('service_types');
    }
};
