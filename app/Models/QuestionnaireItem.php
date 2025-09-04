<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireItem extends Model
{
    protected $fillable = ['questionnaire_id','code','text','sort_order','scale_min','scale_max','reverse_scored'];

    public function questionnaire(): BelongsTo { return $this->belongsTo(Questionnaire::class); }
}
