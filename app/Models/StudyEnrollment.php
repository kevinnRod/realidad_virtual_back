<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyEnrollment extends Model
{
    protected $fillable = ['user_id','study_id','status','enrolled_at','withdrawal_reason'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function study(): BelongsTo { return $this->belongsTo(Study::class); }
}
