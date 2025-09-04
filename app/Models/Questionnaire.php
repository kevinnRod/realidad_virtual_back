<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    protected $fillable = ['code','version','title','is_active'];

    public function items(): HasMany { return $this->hasMany(QuestionnaireItem::class); }
    public function assignments(): HasMany { return $this->hasMany(QuestionnaireAssignment::class); }
}
