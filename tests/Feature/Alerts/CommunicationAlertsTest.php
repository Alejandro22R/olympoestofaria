<?php

use App\Domain\Alerts\DiskUsage;
use App\Domain\Alerts\RunScheduledAlerts;
use App\Domain\Auth\Role;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Domain\Notifications\Customer\ScheduledCustomerMessages;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Quotes\Actions\DecideQuote;
use App\Domain\Settings\AlertSettings;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Project;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Notifications\CustomerMessage;
use App\Notifications\StaffAlert;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    seedProduction();
    InventoryItem::query()->update(['is_active' => false]);
    freezeMonday();
    $this->owner = userWithRole(Role::Owner);
    $this->reception = userWithRole(Role::Reception);
    $this->run = fn () => app(RunScheduledAlerts::class)->execute();
});

it('alerts a lead without answer after the configured hours, once', function () {
    Notification::fake();
    $customer = Customer::factory()->create();
    Project::query()->create(['customer_id' => $customer->id, 'status' => ProjectStatus::Submitted, 'configuration' => [], 'submitted_at' => now()->subHours(5)]);

    expect(($this->run)()['lead_unanswered'])->toBe(1)
        ->and(($this->run)()['lead_unanswered'])->toBe(0);
    Notification::assertSentTo($this->reception, StaffAlert::class, fn (StaffAlert $a) => $a->type === 'lead_unanswered' && $a->alertKey !== null);
});

it('uses the new threshold after the owner changes it', function () {
    Notification::fake();
    $customer = Customer::factory()->create();
    Project::query()->create(['customer_id' => $customer->id, 'status' => ProjectStatus::Submitted, 'configuration' => [], 'submitted_at' => now()->subHours(5)]);
    $s = app(AlertSettings::class);
    $s->lead_unanswered_hours = 24;
    $s->save();

    expect(($this->run)()['lead_unanswered'])->toBe(0);
});

it('alerts an unpaid deposit and reminds the customer of an unanswered quote', function () {
    Notification::fake();
    $approved = sentQuote(100000);
    app(DecideQuote::class)->approve($approved);
    $sent = sentQuote(50000, ['email' => 'otro@example.com']);

    $this->travel(4)->days();
    $result = ($this->run)();

    expect($result['deposit_overdue'])->toBe(1)->and($result['quote_unanswered'])->toBe(1);
    Notification::assertSentTo($sent->customer, CustomerMessage::class, fn (CustomerMessage $m) => $m->event === MessageEvent::QuoteReminder);
});

it('alerts a delivered order with balance due', function () {
    Notification::fake();
    $order = WorkOrder::factory()->inStatus(WorkOrderStatus::Delivered)->create();
    $order->items()->create(['description' => 'Bancos', 'price_cents' => 90000]);
    $order->forceFill(['delivered_at' => now()->subDays(3)])->save();

    expect(($this->run)()['balance_overdue'])->toBe(1);
});

it('sends technical alerts only to the owner and by mail', function () {
    Notification::fake();
    app()->instance(DiskUsage::class, new class extends DiskUsage
    {
        public function freePercent(): ?float
        {
            return 4.0;
        }
    });
    foreach (range(1, 5) as $i) {
        DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'redis', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()]);
    }

    $result = ($this->run)();

    expect($result['failed_jobs'])->toBe(1)->and($result['disk'])->toBe(1);
    Notification::assertSentTo($this->owner, StaffAlert::class, fn (StaffAlert $a, array $channels) => $a->type === 'technical' && in_array('mail', $channels, true));
    Notification::assertNotSentTo($this->reception, StaffAlert::class, fn (StaffAlert $a) => $a->type === 'technical');
});

it('escalates unread alerts to the owner after the configured hours', function () {
    $customer = Customer::factory()->create();
    Project::query()->create(['customer_id' => $customer->id, 'status' => ProjectStatus::Submitted, 'configuration' => [], 'submitted_at' => now()->subHours(5)]);
    ($this->run)(); // crea la alerta (base de datos real) para recepción y dueño

    $this->travel(5)->hours();
    Notification::fake();
    expect(($this->run)()['escalated'])->toBe(1)
        ->and(($this->run)()['escalated'])->toBe(0);
    Notification::assertSentTo($this->owner, StaffAlert::class, fn (StaffAlert $a) => $a->type === 'escalated');
});

it('does not escalate an alert somebody already opened', function () {
    $customer = Customer::factory()->create();
    Project::query()->create(['customer_id' => $customer->id, 'status' => ProjectStatus::Submitted, 'configuration' => [], 'submitted_at' => now()->subHours(5)]);
    ($this->run)();
    $this->reception->unreadNotifications()->first()->markAsRead();

    $this->travel(5)->hours();
    expect(($this->run)()['escalated'])->toBe(0);
});

it('lets each person receive chosen alert types by mail', function () {
    $this->reception->forceFill(['notification_preferences' => ['mail' => ['leads' => true]]])->save();
    $alert = new StaffAlert(type: 'lead_created', titleKey: 'notifications.lead_created');

    expect($alert->via($this->reception->fresh()))->toContain('mail')
        ->and($alert->via($this->owner))->not->toContain('mail');
});

it('sends one appointment reminder per window and never twice', function () {
    Mail::fake();
    $this->seed(MessageTemplateSeeder::class);
    $customer = Customer::factory()->create(['email' => 'r@example.com']);
    Appointment::factory()->for(Vehicle::factory()->for($customer))->create(['scheduled_at' => now()->addHours(20)]);

    expect(app(ScheduledCustomerMessages::class)->execute()['reminders'])->toBe(1);
    app(ScheduledCustomerMessages::class)->execute();
    Mail::assertSentCount(1);

    $this->travel(18)->hours(); // ventana de 2 h
    app(ScheduledCustomerMessages::class)->execute();
    Mail::assertSentCount(2);
});

it('asks for a review some days after delivery and reminds the warranty check', function () {
    Notification::fake();
    $order = WorkOrder::factory()->inStatus(WorkOrderStatus::Delivered)->create();
    $order->forceFill(['delivered_at' => now()->subDays(4)])->save();
    $order->warranty()->create(['starts_at' => now()->subYear(), 'ends_at' => now()->addDays(10)]);

    $result = app(ScheduledCustomerMessages::class)->execute();

    expect($result['reviews'])->toBe(1)->and($result['warranty'])->toBe(1);
    Notification::assertSentTo($order->vehicle->customer, CustomerMessage::class, fn (CustomerMessage $m) => $m->event === MessageEvent::ReviewRequest && str_contains((string) $m->vars['link'], '/avaliar/'));
    expect($order->warranty->fresh()->reminder_sent_at)->not->toBeNull();
});

it('runs the customer messages command', function () {
    $this->artisan('olympo:customer-messages')->assertSuccessful();
});
