<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Vital;
use Illuminate\Http\Request;


class VitalController extends Controller
{
public function index() { return Vital::latest('measured_at')->paginate(20); }
public function show(Vital $vital) { return $vital; }
public function store(Request $r) {
$data = $r->validate([
'user_id'=>'required|exists:users,id',
'session_id'=>'nullable|exists:vr_sessions,id',
'measured_at'=>'required|date',
'phase'=>'required|in:baseline,pre,post,followup',
'posture'=>'nullable|in:seated,standing',
'bp_sys'=>'nullable|integer','bp_dia'=>'nullable|integer','pulse'=>'nullable|integer',
'device_label'=>'nullable|string'
]);
return Vital::create($data);
}
public function destroy(Vital $vital) { $vital->delete(); return response()->noContent(); }
}