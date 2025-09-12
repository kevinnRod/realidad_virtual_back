<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Environment;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function index(Request $request)
    {
        $q = Environment::query();

        if (!is_null($request->query('active'))) {
            $q->where('is_active', (bool) $request->boolean('active'));
        }

        if ($term = $request->query('q')) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $q->orderBy('name');

        $perPage = (int) $request->integer('per_page', 0);
        if ($perPage > 0) {
            return $q->paginate(min($perPage, 100));
        }

        return $q->get();
    }

    public function show(Environment $environment)
    {
        return $environment;
    }
}
