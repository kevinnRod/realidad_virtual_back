<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Vital;
use App\Models\VrSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class VitalController extends Controller
{
public function index() { return Vital::latest('measured_at')->paginate(20); }
public function show(Vital $vital) { return $vital; }

public function store(Request $r)
{
    $data = $r->validate([
        'session_id'   => ['required','exists:vr_sessions,id'],
        'phase'        => ['required', Rule::in(['pre','post'])],
        'posture'      => ['nullable', Rule::in(['seated','standing'])],
        'bp_sys'       => ['nullable','integer'],
        'bp_dia'       => ['nullable','integer'],
        'pulse'        => ['nullable','integer'],
        'device_label' => ['nullable','string'],
    ]);

    $session = \App\Models\VrSession::findOrFail($data['session_id']);
    $data['user_id'] = $session->user_id;

    // Hora actual del servidor
    $data['measured_at'] = now();

    return Vital::create($data);
}


public function destroy(Vital $vital) { $vital->delete(); return response()->noContent(); }
}