<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAssignment;
use App\Models\QuestionnaireResponse;

class FixSatisfVideoCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $q = Questionnaire::where('code', 'satisf_video')->first();
        if (!$q) return;

        DB::transaction(function () use ($q) {
            // Para cada par (user_id, session_id) con un POST de satisf_video
            $posts = QuestionnaireAssignment::where('questionnaire_id', $q->id)
                ->where('context', 'post')
                ->get();

            foreach ($posts as $post) {
                $pre = QuestionnaireAssignment::where('questionnaire_id', $q->id)
                    ->where('user_id', $post->user_id)
                    ->where('session_id', $post->session_id)
                    ->where('context', 'pre')
                    ->first();

                // Si no existe PRE, simplemente conviértelo a PRE
                if (!$pre) {
                    $post->context = 'pre';
                    $post->save();
                    continue;
                }

                // Si existe PRE:
                $preCount  = QuestionnaireResponse::where('assignment_id', $pre->id)->count();
                $postCount = QuestionnaireResponse::where('assignment_id', $post->id)->count();

                // Si PRE no tiene respuestas y POST sí, movemos respuestas POST -> PRE
                if ($preCount === 0 && $postCount > 0) {
                    $postResponses = QuestionnaireResponse::where('assignment_id', $post->id)->get();
                    foreach ($postResponses as $r) {
                        QuestionnaireResponse::updateOrCreate(
                            ['assignment_id' => $pre->id, 'item_id' => $r->item_id],
                            ['value' => $r->value, 'answered_at' => $r->answered_at]
                        );
                    }
                }

                // Recalcular score del PRE
                $this->recalcScore($pre);

                // Borrar el POST sobrante (respuestas y score)
                QuestionnaireResponse::where('assignment_id', $post->id)->delete();
                $post->score()?->delete();
                $post->delete();
            }
        });
    }

    private function recalcScore(QuestionnaireAssignment $a): void
    {
        $responses = $a->responses()->with('item')->get();
        $total = 0; $details = [];
        foreach ($responses as $res) {
            $val = (int)$res->value;
            $max = $res->item->scale_max ?? 5;
            $rev = (bool)($res->item->reverse_scored ?? false);
            $sc = $rev ? ($max - $val) : $val;
            $total += $sc;
            $details[$res->item->id] = $sc;
        }
        $a->score()->updateOrCreate([], [
            'score_total' => $total,
            'score_json'  => $details,
        ]);
    }
}
