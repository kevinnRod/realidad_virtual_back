<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\EligibilityScreening;
use Illuminate\Http\Request;


class EligibilityScreeningController extends Controller
{
public function index() { return EligibilityScreening::latest('screened_at')->paginate(20); }
public function show(EligibilityScreening $eligibility_screening) { return $eligibility_screening; }
public function store(Request $r) {
$data = $r->validate([
'user_id'=>'required|exists:users,id', 'screened_at'=>'required|date',
'hypertension_dx'=>'boolean','bp_sys_rest'=>'nullable|integer','bp_dia_rest'=>'nullable|integer',
'antihypertensive_change_4w'=>'boolean','cardiovascular_disease'=>'boolean','epilepsy_photosensitive'=>'boolean',
'vestibular_disorder'=>'boolean','psychiatric_unstable'=>'boolean','psych_rx_change_4w'=>'boolean',
'pregnancy'=>'boolean','vr_intolerance'=>'boolean','caffeine_2h'=>'boolean','tobacco_2h'=>'boolean','alcohol_2h'=>'boolean',
'eligible'=>'required|boolean','notes'=>'nullable|string'
]);
return EligibilityScreening::create($data);
}
}