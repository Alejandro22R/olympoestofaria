<?php

namespace App\Domain\Customers\Access;

use App\Domain\Auth\Role;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cuenta ligera del cliente para el portal (rol customer, sin contraseña conocida):
 * se usa para la sesión al abrir los enlaces firmados y para autorizar su canal privado.
 */
class EnsureCustomerUser
{
    public function execute(Customer $customer): User
    {
        if ($customer->user !== null) {
            return $customer->user;
        }

        return DB::transaction(function () use ($customer): User {
            $email = filled($customer->email) && ! User::query()->where('email', $customer->email)->exists()
                ? (string) $customer->email
                : 'cliente-'.$customer->id.'@portal.invalid';

            $user = User::query()->create([
                'name' => $customer->name,
                'email' => $email,
                'password' => Str::password(40),
                'locale' => $customer->locale,
                'branch_id' => $customer->branch_id,
            ]);
            $user->assignRole(Role::Customer->value);

            $customer->forceFill(['user_id' => $user->id])->save();

            return $user;
        });
    }
}
