<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use Illuminate\Http\Request;

class QuestionnaireController extends Controller
{
    // GET /api/questionnaires
    public function index()
    {
        return \App\Models\Questionnaire::query()->paginate(20);
    }

    // GET /api/questionnaires/{questionnaire}
    public function show(Questionnaire $questionnaire)
    {
        return $questionnaire->load('items');
    }

    // GET /api/questionnaires/{questionnaire}/items
    public function items(Questionnaire $questionnaire, Request $r)
    {
        $q = $questionnaire->items()
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc'); // fallback por si algún item no tiene sort_order

        $perPage = (int) $r->integer('per_page', 50);

        return $perPage > 0
            ? $q->paginate(min($perPage, 200))
            : $q->get();
    }

}
