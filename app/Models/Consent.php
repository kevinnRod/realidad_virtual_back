<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    protected $fillable = ['user_id',
    'version','accepted_at',
    'signature_path','notes'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
