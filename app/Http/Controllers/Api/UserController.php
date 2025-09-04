<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class UserController extends Controller
{
public function me(Request $r) { return $r->user()->load(['enrollments.study']); }
}