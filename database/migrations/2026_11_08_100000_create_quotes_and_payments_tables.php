<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cotización final (precio confirmado por el taller). Cada revisión es una versión nueva.
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('previous_quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 16)->default('draft');
            $table->string('vehicle_label')->nullable();
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('deposit_cents')->default(0);
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->string('report_disk', 32)->nullable();
            $table->string('report_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_via', 16)->nullable();
            $table->string('approval_ip', 45)->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('deposit_paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'valid_until']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->unsignedBigInteger('unit_price_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreign('quote_id')->references('id')->on('quotes')->nullOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // external_reference / clave de idempotencia
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16);      // deposit | balance
            $table->string('method', 16);    // pix | cash | transfer | card
            $table->string('provider', 16);  // mercadopago | manual
            $table->string('mode', 16)->nullable(); // sandbox | production
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('amount_cents');
            $table->string('gateway_id')->nullable()->index();
            $table->text('pix_qr_code')->nullable();
            $table->text('pix_qr_base64')->nullable();
            $table->string('pix_ticket_url', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status_detail')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['provider', 'status']);
        });

        // Registro e idempotencia de webhooks de pago.
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 16);
            $table->string('event_key');
            $table->string('topic', 40)->nullable();
            $table->string('resource_id')->nullable();
            $table->jsonb('payload');
            $table->string('result', 32)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payments');
        Schema::table('work_orders', fn (Blueprint $table) => $table->dropForeign(['quote_id']));
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
