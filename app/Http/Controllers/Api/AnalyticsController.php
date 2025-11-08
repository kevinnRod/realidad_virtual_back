<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    /**
     * HU18: Resumen de resultados (N, medias, ΔPSS/ΔPAS, %CSAT≥4)
     * GET /api/analytics/summary?start_date=2025-01-01&end_date=2025-12-31&cohort_id=1
     */
    // En AnalyticsController.php, actualizar el método summary():

public function summary(Request $request)
{
    $validated = $request->validate([
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date',
        'cohort_id' => 'nullable|integer|exists:studies,id',
        'study_type' => 'nullable|integer|exists:studies,id', // ✅ NUEVO
        'user_id' => 'nullable|integer|exists:users,id',        // ✅ NUEVO
            'device_id' => 'nullable|integer|exists:devices,id', 
    ]);

    $startDate = $validated['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
    $endDate = $validated['end_date'] ?? Carbon::now()->toDateString();
    $cohortId = $validated['cohort_id'] ?? null;
    $studyType = $validated['study_type'] ?? null; // ✅ NUEVO
    $userId = $validated['user_id'] ?? null;          // ✅ NUEVO
    $deviceId = $validated['device_id'] ?? null;      // ✅ NUEVO

    // ─── 1. N Sesiones ───────────────────────────────────
    $sessionsQuery = DB::table('vr_sessions')
        ->whereBetween('created_at', [$startDate, $endDate]);

    if ($cohortId) {
        $sessionsQuery->whereIn('user_id', function($q) use ($cohortId) {
            $q->select('user_id')
              ->from('study_enrollments')
              ->where('study_id', $cohortId);
        });
    }

    // ✅ NUEVO: Filtro por tipo de estudio
    if ($studyType) {
        $sessionsQuery->where('study_id', $studyType);
    }

    // ✅ NUEVO: Filtro por usuario específico
        if ($userId) {
            $sessionsQuery->where('user_id', $userId);
        }

    // ✅ NUEVO: Filtro por dispositivo específico
        if ($deviceId) {
            $sessionsQuery->where('device_id', $deviceId);
        }

    $totalSessions = $sessionsQuery->count();

    // ─── 2. PSS-10 Pre/Post y Δ ─────────────────────────
    $pssData = $this->calculatePssDeltas($startDate, $endDate, $cohortId, $studyType); // ✅ Pasar $studyType

    // ─── 3. Satisfacción (Video vs VR) ──────────────────
    $satisfactionData = $this->calculateSatisfactionDeltas($startDate, $endDate, $cohortId, $studyType); // ✅ Pasar $studyType

    // ─── 4. %CSAT ≥ 4 (para VR) ──────────────────────────
    $csatPercentage = $this->calculateCsatPercentage($startDate, $endDate, $cohortId, $studyType); // ✅ Pasar $studyType

    // ─── 5. Presión Arterial (Systolic/Diastolic) Pre/Post ─
    $bloodPressureData = $this->calculateBloodPressureDeltas($startDate, $endDate, $cohortId, $studyType); // ✅ Pasar $studyType

    return response()->json([
        'filters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cohort_id' => $cohortId,
            'study_type' => $studyType, // ✅ NUEVO
            'user_id' => $userId,        // ✅ NUEVO
            'device_id' => $deviceId,    // ✅ NUEVO
        ],
        'summary' => [
            'total_sessions' => $totalSessions,
            'pss' => $pssData,
            'satisfaction' => $satisfactionData,
            'csat_percentage' => $csatPercentage,
            'blood_pressure' => $bloodPressureData,
        ],
    ]);
}


    /**
 * Satisfacción (CSAT) comparando Video vs VR.
 * Detecta dinámicamente el campo de modo en vr_sessions (is_vr | mode | delivery_mode | modality | experience_type | media_type | content_type | intervention_type).
 * Devuelve medias y delta (VR - Video).
 */

/** Satisfacción (VR vs Video) usando codes: satisf (VR) y satisf_video (Video) */
    private function calculateSatisfactionDeltas($startDate, $endDate, $cohortId, $studyType = null, $userId = null, $deviceId = null)
    {
    $base = DB::table('questionnaire_scores as qs')
        ->join('questionnaire_assignments as qa', 'qs.assignment_id', '=', 'qa.id')
        ->join('questionnaires as q', 'qa.questionnaire_id', '=', 'q.id')
        ->join('vr_sessions as vs', 'qa.session_id', '=', 'vs.id')
        ->whereBetween('qa.created_at', [$startDate, $endDate])
        ->whereNotNull('qa.completed_at');

    if ($cohortId) {
        $base->whereIn('qa.user_id', function ($q) use ($cohortId) {
            $q->select('user_id')->from('study_enrollments')->where('study_id', $cohortId);
        });
    }

    if ($studyType) {
        $base->where('vs.study_id', $studyType);
    }

    if ($userId) {
            $base->where('qa.user_id', $userId);
    }

    if ($deviceId) {
            $base->where('vs.device_id', $deviceId);
    }

    if ($userId) {
            $base->where('qa.user_id', $userId);
        }

        if ($deviceId) {
            $base->where('vs.device_id', $deviceId);
        }

    $vrAvg    = (clone $base)->where('q.code', 'satisf')->avg('qs.score_total');          // VR
    $videoAvg = (clone $base)->where('q.code', 'satisf_video')->avg('qs.score_total');    // Video

    $overall = null;
    $delta   = null;

    if ($vrAvg !== null && $videoAvg !== null) {
        $delta = round($vrAvg - $videoAvg, 2);
    } else {
        $overall = $vrAvg !== null ? round($vrAvg, 2) : ($videoAvg !== null ? round($videoAvg, 2) : null);
    }

    return [
        'vr_mean'              => $vrAvg    !== null ? round($vrAvg, 2)    : null,
        'video_mean'           => $videoAvg !== null ? round($videoAvg, 2) : null,
        'overall_mean'         => $overall,
        'delta_vr_minus_video' => $delta,
        'group_by'             => 'questionnaires.code',
        'note'                 => 'Usa codes: satisf (VR) y satisf_video (Video).',
    ];
}

/** Presión arterial: intenta (1) cuestionarios BP si existen; si no, (2) tabla de vitales; si no, null */
private function calculateBloodPressureDeltas($startDate, $endDate, $cohortId, $studyType = null, $userId = null, $deviceId = null)
{
    // Si no existe la tabla vitals, mantener el comportamiento anterior
    if (!Schema::hasTable('vitals')) {
        return [
            'systolic'  => ['pre_mean' => null, 'post_mean' => null, 'delta' => null],
            'diastolic' => ['pre_mean' => null, 'post_mean' => null, 'delta' => null],
            'source'    => null,
            'note'      => 'No se encontró la tabla vitals.',
        ];
    }

    // Base: unir vitals con sesiones para usar tu mismo rango por vs.created_at
    $base = DB::table('vitals as vt')
        ->join('vr_sessions as s', 'vt.session_id', '=', 's.id')
        ->whereBetween('s.created_at', [$startDate, $endDate]);

    if ($cohortId) {
        $base->whereIn('s.user_id', function ($q) use ($cohortId) {
            $q->select('user_id')->from('study_enrollments')->where('study_id', $cohortId);
        });
    }

    if ($studyType) {
        $base->where('s.study_id', $studyType); // ✅ corregido alias
    }

    if ($userId) {
            $base->where('s.user_id', $userId);
        }

        if ($deviceId) {
            $base->where('s.device_id', $deviceId);
        }

    // (Opcional) sólo mediciones sentadas; si quieres ambas, elimina este where
    $base = $base->where(function ($w) {
        $w->whereNull('vt.posture')->orWhere('vt.posture', 'seated');
    });

    // Promedios pre/post
    $preSys  = (clone $base)->where('vt.phase', 'pre')->avg('vt.bp_sys');
    $postSys = (clone $base)->where('vt.phase', 'post')->avg('vt.bp_sys');

    $preDia  = (clone $base)->where('vt.phase', 'pre')->avg('vt.bp_dia');
    $postDia = (clone $base)->where('vt.phase', 'post')->avg('vt.bp_dia');

    return [
        'systolic' => [
            'pre_mean'  => $preSys  !== null ? round($preSys, 2)  : null,
            'post_mean' => $postSys !== null ? round($postSys, 2) : null,
            'delta'     => ($preSys !== null && $postSys !== null) ? round($postSys - $preSys, 2) : null,
        ],
        'diastolic' => [
            'pre_mean'  => $preDia  !== null ? round($preDia, 2)  : null,
            'post_mean' => $postDia !== null ? round($postDia, 2) : null,
            'delta'     => ($preDia !== null && $postDia !== null) ? round($postDia - $preDia, 2) : null,
        ],
        'source' => 'vitals',
        'note'   => 'Cálculo desde vitals (phase=pre/post, posture=seated).',
    ];
}


/** Helper: BP desde cuestionarios por items (requiere questionnaire_items con codes 'sbp'/'dbp') */
private function bpFromQuestionnaireItems($startDate, $endDate, $cohortId): ?array
{
    // Ajusta aquí el/los códigos de tu cuestionario de PA si lo usas (ej. 'bp')
    $bpCodes = ['bp'];

    $itemsTable = DB::table('questionnaire_items')->select('id','questionnaire_id','code');
    $itemsExist = $itemsTable->count() > 0;
    if (!$itemsExist) return null;

    $base = DB::table('questionnaire_responses as r')
        ->join('questionnaire_assignments as qa', 'r.assignment_id','=','qa.id')
        ->join('questionnaires as q', 'qa.questionnaire_id','=','q.id')
        ->join('questionnaire_items as i', 'r.item_id','=','i.id')
        ->whereBetween('qa.created_at', [$startDate, $endDate])
        ->whereNotNull('qa.completed_at');

    if ($cohortId) {
        $base->whereIn('qa.user_id', function ($q) use ($cohortId) {
            $q->select('user_id')->from('study_enrollments')->where('study_id', $cohortId);
        });
    }

    // ¿Existe algún code de BP?
    $hasBp = (clone $base)->whereIn('q.code', $bpCodes)->count() > 0;
    if (!$hasBp) return null;

    $preSys  = (clone $base)->whereIn('q.code',$bpCodes)->where('qa.context','pre')
                ->whereIn(DB::raw('LOWER(i.code)'), ['sbp','systolic'])->avg('r.value');
    $postSys = (clone $base)->whereIn('q.code',$bpCodes)->where('qa.context','post')
                ->whereIn(DB::raw('LOWER(i.code)'), ['sbp','systolic'])->avg('r.value');

    $preDia  = (clone $base)->whereIn('q.code',$bpCodes)->where('qa.context','pre')
                ->whereIn(DB::raw('LOWER(i.code)'), ['dbp','diastolic'])->avg('r.value');
    $postDia = (clone $base)->whereIn('q.code',$bpCodes)->where('qa.context','post')
                ->whereIn(DB::raw('LOWER(i.code)'), ['dbp','diastolic'])->avg('r.value');

    return [
        'systolic' => [
            'pre_mean'  => $preSys  !== null ? round($preSys, 2)  : null,
            'post_mean' => $postSys !== null ? round($postSys, 2) : null,
            'delta'     => ($preSys !== null && $postSys !== null) ? round($postSys - $preSys, 2) : null,
        ],
        'diastolic' => [
            'pre_mean'  => $preDia  !== null ? round($preDia, 2)  : null,
            'post_mean' => $postDia !== null ? round($postDia, 2) : null,
            'delta'     => ($preDia !== null && $postDia !== null) ? round($postDia - $preDia, 2) : null,
        ],
    ];
}

/** Helper: BP desde vital_signs (si la tienes) */
private function bpFromVitalSigns($startDate, $endDate, $cohortId): ?array
{
    // Detectar columnas típicas
    $sysCol = Schema::hasColumn('vital_signs','systolic')  ? 'systolic'  :
              (Schema::hasColumn('vital_signs','sbp')      ? 'sbp'       : null);
    $diaCol = Schema::hasColumn('vital_signs','diastolic') ? 'diastolic' :
              (Schema::hasColumn('vital_signs','dbp')      ? 'dbp'       : null);

    if (!$sysCol || !$diaCol) return null;

    // Contexto (boolean o string)
    $ctxCol = null; $ctxType = null;
    if (Schema::hasColumn('vital_signs','is_pre')) { $ctxCol='is_pre'; $ctxType='boolean'; }
    elseif (Schema::hasColumn('vital_signs','context')) { $ctxCol='context'; $ctxType='string'; }

    $base = DB::table('vital_signs as vt')
        ->join('vr_sessions as s','vt.session_id','=','s.id')
        ->whereBetween('s.created_at', [$startDate, $endDate]);

    if ($cohortId) {
        $base->whereIn('s.user_id', function ($q) use ($cohortId) {
            $q->select('user_id')->from('study_enrollments')->where('study_id', $cohortId);
        });
    }

    if ($ctxCol) {
        if ($ctxType === 'boolean') {
            $preSys  = (clone $base)->where("vt.$ctxCol",1)->avg("vt.$sysCol");
            $postSys = (clone $base)->where("vt.$ctxCol",0)->avg("vt.$sysCol");
            $preDia  = (clone $base)->where("vt.$ctxCol",1)->avg("vt.$diaCol");
            $postDia = (clone $base)->where("vt.$ctxCol",0)->avg("vt.$diaCol");
        } else {
            $preSys  = (clone $base)->whereIn(DB::raw("LOWER(vt.$ctxCol)"),['pre','baseline','before'])->avg("vt.$sysCol");
            $postSys = (clone $base)->whereIn(DB::raw("LOWER(vt.$ctxCol)"),['post','after','followup'])->avg("vt.$sysCol");
            $preDia  = (clone $base)->whereIn(DB::raw("LOWER(vt.$ctxCol)"),['pre','baseline','before'])->avg("vt.$diaCol");
            $postDia = (clone $base)->whereIn(DB::raw("LOWER(vt.$ctxCol)"),['post','after','followup'])->avg("vt.$diaCol");
        }
    } else {
        // Sin contexto: primera vs última medición por sesión (si hay timestamps)
        if (!Schema::hasColumn('vital_signs','created_at')) return null;

        $first = (clone $base)->select('s.id as sid', DB::raw('MIN(vt.created_at) as ts'))->groupBy('s.id');
        $last  = (clone $base)->select('s.id as sid', DB::raw('MAX(vt.created_at) as ts'))->groupBy('s.id');

        $preSys = DB::table('vr_sessions as s')
            ->joinSub($first,'f',fn($j)=>$j->on('s.id','=','f.sid'))
            ->join('vital_signs as vt', fn($j)=>$j->on('vt.session_id','=','s.id')->on('vt.created_at','=','f.ts'))
            ->avg("vt.$sysCol");
        $postSys = DB::table('vr_sessions as s')
            ->joinSub($last,'l',fn($j)=>$j->on('s.id','=','l.sid'))
            ->join('vital_signs as vt', fn($j)=>$j->on('vt.session_id','=','s.id')->on('vt.created_at','=','l.ts'))
            ->avg("vt.$sysCol");

        $preDia = DB::table('vr_sessions as s')
            ->joinSub($first,'f',fn($j)=>$j->on('s.id','=','f.sid'))
            ->join('vital_signs as vt', fn($j)=>$j->on('vt.session_id','=','s.id')->on('vt.created_at','=','f.ts'))
            ->avg("vt.$diaCol");
        $postDia = DB::table('vr_sessions as s')
            ->joinSub($last,'l',fn($j)=>$j->on('s.id','=','l.sid'))
            ->join('vital_signs as vt', fn($j)=>$j->on('vt.session_id','=','s.id')->on('vt.created_at','=','l.ts'))
            ->avg("vt.$diaCol");
    }

    return [
        'systolic' => [
            'pre_mean'  => isset($preSys)  && $preSys  !== null ? round($preSys, 2)  : null,
            'post_mean' => isset($postSys) && $postSys !== null ? round($postSys, 2) : null,
            'delta'     => (isset($preSys,$postSys) && $preSys !== null && $postSys !== null) ? round($postSys - $preSys, 2) : null,
        ],
        'diastolic' => [
            'pre_mean'  => isset($preDia)  && $preDia  !== null ? round($preDia, 2)  : null,
            'post_mean' => isset($postDia) && $postDia !== null ? round($postDia, 2) : null,
            'delta'     => (isset($preDia,$postDia) && $preDia !== null && $postDia !== null) ? round($postDia - $preDia, 2) : null,
        ],
    ];
}

/**
 * PAD = Presión Arterial Diastólica (misma lógica que PAS pero con código 'PAD').
 */
private function calculatePadDeltas($startDate, $endDate, $cohortId)
{
    $query = DB::table('questionnaire_scores as qs')
        ->join('questionnaire_assignments as qa', 'qs.assignment_id', '=', 'qa.id')
        ->join('questionnaires as q', 'qa.questionnaire_id', '=', 'q.id')
        ->where('q.code', 'PAD')
        ->whereBetween('qa.created_at', [$startDate, $endDate])
        ->whereNotNull('qa.completed_at');

    if ($cohortId) {
        $query->whereIn('qa.user_id', function ($q) use ($cohortId) {
            $q->select('user_id')->from('study_enrollments')->where('study_id', $cohortId);
        });
    }

    $pre = (clone $query)->where('qa.context', 'pre')->avg('qs.score_total');
    $post = (clone $query)->where('qa.context', 'post')->avg('qs.score_total');

    return [
        'pre_mean' => $pre ? round($pre, 2) : null,
        'post_mean' => $post ? round($post, 2) : null,
        'delta' => ($pre && $post) ? round($post - $pre, 2) : null,
    ];
}

/**
 * Detecta dinámicamente una columna utilizable para diferenciar VR vs Video en vr_sessions.
 * Prioriza booleanos (is_vr) y luego string (mode, delivery_mode, ...).
 * Retorna ['column' => string, 'type' => 'boolean'|'string'] o null si no hay coincidencias.
 */
private function detectSessionModeColumn(): ?array
{
    $schema = DB::getDatabaseName();

    $booleanCandidates = ['is_vr', 'is_virtual', 'vr_mode'];
    $stringCandidates  = ['mode', 'delivery_mode', 'modality', 'experience_type', 'media_type', 'content_type', 'intervention_type'];

    $candidates = array_merge($booleanCandidates, $stringCandidates);

    $cols = DB::table('information_schema.columns')
        ->select('column_name', 'data_type')
        ->where('table_schema', $schema)
        ->where('table_name', 'vr_sessions')
        ->whereIn('column_name', $candidates)
        ->get();

    if ($cols->isEmpty()) {
        return null;
    }

    // Preferir booleanos si existen
    foreach ($booleanCandidates as $bc) {
        $hit = $cols->firstWhere('column_name', $bc);
        if ($hit) {
            // MySQL puede tener tinyint(1) como boolean
            return ['column' => $bc, 'type' => 'boolean'];
        }
    }

    // Si no hay booleanos, usar el primer string disponible
    foreach ($stringCandidates as $sc) {
        $hit = $cols->firstWhere('column_name', $sc);
        if ($hit) {
            return ['column' => $sc, 'type' => 'string'];
        }
    }

    return null;
}


    /** PSS-10 (pre/post/delta) - usa code=pss10 */
// Ejemplo en calculatePssDeltas:
private function calculatePssDeltas($startDate, $endDate, $cohortId, $studyType = null, $userId = null, $deviceId = null)
{
    $query = DB::table('questionnaire_scores as qs')
        ->join('questionnaire_assignments as qa', 'qs.assignment_id', '=', 'qa.id')
        ->join('questionnaires as q', 'qa.questionnaire_id', '=', 'q.id')
        ->join('vr_sessions as vs', 'qa.session_id', '=', 'vs.id') // ✅ JOIN con vr_sessions
        ->where('q.code', 'pss10')
        ->whereBetween('qa.created_at', [$startDate, $endDate])
        ->whereNotNull('qa.completed_at');

    if ($cohortId) {
        $query->whereIn('qa.user_id', function($q) use ($cohortId) {
            $q->select('user_id')->from('study_enrollments')->where('study_id', $cohortId);
        });
    }

    // ✅ NUEVO: Filtro por tipo de estudio
    if ($studyType) {
        $query->where('vs.study_id', $studyType);
    }

    // ✅ NUEVO: Filtros adicionales
        if ($userId) {
            $query->where('qa.user_id', $userId);
        }

        if ($deviceId) {
            $query->where('vs.device_id', $deviceId);
        }

    $pre  = (clone $query)->where('qa.context', 'pre')->avg('qs.score_total');
    $post = (clone $query)->where('qa.context', 'post')->avg('qs.score_total');

    return [
        'pre_mean'  => $pre  !== null ? round($pre, 2)  : null,
        'post_mean' => $post !== null ? round($post, 2) : null,
        'delta'     => ($pre !== null && $post !== null) ? round($post - $pre, 2) : null,
    ];
}

    /**
     * Calcula medias PAS pre/post y delta
     */
    private function calculatePasDeltas($startDate, $endDate, $cohortId)
    {
        $query = DB::table('questionnaire_scores as qs')
            ->join('questionnaire_assignments as qa', 'qs.assignment_id', '=', 'qa.id')
            ->join('questionnaires as q', 'qa.questionnaire_id', '=', 'q.id')
            ->where('q.code', 'PAS') // Código del cuestionario PAS
            ->whereBetween('qa.created_at', [$startDate, $endDate])
            ->where('qa.completed_at', '!=', null); // Completado

        if ($cohortId) {
            $query->whereIn('qa.user_id', function($q) use ($cohortId) {
                $q->select('user_id')
                  ->from('study_enrollments')
                  ->where('study_id', $cohortId);
            });
        }

        $pre = (clone $query)->where('qa.context', 'pre')->avg('qs.score_total');
        $post = (clone $query)->where('qa.context', 'post')->avg('qs.score_total');

        return [
            'pre_mean' => $pre ? round($pre, 2) : null,
            'post_mean' => $post ? round($post, 2) : null,
            'delta' => ($pre && $post) ? round($post - $pre, 2) : null,
        ];
    }

private function calculateCsatPercentage($startDate, $endDate, $cohortId, $studyType = null, $userId = null, $deviceId = null)
{
    // Respuestas del cuestionario de satisfacción VR
    $base = DB::table('questionnaire_responses as r')
        ->join('questionnaire_assignments as qa', 'qa.id', '=', 'r.assignment_id')
        ->join('questionnaires as q', 'q.id', '=', 'qa.questionnaire_id')
        ->join('vr_sessions as vs', 'qa.session_id', '=', 'vs.id')
        ->where('q.code', 'satisf')
        ->whereBetween('qa.created_at', [$startDate, $endDate])
        ->whereNotNull('qa.completed_at');

    if ($cohortId) {
        $base->whereIn('qa.user_id', function ($q) use ($cohortId) {
            $q->select('user_id')->from('study_enrollments')->where('study_id', $cohortId);
        });
    }

    if ($studyType) {
        $base->where('vs.study_id', $studyType);
    }

// ✅ NUEVO: Filtros adicionales
        if ($userId) {
            $base->where('qa.user_id', $userId);
        }

        if ($deviceId) {
            $base->where('vs.device_id', $deviceId);
        }

    // Promedio por asignación (agrupamos y alias del agregado)
    $perAssignment = (clone $base)
        ->groupBy('r.assignment_id')
        ->selectRaw('r.assignment_id, AVG(r.value) as avg_item');

    // Total de asignaciones con respuestas
    $total = (clone $perAssignment)->count();
    if ($total === 0) return null;

    // Asignaciones con promedio >= 4
    $satisfied = (clone $perAssignment)
        ->having('avg_item', '>=', 4)
        ->count();

    return round(($satisfied / $total) * 100, 1);
}


    /**
     * HU19: Exportar CSV
     * GET /api/analytics/export/csv?start_date=...&end_date=...&cohort_id=...
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'cohort_id' => 'nullable|integer|exists:studies,id',
            'study_type' => 'nullable|integer|exists:studies,id',
            'user_id' => 'nullable|integer|exists:users,id',        // ✅ NUEVO
            'device_id' => 'nullable|integer|exists:devices,id',    // ✅ NUEVO
        ]);

        $data = $this->getExportData($validated);

        $filename = 'serenity_vr_results_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8 (Excel compatibility)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Encabezados
            fputcsv($file, [
    'user_anonymous_id',
    'session_number',
    'session_date',
    'pss_pre',
    'pss_post',
    'pss_delta',
    'bp_sys_pre',
    'bp_sys_post',
    'bp_sys_delta',
    'satisfaction_video',
    'satisfaction_vr',
    'satisfaction_delta',
    'total_duration_minutes',
    'timezone'
]);


            // Datos
            foreach ($data as $row) {
                fputcsv($file, [
    $row['user_anonymous_id'] ?? null,
    $row['session_no'] ?? null,             // nombre real del campo en BD
    $row['session_date'] ?? null,
    $row['pss_pre'] ?? null,
    $row['pss_post'] ?? null,
    $row['pss_delta'] ?? null,
    $row['bp_sys_pre'] ?? null,
    $row['bp_sys_post'] ?? null,
    $row['bp_sys_delta'] ?? null,
    $row['satisfaction_video'] ?? null,
    $row['satisfaction_vr'] ?? null,
    $row['satisfaction_delta'] ?? null,
    $row['total_duration_minutes'] ?? null,
    $row['timezone'] ?? null,
]);


            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * HU19: Exportar JSON
     * GET /api/analytics/export/json?start_date=...&end_date=...&cohort_id=...
     */
    public function exportJson(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'cohort_id' => 'nullable|integer|exists:studies,id',
            'study_type' => 'nullable|integer|exists:studies,id',
            'user_id' => 'nullable|integer|exists:users,id',        // ✅ NUEVO
            'device_id' => 'nullable|integer|exists:devices,id',    // ✅ NUEVO
        ]);

        $data = $this->getExportData($validated);

        $export = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'timezone' => config('app.timezone'),
                'filters' => $validated,
                'data_dictionary' => [
                    'user_anonymous_id' => 'ID anónimo del participante',
                    'session_number' => 'Número de sesión VR',
                    'session_date' => 'Fecha de la sesión',
                    'pss_pre' => 'Puntuación PSS-10 pre-sesión',
                    'pss_post' => 'Puntuación PSS-10 post-sesión',
                    'pss_delta' => 'Cambio en PSS (post - pre)',
                    'pas_pre' => 'Puntuación PAS pre-sesión',
                    'pas_post' => 'Puntuación PAS post-sesión',
                    'pas_delta' => 'Cambio en PAS (post - pre)',
                    'csat_score' => 'Puntuación CSAT (1-5)',
                    'total_duration_minutes' => 'Duración total en minutos',
                ]
            ],
            'data' => $data
        ];

        $filename = 'serenity_vr_results_' . date('Y-m-d_His') . '.json';

        return response()->json($export)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Obtiene datos para exportación
     */
    private function getExportData(array $filters)
{
    $startDate = $filters['start_date'] ?? Carbon::now()->subDays(30)->toDateString();
    $endDate = $filters['end_date'] ?? Carbon::now()->toDateString();
    $cohortId = $filters['cohort_id'] ?? null;
    $studyType = $filters['study_type'] ?? null; // ✅ NUEVO
    $userId = $filters['user_id'] ?? null;          // ✅ NUEVO
        $deviceId = $filters['device_id'] ?? null;      // ✅ NUEVO

    $query = DB::table('vr_sessions as vs')
        ->join('users as u', 'vs.user_id', '=', 'u.id')
        ->leftJoin('questionnaire_assignments as qa_pss_pre', function($join) {
            $join->on('vs.id', '=', 'qa_pss_pre.session_id')
                 ->where('qa_pss_pre.context', 'pre');
        })
        ->leftJoin('questionnaire_scores as qs_pss_pre', 'qa_pss_pre.id', '=', 'qs_pss_pre.assignment_id')
        ->leftJoin('questionnaires as q1', function($join) {
            $join->on('qa_pss_pre.questionnaire_id', '=', 'q1.id')
                 ->where('q1.code', 'pss10');
        })
        ->leftJoin('questionnaire_assignments as qa_pss_post', function($join) {
            $join->on('vs.id', '=', 'qa_pss_post.session_id')
                 ->where('qa_pss_post.context', 'post');
        })
        ->leftJoin('questionnaire_scores as qs_pss_post', 'qa_pss_post.id', '=', 'qs_pss_post.assignment_id')
        ->leftJoin('questionnaires as q2', function($join) {
            $join->on('qa_pss_post.questionnaire_id', '=', 'q2.id')
                 ->where('q2.code', 'pss10');
        })
        // Satisfacción Video
        ->leftJoin('questionnaire_assignments as qa_sat_video', function($join) {
            $join->on('vs.id', '=', 'qa_sat_video.session_id')
                 ->where('qa_sat_video.context', 'pre');
        })
        ->leftJoin('questionnaire_scores as qs_sat_video', 'qa_sat_video.id', '=', 'qs_sat_video.assignment_id')
        ->leftJoin('questionnaires as q3', function($join) {
            $join->on('qa_sat_video.questionnaire_id', '=', 'q3.id')
                 ->where('q3.code', 'satisf_video');
        })
        // Satisfacción VR
        ->leftJoin('questionnaire_assignments as qa_sat_vr', function($join) {
            $join->on('vs.id', '=', 'qa_sat_vr.session_id')
                 ->where('qa_sat_vr.context', 'post');
        })
        ->leftJoin('questionnaire_scores as qs_sat_vr', 'qa_sat_vr.id', '=', 'qs_sat_vr.assignment_id')
        ->leftJoin('questionnaires as q4', function($join) {
            $join->on('qa_sat_vr.questionnaire_id', '=', 'q4.id')
                 ->where('q4.code', 'satisf');
        })
        // Presión Arterial
        ->leftJoin('vitals as vt_pre', function($join) {
            $join->on('vs.id', '=', 'vt_pre.session_id')
                 ->where('vt_pre.phase', 'pre');
        })
        ->leftJoin('vitals as vt_post', function($join) {
            $join->on('vs.id', '=', 'vt_post.session_id')
                 ->where('vt_post.phase', 'post');
        })
        ->whereBetween('vs.created_at', [$startDate, $endDate])
        ->select([
            DB::raw("CONCAT('USER_', LPAD(u.id, 4, '0')) as user_anonymous_id"),
            'vs.session_no',
            DB::raw('DATE(vs.created_at) as session_date'),
            'qs_pss_pre.score_total as pss_pre',
            'qs_pss_post.score_total as pss_post',
            DB::raw('(qs_pss_post.score_total - qs_pss_pre.score_total) as pss_delta'),
            'qs_sat_video.score_total as satisfaction_video',
            'qs_sat_vr.score_total as satisfaction_vr',
            DB::raw('(qs_sat_vr.score_total - qs_sat_video.score_total) as satisfaction_delta'),
            'vt_pre.bp_sys as bp_sys_pre',
            'vt_pre.bp_dia as bp_dia_pre',
            'vt_post.bp_sys as bp_sys_post',
            'vt_post.bp_dia as bp_dia_post',
            DB::raw('(vt_post.bp_sys - vt_pre.bp_sys) as bp_sys_delta'),
            DB::raw('(vt_post.bp_dia - vt_pre.bp_dia) as bp_dia_delta'),
            'vs.total_duration_minutes',
            DB::raw("'" . config('app.timezone') . "' as timezone")
        ]);

    if ($cohortId) {
        $query->whereIn('vs.user_id', function($q) use ($cohortId) {
            $q->select('user_id')
              ->from('study_enrollments')
              ->where('study_id', $cohortId);
        });
    }

    // ✅ NUEVO: Filtro por tipo de estudio
    if ($studyType) {
        $query->where('vs.study_id', $studyType);
    }

     // ✅ NUEVO: Filtros adicionales
        if ($userId) {
            $query->where('vs.user_id', $userId);
        }

        if ($deviceId) {
            $query->where('vs.device_id', $deviceId);
        }

    return $query->get()->map(fn($row) => (array) $row)->toArray();

}
}