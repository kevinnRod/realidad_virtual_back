<?php
namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Study;
use Illuminate\Http\Request;


class StudyController extends Controller
{
public function index() { return Study::paginate(20); }
public function show(Study $study) { return $study; }
public function store(Request $r) {
$data = $r->validate(['name'=>'required|string','description'=>'nullable','start_date'=>'nullable|date','end_date'=>'nullable|date','status'=>'in:draft,active,closed']);
return Study::create($data);
}
public function update(Request $r, Study $study) {
$data = $r->validate(['name'=>'sometimes|string','description'=>'nullable','start_date'=>'nullable|date','end_date'=>'nullable|date','status'=>'in:draft,active,closed']);
$study->update($data); return $study;
}
public function destroy(Study $study) { $study->delete(); return response()->noContent(); }
}