<?php

namespace App\Domain\Users\Actions;

use App\Domain\Auth\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alta (invitación) y edición del personal. Al invitar se crea con contraseña aleatoria y se envía
 * el enlace para definirla. Reglas: siempre queda al menos un dueño activo y nadie se quita a sí
 * mismo el rol de dueño ni se desactiva.
 */
class SaveStaffUser
{
    /**
     * @param  array{name: string, email: string, role: string, locale?: string, is_active?: bool}  $data
     */
    public function execute(array $data, ?User $user = null, ?User $actor = null): User
    {
        $role = Role::tryFrom((string) $data['role']);
        if ($role === null || ! in_array($role, Role::staff(), true)) {
            throw ValidationException::withMessages(['role' => __('users.errors.invalid_role')]);
        }

        $active = (bool) ($data['is_active'] ?? true);
        $email = Str::lower(trim($data['email']));

        if (User::query()->where('email', $email)->when($user, fn ($q) => $q->whereKeyNot($user->id))->exists()) {
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => __('users.fields.email')])]);
        }

        if ($user !== null && $actor !== null && $user->is($actor) && (! $active || $role !== Role::Owner && $user->isOwner())) {
            throw ValidationException::withMessages(['role' => __('users.errors.self_demote')]);
        }

        if ($user !== null && $user->isOwner() && ($role !== Role::Owner || ! $active) && self::activeOwners($user) === 0) {
            throw ValidationException::withMessages(['role' => __('users.errors.last_owner')]);
        }

        $invite = $user === null;

        $user = DB::transaction(function () use ($data, $user, $role, $active, $email): User {
            $user ??= new User(['password' => Str::password(32)]);
            $user->fill([
                'name' => trim($data['name']),
                'email' => $email,
                'locale' => $data['locale'] ?? $user->locale ?? 'pt_BR',
                'is_active' => $active,
            ])->save();
            $user->syncRoles([$role->value]);

            return $user;
        });

        if ($invite) {
            Password::broker()->sendResetLink(['email' => $user->email]);
        }

        return $user;
    }

    /** Dueños activos sin contar al usuario indicado. */
    private static function activeOwners(User $except): int
    {
        return User::role(Role::Owner->value)->where('is_active', true)->whereKeyNot($except->id)->count();
    }
}
