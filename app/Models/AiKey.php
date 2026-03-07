<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKey extends Model
{
    protected $fillable = [
        'provider',
        'key',
        'is_active',
        'last_used_at',
        'cooldown_until'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'cooldown_until' => 'datetime'
    ];

    public function logs()
    {
        return $this->hasMany(AiUsageLog::class);
    }
}
