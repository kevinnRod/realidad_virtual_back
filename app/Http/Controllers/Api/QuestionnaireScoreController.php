<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\QuestionnaireAssignment;
use App\Services\QuestionnaireScoringService;


class QuestionnaireScoreController extends Controller
{
public function scoreAndSave(QuestionnaireAssignment $assignment, QuestionnaireScoringService $svc)
{
$score = $svc->scoreAssignment($assignment->id);
return response()->json($score);
}
}