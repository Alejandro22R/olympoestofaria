<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Plantillas editables por evento, canal e idioma (variables {{nome}}…).
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event', 60);
            $table->string('channel', 16); // mail | whatsapp
            $table->string('locale', 8);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('whatsapp_template')->nullable(); // nombre aprobado por Meta
            $table->string('whatsapp_language', 10)->nullable();
            $table->jsonb('whatsapp_params')->nullable(); // orden de variables del cuerpo
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event', 'channel', 'locale']);
        });

        // Registro de envíos (soporte): canal, estado, intento, error.
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('notifiable');
            $table->string('event', 60);
            $table->string('channel', 16);
            $table->string('recipient')->nullable();
            $table->string('status', 16); // sent | failed | skipped
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('idempotency_key')->nullable()->index();
            $table->string('provider_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->jsonb('notification_preferences')->nullable()->after('contact_opt_in_at');
            $table->timestamp('unsubscribed_at')->nullable()->after('notification_preferences');
            $table->string('referral_code', 16)->nullable()->unique()->after('unsubscribed_at');
            $table->foreignId('referred_by_customer_id')->nullable()->after('referral_code')->constrained('customers')->nullOnDelete();
        });

        // Clientes existentes: código de referido propio.
        DB::table('customers')->whereNull('referral_code')->orderBy('id')->each(function (object $row): void {
            do {
                $code = strtoupper(Str::random(6));
            } while (DB::table('customers')->where('referral_code', $code)->exists());
            DB::table('customers')->where('id', $row->id)->update(['referral_code' => $code]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('referred_by_customer_id')->nullable()->after('customer_id')->constrained('customers')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('notification_preferences')->nullable();
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::table('alert_logs', function (Blueprint $table) {
            $table->timestamp('escalated_at')->nullable();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('consent_publish')->default(false);
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();
        });

        Schema::table('warranties', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('warranties', fn (Blueprint $t) => $t->dropColumn('reminder_sent_at'));
        Schema::dropIfExists('reviews');
        Schema::table('alert_logs', fn (Blueprint $t) => $t->dropColumn('escalated_at'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['notification_preferences', 'app_authentication_secret', 'app_authentication_recovery_codes', 'last_login_at']));
        Schema::table('projects', fn (Blueprint $t) => $t->dropConstrainedForeignId('referred_by_customer_id'));
        Schema::table('customers', function (Blueprint $t) {
            $t->dropConstrainedForeignId('referred_by_customer_id');
            $t->dropColumn(['notification_preferences', 'unsubscribed_at', 'referral_code']);
        });
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('message_templates');
    }
};
