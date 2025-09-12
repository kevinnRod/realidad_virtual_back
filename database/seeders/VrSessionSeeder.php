<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Study;
use App\Models\Device;
use App\Models\Environment;
use App\Models\VrSession;
use App\Models\VrSessionSegment;

class VrSessionSeeder extends Seeder
{
    public function run(): void
    {
        $user    = User::where('email', 'paciente@serenityvr.test')->first() ?? User::first();
        $study   = Study::where('name', 'Protocolo Estrés VR')->first() ?? Study::first();
        $device  = Device::where('code', 'MQ3-001')->first() ?? Device::first();

        if (!$user || !$study || !$device) {
            $this->command->warn('Faltan user/study/device para crear sesiones.');
            return;
        }

        $forest   = Environment::where('code', 'forest')->first();
        $beach    = Environment::where('code', 'beach')->first();
        $mountain = Environment::where('code', 'mountain')->first();

        // Dos sesiones de ejemplo con segmentos que suman ~10 minutos
        $sessions = [
            [
                'session_no' => 1,
                'scheduled_at' => now()->addDays(1)->setTime(10, 0),
                'notes' => 'Sesión inicial',
                'segments' => [
                    ['env' => $forest,   'min' => 4],
                    ['env' => $beach,    'min' => 4],
                    ['env' => $mountain, 'min' => 2],
                ],
            ],
            [
                'session_no' => 2,
                'scheduled_at' => now()->addDays(3)->setTime(10, 0),
                'notes' => 'Sesión de seguimiento',
                'segments' => [
                    ['env' => $beach,    'min' => 5],
                    ['env' => $forest,   'min' => 3],
                    ['env' => $mountain, 'min' => 2],
                ],
            ],
        ];

        DB::transaction(function () use ($sessions, $user, $study, $device) {
            foreach ($sessions as $s) {
                $total = collect($s['segments'])->sum('min');

                $session = VrSession::updateOrCreate(
                    [
                        'user_id'   => $user->id,
                        'study_id'  => $study->id,
                        'session_no'=> $s['session_no'],
                    ],
                    [
                        'device_id'               => $device->id,
                        'scheduled_at'            => $s['scheduled_at'],
                        'total_duration_minutes'  => $total,
                        'vr_app_version'          => '1.0.0',
                        'notes'                   => $s['notes'],
                    ]
                );

                // limpiar segmentos previos por si el updateOrCreate encontró existente
                $session->segments()->delete();

                $order = 1;
                foreach ($s['segments'] as $seg) {
                    if (!$seg['env']) continue;
                    VrSessionSegment::create([
                        'vr_session_id'   => $session->id,
                        'environment_id'  => $seg['env']->id,
                        'sort_order'      => $order++,
                        'duration_minutes'=> $seg['min'],
                        // opcionalmente puedes setear started/ended en ejecución real
                    ]);
                }
            }
        });
    }
}
