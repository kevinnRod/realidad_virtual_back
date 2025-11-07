<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('es_ES');
        $passwordHash = Hash::make('password');

        // Usuarios base (todos con "password")
        User::updateOrCreate(
            ['email' => 'demo@serenityvr.test'],
            ['name' => 'Demo', 'password' => $passwordHash, 'is_admin' => false]
        );

        User::updateOrCreate(
            ['email' => 'paciente@serenityvr.test'],
            ['name' => 'Paciente', 'password' => $passwordHash, 'is_admin' => false]
        );

        User::updateOrCreate(
            ['email' => 'admin@serenityvr.test'],
            ['name' => 'Admin', 'password' => $passwordHash, 'is_admin' => true]
        );

        // Helper para email tipo "nombre_i@gmail.com"
        $buildEmail = function (string $firstName, string $lastName1): string {
            // inicial del primer apellido
            $initial = mb_substr($lastName1, 0, 1, 'UTF-8');

            // normalizar (quitar acentos / espacios) y a minúsculas
            $base = Str::lower(
                Str::of(Str::ascii($firstName))->replace(' ', '')
            ) . '_' . Str::lower(Str::ascii($initial));

            $domain = '@gmail.com';
            $email = $base . $domain;

            // asegurar unicidad en BD
            $suffix = 2;
            while (User::where('email', $email)->exists()) {
                $email = $base . $suffix . $domain;
                $suffix++;
            }
            return $email;
        };

        // 32 usuarios adicionales
        for ($i = 1; $i <= 32; $i++) {
            $firstName = $faker->firstName();
            $last1     = $faker->lastName();
            $last2     = $faker->lastName();
            $name      = "{$firstName} {$last1} {$last2}";
            $email     = $buildEmail($firstName, $last1);

            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $name,
                    'password' => $passwordHash,
                    'is_admin' => false,
                ]
            );
        }
    }
}
