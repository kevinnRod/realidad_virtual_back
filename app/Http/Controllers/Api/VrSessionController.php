<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAssignment;
use App\Models\VrSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VrSessionController extends Controller
{
public function index(Request $r)
{
    $user = $r->user();
    $q    = trim((string) $r->query('q', ''));
    $isAdmin = (bool) ($user->is_admin ?? ($user->role ?? null) === 'admin');

    $base = VrSession::query()
        ->with([
            'study:id,name',
            'device:id,code',
            'user:id,name,email,code',
            // traemos sólo el primer segmento y su environment
            'segments' => function ($q) {
                $q->orderBy('sort_order')->limit(1)
                  ->with(['environment:id,name']);
            },
        ])
        ->select([
            'id','user_id','study_id','device_id',
            'session_no','scheduled_at','started_at','ended_at','created_at',
            'total_duration_minutes',
        ]);

    if (!$isAdmin) {
        $base->where('user_id', $user->id);
    } else if ($q !== '') {
        $base->whereHas('user', function ($w) use ($q) {
            $w->where('name', 'like', "%{$q}%")
              ->orWhere('email', 'like', "%{$q}%")
              ->orWhere('code', 'like', "%{$q}%");
        });
    }

    $base->orderByRaw(
        'COALESCE(vr_sessions.started_at, vr_sessions.scheduled_at, vr_sessions.created_at) DESC, vr_sessions.id DESC'
    );
    return $base->paginate(20);
}


public function show(VrSession $vr_session) { return $vr_session->load(['user','study','device','vitals','adverseEvents']); }

public function nextNumber(Request $r)
{
    $data = $r->validate([
        'study_id' => ['required','exists:studies,id'],
        'user_id'  => ['nullable','integer','exists:users,id'],
    ]);

    $userId = $data['user_id'] ?? $r->user()->id;

    // ATENCIÓN: no necesitamos lock aquí (solo lectura),
    // el lock real está en store(). Este es informativo para UI.
    $max = DB::table('vr_sessions')
        ->where('user_id',  $userId)
        ->where('study_id', $data['study_id'])
        ->max('session_no');

    return response()->json([
        'next_session_no' => ($max ?? 0) + 1
    ]);
}

private function ensureDefaultAssignmentsForSession($session): void
{
    // Buscar los cuestionarios por código
    $pssId         = Questionnaire::where('code', 'pss10')->value('id');
    $satVrId       = Questionnaire::where('code', 'satisf')->value('id');
    $satVideoId    = Questionnaire::where('code', 'satisf_video')->value('id');

    // Verificación mínima: asegúrate de que al menos existen PSS y ambos SAT
    if (!$pssId || !$satVrId || !$satVideoId) {
        return; // evita romper si falta alguno
    }

    $now        = now();
    $assignedAt = $session->scheduled_at ?? $now;
    $duePre     = $assignedAt;
    $duePost    = $assignedAt->copy()->addHours(6); // POST con ventana

    $combos = [
        // Cuestionarios PSS
        [$pssId,       'pre',  $assignedAt, $duePre],
        [$pssId,       'post', $assignedAt, $duePost],
        
        // Cuestionario de satisfacción del video para PRE
        [$satVideoId,  'pre',  $assignedAt, $duePre],

        // Cuestionario de satisfacción de VR para POST
        [$satVrId,     'post', $assignedAt, $duePost],
    ];

    foreach ($combos as [$qid, $ctx, $asgn, $due]) {
        QuestionnaireAssignment::firstOrCreate(
            [
                'user_id'         => $session->user_id,
                'questionnaire_id'=> $qid,
                'session_id'      => $session->id,
                'context'         => $ctx,
            ],
            [
                'study_id'   => $session->study_id,
                'assigned_at'=> $asgn,
                'due_at'     => $due,
            ]
        );
    }
}



public function store(Request $r)
{
    $usesSegments = is_array($r->input('segments'));

    // Reglas base: session_no ahora es OPCIONAL
    $baseRules = [
        'study_id'     => ['required','exists:studies,id'],
        'device_id'    => ['nullable','exists:devices,id'],
        'session_no'   => ['nullable','integer','min:1'], // <- ya NO required
        'scheduled_at' => ['nullable','date'],
        'started_at'   => ['nullable','date'],
        'ended_at'     => ['nullable','date','after_or_equal:started_at'],
        'notes'        => ['nullable','string','max:1000'],
        'user_id'      => ['sometimes','integer','exists:users,id'],
    ];

    // Si el cliente envía session_no, validamos que sea único para (user, study)
    if ($r->filled('session_no')) {
        $baseRules['session_no'][] = Rule::unique('vr_sessions', 'session_no')
            ->where(fn($q) => $q
                ->where('user_id', $r->input('user_id', optional($r->user())->id))
                ->where('study_id', $r->input('study_id'))
            );
    }

    $rules = $usesSegments
        ? $baseRules + [
            'segments'                    => ['required','array','min:1'],
            'segments.*.environment_id'   => ['required','exists:environments,id'],
            'segments.*.duration_minutes' => ['required','integer','min:1','max:60'],
            'segments.*.sort_order'       => ['nullable','integer','min:1'],
            'segments.*.started_at'       => ['nullable','date'],
            'segments.*.ended_at'         => ['nullable','date'], // valida en lógica si quieres after_or_equal por cada item
            'segments.*.transition'       => ['nullable','string','max:30'],
        ]
        : $baseRules + [
            'duration_sec'   => ['nullable','integer','min:1'],
            'environment'    => ['nullable','string','max:80'],
            'vr_app_version' => ['nullable','string','max:40'],
        ];

    $data = $r->validate($rules);

    $currentUserId = $r->user()->id;
    $targetUserId  = $data['user_id'] ?? $currentUserId;

    if ($targetUserId !== $currentUserId && !Gate::allows('session.createForOthers')) {
        abort(403, 'No autorizado para crear sesiones para otros usuarios.');
    }

    return DB::transaction(function () use ($data, $usesSegments, $targetUserId) {
        // Calcula session_no de forma ATÓMICA si no vino en el request
        $sessionNo = $data['session_no'] ?? (
            DB::table('vr_sessions')
                ->where('user_id',  $targetUserId)
                ->where('study_id', $data['study_id'])
                ->lockForUpdate() // evita carreras
                ->max('session_no')
            + 1 // si max() es null, null+1 => 1 en PHP
        );

        $payload = [
            'user_id'      => $targetUserId,
            'study_id'     => $data['study_id'],
            'device_id'    => $data['device_id'] ?? null,
            'session_no'   => $sessionNo,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'started_at'   => $data['started_at'] ?? null,
            'ended_at'     => $data['ended_at'] ?? null,
            'notes'        => $data['notes'] ?? null,
        ];

        if (!$usesSegments) {
            $payload += [
                'duration_sec'   => $data['duration_sec'] ?? null,
                'environment'    => $data['environment'] ?? null,
                'vr_app_version' => $data['vr_app_version'] ?? null,
            ];
        }

        $session = VrSession::create($payload);

        if ($usesSegments) {
            $total = 0; $order = 1;
            foreach ($data['segments'] as $seg) {
                // (opcional) validar aquí ended_at >= started_at por cada segmento si ambos existen
                $session->segments()->create([
                    'environment_id'   => $seg['environment_id'],
                    'duration_minutes' => (int) $seg['duration_minutes'],
                    'sort_order'       => $seg['sort_order'] ?? $order++,
                    'started_at'       => $seg['started_at'] ?? null,
                    'ended_at'         => $seg['ended_at'] ?? null,
                    'transition'       => $seg['transition'] ?? null,
                ]);
                $total += (int) $seg['duration_minutes'];
            }
            $session->update(['total_duration_minutes' => $total]);
        }

        $this->ensureDefaultAssignmentsForSession($session);

        // Respuesta
        return response()->json(
            $usesSegments
                ? $session->load(['segments.environment','study','device'])
                : $session->load(['study','device']),
            201
        );
    });
}

public function update(Request $r, VrSession $vr_session) {
$data = $r->validate([
'device_id'=>'nullable|exists:devices,id',
'scheduled_at'=>'nullable|date','started_at'=>'nullable|date','ended_at'=>'nullable|date',
'duration_sec'=>'nullable|integer','environment'=>'nullable|string','vr_app_version'=>'nullable|string','notes'=>'nullable|string'
]);
$vr_session->update($data); return $vr_session;
}


public function destroy(VrSession $vr_session) { $vr_session->delete(); return response()->noContent(); }


public function byUser(User $user) {
return VrSession::where('user_id',$user->id)->with(['study','device'])->orderBy('session_no')->get();
}

    // VrSessionController.php
public function countToday()
{
    $user = Auth::user();
    if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

    $count = VrSession::where('user_id', $user->id)
        ->whereDate('created_at', today())
        ->count();

    return response()->json(['count' => $count]);
}



public function lastWeekSessions(Request $request) {
    $user = $request->user();
    $since = now()->subDays(6)->startOfDay();

    $sessions = VrSession::where('user_id', $user->id)
        ->where('created_at', '>=', $since)
        ->with(['segments.environment'])
        ->get();

    return response()->json($sessions);
}


}


