<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionnaireAssignment;
use Illuminate\Http\Request;

class QuestionnaireAssignmentController extends Controller
{
    // GET /api/questionnaire-assignments?user_id=&study_id=&session_id=&status=pending|completed
    public function index(Request $r)
    {
        $q = QuestionnaireAssignment::query()
            ->with(['user','questionnaire','study','vrSession']);

        if ($r->filled('user_id'))    $q->where('user_id', $r->integer('user_id'));
        if ($r->filled('study_id'))   $q->where('study_id', $r->integer('study_id'));
        if ($r->filled('session_id')) $q->where('session_id', $r->integer('session_id'));
        if ($r->filled('status')) {
            $status = $r->string('status')->toString();
            if ($status === 'pending')   $q->whereNull('completed_at');
            if ($status === 'completed') $q->whereNotNull('completed_at');
        }

        return $q->paginate(20);
    }

    // GET /api/questionnaire-assignments/{questionnaire_assignment}
    public function show(QuestionnaireAssignment $questionnaireAssignment)
    {
        $assignment = $questionnaireAssignment->load([
            'questionnaire',                         // meta del cuestionario
            'responses.item'                         // respuestas + ítem (reverse_scored, etc.)
        ]);

        return response()->json($assignment);
    }

    // POST /api/questionnaire-assignments
    public function store(Request $r)
    {
        $data = $r->validate([
            'user_id'          => ['required','exists:users,id'],           // si luego quieres usar $r->user()->id, cámbialo a sometimes
            'questionnaire_id' => ['required','exists:questionnaires,id'],
            'study_id'         => ['nullable','exists:studies,id'],
            'session_id'       => ['nullable','exists:vr_sessions,id'],
            'context'          => ['required','in:baseline,pre,post,followup'],
            'assigned_at'      => ['required','date'],
            'due_at'           => ['nullable','date'],
        ]);

        return QuestionnaireAssignment::create($data);
    }

    // PUT /api/questionnaire-assignments/{questionnaire_assignment}
    public function update(Request $r, QuestionnaireAssignment $questionnaire_assignment)
    {
        $data = $r->validate(['completed_at' => ['nullable','date']]);
        $questionnaire_assignment->update($data);
        return $questionnaire_assignment;
    }

    // DELETE /api/questionnaire-assignments/{questionnaire_assignment}
    public function destroy(QuestionnaireAssignment $questionnaire_assignment)
    {
        $questionnaire_assignment->delete();
        return response()->noContent();
    }

public function complete(QuestionnaireAssignment $assignment)
{
    if (!$assignment->completed_at) {
        $assignment->completed_at = now();
        $assignment->save();
    }

    // si no hay score guardado, crearlo
    if (!$assignment->score()->exists()) {
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

    return response()->json($assignment->fresh()->load(['questionnaire','responses.item','score']));
}


// QuestionnaireAssignmentController.php
public function lastWeekScores(Request $request)
{
    $user = $request->user();
    $since = now()->subDays(7)->startOfDay();

    $assignments = QuestionnaireAssignment::with('score')
        ->where('user_id', $user->id)
        ->where('completed_at', '>=', $since)
        ->get()
        ->map(function ($assignment) {
            return [
                'id'      => $assignment->id,
                'score'   => $assignment->score?->score_total ? (float) $assignment->score->score_total : null,
                'context' => $assignment->context,
            ];
        });

    return response()->json($assignments);
}


}
