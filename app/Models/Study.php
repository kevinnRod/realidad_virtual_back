<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Study extends Model
{
    protected $fillable = ['name','description','start_date','end_date','status'];

    public function enrollments(): HasMany { return $this->hasMany(StudyEnrollment::class); }
    public function vrSessions(): HasMany { return $this->hasMany(VrSession::class); }
    public function questionnaireAssignments(): HasMany { return $this->hasMany(QuestionnaireAssignment::class); }
}
