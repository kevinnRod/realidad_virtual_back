<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Questionnaire;


class QuestionnaireController extends Controller
{
public function index() { return Questionnaire::where('is_active',1)->paginate(20); }
public function show(Questionnaire $questionnaire) { return $questionnaire->load('items'); }
public function items(Questionnaire $questionnaire) { return $questionnaire->items()->orderBy('sort_order')->get(); }
}


// =============================================
// app/Http/Controllers/Api/QuestionnaireAssignmentController.php
// =============================================
namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\QuestionnaireAssignment;
use Illuminate\Http\Request;


class QuestionnaireAssignmentController extends Controller
{
public function index() {
return QuestionnaireAssignment::with(['user','questionnaire','study','vrSession'])->paginate(20);
}


public function show(QuestionnaireAssignment $questionnaire_assignment) {
return $questionnaire_assignment->load(['questionnaire.items','responses','score']);
}


public function store(Request $r) {
$data = $r->validate([
'user_id'=>'required|exists:users,id',
'questionnaire_id'=>'required|exists:questionnaires,id',
'study_id'=>'nullable|exists:studies,id',
'session_id'=>'nullable|exists:vr_sessions,id',
'context'=>'required|in:baseline,pre,post,followup',
'assigned_at'=>'required|date','due_at'=>'nullable|date'
]);
return QuestionnaireAssignment::create($data);
}


public function update(Request $r, QuestionnaireAssignment $questionnaire_assignment) {
$data = $r->validate(['completed_at'=>'nullable|date']);
$questionnaire_assignment->update($data); return $questionnaire_assignment;
}


public function destroy(QuestionnaireAssignment $questionnaire_assignment) { $questionnaire_assignment->delete(); return response()->noContent(); }


public function complete(QuestionnaireAssignment $assignment) {
$assignment->update(['completed_at' => now()]);
return $assignment->fresh('score');
}
}