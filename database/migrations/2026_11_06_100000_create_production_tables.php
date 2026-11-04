<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Etapas del tablero de producción (editables en Ajustes). Cada etapa pertenece a un estado de la orden.
        Schema::create('workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');
            $table->string('status', 16);
            $table->string('color_hex', 7)->default('#0B8FD9');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('visible_to_customer')->default(true);
            $table->boolean('notifies_customer')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('quote_id')->nullable()->index(); // Fase 5
            $table->string('status', 16)->default('received');
            $table->foreignId('workflow_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('stage_changed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('materials_consumed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('board_position')->default(0);
            $table->timestamps();

            $table->index(['status', 'due_at']);
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_variant_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('unit', 8);
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->unsignedBigInteger('unit_cost_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 10, 3)->default(0); // material a consumir (unidad del ítem de inventario)
            $table->decimal('hours', 6, 2)->default(0);
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->timestamps();
        });

        Schema::create('work_order_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16);
            $table->text('note')->nullable();
            $table->jsonb('photos')->default('[]');
            $table->boolean('visible_to_customer')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->decimal('quantity', 12, 3); // con signo: + entrada, − consumo
            $table->decimal('stock_after', 12, 3);
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('mileage_km')->nullable();
            $table->jsonb('marks_status')->default('{}'); // id de marca del check-in => repaired|unchanged
            $table->text('notes')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at');
            $table->string('report_disk', 32)->nullable();
            $table->string('report_path')->nullable();
            $table->timestamp('report_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('warranty_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('months');
            $table->jsonb('conditions')->nullable();
            $table->timestamps();
        });

        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->text('conditions')->nullable();
            $table->timestamps();
        });

        // Siluetas por carrocería con zonas SVG (antes en código).
        Schema::create('vehicle_silhouettes', function (Blueprint $table) {
            $table->id();
            $table->string('body_type', 32)->unique();
            $table->jsonb('name');
            $table->string('view_box', 32)->default('0 0 200 400');
            $table->text('outline');
            $table->jsonb('zones');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Envíos de documentos (actas de recepción y de entrega), polimórfico.
        Schema::create('report_sends', function (Blueprint $table) {
            $table->id();
            $table->morphs('sendable');
            $table->string('channel', 16);
            $table->string('recipient');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        DB::statement("INSERT INTO report_sends (sendable_type, sendable_id, channel, recipient, sent_by, sent_at, created_at, updated_at)
            SELECT 'App\\Models\\Inspection', inspection_id, channel, recipient, sent_by, sent_at, created_at, updated_at FROM inspection_report_sends");
        Schema::drop('inspection_report_sends');

        // Antirrepetición de alertas programadas: una alerta por clave.
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type', 40);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['alert_logs', 'report_sends', 'vehicle_silhouettes', 'warranties', 'warranty_terms', 'deliveries', 'inventory_movements', 'work_order_updates', 'work_order_items', 'inventory_items', 'work_orders', 'workflow_stages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
