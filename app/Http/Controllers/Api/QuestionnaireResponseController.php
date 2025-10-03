<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\QuestionnaireAssignment;
use App\Models\QuestionnaireItem;
use App\Models\QuestionnaireResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class QuestionnaireResponseController extends Controller
{
/**
* Store batch of responses: { assignment_id, responses: [{item_id,value,answered_at?}, ...] }
*/
public function store(Request $r)
{
    $data = $r->validate([
        'assignment_id'         => 'required|exists:questionnaire_assignments,id',
        'responses'             => 'required|array|min:1',
        'responses.*.item_id'   => 'required|exists:questionnaire_items,id',
        'responses.*.value'     => 'required|numeric',
        'responses.*.answered_at' => 'nullable|date',
    ]);

    $assignment = QuestionnaireAssignment::with('questionnaire')
        ->findOrFail($data['assignment_id']);

    DB::transaction(function () use ($data, $assignment) {
        $now  = now();
        $rows = collect($data['responses'])->map(function ($res) use ($data, $now) {
            return [
                'assignment_id' => $data['assignment_id'],
                'item_id'       => $res['item_id'],
                'value'         => $res['value'],
                'answered_at'   => $res['answered_at'] ?? $now,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        })->all();

        QuestionnaireResponse::upsert(
            $rows,
            ['assignment_id','item_id'],
            ['value','answered_at','updated_at']
        );

        // marcar completado si respondió todo
        $totalItems = QuestionnaireItem::where('questionnaire_id', $assignment->questionnaire_id)->count();
        $answered   = QuestionnaireResponse::where('assignment_id', $assignment->id)->count();

        if ($totalItems > 0 && $answered >= $totalItems && is_null($assignment->completed_at)) {
            $assignment->completed_at = $now;
            $assignment->save();
        }

        // calcular y guardar score si ya está completado y no existe
        if ($assignment->completed_at && !$assignment->score()->exists()) {
            $responses = $assignment->responses()->with('item')->get();
            $total = 0;
            foreach ($responses as $response) {
                $val = (int) $response->value;
                if ($response->item->reverse_scored ?? false) {
                    $val = 6 - $val;
                }
                $total += $val;
            }

            $assignment->score()->create([
                'score_total' => $total,
                'score_json'  => ['raw' => $total]
            ]);
        }
    });

    $fresh = QuestionnaireAssignment::with(['questionnaire','responses.item','score'])
        ->find($data['assignment_id']);

    return response()->json([
        'message'    => 'Responses saved',
        'assignment' => $fresh,
    ]);
}


}