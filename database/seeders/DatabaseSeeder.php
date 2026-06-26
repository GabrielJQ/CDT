<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@cdt.local'),
        ], [
            'name' => env('ADMIN_NAME', 'Administrador'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'role' => RolUsuario::Admin,
            'region_id' => null,
            'unidad_operativa_id' => null,
            'is_active' => true,
        ]);
    }
}
