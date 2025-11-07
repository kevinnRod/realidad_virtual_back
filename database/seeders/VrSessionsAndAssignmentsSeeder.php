<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Study;
use App\Models\Device;
use App\Models\Environment;
use App\Models\VrSession;
use App\Models\VrSessionSegment;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAssignment;

class VrSessionsAndAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        // --- Prerrequisitos ---
        $study  = Study::where('name', 'Protocolo Estrés VR')->first() ?? Study::first();
        $device = Device::where('code', 'MQ3-001')->first() ?? Device::first();

        if (!$study || !$device) {
            $this->command?->warn('Faltan Study y/o Device.');
            return;
        }

        $envs = Environment::whereIn('code', ['zen', 'beach', 'forest'])->get()->keyBy('code');
        if ($envs->count() < 3) {
            $this->command?->warn('Faltan environments con code: zen, beach, forest.');
            return;
        }

        $qPss         = Questionnaire::where('code', 'pss10')->first();
        $qSatisfVr    = Questionnaire::where('code', 'satisf')->first();
        $qSatisfVideo = Questionnaire::where('code', 'satisf_video')->first();

        if (!$qPss || !$qSatisfVr || !$qSatisfVideo) {
            $this->command?->warn('Faltan cuestionarios: pss10, satisf y/o satisf_video.');
            return;
        }

        // 32 usuarios objetivo: no-admin con gmail (del UserSeeder estático)
        $users = User::where('is_admin', false)
            ->where('email', 'like', '%@gmail.com')
            ->orderBy('id')
            ->take(32)
            ->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No hay usuarios @gmail.com no-admin.');
            return;
        }

        // --- Agenda: lun 3/11/2025 a jue 6/11/2025, 8 por día, slots cada 30 min desde 09:00 ---
        $base = Carbon::create(2025, 11, 3, 9, 0, 0); // Lunes 3 nov 2025 09:00
        $slotMinutes = 30;

        // Blueprint de segmentos
        $segments = [
            ['code' => 'zen',    'min' => 5],
            ['code' => 'beach',  'min' => 3],
            ['code' => 'forest', 'min' => 3],
        ];
        $totalMinutes = collect($segments)->sum('min'); // 11

        DB::transaction(function () use (
            $users, $study, $device, $envs, $segments, $totalMinutes, $base, $slotMinutes,
            $qPss, $qSatisfVr, $qSatisfVideo
        ) {
            foreach ($users->values() as $idx => $user) {
                $dayOffset = $idx % 4;         // 0..3 => lun..jue
                $slot      = intdiv($idx, 4);  // 0..7 => 8 slots por día

                $scheduledAt = $base->copy()
                    ->addDays($dayOffset)
                    ->addMinutes($slot * $slotMinutes);

                // --- Sesión VR (session_no = 1) ---
                $session = VrSession::updateOrCreate(
                    [
                        'user_id'    => $user->id,
                        'study_id'   => $study->id,
                        'session_no' => 1,
                    ],
                    [
                        'device_id'              => $device->id,
                        'scheduled_at'           => $scheduledAt,
                        'total_duration_minutes' => $totalMinutes,
                        'vr_app_version'         => '1.0.0',
                        'notes'                  => 'Sesión estándar (zen 5m + beach 3m + forest 3m)',
                    ]
                );

                // Limpiamos y recreamos segmentos
                $session->segments()->delete();
                $order = 1;
                foreach ($segments as $seg) {
                    $env = $envs[$seg['code']];
                    VrSessionSegment::create([
                        'vr_session_id'    => $session->id,
                        'environment_id'   => $env->id,
                        'sort_order'       => $order++,
                        'duration_minutes' => $seg['min'],
                    ]);
                }

                // --- Cuestionarios vinculados a esta sesión ---
                // Tiempos relativos al scheduled_at
                $preAt        = $scheduledAt->copy();            // inicio
                $postAt       = $scheduledAt->copy()->addMinutes(20);
                $videoAt      = $scheduledAt->copy()->addMinutes(24); // antes que VR
                $satisfVrAt   = $scheduledAt->copy()->addMinutes(30); // después del video

                // PSS-10 PRE
                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qPss->id,
                        'context'          => 'pre',
                        'session_id'       => $session->id,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $preAt,
                        'due_at'       => null,
                        'completed_at' => null,
                    ]
                );

                // PSS-10 POST
                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qPss->id,
                        'context'          => 'post',
                        'session_id'       => $session->id,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $postAt,
                        'due_at'       => null,
                        'completed_at' => null,
                    ]
                );

                // Satisfacción VIDEO NATURALISTA (pendiente, sin completed_at)
                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qSatisfVideo->id,
                        'context'          => 'pre',
                        'session_id'       => $session->id,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $videoAt,
                        'due_at'       => null,
                        'completed_at' => null, // <-- lo completarás manualmente
                    ]
                );

                // Satisfacción VR (completado)
                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qSatisfVr->id,
                        'context'          => 'post',
                        'session_id'       => $session->id,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $satisfVrAt,
                        'due_at'       => null,
                        'completed_at' => null,
                    ]
                );
            }
        });
    }
}
