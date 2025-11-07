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
        $passwordHash = Hash::make('password');

        // Usuarios base
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

        // Lista estática de 32 nombres (Nombre + 2 apellidos)
        $names = [
            'Nicolás Rubio Sánchez',
            'Laura Paredes Molina',
            'Diego Castillo Romero',
            'Marta Ibáñez Alonso',
            'Jorge Herrera Cano',
            'Paula Ríos Navarro',
            'Andrés Gómez Velez',
            'Silvia Lozano Prieto',
            'Daniel Cortés Aguilar',
            'Elena Serrano Muñoz',
            'Pablo Navas Ortega',
            'Carmen Ruiz Cabrera',
            'Alberto Varela Domínguez',
            'Lucía Campos Esteban',
            'Sergio Márquez Duarte',
            'Sofía Medina Salas',
            'Javier Álvarez Gallego',
            'Natalia Fuentes Bravo',
            'Víctor Ponce Aranda',
            'Irene Cabrera Solís',
            'Óscar Barrios Palacios',
            'Valeria Cárdenas León',
            'Marcos Delgado Pizarro',
            'Claudia Salinas Pineda',
            'Manuel Arias Cifuentes',
            'Alicia Morales Tejada',
            'Tomás Valverde Quiroga',
            'Beatriz Carrillo Sáenz',
            'Rubén Montoya Plaza',
            'Andrea Núñez Castaño',
            'Héctor Roldán Sevilla',
            'Gabriela Benítez Portillo',
        ];

        // Helper: genera email tipo "nombre_i@gmail.com" (e.g., nicolas_r@gmail.com)
        $buildEmail = function (string $fullName): string {
            $parts = preg_split('/\s+/', trim($fullName));
            // Asumimos formato: Nombre Apellido1 Apellido2
            $firstName = $parts[0] ?? 'user';
            $last1     = $parts[1] ?? 'x';

            $firstNameAscii = Str::lower(Str::ascii($firstName));
            $initialAscii   = Str::lower(Str::ascii(mb_substr($last1, 0, 1, 'UTF-8')));

            $base   = $firstNameAscii . '_' . $initialAscii;
            $domain = '@gmail.com';
            $email  = $base . $domain;

            // Asegurar unicidad si ya existe
            $suffix = 2;
            while (User::where('email', $email)->exists()) {
                $email = $base . $suffix . $domain;
                $suffix++;
            }
            return $email;
        };

        foreach ($names as $fullName) {
            $email = $buildEmail($fullName);

            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $fullName,
                    'password' => $passwordHash,
                    'is_admin' => false,
                ]
            );
        }
    }
}
