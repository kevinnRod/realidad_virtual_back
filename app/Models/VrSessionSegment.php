<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VrSessionSegment extends Model {
  protected $fillable = ['vr_session_id','environment_id',
  'sort_order','duration_minutes',
  'started_at','ended_at',
  'transition'];
  public function session(){ 
    return $this->belongsTo(VrSession::class,'vr_session_id'); 
  }

  public function environment(){ 
    return $this->belongsTo(Environment::class); 
  }
}
