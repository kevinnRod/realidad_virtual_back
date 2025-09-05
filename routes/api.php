<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudyController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\VrSessionController;
use App\Http\Controllers\Api\VitalController;
use App\Http\Controllers\Api\QuestionnaireController;
use App\Http\Controllers\Api\QuestionnaireAssignmentController;
use App\Http\Controllers\Api\QuestionnaireResponseController;
use App\Http\Controllers\Api\QuestionnaireScoreController;
use App\Http\Controllers\Api\AdverseEventController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudyEnrollmentController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\EligibilityScreeningController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;

Route::get('/ping', fn() => response()->json(['ok' => true]));

Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']); 

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('users/me', [UserController::class, 'me']);
    Route::get('users/{user}/vr-sessions', [VrSessionController::class, 'byUser']);


// CRUDs principales
Route::apiResource('studies', StudyController::class);
Route::apiResource('devices', DeviceController::class);
Route::apiResource('vr-sessions', VrSessionController::class);
Route::apiResource('vitals', VitalController::class)->only(['index','store','show','destroy']);
Route::apiResource('adverse-events', AdverseEventController::class)->only(['index','store','show','destroy']);


// Cuestionarios
Route::get('questionnaires/{questionnaire}/items', [QuestionnaireController::class, 'items']);
Route::apiResource('questionnaires', QuestionnaireController::class)->only(['index','show']);


// Asignaciones y respuestas
Route::apiResource('questionnaire-assignments', QuestionnaireAssignmentController::class)->only(['index','store','show','update','destroy']);
Route::post('questionnaire-assignments/{assignment}/complete', [QuestionnaireAssignmentController::class, 'complete']);
Route::apiResource('questionnaire-responses', QuestionnaireResponseController::class)->only(['store']);
Route::post('questionnaire-assignments/{assignment}/score', [QuestionnaireScoreController::class, 'scoreAndSave']);


// Consentimientos y screening
Route::apiResource('consents', ConsentController::class)->only(['index','store','show']);
Route::apiResource('eligibility-screenings', EligibilityScreeningController::class)->only(['index','store','show']);


// Enrolamientos
Route::apiResource('study-enrollments', StudyEnrollmentController::class)->only(['index','store','show','update']);
});