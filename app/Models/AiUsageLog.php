<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $fillable = [
        'ai_key_id',
        'model',
        'success',
        'error_message',
        'latency_ms'
    ];

    protected $casts = [
        'success' => 'boolean'
    ];

    public function key()
    {
        return $this->belongsTo(AiKey::class, 'ai_key_id');
    }
}
