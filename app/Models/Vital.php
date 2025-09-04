<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    protected $fillable = ['user_id','session_id','measured_at','phase','posture','bp_sys','bp_dia','pulse','device_label'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function vrSession(): BelongsTo { return $this->belongsTo(VrSession::class, 'session_id'); }
}
