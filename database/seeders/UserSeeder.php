<?php

namespace Database\Seeders;

use App\Domain\Auth\Role;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios de demostración (solo desarrollo). Contraseña: "password".
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::default();

        $users = [
            ['name' => 'Dono Olympo', 'email' => 'owner@olympo.test', 'role' => Role::Owner, 'locale' => 'pt_BR'],
            ['name' => 'Recepção', 'email' => 'reception@olympo.test', 'role' => Role::Reception, 'locale' => 'pt_BR'],
            ['name' => 'Estofador', 'email' => 'upholsterer@olympo.test', 'role' => Role::Upholsterer, 'locale' => 'es'],
            ['name' => 'Cliente Demo', 'email' => 'customer@olympo.test', 'role' => Role::Customer, 'locale' => 'pt_BR'],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(['email' => $data['email']], [
                'name' => $data['name'],
                'password' => 'password',
                'locale' => $data['locale'],
                'branch_id' => $branch?->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$data['role']->value]);
        }
    }
}
