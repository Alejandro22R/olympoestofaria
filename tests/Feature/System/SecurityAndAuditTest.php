<?php

use App\Domain\Auth\Role;
use App\Domain\Settings\AuditSettings;
use App\Domain\Settings\GeneralSettings;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Support\InitialsAvatarProvider;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('sends security headers on every web response', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("frame-ancestors 'self'")->toContain("object-src 'none'")->toContain('https://challenges.cloudflare.com')
        ->and($response->headers->has('Strict-Transport-Security'))->toBeFalse(); // solo por https
});

it('sends HSTS on https when enabled', function () {
    config(['olympo.security.hsts' => true]);

    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('rate limits the public portal per IP', function () {
    RateLimiter::clear('portal');
    foreach (range(1, 120) as $i) {
        $this->get('/termos');
    }

    $this->get('/termos')->assertStatus(429);
});

it('keeps every admin resource closed to portal customers', function () {
    $this->actingAs(userWithRole(Role::Customer));

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        $this->get($resource::getUrl('index'))->assertForbidden();
    }
});

it('keeps the owner-only areas closed to the upholsterer', function (string $url) {
    $this->actingAs(userWithRole(Role::Upholsterer))->get($url)->assertForbidden();
})->with(['/admin/settings/audit', '/admin/settings/backups', '/admin/settings/privacy', '/admin/settings/users', '/admin/reports']);

it('only lets the owner open Horizon', function () {
    expect(Gate::forUser(userWithRole(Role::Owner))->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser(userWithRole(Role::Reception))->allows('viewHorizon'))->toBeFalse();
});

it('draws avatars locally instead of sending names to a third party', function () {
    $owner = userWithRole(Role::Owner, ['name' => 'Ana Lima']);

    expect(app(InitialsAvatarProvider::class)->get($owner))->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr(app(InitialsAvatarProvider::class)->get($owner), 26)))->toContain('>AL<');
    $this->actingAs($owner)->get('/admin')->assertOk()->assertDontSee('ui-avatars.com');
});

it('lists the audit trail with old and new values and exports it', function () {
    $owner = userWithRole(Role::Owner);
    $this->actingAs($owner);
    $s = app(GeneralSettings::class);
    $s->business_name = 'Olympo Centro';
    $s->save();

    $entry = Activity::query()->where('description', 'settings.updated')->latest('id')->firstOrFail();
    Livewire::test(ListActivities::class)
        ->assertCanSeeTableRecords([$entry])
        ->mountTableAction('view', $entry)->assertMountedActionModalSee(['Olympo Centro', 'Olympo Estofaria']);

    Livewire::test(ListActivities::class)->callAction('export')->assertFileDownloaded();
    expect(Activity::query()->where('description', 'audit.exported')->exists())->toBeTrue();
});

it('changes the audit retention (minimum 180 days) and cleans old entries', function () {
    $this->actingAs(userWithRole(Role::Owner));
    Livewire::test(ListActivities::class)->callAction('retention', data: ['retention_days' => 30])->assertHasActionErrors(['retention_days']);
    Livewire::test(ListActivities::class)->callAction('retention', data: ['retention_days' => 365])->assertHasNoActionErrors();
    expect(app(AuditSettings::class)->refresh()->retention_days)->toBe(365);

    activity()->log('antigo');
    Activity::query()->where('description', 'antigo')->update(['created_at' => now()->subDays(400)]);
    $this->artisan('olympo:audit:clean')->assertSuccessful();

    expect(Activity::query()->where('description', 'antigo')->exists())->toBeFalse();
});

it('creates the first owner from the console', function () {
    $this->artisan('olympo:create-owner', ['email' => 'Dono@Olympo.com.br', 'name' => 'Dono'])
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'curta')->assertFailed();

    $this->artisan('olympo:create-owner', ['email' => 'Dono@Olympo.com.br', 'name' => 'Dono'])
        ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'uma-senha-forte-123')->assertSuccessful();

    $user = User::query()->where('email', 'dono@olympo.com.br')->firstOrFail();
    expect($user->isOwner())->toBeTrue()->and(Hash::check('uma-senha-forte-123', $user->password))->toBeTrue();
});
