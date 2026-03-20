<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CepScanLog extends Model
{
    protected $fillable = [
        'cep',
        'status',
        'logradouro',
        'bairro',
        'cidade',
        'uf',
        'codigo_ibge',
        'lat',
        'lng',
        'error_message',
        'source',
        'state_target',
        'response_time_ms',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'response_time_ms' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(CepScanSession::class);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByState($query, $state)
    {
        return $query->where('state_target', $state);
    }
}
