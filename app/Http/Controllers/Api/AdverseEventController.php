<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdverseEvent;  
use App\Models\VrSession; 
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class AdverseEventController extends Controller
{
    public function index(Request $r)
{
    return AdverseEvent::when($r->filled('session_id'), fn($q) =>
                $q->where('session_id', $r->integer('session_id'))
            )
            ->latest('occurred_at')
            ->paginate(20);
}



    public function show(AdverseEvent $adverseEvent)
    {
        return $adverseEvent;
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'session_id'  => ['required','exists:vr_sessions,id'],  
        'type'         => ['required','string','max:100'],
        'severity'     => ['nullable','string','max:50'],
        'related_to_vr'=> ['boolean'],
        'notes'        => ['nullable','string'],
        'occurred_at'  => ['nullable','date'], // puede venir vacío
        'tz'           => ['nullable','string'], // opcional: zona horaria del front
    ]);

    $session = \App\Models\VrSession::findOrFail($data['session_id']);
    $data['user_id'] = $session->user_id;

    // Normaliza occurred_at:
    // 1) si viene, parsea a UTC
    if (!empty($data['occurred_at'])) {
        $tz = $data['tz'] ?? 'UTC';
        $data['occurred_at'] = Carbon::parse($data['occurred_at'], $tz)->utc();
    } else {
        // 2) si no viene, usa created_at (now) o la fecha programada de la sesión
        $data['occurred_at'] = now(); // o Session::find($data['session_id'])->scheduled_at ?? now()
    }

    $event = AdverseEvent::create($data);

    return response()->json($event, 201);
}

    public function destroy(AdverseEvent $adverseEvent)
    {
        $adverseEvent->delete();
        return response()->noContent();
    }
}
