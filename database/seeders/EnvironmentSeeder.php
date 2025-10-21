<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Environment;

class EnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'zen',   'name' => 'Sala zen',     'recommended_duration_minutes' => 10, 'is_active' => true , 'image_url' => 'entornos/zen.jpg'],
            ['code' => 'forest',   'name' => 'Bosque',     'recommended_duration_minutes' => 10, 'is_active' => true, 'image_url' => 'entornos/bosque.jpg'],
            ['code' => 'beach',    'name' => 'Playa',      'recommended_duration_minutes' => 10, 'is_active' => true, 'image_url' => 'entornos/playa.jpg'],
            ['code' => 'mountain', 'name' => 'Montaña',    'recommended_duration_minutes' => 10, 'is_active' => true],
            ['code' => 'river',    'name' => 'Río',        'recommended_duration_minutes' => 10, 'is_active' => true],
            ['code' => 'rain',     'name' => 'Lluvia',     'recommended_duration_minutes' => 10, 'is_active' => true],
        ];

        foreach ($rows as $r) {
            Environment::updateOrCreate(['code' => $r['code']], $r);
        }
    }
}
