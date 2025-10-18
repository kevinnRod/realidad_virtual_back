<?php

// app/Models/LoginCode.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LoginCode extends Model
{
    protected $fillable = ['user_id', 'code', 'expires_at'];
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

}
