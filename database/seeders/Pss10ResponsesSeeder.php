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

        // 32 usuarios objetivo (no-admin, @gmail.com)
        $users = User::where('is_admin', false)
            ->where('email', 'like', '%@gmail.com')
            ->orderBy('id')
            ->take(32)
            ->get();

        // Items PSS-10 ordenados por sort_order 1..10
        $items = QuestionnaireItem::where('questionnaire_id', $qPss->id)
            ->orderBy('sort_order')
            ->get()
            ->values();

        if ($items->count() !== 10) {
            $this->command?->warn("PSS-10 debería tener 10 items; encontrados: {$items->count()}.");
            return;
        }

        // ===== MATRICES DE RESPUESTAS (P001..P032) =====
        $pre = [
            [3,4,4,1,0,2,0,3,3,4],[0,4,4,0,0,2,0,1,3,2],[3,1,2,1,1,4,0,1,3,0],
            [3,2,1,3,4,4,0,0,3,3],[1,3,0,3,0,3,0,1,3,2],[4,4,4,3,1,4,0,0,0,2],
            [3,2,4,1,0,3,0,2,1,4],[4,2,0,2,2,4,2,0,3,1],[4,3,2,0,0,2,2,1,4,3],
            [1,4,4,1,2,3,1,1,0,1],[0,3,2,1,3,2,1,2,2,1],[2,2,4,2,1,4,2,0,4,3],
            [4,4,2,0,0,3,2,3,0,3],[3,2,1,0,3,2,1,2,3,4],[4,2,3,0,0,0,1,1,1,2],
            [4,2,1,1,4,2,2,2,2,4],[3,3,3,1,0,4,0,0,0,3],[3,0,3,0,1,2,2,1,4,1],
            [2,1,4,3,0,3,2,1,3,2],[1,4,4,1,0,2,2,3,4,4],[3,1,2,3,1,0,0,2,0,2],
            [3,3,3,0,2,2,0,2,3,4],[3,3,1,1,0,2,4,1,4,3],[4,3,3,1,0,4,0,0,3,2],
            [4,4,4,1,0,4,1,4,4,4],[2,3,4,0,0,3,3,2,3,1],[3,4,1,1,3,4,2,2,3,2],
            [4,4,4,3,1,3,2,1,4,4],[4,2,3,0,1,0,1,1,2,3],[0,0,4,1,0,3,2,0,3,1],
            [1,3,3,1,2,4,1,0,4,2],[3,1,0,4,2,3,1,0,2,2],
        ];

        $post = [
            [2,0,0,3,4,1,4,3,1,1],[1,2,1,1,2,2,1,1,1,1],[2,3,2,3,4,1,2,2,0,2],
            [0,1,1,3,3,1,3,4,3,1],[2,0,1,3,4,0,3,4,1,2],[0,3,2,3,2,1,3,3,3,1],
            [2,1,2,3,4,0,4,2,0,1],[0,3,2,4,0,1,3,1,0,2],[1,2,1,2,4,3,2,0,1,1],
            [2,4,1,4,3,2,1,2,0,1],[1,2,0,1,2,2,4,3,3,1],[1,1,0,4,2,1,3,3,0,4],
            [3,0,0,3,4,4,2,3,3,1],[2,0,2,4,4,0,3,2,1,1],[0,0,3,3,3,2,4,0,2,3],
            [0,2,0,2,3,0,3,4,2,1],[1,2,1,1,3,3,2,4,2,1],[2,0,3,4,4,0,1,1,1,1],
            [1,0,0,4,3,3,0,3,3,1],[0,2,1,3,0,2,0,4,1,0],[1,0,2,4,4,1,1,3,2,0],
            [2,1,1,4,3,1,1,2,3,0],[1,4,0,4,4,0,3,3,1,0],[0,0,0,4,4,0,2,4,1,2],
            [0,1,4,2,1,1,4,2,2,2],[0,1,2,3,1,4,3,4,0,0],[1,0,4,2,2,0,1,2,1,2],
            [0,1,0,2,3,0,3,2,2,0],[3,0,0,4,0,2,4,4,2,4],[1,3,1,4,1,3,4,4,0,1],
            [0,2,0,1,2,3,2,3,1,0],[1,2,0,3,3,3,3,4,1,1],
        ];

        // Helper: persiste respuestas (upsert) y score como hace tu controlador
        $saveBlock = function (QuestionnaireAssignment $assignment, array $values) use ($items) {
            $now = now();

            // UPsert responses
            $rows = [];
            foreach ($items as $k => $item) {
                $rows[] = [
                    'assignment_id' => $assignment->id,
                    'item_id'       => $item->id,
                    'value'         => (int)($values[$k] ?? 0),
                    'answered_at'   => ($assignment->assigned_at ? Carbon::parse($assignment->assigned_at) : $now)->copy()->addMinutes($k),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            QuestionnaireResponse::upsert(
                $rows,
                ['assignment_id','item_id'],
                ['value','answered_at','updated_at']
            );

            // Marcar completado si respondió todo
            $totalItems = $items->count();
            $answered   = QuestionnaireResponse::where('assignment_id', $assignment->id)->count();
            if ($totalItems > 0 && $answered >= $totalItems && is_null($assignment->completed_at)) {
                $assignment->completed_at = $now; // o deja el que ya tenga
                $assignment->save();
            }

            // Score (crear/actualizar)
            $responses = $assignment->responses()->with('item')->get();
            $total = 0;
            $details = [];
            foreach ($responses as $response) {
                $val = (int)$response->value;
                $max = $response->item->scale_max ?? 4; // PSS: 0..4
                $rev = (bool)($response->item->reverse_scored ?? false);
                $scored = $rev ? ($max - $val) : $val;
                $total += $scored;
                $details[$response->item->id] = $scored;
            }

            // hasOne ->updateOrCreate() está scoping por assignment_id
            $assignment->score()->updateOrCreate([], [
                'score_total' => $total,
                'score_json'  => $details,
            ]);
        };

        DB::transaction(function () use ($users, $study, $qPss, $items, $pre, $post, $saveBlock) {
            foreach ($users->values() as $idx => $user) {
                // Sesión del usuario para tiempos base
                $session = VrSession::where('user_id', $user->id)->where('session_no', 1)->first();
                $scheduledAt = $session?->scheduled_at
                    ? Carbon::parse($session->scheduled_at)
                    : now();

                // Assignment PRE (asegurar created/assigned/completed)
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
                        'completed_at' => $scheduledAt->copy()->addMinutes(10),
                    ]
                );
                $saveBlock($preAssignment, $pre[$idx]);

                // Assignment POST
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
                $saveBlock($postAssignment, $post[$idx]);
            }
        });
    }
}
