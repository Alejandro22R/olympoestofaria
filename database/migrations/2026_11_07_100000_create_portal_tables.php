<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Proyecto armado por el cliente en el constructor (lead → cotización en la Fase 5).
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('submitted');
            $table->jsonb('configuration');
            $table->unsignedBigInteger('estimate_min_cents')->default(0);
            $table->unsignedBigInteger('estimate_max_cents')->default(0);
            $table->string('source', 32)->default('builder');
            $table->text('notes')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });

        // Consentimiento de contacto por WhatsApp/correo (opt-in) para el cliente.
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('contact_opt_in_at')->nullable()->after('lgpd_consent_at');
        });

        // Biblioteca de medios del portal: fotos, antes/después y videos del taller.
        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->jsonb('title');
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->jsonb('placements')->default('[]'); // hero, gallery, before_after, services, reels
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('video_status', 16)->nullable(); // pending|processing|ready|failed
            $table->string('video_mp4_path')->nullable();
            $table->string('video_webm_path')->nullable();
            $table->string('video_poster_path')->nullable();
            $table->string('video_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_items');
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('contact_opt_in_at'));
        Schema::dropIfExists('projects');
    }
};
