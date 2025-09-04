<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdverseEvent extends Model
{
    protected $fillable = ['user_id','session_id','type','severity','related_to_vr','action_taken','resolved_at','notes'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function vrSession(): BelongsTo { return $this->belongsTo(VrSession::class, 'session_id'); }
}
