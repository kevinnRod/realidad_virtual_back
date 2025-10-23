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
use App\Http\Controllers\Api\VrAuthController;
use App\Http\Controllers\Api\VrSessionSegmentController;

// Route::get('/ping', fn() => response()->json(['ok' => true]));

Route::post('/register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Login desde RV
Route::post('vr/login', [VrAuthController::class, 'loginWithCode']);

// Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
//     return $request->user()->load(['study', 'organization']); // incluye relaciones si necesitas
// });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('logout', [AuthController::class, 'logout']);
    // Route::get('/user', fn (Request $request) => $request->user());


    Route::middleware('admin')->group(function () {
            Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        });
    Route::post('vr/generate-code', [VrAuthController::class, 'generateCode']);
    Route::get('/vr/code-status', [VrAuthController::class, 'checkCodeStatus']);


    Route::get('users/{user}/vr-sessions', [VrSessionController::class, 'byUser'])->where('user', '[0-9]+');
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/profile/password', [UserController::class, 'updatePassword']);



    Route::get('/vr-sessions/next-number', [VrSessionController::class, 'nextNumber']);


    Route::get('vr-sessions/today-count', [VrSessionController::class, 'countToday']);
    Route::get('vr-sessions/last-week', [VrSessionController::class, 'lastWeekSessions']);
    Route::get('vr-sessions/environment-durations', [VrSessionSegmentController::class, 'durationByEnvironment']);
    Route::get('questionnaire-assignments/last-week-scores', [QuestionnaireAssignmentController::class, 'lastWeekScores']);


    // routes/api.php
    Route::get('vitals/avg-heart-rate', [VitalController::class, 'avgHeartRate']);
    // CRUDs principales
    Route::apiResource('studies', StudyController::class);
    Route::apiResource('devices', DeviceController::class);

    //Listar sesiones
    Route::get('vr-sessions/unity', [VrSessionController::class, 'forUnity']);

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
    Route::get('/consents', [ConsentController::class, 'index']);
    Route::get('/consents/check', [ConsentController::class, 'check']);
    Route::post('/consents', [ConsentController::class, 'store']);
    Route::get('/consents/{id}', [ConsentController::class, 'show']);

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




    // Iniciar y finalizar sesión VR
    Route::post('/vr-sessions/{session}/start', [VrSessionController::class, 'startSession']);
    Route::post('/vr-sessions/{session}/end',   [VrSessionController::class, 'endSession']);

});
