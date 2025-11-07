<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Study;
use App\Models\VrSession;
use App\Models\Questionnaire;
use App\Models\QuestionnaireItem;
use App\Models\QuestionnaireAssignment;
use App\Models\QuestionnaireResponse;

class Pss10ResponsesSeeder extends Seeder
{
    public function run(): void
    {
        $study = Study::where('name', 'Protocolo Estrés VR')->first() ?? Study::first();
        $qPss  = Questionnaire::where('code', 'pss10')->first();

        if (!$study || !$qPss) {
            $this->command?->warn('Faltan Study o Questionnaire pss10.');
            return;
        }

        // Usuarios objetivo (mismo criterio que los demás seeders)
        $users = User::where('is_admin', false)
            ->where('email', 'like', '%@gmail.com')
            ->orderBy('id')
            ->take(32)
            ->get();

        if ($users->count() !== 32) {
            $this->command?->warn("Se esperaban 32 usuarios objetivo, encontrados: {$users->count()}.");
        }

        // Items PSS-10 en orden 1..10
        $items = QuestionnaireItem::where('questionnaire_id', $qPss->id)
            ->orderBy('sort_order')
            ->get()
            ->values();

        if ($items->count() !== 10) {
            $this->command?->warn("PSS-10 debería tener 10 items; encontrados: {$items->count()}.");
            return;
        }

        // --------------------------
        // MATRICES DE RESPUESTAS
        // Orden: P001..P032 (32 filas), columnas i1..i10
        // --------------------------

        $pre = [
            [3,4,4,1,0,2,0,3,3,4],
            [0,4,4,0,0,2,0,1,3,2],
            [3,1,2,1,1,4,0,1,3,0],
            [3,2,1,3,4,4,0,0,3,3],
            [1,3,0,3,0,3,0,1,3,2],
            [4,4,4,3,1,4,0,0,0,2],
            [3,2,4,1,0,3,0,2,1,4],
            [4,2,0,2,2,4,2,0,3,1],
            [4,3,2,0,0,2,2,1,4,3],
            [1,4,4,1,2,3,1,1,0,1],
            [0,3,2,1,3,2,1,2,2,1],
            [2,2,4,2,1,4,2,0,4,3],
            [4,4,2,0,0,3,2,3,0,3],
            [3,2,1,0,3,2,1,2,3,4],
            [4,2,3,0,0,0,1,1,1,2],
            [4,2,1,1,4,2,2,2,2,4],
            [3,3,3,1,0,4,0,0,0,3],
            [3,0,3,0,1,2,2,1,4,1],
            [2,1,4,3,0,3,2,1,3,2],
            [1,4,4,1,0,2,2,3,4,4],
            [3,1,2,3,1,0,0,2,0,2],
            [3,3,3,0,2,2,0,2,3,4],
            [3,3,1,1,0,2,4,1,4,3],
            [4,3,3,1,0,4,0,0,3,2],
            [4,4,4,1,0,4,1,4,4,4],
            [2,3,4,0,0,3,3,2,3,1],
            [3,4,1,1,3,4,2,2,3,2],
            [4,4,4,3,1,3,2,1,4,4],
            [4,2,3,0,1,0,1,1,2,3],
            [0,0,4,1,0,3,2,0,3,1],
            [1,3,3,1,2,4,1,0,4,2],
            [3,1,0,4,2,3,1,0,2,2],
        ];

        $post = [
            [2,0,0,3,4,1,4,3,1,1],
            [1,2,1,1,2,2,1,1,1,1],
            [2,3,2,3,4,1,2,2,0,2],
            [0,1,1,3,3,1,3,4,3,1],
            [2,0,1,3,4,0,3,4,1,2],
            [0,3,2,3,2,1,3,3,3,1],
            [2,1,2,3,4,0,4,2,0,1],
            [0,3,2,4,0,1,3,1,0,2],
            [1,2,1,2,4,3,2,0,1,1],
            [2,4,1,4,3,2,1,2,0,1],
            [1,2,0,1,2,2,4,3,3,1],
            [1,1,0,4,2,1,3,3,0,4],
            [3,0,0,3,4,4,2,3,3,1],
            [2,0,2,4,4,0,3,2,1,1],
            [0,0,3,3,3,2,4,0,2,3],
            [0,2,0,2,3,0,3,4,2,1],
            [1,2,1,1,3,3,2,4,2,1],
            [2,0,3,4,4,0,1,1,1,1],
            [1,0,0,4,3,3,0,3,3,1],
            [0,2,1,3,0,2,0,4,1,0],
            [1,0,2,4,4,1,1,3,2,0],
            [2,1,1,4,3,1,1,2,3,0],
            [1,4,0,4,4,0,3,3,1,0],
            [0,0,0,4,4,0,2,4,1,2],
            [0,1,4,2,1,1,4,2,2,2],
            [0,1,2,3,1,4,3,4,0,0],
            [1,0,4,2,2,0,1,2,1,2],
            [0,1,0,2,3,0,3,2,2,0],
            [3,0,0,4,0,2,4,4,2,4],
            [1,3,1,4,1,3,4,4,0,1],
            [0,2,0,1,2,3,2,3,1,0],
            [1,2,0,3,3,3,3,4,1,1],
        ];

        DB::transaction(function () use ($users, $study, $qPss, $items, $pre, $post) {
            foreach ($users->values() as $idx => $user) {
                // Sesión (si existe) para capturar hora base
                $session = VrSession::where('user_id', $user->id)->where('session_no', 1)->first();
                $scheduledAt = $session?->scheduled_at
                    ? Carbon::parse($session->scheduled_at)
                    : Carbon::now();

                // --- PRE ---
                $preAssignment = QuestionnaireAssignment::firstOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qPss->id,
                        'context'          => 'pre',
                        'session_id'       => $session->id ?? null,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $scheduledAt->copy(),
                        // si ya existía con completed_at, no lo tocamos
                        'completed_at' => $scheduledAt->copy()->addMinutes(10),
                    ]
                );

                // limpiar/rescribir respuestas actuales para asegurar consistencia
                QuestionnaireResponse::where('assignment_id', $preAssignment->id)->delete();

                foreach ($items as $k => $item) {
                    $value = $pre[$idx][$k] ?? null;
                    if ($value === null) continue;

                    QuestionnaireResponse::create([
                        'assignment_id' => $preAssignment->id,
                        'item_id'       => $item->id,
                        'value'         => $value,
                    ]);
                }

                // --- POST ---
                $postAssignment = QuestionnaireAssignment::firstOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qPss->id,
                        'context'          => 'post',
                        'session_id'       => $session->id ?? null,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $scheduledAt->copy()->addMinutes(20),
                        'completed_at' => $scheduledAt->copy()->addMinutes(30),
                    ]
                );

                QuestionnaireResponse::where('assignment_id', $postAssignment->id)->delete();

                foreach ($items as $k => $item) {
                    $value = $post[$idx][$k] ?? null;
                    if ($value === null) continue;

                    QuestionnaireResponse::create([
                        'assignment_id' => $postAssignment->id,
                        'item_id'       => $item->id,
                        'value'         => $value,
                    ]);
                }
            }
        });
    }
}
