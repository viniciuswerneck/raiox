<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LlmLog extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'response_time_ms',
        'status',
        'error_message',
        'agent_name',
        'agent_version',
    ];
}
