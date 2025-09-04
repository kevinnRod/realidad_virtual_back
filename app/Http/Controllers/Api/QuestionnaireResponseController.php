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
'assignment_id' => 'required|exists:questionnaire_assignments,id',
'responses' => 'required|array|min:1',
'responses.*.item_id' => 'required|exists:questionnaire_items,id',
'responses.*.value' => 'required|numeric',
'responses.*.answered_at' => 'nullable|date',
]);


$assignment = QuestionnaireAssignment::findOrFail($data['assignment_id']);


DB::transaction(function () use ($data) {
foreach ($data['responses'] as $res) {
QuestionnaireResponse::updateOrCreate(
['assignment_id' => $data['assignment_id'], 'item_id' => $res['item_id']],
['value' => $res['value'], 'answered_at' => $res['answered_at'] ?? now()]
);
}
});


return response()->json(['message' => 'Responses saved']);
}
}