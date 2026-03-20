<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CepScanSession extends Model
{
    protected $fillable = [
        'state',
        'status',
        'limit_planned',
        'processed',
        'success',
        'failed',
        'skipped',
        'delay_ms',
        'started_at',
        'finished_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'processed' => 'integer',
        'success' => 'integer',
        'failed' => 'integer',
        'skipped' => 'integer',
        'delay_ms' => 'integer',
    ];

    public function logs()
    {
        return $this->hasMany(CepScanLog::class);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getDurationAttribute()
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->finished_at ?? now();
        return $this->started_at->diffForHumans($end, true);
    }

    public function getSuccessRateAttribute()
    {
        if ($this->processed === 0) {
            return 0;
        }

        return round(($this->success / $this->processed) * 100, 1);
    }
}
