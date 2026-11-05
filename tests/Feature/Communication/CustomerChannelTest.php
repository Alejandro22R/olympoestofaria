<?php

use App\Domain\Notifications\Customer\CustomerNotifier;
use App\Domain\Notifications\Customer\MessageEvent;
use App\Domain\Settings\NotificationSettings;
use App\Domain\Settings\WhatsAppSettings;
use App\Mail\CustomerMessageMail;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Notifications\CustomerMessage;
use Carbon\CarbonImmutable;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Mail::fake();
    $this->seed(MessageTemplateSeeder::class);
    $this->customer = Customer::factory()->create([
        'name' => 'Ana Souza', 'email' => 'ana@example.com', 'phone' => '+5511987654321',
        'locale' => 'pt_BR', 'contact_opt_in_at' => now(),
    ]);
    $this->send = fn (MessageEvent $event, array $vars = [], string $key = 'k-1') => app(CustomerNotifier::class)
        ->send($this->customer, $event, $vars, $key);
});

function enableWhatsApp(): void
{
    $s = app(WhatsAppSettings::class);
    $s->enabled = true;
    $s->phone_number_id = '123456';
    $s->access_token = 'EAAG-test';
    $s->save();
}

it('sends the mail template with the variables replaced and logs it', function () {
    ($this->send)(MessageEvent::PaymentApproved, ['valor' => 'R$ 300,00', 'tipo' => 'Sinal', 'link' => 'https://x.test']);

    Mail::assertSent(CustomerMessageMail::class, fn (CustomerMessageMail $m) => $m->hasTo('ana@example.com')
        && str_contains($m->body, 'Ana Souza') && str_contains($m->body, 'R$ 300,00'));
    expect(NotificationLog::query()->where('status', 'sent')->where('channel', 'mail')->count())->toBe(1);
});

it('does not send the same message twice (idempotency key)', function () {
    ($this->send)(MessageEvent::PaymentApproved, ['valor' => '1'], 'payment:1:approved');
    ($this->send)(MessageEvent::PaymentApproved, ['valor' => '1'], 'payment:1:approved');

    Mail::assertSentCount(1);
});

it('skips marketing without opt-in and logs why', function () {
    $this->customer->forceFill(['contact_opt_in_at' => null])->save();

    ($this->send)(MessageEvent::ReviewRequest, ['placa' => 'ABC1D23', 'link' => 'x']);

    Mail::assertNothingSent();
    expect(NotificationLog::query()->value('status'))->toBe('skipped');
});

it('stops progress updates after unsubscribing but keeps transactional messages', function () {
    $this->customer->forceFill(['unsubscribed_at' => now()])->save();

    ($this->send)(MessageEvent::WorkOrderStage, ['placa' => 'ABC1D23', 'etapa' => 'Costura', 'link' => 'x'], 'stage');
    Mail::assertNothingSent();

    ($this->send)(MessageEvent::AppointmentCancelled, ['placa' => 'ABC1D23', 'data' => '10/11/2026', 'hora' => '09:00'], 'cancel');
    Mail::assertSentCount(1);
});

it('sends by WhatsApp Cloud API with the approved template and ordered parameters', function () {
    enableWhatsApp();
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

    ($this->send)(MessageEvent::WorkOrderReady, ['placa' => 'ABC1D23', 'saldo' => 'R$ 100,00', 'link' => 'https://x.test']);

    Http::assertSent(fn (Request $r) => $r->url() === 'https://graph.facebook.com/v21.0/123456/messages'
        && $r->hasHeader('Authorization', 'Bearer EAAG-test')
        && $r['to'] === '5511987654321'
        && $r['template']['name'] === 'olympo_work_order_ready'
        && in_array('ABC1D23', array_column($r['template']['components'][0]['parameters'], 'text'), true));
    Mail::assertNothingSent();
    expect(NotificationLog::query()->where('channel', 'whatsapp')->value('provider_id'))->toBe('wamid.1');
});

it('falls back to mail when WhatsApp fails and logs both attempts', function () {
    enableWhatsApp();
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

    ($this->send)(MessageEvent::WorkOrderReady, ['placa' => 'ABC1D23', 'saldo' => 'R$ 0,00', 'link' => 'x']);

    Mail::assertSentCount(1);
    expect(NotificationLog::query()->orderBy('id')->pluck('status', 'channel')->all())->toBe(['whatsapp' => 'failed', 'mail' => 'sent']);
});

it('fails (so the queue retries) when fallback is disabled and the only channel fails', function () {
    enableWhatsApp();
    $s = app(NotificationSettings::class);
    $s->fallback_enabled = false;
    $s->save();
    Http::fake(['graph.facebook.com/*' => Http::response([], 500)]);

    ($this->send)(MessageEvent::WorkOrderReady, ['placa' => 'ABC1D23']);
})->throws(RuntimeException::class);

it('delays non-urgent messages until the quiet hours end, but not transactional ones', function () {
    Notification::fake();
    $this->travelTo(local('2026-11-09 22:30'));

    ($this->send)(MessageEvent::WorkOrderStage, ['placa' => 'ABC1D23'], 'stage');
    ($this->send)(MessageEvent::PaymentApproved, ['valor' => '1'], 'pay');

    Notification::assertSentTo($this->customer, CustomerMessage::class, fn (CustomerMessage $n) => $n->event === MessageEvent::WorkOrderStage
        && $n->delay instanceof DateTimeInterface
        && CarbonImmutable::instance($n->delay)->setTimezone('America/Sao_Paulo')->format('Y-m-d H:i') === '2026-11-10 08:00');
    Notification::assertSentTo($this->customer, CustomerMessage::class, fn (CustomerMessage $n) => $n->event === MessageEvent::PaymentApproved && $n->delay === null);
});

it('uses the Spanish template for Spanish-speaking customers', function () {
    $this->customer->forceFill(['locale' => 'es'])->save();

    ($this->send)(MessageEvent::PaymentApproved, ['valor' => '1', 'tipo' => 'Anticipo', 'link' => 'x']);

    Mail::assertSent(CustomerMessageMail::class, fn (CustomerMessageMail $m) => str_contains($m->body, 'Recibimos tu pago'));
});

it('adds the unsubscribe link only to optional messages', function () {
    ($this->send)(MessageEvent::WorkOrderStage, ['placa' => 'ABC1D23', 'etapa' => 'Costura', 'link' => 'x'], 'a');
    ($this->send)(MessageEvent::PaymentApproved, ['valor' => '1'], 'b');

    Mail::assertSent(CustomerMessageMail::class, fn (CustomerMessageMail $m) => str_contains($m->body, 'Costura') && $m->unsubscribeUrl !== null);
    Mail::assertSent(CustomerMessageMail::class, fn (CustomerMessageMail $m) => ! str_contains($m->body, 'Costura') && $m->unsubscribeUrl === null);
});

it('prunes old logs using the retention setting', function () {
    NotificationLog::query()->create(['event' => 'payment_approved', 'channel' => 'mail', 'status' => 'sent'])
        ->forceFill(['created_at' => now()->subDays(200)])->save();
    NotificationLog::query()->create(['event' => 'payment_approved', 'channel' => 'mail', 'status' => 'sent']);

    $this->artisan('model:prune', ['--model' => [NotificationLog::class]])->assertSuccessful();

    expect(NotificationLog::query()->count())->toBe(1);
});
