<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyEnrollment;
use Illuminate\Http\Request;


class StudyEnrollmentController extends Controller
{
public function index() { return StudyEnrollment::with(['user','study'])->paginate(20); }
public function show(StudyEnrollment $study_enrollment) { return $study_enrollment->load(['user','study']); }
public function store(Request $r) {
$data = $r->validate([
'user_id'=>'required|exists:users,id', 'study_id'=>'required|exists:studies,id',
'status'=>'in:invited,screened,enrolled,withdrawn,completed', 'enrolled_at'=>'nullable|date','withdrawal_reason'=>'nullable|string'
]);
return StudyEnrollment::create($data);
}
public function update(Request $r, StudyEnrollment $study_enrollment) {
$data = $r->validate([
'status'=>'in:invited,screened,enrolled,withdrawn,completed', 'enrolled_at'=>'nullable|date','withdrawal_reason'=>'nullable|string'
]);
$study_enrollment->update($data); return $study_enrollment;
}
}