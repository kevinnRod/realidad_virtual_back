<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Study;
use App\Models\Environment;

class StudySeeder extends Seeder
{
    public function run(): void
    {
        $forest   = Environment::where('code', 'forest')->first();
        $beach    = Environment::where('code', 'beach')->first();
        $mountain = Environment::where('code', 'mountain')->first();

        // Crea estudios simples (ajusta campos a tu modelo)
        $stress = Study::updateOrCreate(
            ['name' => 'Protocolo Estrés VR'],
            ['default_environment_id' => optional($forest)->id]
        );

        $sleep = Study::updateOrCreate(
            ['name' => 'Mejor Sueño VR'],
            ['default_environment_id' => optional($beach)->id]
        );

        // Si tienes la tabla pivot environment_study, asigna permitidos
        if (Schema::hasTable('environment_study')) {
            $stress->environments()->sync([$forest->id, $beach->id, $mountain->id]);
            $sleep->environments()->sync([$beach->id, $mountain->id]);
        }
    }
}
