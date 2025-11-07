<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Study;
use App\Models\VrSession;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAssignment;

class QuestionnaireAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $study = Study::where('name', 'Protocolo Estrés VR')->first() ?? Study::first();
        if (!$study) {
            $this->command?->warn('No hay Study para asignar cuestionarios.');
            return;
        }

        // Cuestionarios
        $qPss         = Questionnaire::where('code', 'pss10')->first();
        $qSatisfVr    = Questionnaire::where('code', 'satisf')->first();
        $qSatisfVideo = Questionnaire::where('code', 'satisf_video')->first();

        if (!$qPss || !$qSatisfVr || !$qSatisfVideo) {
            $this->command?->warn('Faltan cuestionarios: pss10, satisf y/o satisf_video.');
            return;
        }

        // Usuarios objetivo: 32 no-admin con gmail (del UserSeeder estático)
        $users = User::where('is_admin', false)
            ->where('email', 'like', '%@gmail.com')
            ->orderBy('id')
            ->take(32)
            ->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No se encontraron usuarios objetivo (@gmail.com no-admin).');
            return;
        }

        // Ventana: Lun 3/11/2025 a Jue 6/11/2025, 8 usuarios/día, slots de 30 min desde 09:00
        $base = Carbon::create(2025, 11, 3, 9, 0, 0);
        $slotMinutes = 30;

        DB::transaction(function () use ($users, $study, $qPss, $qSatisfVr, $qSatisfVideo, $base, $slotMinutes) {
            foreach ($users->values() as $idx => $user) {
                $dayOffset = $idx % 4;        // 0..3 => lun..jue
                $slot      = intdiv($idx, 4); // 0..7 => 8 slots por día

                // PSS-10 PRE
                $preAt = $base->copy()->addDays($dayOffset)->addMinutes($slot * $slotMinutes);

                // Si existe sesión VR (session_no = 1), la enlazamos; si no, null.
                $session = VrSession::where('user_id', $user->id)->where('session_no', 1)->first();

                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qPss->id,
                        'context'          => 'pre',
                        'session_id'       => $session->id ?? null,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $preAt,
                        'due_at'       => null,
                        'completed_at' => null,
                    ]
                );

                // PSS-10 POST (mismo día, un poco después)
                $postAt = $preAt->copy()->addMinutes(20);
                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qPss->id,
                        'context'          => 'post',
                        'session_id'       => $session->id ?? null,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $postAt,
                        'due_at'       => null,
                        'completed_at' => null,
                    ]
                );

                // --- Satisfacción de VIDEO NATURALISTA (PREVIO a la de VR) ---
                // Se crea pero queda pendiente (completed_at = NULL)
                $videoAt = $preAt->copy()->addMinutes(24); // antes que la de VR
                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qSatisfVideo->id,
                        'context'          => 'post',
                        'session_id'       => null, // normalmente no va ligada a VrSession
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $videoAt,
                        'due_at'       => null,
                        'completed_at' => null, // <-- lo completas tú manualmente luego
                    ]
                );

                // --- Satisfacción VR (POST) ---
                $satisfVrAt = $preAt->copy()->addMinutes(30); // después del video
                QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qSatisfVr->id,
                        'context'          => 'post',
                        'session_id'       => $session->id ?? null,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $satisfVrAt,
                        'due_at'       => null,
                        'completed_at' => $satisfVrAt->copy()->addMinutes(8),
                    ]
                );
            }
        });
    }
}
