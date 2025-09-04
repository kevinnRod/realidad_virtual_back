<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = ['code','type','serial','location'];

    public function vrSessions(): HasMany { return $this->hasMany(VrSession::class); }
}
