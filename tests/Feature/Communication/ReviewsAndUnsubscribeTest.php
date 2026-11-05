<?php

use App\Domain\Auth\Role;
use App\Domain\Customers\Access\TrackingLink;
use App\Domain\Reviews\Actions\PublishReview;
use App\Domain\Reviews\Actions\SubmitReview;
use App\Domain\WorkOrders\Enums\WorkOrderStatus;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Livewire\Portal\ReviewPage;
use App\Models\Customer;
use App\Models\Review;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    seedProduction();
    $this->customer = Customer::factory()->create(['name' => 'Carla Mendes Lima', 'email' => 'carla@example.com']);
    $this->order = WorkOrder::factory()->for(Vehicle::factory()->for($this->customer))->inStatus(WorkOrderStatus::Delivered)->create();
});

it('opens the review page with the signed link and saves the review', function () {
    $reception = userWithRole(Role::Reception);
    $url = URL::temporarySignedRoute('portal.review', now()->addDays(10), ['workOrder' => $this->order->id]);

    $this->get($url)->assertOk()->assertSee('Como foi o serviço?');

    Livewire::test(ReviewPage::class, ['workOrder' => $this->order])
        ->set('rating', 5)->set('comment', 'Ficou perfeito')->set('consent', true)
        ->call('save')->assertHasNoErrors()->assertSet('sent', true);

    $review = $this->order->review()->first();
    expect($review->rating)->toBe(5)->and($review->consent_publish)->toBeTrue()->and($review->is_published)->toBeFalse();
    Notification::assertSentTo($reception, StaffAlert::class, fn (StaffAlert $a) => $a->type === 'review');
});

it('blocks the review page without a signature', function () {
    $this->get(route('portal.review', $this->order))->assertForbidden();
});

it('only accepts reviews after delivery and with a 1–5 rating', function () {
    $this->order->forceFill(['status' => WorkOrderStatus::Ready])->save();
    expect(fn () => app(SubmitReview::class)->execute($this->order->fresh(), ['rating' => 5]))->toThrow(ValidationException::class);

    $this->order->forceFill(['status' => WorkOrderStatus::Delivered])->save();
    expect(fn () => app(SubmitReview::class)->execute($this->order->fresh(), ['rating' => 9]))->toThrow(ValidationException::class);
});

it('publishes only reviews the customer allowed and shows them on the landing', function () {
    $this->actingAs(userWithRole(Role::Reception));
    $private = app(SubmitReview::class)->execute($this->order, ['rating' => 4, 'comment' => 'Bom', 'consent_publish' => false]);
    expect(fn () => app(PublishReview::class)->execute($private, true))->toThrow(ValidationException::class);

    $private->forceFill(['consent_publish' => true, 'comment' => 'Trabalho impecável'])->save();
    app(PublishReview::class)->execute($private->fresh(), true);

    $this->get('/')->assertOk()->assertSee('Trabalho impecável')->assertSee('Carla L.')->assertDontSee('Mendes');
});

it('lets reception moderate reviews but not the upholsterer', function () {
    Review::query()->create(['work_order_id' => $this->order->id, 'customer_id' => $this->customer->id, 'rating' => 5, 'consent_publish' => true]);

    $this->actingAs(userWithRole(Role::Reception));
    Livewire::test(ListReviews::class)->assertOk()->callTableAction('togglePublish', Review::first());
    expect(Review::first()->is_published)->toBeTrue();

    $this->actingAs(userWithRole(Role::Upholsterer))->get('/admin/reviews')->assertForbidden();
});

it('unsubscribes with the signed link only after confirming (POST)', function () {
    $url = URL::temporarySignedRoute('portal.unsubscribe', now()->addDay(), ['customer' => $this->customer->id]);

    $this->get($url)->assertOk()->assertSee('Confirmar cancelamento');
    expect($this->customer->fresh()->unsubscribed_at)->toBeNull();

    $this->post($url)->assertOk()->assertSee('Pronto');
    expect($this->customer->fresh()->unsubscribed_at)->not->toBeNull();

    $this->get(route('portal.unsubscribe', $this->customer))->assertForbidden();
});

it('shows the referral link on the tracking page', function () {
    $url = app(TrackingLink::class)->url($this->order);

    $this->get($url)->assertOk()->assertSee('?ref='.$this->customer->referral_code, false);
});
