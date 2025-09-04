<?php


namespace App\Services;


use App\Models\QuestionnaireAssignment;
use App\Models\QuestionnaireItem;
use App\Models\QuestionnaireResponse;
use App\Models\QuestionnaireScore;
use Illuminate\Support\Facades\DB;


class QuestionnaireScoringService
{
/**
* Calcula y guarda puntaje (soporta PSS-10 v1 con inversión de ítems 4,5,7,8)
*/
public function scoreAssignment(int $assignmentId): array
{
$assignment = QuestionnaireAssignment::with(['questionnaire','responses','questionnaire.items'])->findOrFail($assignmentId);
$questionnaire = $assignment->questionnaire;


$reverseCodes = [];
foreach ($questionnaire->items as $it) {
if ($it->reverse_scored) { $reverseCodes[$it->id] = true; }
}


$sum = 0; $details = [];
foreach ($assignment->responses as $resp) {
$item = $questionnaire->items->firstWhere('id', $resp->item_id);
if (!$item) { continue; }
$val = (float)$resp->value;
if ($item->reverse_scored) {
// Escala 0..4 => invertido: 4 - val
$val = ($item->scale_max - $val);
}
$sum += $val;
$details[] = ['item_id'=>$item->id,'code'=>$item->code,'value'=>$resp->value,'scored'=>$val,'reverse'=>$item->reverse_scored];
}


// Guardar o actualizar puntaje
$score = DB::transaction(function () use ($assignmentId, $sum, $details) {
return QuestionnaireScore::updateOrCreate(
['assignment_id' => $assignmentId],
['score_total' => $sum, 'score_json' => json_encode(['items'=>$details])]
);
});


return ['assignment_id'=>$assignmentId,'score_total'=>$score->score_total,'details'=>$details];
}
}