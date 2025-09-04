<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\AdverseEvent;
use Illuminate\Http\Request;


class AdverseEventController extends Controller
{
public function index() { return AdverseEvent::with('vrSession')->paginate(20); }
public function show(AdverseEvent $adverse_event) { return $adverse_event->load('vrSession'); }
public function store(Request $r) {
$data = $r->validate([
'user_id'=>'required|exists:users,id',
'session_id'=>'required|exists:vr_sessions,id',
'type'=>'required|in:nausea,dizziness,headache,other',
'severity'=>'required|integer|min:1|max:5',
'related_to_vr'=>'boolean','action_taken'=>'nullable|string','resolved_at'=>'nullable|date','notes'=>'nullable|string'
]);
return AdverseEvent::create($data);
}
public function destroy(AdverseEvent $adverse_event) { $adverse_event->delete(); return response()->noContent(); }
}