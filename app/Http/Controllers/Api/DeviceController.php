<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;


class DeviceController extends Controller
{
public function index() { return Device::paginate(20); }
public function show(Device $device) { return $device; }
public function store(Request $r) {
$data = $r->validate(['code'=>'required|string|unique:devices,code','type'=>'nullable|string','serial'=>'nullable|string','location'=>'nullable|string']);
return Device::create($data);
}
public function update(Request $r, Device $device) {
$data = $r->validate(['code'=>'sometimes|string|unique:devices,code,'.$device->id,'type'=>'nullable|string','serial'=>'nullable|string','location'=>'nullable|string']);
$device->update($data); return $device;
}
public function destroy(Device $device) { $device->delete(); return response()->noContent(); }
}