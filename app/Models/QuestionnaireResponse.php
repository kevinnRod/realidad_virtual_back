<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireResponse extends Model
{
    protected $fillable = ['assignment_id','item_id','value','answered_at'];

    public function assignment(): BelongsTo { return $this->belongsTo(QuestionnaireAssignment::class); }
    public function item(): BelongsTo { return $this->belongsTo(QuestionnaireItem::class); }
}
