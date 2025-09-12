<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'demo@serenityvr.test'],
            ['name' => 'Demo', 'password' => Hash::make('secret123'), 'is_admin' => false]
        );

        User::updateOrCreate(
            ['email' => 'paciente@serenityvr.test'],
            ['name' => 'Paciente', 'password' => Hash::make('secret123'), 'is_admin' => false]
        );

        User::updateOrCreate(
            ['email' => 'admin@serenityvr.test'],
            ['name' => 'Admin', 'password' => 'secret123', 'is_admin' => true]
        );
    }
}
