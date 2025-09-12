<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAssignment;
use App\Models\VrSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VrSessionController extends Controller
{
public function index() { return VrSession::with(['user','study','device'])->paginate(20); }
public function show(VrSession $vr_session) { return $vr_session->load(['user','study','device','vitals','adverseEvents']); }


private function ensureDefaultAssignmentsForSession($session): void
{
    // Busca IDs por código (o por título si no tienes code)
    $pssId = Questionnaire::where('code','pss10')->value('id')
         ?? Questionnaire::where('title','like','%PSS%')->value('id');

    $satId = Questionnaire::where('code','satisf')->value('id')
         ?? Questionnaire::where('title','like','%Satisf%')->value('id');

    if (!$pssId || !$satId) {
        // Si falta alguno, no rompas la creación de sesión
        return;
    }

    $now        = now();
    $assignedAt = $session->scheduled_at ?? $now;
    $duePre     = $assignedAt;                     // pre: hasta el inicio
    $duePost    = ($session->scheduled_at ?? $now)->copy()->addHours(6); // post: ventana 6h (ajusta)

    $combos = [
        [$pssId,  'pre',  $assignedAt, $duePre],
        [$pssId,  'post', $assignedAt, $duePost],
        [$satId,  'pre',  $assignedAt, $duePre],
        [$satId,  'post', $assignedAt, $duePost],
    ];

    foreach ($combos as [$qid,$ctx,$asgn,$due]) {
        QuestionnaireAssignment::firstOrCreate(
            [
                'user_id'    => $session->user_id,
                'questionnaire_id' => $qid,
                'session_id' => $session->id,
                'context'    => $ctx, // 'pre' | 'post'
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

        // Reglas base
        $baseRules = [
            'study_id'     => ['required','exists:studies,id'],
            'device_id'    => ['nullable','exists:devices,id'],
            'session_no'   => ['required','integer','min:1'],
            'scheduled_at' => ['nullable','date'],
            'started_at'   => ['nullable','date'],
            'ended_at'     => ['nullable','date'],
            'notes'        => ['nullable','string','max:1000'],
            // user_id solo si un admin crea para otro usuario
            'user_id'      => ['sometimes','integer','exists:users,id'],
        ];

        $rules = $usesSegments
            ? $baseRules + [
                'segments'                        => ['required','array','min:1'],
                'segments.*.environment_id'       => ['required','exists:environments,id'],
                'segments.*.duration_minutes'     => ['required','integer','min:1','max:60'],
                'segments.*.sort_order'           => ['nullable','integer','min:1'],
                'segments.*.started_at'           => ['nullable','date'],
                'segments.*.ended_at'             => ['nullable','date','after_or_equal:segments.*.started_at'],
                'segments.*.transition'           => ['nullable','string','max:30'],
            ]
            : $baseRules + [
                // Legacy (si aún lo usas)
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

        return DB::transaction(function () use ($r, $data, $usesSegments, $targetUserId) {
            if ($usesSegments) {
                // —— ESQUEMA NUEVO (con segmentos) ——
                $session = VrSession::create([
                    'user_id'      => $targetUserId,
                    'study_id'     => $data['study_id'],
                    'device_id'    => $data['device_id'] ?? null,
                    'session_no'   => $data['session_no'],
                    'scheduled_at' => $data['scheduled_at'] ?? null,
                    'started_at'   => $data['started_at'] ?? null,
                    'ended_at'     => $data['ended_at'] ?? null,
                    'notes'        => $data['notes'] ?? null,
                ]);

                $total = 0; $order = 1;
                foreach ($data['segments'] as $seg) {
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
                $this->ensureDefaultAssignmentsForSession($session);

                return response()->json(
                    $session->load(['segments.environment','study','device']),
                    201
                );
            }

            // —— ESQUEMA LEGACY ——
            $session = VrSession::create([
                'user_id'        => $targetUserId,
                'study_id'       => $data['study_id'],
                'device_id'      => $data['device_id'] ?? null,
                'session_no'     => $data['session_no'],
                'scheduled_at'   => $data['scheduled_at'] ?? null,
                'started_at'     => $data['started_at'] ?? null,
                'ended_at'       => $data['ended_at'] ?? null,
                'duration_sec'   => $data['duration_sec'] ?? null,
                'environment'    => $data['environment'] ?? null,
                'vr_app_version' => $data['vr_app_version'] ?? null,
                'notes'          => $data['notes'] ?? null,
            ]);

            // También genera asignaciones por defecto aquí
            $this->ensureDefaultAssignmentsForSession($session);

            return response()->json($session, 201);
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
}