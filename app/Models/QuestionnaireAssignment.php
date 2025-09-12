<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireAssignment extends Model
{
    protected $fillable = ['user_id','questionnaire_id','study_id','session_id','context','assigned_at','due_at','completed_at'];

    protected $casts = [
    'assigned_at'   => 'datetime',
    'due_at'        => 'datetime',
    'completed_at'  => 'datetime',
];


    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function questionnaire(): BelongsTo { return $this->belongsTo(Questionnaire::class); }
    public function study(): BelongsTo { return $this->belongsTo(Study::class); }
    public function vrSession(): BelongsTo { return $this->belongsTo(VrSession::class, 'session_id'); }

    public function responses(): HasMany { return $this->hasMany(QuestionnaireResponse::class, 'assignment_id'); }
    public function score(): HasMany { return $this->hasMany(QuestionnaireScore::class, 'assignment_id'); }
}
