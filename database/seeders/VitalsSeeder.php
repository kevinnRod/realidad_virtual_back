<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\VrSession;

class VitalsSeeder extends Seeder
{
    public function run(): void
    {
        // 32 usuarios objetivo (mismo criterio que el resto de seeders)
        $users = User::where('is_admin', false)
            ->where('email', 'like', '%@gmail.com')
            ->orderBy('id')
            ->take(32)
            ->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No hay usuarios objetivo para vitals.');
            return;
        }

        // Presión sistólica REAL (PRE/POST) en el orden de los 32 usuarios
        $sysPre  = [147,128,132,136,125,132,132,116,141,137,126,130,137,130,130,119,137,133,134,118,147,133,129,150,132,119,128,111,141,128,125,142];
        $sysPost = [138,121,126,130,119,125,120,106,132,135,124,121,129,126,122,116,123,129,124,111,137,120,115,137,120,114,116,107,132,122,109,126];

        $deviceLabel = 'OMRON-M2';

        DB::transaction(function () use ($users, $sysPre, $sysPost, $deviceLabel) {

            foreach ($users->values() as $i => $user) {
                $preSys  = (int)$sysPre[$i];
                $postSys = (int)$sysPost[$i];

                // Sesión del usuario (session_no = 1); si no existe, igual insertamos sin session_id
                $session = VrSession::where('user_id', $user->id)->where('session_no', 1)->first();
                $base    = $session?->scheduled_at ? Carbon::parse($session->scheduled_at) : Carbon::create(2025,11,3,9,0,0);

                // --- Simulación de diastólica y pulso (valores plausibles) ---
                // Diastólica ≈ sistólica - (30..55) en pre, (28..50) en post
                $preDia  = max(55, min(99,  $preSys  - random_int(30, 55)));
                $postDia = max(50, min(95,  $postSys - random_int(28, 50)));

                // Pulso pre ~ 68..96 ajustado por sistólica; post baja 6..14 lpm
                $prePulse  = max(60, min(105, random_int(68, 92) + intdiv(max(0, $preSys - 125), 6)));
                $postPulse = max(55, $prePulse - random_int(6, 14));

                $now = now();

                // --- PRE ---
                DB::table('vitals')->updateOrInsert(
                    [
                        'user_id'    => $user->id,
                        'session_id' => $session->id ?? null,
                        'phase'      => 'pre',
                    ],
                    [
                        'measured_at'  => $base->copy(),
                        'posture'      => 'seated',
                        'bp_sys'       => $preSys,
                        'bp_dia'       => $preDia,
                        'pulse'        => $prePulse,
                        'device_label' => $deviceLabel,
                        'updated_at'   => $now,
                        'created_at'   => $now,
                    ]
                );

                // --- POST ---
                DB::table('vitals')->updateOrInsert(
                    [
                        'user_id'    => $user->id,
                        'session_id' => $session->id ?? null,
                        'phase'      => 'post',
                    ],
                    [
                        'measured_at'  => $base->copy()->addMinutes(11), // coincide con duración total (5+3+3)
                        'posture'      => 'seated',
                        'bp_sys'       => $postSys,
                        'bp_dia'       => $postDia,
                        'pulse'        => $postPulse,
                        'device_label' => $deviceLabel,
                        'updated_at'   => $now,
                        'created_at'   => $now,
                    ]
                );
            }
        });
    }
}
