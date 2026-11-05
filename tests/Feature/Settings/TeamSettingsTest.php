<?php

use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use App\Domain\Settings\SecuritySettings;
use App\Domain\Users\Actions\SaveStaffUser;
use App\Domain\Users\Actions\UpdateRolePermissions;
use App\Filament\Clusters\Settings\Pages\ManageRolePermissions;
use App\Filament\Clusters\Settings\Pages\ManageSecuritySettings;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Http\Middleware\EnforceIdleTimeout;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(fn () => $this->actingAs($this->owner = userWithRole(Role::Owner)));

it('invites a team member with a password link', function () {
    Notification::fake();

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: ['name' => 'Rita', 'email' => 'Rita@Olympo.test', 'role' => Role::Reception->value, 'locale' => 'es'])
        ->assertHasNoActionErrors();

    $user = User::query()->where('email', 'rita@olympo.test')->firstOrFail();
    expect($user->hasRole(Role::Reception->value))->toBeTrue()->and($user->locale)->toBe('es');
    Notification::assertSentTo($user, ResetPassword::class);
});

it('keeps at least one active owner and stops owners from demoting themselves', function () {
    expect(fn () => app(SaveStaffUser::class)->execute(['name' => 'Dono', 'email' => $this->owner->email, 'role' => 'reception'], $this->owner, $this->owner))
        ->toThrow(ValidationException::class);

    $other = userWithRole(Role::Owner);
    app(SaveStaffUser::class)->execute(['name' => $other->name, 'email' => $other->email, 'role' => 'owner', 'is_active' => false], $other, $this->owner);
    expect($other->fresh()->is_active)->toBeFalse();

    // El único dueño activo restante no puede perder el rol (aunque lo intente otra persona o el sistema).
    expect(fn () => app(SaveStaffUser::class)->execute(['name' => 'Dono', 'email' => $this->owner->email, 'role' => 'reception'], $this->owner))
        ->toThrow(ValidationException::class, __('users.errors.last_owner'));
});

it('rejects the customer role for team members', function () {
    app(SaveStaffUser::class)->execute(['name' => 'X', 'email' => 'x@x.test', 'role' => 'customer']);
})->throws(ValidationException::class);

it('lists only staff, not portal customers', function () {
    $customer = userWithRole(Role::Customer, ['name' => 'Cliente Portal']);

    Livewire::test(ManageUsers::class)->assertCanNotSeeTableRecords([$customer])->assertCanSeeTableRecords([$this->owner]);
});

it('keeps users and roles owner-only', function (string $url) {
    $this->actingAs(userWithRole(Role::Reception))->get($url)->assertForbidden();
})->with(['/admin/settings/users', '/admin/settings/roles', '/admin/settings/security']);

it('edits the permission matrix, audits it and the seeder keeps the change', function () {
    Livewire::test(ManageRolePermissions::class)
        ->fillForm(['upholsterer' => [Permission::WorkOrdersView->value, Permission::InventoryManage->value]])
        ->call('save')->assertHasNoFormErrors();

    $role = RoleModel::findByName('upholsterer', 'web');
    expect($role->hasPermissionTo(Permission::InventoryManage->value))->toBeTrue()
        ->and($role->hasPermissionTo(Permission::AccessPanel->value))->toBeTrue() // se agrega solo
        ->and($role->hasPermissionTo(Permission::CustomersView->value))->toBeFalse()
        ->and(Activity::query()->where('description', 'roles.updated')->exists())->toBeTrue();

    $this->seed(RolesAndPermissionsSeeder::class);
    expect(RoleModel::findByName('upholsterer', 'web')->hasPermissionTo(Permission::CustomersView->value))->toBeFalse();

    expect(fn () => app(UpdateRolePermissions::class)->execute(Role::Owner, []))->toThrow(ValidationException::class);
});

it('never grants settings or users to other roles', function () {
    app(UpdateRolePermissions::class)->execute(Role::Reception, [Permission::SettingsManage->value, Permission::UsersManage->value, Permission::CustomersView->value]);

    $user = userWithRole(Role::Reception);
    expect($user->can(Permission::SettingsManage->value))->toBeFalse()
        ->and($user->can(Permission::UsersManage->value))->toBeFalse();
});

it('saves security settings and validates ranges', function () {
    Livewire::test(ManageSecuritySettings::class)
        ->fillForm(['max_login_attempts' => 1])->call('save')->assertHasFormErrors(['max_login_attempts']);

    Livewire::test(ManageSecuritySettings::class)
        ->fillForm(['require_2fa' => false, 'idle_timeout_minutes' => 30, 'max_login_attempts' => 6])->call('save')->assertHasNoFormErrors();

    expect(app(SecuritySettings::class)->refresh()->idle_timeout_minutes)->toBe(30);
});

it('logs out after the idle timeout', function () {
    $s = app(SecuritySettings::class);
    $s->idle_timeout_minutes = 30;
    $s->save();

    $this->get('/admin')->assertOk();
    $this->travel(31)->minutes();
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('keeps the session while there is activity', function () {
    $s = app(SecuritySettings::class);
    $s->idle_timeout_minutes = 30;
    $s->save();

    $this->get('/admin')->assertOk();
    $this->travel(20)->minutes();
    $this->get('/admin')->assertOk();
    $this->travel(20)->minutes();
    $this->get('/admin')->assertOk();
    expect(session(EnforceIdleTimeout::KEY))->not->toBeNull();
});

it('forces two-factor setup when the owner requires it', function () {
    $s = app(SecuritySettings::class);
    $s->require_2fa = true;
    $s->save();

    $this->get('/admin')->assertRedirect();

    $this->owner->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $this->get('/admin')->assertOk();
});

it('records the last login', function () {
    auth()->logout();
    $user = userWithRole(Role::Reception, ['password' => 'secret-123']);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'secret-123'])->call('authenticate');

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('blocks the login after the configured attempts', function () {
    auth()->logout();
    $s = app(SecuritySettings::class);
    $s->max_login_attempts = 3;
    $s->save();
    $user = userWithRole(Role::Reception, ['password' => 'secret-123']);

    $login = Livewire::test(Login::class);
    foreach (range(1, 3) as $i) {
        $login->fillForm(['email' => $user->email, 'password' => 'wrong'])->call('authenticate')->assertHasFormErrors(['email']);
    }
    $login->fillForm(['email' => $user->email, 'password' => 'secret-123'])->call('authenticate');

    $this->assertGuest();
});

it('saves the mail preferences from the profile', function () {
    Livewire::test(EditProfile::class)
        ->fillForm(['notification_preferences' => ['mail_categories' => ['payments', 'agenda']]])
        ->call('save')->assertHasNoFormErrors();

    expect($this->owner->fresh()->notification_preferences['mail'])->toMatchArray(['payments' => true, 'agenda' => true, 'leads' => false]);
});
