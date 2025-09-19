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

    // (Opcional) Validación de rango por ítem
    // Descomenta si quieres rechazar respuestas fuera de escala.
    /*
    $itemIds = collect($data['responses'])->pluck('item_id')->unique()->all();
    $items = QuestionnaireItem::whereIn('id', $itemIds)
        ->get(['id','scale_min','scale_max'])
        ->keyBy('id');

    foreach ($data['responses'] as $idx => $res) {
        $it = $items[$res['item_id']] ?? null;
        if ($it) {
            $v = $res['value'];
            if ($v < ($it->scale_min ?? 0) || $v > ($it->scale_max ?? 4)) {
                return response()->json([
                    'message' => "Respuesta fuera de rango para item {$res['item_id']}",
                    'errors'  => ["responses.$idx.value" => ["El valor debe estar entre {$it->scale_min} y {$it->scale_max}."]]
                ], 422);
            }
        }
    }
    */

    DB::transaction(function () use ($data, $assignment) {
        // UPSERT masivo (más eficiente)
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

        // 👇 Autocomplete: si ya respondió todos los ítems del cuestionario, marca completed_at
        $totalItems = QuestionnaireItem::where('questionnaire_id', $assignment->questionnaire_id)->count();
        $answered   = QuestionnaireResponse::where('assignment_id', $assignment->id)->count();

        if ($totalItems > 0 && $answered >= $totalItems && is_null($assignment->completed_at)) {
            $assignment->completed_at = $now;
            $assignment->save();
        }
    });

    // Devuelve el assignment fresco con respuestas para que el front pueda precargar
    $fresh = QuestionnaireAssignment::with(['questionnaire', 'responses.item'])
        ->find($data['assignment_id']);

    return response()->json([
        'message'    => 'Responses saved',
        'assignment' => $fresh,
    ]);
}

}