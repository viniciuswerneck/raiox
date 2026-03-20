<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionComparison extends Model
{
    protected $fillable = [
        'cep_a',
        'cep_b',
        'score_diff',
        'infra_diff',
        'mobilidade_diff',
        'lazer_diff',
        'comparison_data',
        'analysis_text',
    ];

    protected $casts = [
        'comparison_data' => 'array',
        'score_diff' => 'integer',
        'infra_diff' => 'integer',
        'mobilidade_diff' => 'integer',
        'lazer_diff' => 'integer',
    ];

    /**
     * Helper para buscar comparação em qualquer ordem de CEP
     */
    public static function findPair(string $cepA, string $cepB)
    {
        return self::where(function ($q) use ($cepA, $cepB) {
            $q->where('cep_a', $cepA)->where('cep_b', $cepB);
        })->orWhere(function ($q) use ($cepA, $cepB) {
            $q->where('cep_a', $cepB)->where('cep_b', $cepA);
        })->first();
    }
}
