<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionnaireAssignment;
use App\Models\QuestionnaireScore;
use App\Models\Questionnaire;
use App\Models\User;
use App\Models\VrSession;

class QuestionnaireAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'paciente@serenityvr.test')->first();
        $sessions = VrSession::all();
        $pss = Questionnaire::where('code', 'pss10')->first();

        if (!$user || !$pss || $sessions->isEmpty()) {
            echo "Faltan usuario, sesiones o cuestionario PSS-10.\n";
            return;
        }

        foreach ($sessions as $session) {
            foreach (['pre', 'post'] as $context) {
                $assignment = QuestionnaireAssignment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'questionnaire_id' => $pss->id,
                        'study_id' => $session->study_id,
                        'session_id' => $session->id,
                        'context' => $context,
                    ],
                    [
                        'assigned_at' => now()->subDays(rand(1, 6)),
                        'completed_at' => now()->subDays(rand(0, 2)),
                    ]
                );

                QuestionnaireScore::updateOrCreate(
                    ['assignment_id' => $assignment->id],
                    [
                        'score_total' => rand(10, 25),
                        'score_json' => [
                            'pss10_q1' => rand(0, 4),
                            'pss10_q2' => rand(0, 4),
                            'pss10_q3' => rand(0, 4),
                            'pss10_q4' => rand(0, 4),
                            'pss10_q5' => rand(0, 4),
                            'pss10_q6' => rand(0, 4),
                            'pss10_q7' => rand(0, 4),
                            'pss10_q8' => rand(0, 4),
                            'pss10_q9' => rand(0, 4),
                            'pss10_q10'=> rand(0, 4),
                        ]
                    ]
                );
            }
        }
    }
}
