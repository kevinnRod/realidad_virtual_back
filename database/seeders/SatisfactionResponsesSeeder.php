<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\VrSession;
use App\Models\Study;
use App\Models\Questionnaire;
use App\Models\QuestionnaireItem;
use App\Models\QuestionnaireAssignment;
use App\Models\QuestionnaireResponse;

class SatisfactionResponsesSeeder extends Seeder
{
    public function run(): void
    {
        $study = Study::where('name', 'Protocolo Estrés VR')->first() ?? Study::first();

        $qVideo = Questionnaire::where('code', 'satisf_video')->first();
        $qVr    = Questionnaire::where('code', 'satisf')->first();

        if (!$study || !$qVideo || !$qVr) {
            $this->command?->warn('Faltan Study y/o cuestionarios satisf_video / satisf.');
            return;
        }

        // 32 usuarios objetivo (no-admin con gmail) en el mismo orden
        $users = User::where('is_admin', false)
            ->where('email', 'like', '%@gmail.com')
            ->orderBy('id')
            ->take(32)
            ->get();

        $itemsVideo = QuestionnaireItem::where('questionnaire_id', $qVideo->id)
            ->orderBy('sort_order')->get()->values();
        $itemsVr    = QuestionnaireItem::where('questionnaire_id', $qVr->id)
            ->orderBy('sort_order')->get()->values();

        if ($itemsVideo->count() !== 8 || $itemsVr->count() !== 8) {
            $this->command?->warn("Se esperaban 8 ítems para cada cuestionario (video/vr).");
            return;
        }

        // ===== MATRICES (E1..E32 x P1..P8) =====
        $video = [
            [4,3,4,3,2,3,3,4],[4,3,3,2,3,4,3,4],[3,3,4,2,3,3,2,2],[3,4,3,3,3,3,4,3],
            [3,4,4,2,4,2,4,3],[5,4,5,4,3,3,2,3],[4,3,3,2,3,4,3,4],[3,4,4,3,3,2,2,2],
            [3,4,4,3,2,3,2,3],[3,4,3,3,2,3,3,3],[2,3,3,2,1,2,3,3],[3,4,3,3,3,3,3,3],
            [2,3,2,3,2,4,3,3],[4,3,3,3,2,2,3,3],[4,3,3,4,2,4,3,4],[3,2,4,1,2,3,2,2],
            [3,3,4,3,5,4,4,4],[2,3,3,4,3,3,4,2],[4,3,4,2,2,3,3,4],[3,4,3,2,3,3,2,4],
            [4,4,3,4,4,4,5,4],[3,3,4,2,2,4,3,3],[3,2,4,3,3,3,2,3],[5,3,4,3,4,3,3,3],
            [4,3,3,3,4,1,4,2],[4,3,4,4,2,3,3,3],[4,3,4,2,3,3,4,3],[4,4,3,4,2,4,3,4],
            [3,4,2,3,3,3,3,2],[3,4,3,3,3,4,2,4],[4,4,3,3,2,2,2,3],[3,2,4,3,3,3,4,3],
        ];

        $vr = [
            [3,4,4,5,3,3,4,4],[4,4,4,4,3,4,4,4],[3,4,4,4,5,4,3,4],[3,4,4,3,4,4,3,3],
            [4,4,4,4,5,5,3,5],[4,4,4,3,3,4,4,4],[3,3,4,5,3,4,3,3],[4,4,4,4,4,4,4,4],
            [4,5,4,4,4,4,5,3],[4,3,3,3,3,4,3,3],[4,4,4,4,4,4,3,4],[3,4,5,4,4,3,4,3],
            [3,4,5,4,4,3,3,4],[4,5,3,5,3,5,4,3],[5,4,5,4,4,4,5,4],[3,4,3,3,4,3,4,3],
            [4,3,4,4,4,4,3,4],[3,4,4,3,4,4,4,3],[3,4,4,5,4,3,4,4],[4,5,4,4,4,4,3,4],
            [4,4,4,3,3,3,4,4],[4,5,4,4,4,4,4,3],[4,5,4,3,5,3,3,4],[3,3,4,3,3,3,4,3],
            [4,4,4,5,4,4,4,4],[4,5,4,4,3,4,3,3],[4,4,4,4,4,3,2,5],[4,5,4,4,3,4,4,4],
            [4,4,5,4,4,4,3,4],[3,4,5,5,4,4,4,5],[5,5,5,3,3,5,4,5],[4,4,4,3,4,4,5,4],
        ];

        // Helper genérico: upsert responses, marcar completado si corresponde y calcular score
        $saveBlock = function (QuestionnaireAssignment $assignment, $items, array $values) {
            $now = now();
            $assignedAt = $assignment->assigned_at ? Carbon::parse($assignment->assigned_at) : $now;

            $rows = [];
            foreach ($items as $k => $item) {
                $rows[] = [
                    'assignment_id' => $assignment->id,
                    'item_id'       => $item->id,
                    'value'         => (int)($values[$k] ?? 0),
                    'answered_at'   => $assignedAt->copy()->addMinutes($k),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            QuestionnaireResponse::upsert(
                $rows,
                ['assignment_id','item_id'],
                ['value','answered_at','updated_at']
            );

            // marcar completado si respondió todo
            $totalItems = $items->count();
            $answered   = QuestionnaireResponse::where('assignment_id', $assignment->id)->count();
            if ($totalItems > 0 && $answered >= $totalItems && is_null($assignment->completed_at)) {
                $assignment->completed_at = $assignedAt->copy()->addMinutes(8);
                $assignment->save();
            }

            // score
            $responses = $assignment->responses()->with('item')->get();
            $total = 0;
            $details = [];
            foreach ($responses as $response) {
                $val = (int)$response->value;
                $max = $response->item->scale_max ?? 5;
                $rev = (bool)($response->item->reverse_scored ?? false);
                $scored = $rev ? ($max - $val) : $val;
                $total += $scored;
                $details[$response->item->id] = $scored;
            }
            $assignment->score()->updateOrCreate([], [
                'score_total' => $total,
                'score_json'  => $details,
            ]);
        };

        DB::transaction(function () use ($users, $study, $qVideo, $qVr, $itemsVideo, $itemsVr, $video, $vr, $saveBlock) {
            foreach ($users->values() as $idx => $user) {
                $session = VrSession::where('user_id', $user->id)->where('session_no', 1)->first();
                $scheduledAt = $session?->scheduled_at ? Carbon::parse($session->scheduled_at) : now();

                // --- VIDEO NATURALISTA (post) ---
                $videoAssignment = QuestionnaireAssignment::firstOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qVideo->id,
                        'context'          => 'post',
                        'session_id'       => $session->id ?? null,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $scheduledAt->copy()->addMinutes(24),
                        'completed_at' => null, // si ya existía en null, se completará tras respuestas
                    ]
                );
                $saveBlock($videoAssignment, $itemsVideo, $video[$idx]);

                // --- SATISFACCIÓN VR (post) ---
                $vrAssignment = QuestionnaireAssignment::firstOrCreate(
                    [
                        'user_id'          => $user->id,
                        'questionnaire_id' => $qVr->id,
                        'context'          => 'post',
                        'session_id'       => $session->id ?? null,
                    ],
                    [
                        'study_id'     => $study->id,
                        'assigned_at'  => $scheduledAt->copy()->addMinutes(30),
                        'completed_at' => $scheduledAt->copy()->addMinutes(38),
                    ]
                );
                $saveBlock($vrAssignment, $itemsVr, $vr[$idx]);
            }
        });
    }
}
