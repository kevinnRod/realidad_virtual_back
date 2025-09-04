<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireScore extends Model
{
    protected $fillable = ['assignment_id','score_total','score_json'];

    protected $casts = ['score_json' => 'array'];

    public function assignment(): BelongsTo { return $this->belongsTo(QuestionnaireAssignment::class); }
}
