<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdverseEvent extends Model
{
    protected $fillable = [
        'user_id','session_id','type','severity','related_to_vr',
        'action_taken','notes','occurred_at','resolved_at'
    ];

    protected $casts = [
        'related_to_vr' => 'boolean',
        'occurred_at'   => 'datetime',
        'resolved_at'   => 'datetime',
    ];
}