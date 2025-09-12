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
            ['name' => 'Demo', 'password' => Hash::make('secret123')]
        );

        User::updateOrCreate(
            ['email' => 'paciente@serenityvr.test'],
            ['name' => 'Paciente', 'password' => Hash::make('secret123')]
        );
    }
}
