<?php
namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Consent;
use Illuminate\Http\Request;


class ConsentController extends Controller
{
public function index() { return Consent::latest('accepted_at')->paginate(20); }
public function show(Consent $consent) { return $consent; }
public function store(Request $r) {
$data = $r->validate([
'user_id'=>'required|exists:users,id',
'version'=>'required|string','accepted_at'=>'required|date','signature_path'=>'nullable|string','notes'=>'nullable|string'
]);
return Consent::create($data);
}
}