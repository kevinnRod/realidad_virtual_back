<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VrSession extends Model
{
    protected $table = 'vr_sessions';
    protected $fillable = [
        'user_id','study_id','device_id','session_no','scheduled_at',
        'started_at','ended_at','duration_sec','environment','vr_app_version','notes'
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function study(): BelongsTo { return $this->belongsTo(Study::class); }
    public function device(): BelongsTo { return $this->belongsTo(Device::class); }

    public function vitals(): HasMany { return $this->hasMany(Vital::class, 'session_id'); }
    public function adverseEvents(): HasMany { return $this->hasMany(AdverseEvent::class, 'session_id'); }
    public function questionnaireAssignments(): HasMany { return $this->hasMany(QuestionnaireAssignment::class, 'session_id'); }
}
