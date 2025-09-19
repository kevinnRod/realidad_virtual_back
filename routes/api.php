<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StudyController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\VrSessionController;
use App\Http\Controllers\Api\VitalController;
use App\Http\Controllers\Api\AdverseEventController;
use App\Http\Controllers\Api\QuestionnaireController;
use App\Http\Controllers\Api\QuestionnaireResponseController;
use App\Http\Controllers\Api\QuestionnaireScoreController;
use App\Http\Controllers\Api\StudyEnrollmentController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\EligibilityScreeningController;
use App\Http\Controllers\Api\QuestionnaireAssignmentController;

// 👇 NUEVOS
use App\Http\Controllers\Api\EnvironmentController;
use App\Http\Controllers\Api\VrSessionSegmentController;

// Route::get('/ping', fn() => response()->json(['ok' => true]));

Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('users/me', [UserController::class, 'me']);
    Route::get('users/{user}/vr-sessions', [VrSessionController::class, 'byUser']);
    Route::get('/vr-sessions/next-number', [VrSessionController::class, 'nextNumber']);


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

    // Environments
    Route::apiResource('environments', EnvironmentController::class)->only(['index','show']);

    // Session segments

    Route::get   ('vr-sessions/{session}/segments',                   [VrSessionSegmentController::class, 'index']);
    Route::post  ('vr-sessions/{session}/segments',                   [VrSessionSegmentController::class, 'store']);
    Route::put   ('vr-sessions/{session}/segments/{segment}',         [VrSessionSegmentController::class, 'update']);
    Route::delete('vr-sessions/{session}/segments/{segment}',         [VrSessionSegmentController::class, 'destroy']);

    // (Opcional) permitidos y protocolo por estudio (si tienes esos métodos)
    Route::get('studies/{study}/environments', [StudyController::class, 'environments']);
    Route::get('studies/{study}/protocol',     [StudyController::class, 'protocol']);
});
