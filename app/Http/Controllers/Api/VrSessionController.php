<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\VrSession;
use App\Models\User;
use Illuminate\Http\Request;


class VrSessionController extends Controller
{
public function index() { return VrSession::with(['user','study','device'])->paginate(20); }
public function show(VrSession $vr_session) { return $vr_session->load(['user','study','device','vitals','adverseEvents']); }


public function store(Request $r) {
$data = $r->validate([
'user_id'=>'required|exists:users,id',
'study_id'=>'required|exists:studies,id',
'device_id'=>'nullable|exists:devices,id',
'session_no'=>'required|integer|min:1',
'scheduled_at'=>'nullable|date','started_at'=>'nullable|date','ended_at'=>'nullable|date',
'duration_sec'=>'nullable|integer','environment'=>'nullable|string','vr_app_version'=>'nullable|string','notes'=>'nullable|string'
]);
return VrSession::create($data);
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