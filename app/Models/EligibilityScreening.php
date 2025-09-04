<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityScreening extends Model
{
    protected $fillable = [
        'user_id','screened_at','hypertension_dx','bp_sys_rest','bp_dia_rest',
        'antihypertensive_change_4w','cardiovascular_disease','epilepsy_photosensitive',
        'vestibular_disorder','psychiatric_unstable','psych_rx_change_4w',
        'pregnancy','vr_intolerance','caffeine_2h','tobacco_2h','alcohol_2h','eligible','notes'
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
