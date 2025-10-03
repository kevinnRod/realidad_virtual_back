<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VrSession;
use App\Models\VrSessionSegment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VrSessionSegmentController extends Controller
{
    /**
     * GET /vr-sessions/{session}/segments
     */
    public function index(VrSession $session)
    {
        $segments = $session->segments()
            ->with('environment')
            ->orderBy('sort_order')
            ->get();

        return response()->json($segments);
    }

    /**
     * POST /vr-sessions/{session}/segments
     * Body:
     *  - environment_id: required|exists:environments,id
     *  - duration_minutes: required|integer|min:1|max:60
     *  - sort_order: nullable|integer|min:1
     *  - transition: nullable|string|max:30
     *  - started_at, ended_at: nullable|date
     */
    public function store(Request $request, VrSession $session)
    {
        $data = $request->validate([
            'environment_id'   => ['required','exists:environments,id'],
            'duration_minutes' => ['required','integer','min:1','max:60'],
            'sort_order'       => ['nullable','integer','min:1'],
            'transition'       => ['nullable','string','max:30'],
            'started_at'       => ['nullable','date'],
            'ended_at'         => ['nullable','date','after_or_equal:started_at'],
        ]);

        return DB::transaction(function () use ($session, $data) {
            $currentMax = (int) $session->segments()->max('sort_order');
            $newOrder   = $data['sort_order'] ?? ($currentMax + 1);

            // Asegurar que no haya colisión de sort_order: desplaza hacia abajo
            $session->segments()
                ->where('sort_order', '>=', $newOrder)
                ->increment('sort_order');

            $segment = $session->segments()->create([
                'environment_id'   => $data['environment_id'],
                'duration_minutes' => $data['duration_minutes'],
                'sort_order'       => $newOrder,
                'transition'       => $data['transition'] ?? null,
                'started_at'       => $data['started_at'] ?? null,
                'ended_at'         => $data['ended_at'] ?? null,
            ]);

            // Recalcular total de la sesión (opcional si lo manejas en otro lado)
            $total = $session->segments()->sum('duration_minutes');
            $session->update(['total_duration_minutes' => $total]);

            return response()->json(
                $segment->load('environment'),
                201
            );
        });
    }

    /**
     * PUT /vr-sessions/{session}/segments/{segment}
     * Campos iguales a store(); sort_order reordena si cambia.
     */
    public function update(Request $request, VrSession $session, VrSessionSegment $segment)
    {
        // Asegura que el segmento pertenece a la sesión
        if ($segment->vr_session_id !== $session->id) {
            return response()->json(['message' => 'Segment does not belong to this session'], 404);
        }

        $data = $request->validate([
            'environment_id'   => ['sometimes','required','exists:environments,id'],
            'duration_minutes' => ['sometimes','required','integer','min:1','max:60'],
            'sort_order'       => ['sometimes','nullable','integer','min:1'],
            'transition'       => ['sometimes','nullable','string','max:30'],
            'started_at'       => ['sometimes','nullable','date'],
            'ended_at'         => ['sometimes','nullable','date','after_or_equal:started_at'],
        ]);

        return DB::transaction(function () use ($session, $segment, $data) {
            // Reordenar si cambia sort_order
            if (array_key_exists('sort_order', $data) && $data['sort_order']) {
                $newOrder = (int) $data['sort_order'];
                $oldOrder = (int) $segment->sort_order;

                if ($newOrder !== $oldOrder) {
                    if ($newOrder < $oldOrder) {
                        // Mueve hacia arriba: desplaza [new, old-1] +1
                        $session->segments()
                            ->whereBetween('sort_order', [$newOrder, $oldOrder - 1])
                            ->increment('sort_order');
                    } else {
                        // Mueve hacia abajo: desplaza [old+1, new] -1
                        $session->segments()
                            ->whereBetween('sort_order', [$oldOrder + 1, $newOrder])
                            ->decrement('sort_order');
                    }
                    $segment->sort_order = $newOrder;
                }
            }

            // Actualiza otros campos
            foreach (['environment_id','duration_minutes','transition','started_at','ended_at'] as $f) {
                if (array_key_exists($f, $data)) {
                    $segment->{$f} = $data[$f];
                }
            }
            $segment->save();

            // Recalcular total
            $total = $session->segments()->sum('duration_minutes');
            $session->update(['total_duration_minutes' => $total]);

            return response()->json($segment->load('environment'));
        });
    }

    /**
     * DELETE /vr-sessions/{session}/segments/{segment}
     */
    public function destroy(VrSession $session, VrSessionSegment $segment)
    {
        if ($segment->vr_session_id !== $session->id) {
            return response()->json(['message' => 'Segment does not belong to this session'], 404);
        }

        return DB::transaction(function () use ($session, $segment) {
            $segment->delete();

            // Reindexar sort_order (1..N)
            $order = 1;
            $session->segments()
                ->orderBy('sort_order')
                ->get()
                ->each(function ($seg) use (&$order) {
                    $seg->update(['sort_order' => $order++]);
                });

            // Recalcular total
            $total = $session->segments()->sum('duration_minutes');
            $session->update(['total_duration_minutes' => $total]);

            return response()->json(['ok' => true]);
        });
    }

    // VrSessionSegmentController.php


public function durationByEnvironment(Request $request)
{
    $userId = $request->user()->id;
    $since = now()->subDays(6)->startOfDay();

    $durations = DB::table('vr_session_segments AS seg')
        ->join('vr_sessions AS ses', 'seg.vr_session_id', '=', 'ses.id')
        ->join('environments AS env', 'seg.environment_id', '=', 'env.id')
        ->where('ses.user_id', $userId)
        ->where('ses.created_at', '>=', $since)
        ->select('env.name', DB::raw('SUM(seg.duration_minutes) AS total_minutes'))
        ->groupBy('env.name')
        ->orderByDesc('total_minutes')
        ->get();

    return response()->json($durations);
}

}
