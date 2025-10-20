<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Environment extends Model {
  protected $fillable = ['code','name','description','thumbnail_url','asset_bundle','recommended_duration_minutes','is_active', 'image_url'];
  public function segments(){ 
    return $this->hasMany(VrSessionSegment::class); 
  }
  public function sessions(){ 
    return $this->belongsToMany(VrSession::class, 'vr_session_segments'); 
  }
}
