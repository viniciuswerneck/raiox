<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationReport extends Model
{
    protected $fillable = [
        'cep',
        'logradouro',
        'bairro',
        'cidade',
        'uf',
        'codigo_ibge',
        'populacao',
        'idhm',
        'raw_ibge_data',
        'lat',
        'lng',
        'pois_json',
        'climate_json',
        'wiki_json',
        'air_quality_index',
        'walkability_score',
        'average_income',
        'sanitation_rate',
        'history_extract',
        'safety_level',
        'safety_description',
        'search_radius',
        'status', // 'pending', 'processing', 'completed', 'failed'
        'error_message',
        'real_estate_json',
        'territorial_classification',
        'aact_log',
    ];

    protected $casts = [
        'raw_ibge_data' => 'array',
        'pois_json' => 'array',
        'climate_json' => 'array',
        'wiki_json' => 'array',
        'real_estate_json' => 'array',
        'aact_log' => 'array',
        'populacao' => 'integer',
        'idhm' => 'float',
        'lat' => 'float',
        'lng' => 'float',
    ];

    protected static function booted()
    {
        static::saved(function ($report) {
            \Illuminate\Support\Facades\Cache::forget("report_status_{$report->cep}");
            \Illuminate\Support\Facades\Cache::forget('ranking_results_bairro_all');
            \Illuminate\Support\Facades\Cache::forget('ranking_results_bairro_safety');
            \Illuminate\Support\Facades\Cache::forget('ranking_results_bairro_walk');
            \Illuminate\Support\Facades\Cache::forget('ranking_results_bairro_air');
            \Illuminate\Support\Facades\Cache::forget('ranking_results_cidade_all');
            \Illuminate\Support\Facades\Cache::forget('ranking_results_cidade_safety');
            \Illuminate\Support\Facades\Cache::forget('ranking_results_cidade_walk');
            \Illuminate\Support\Facades\Cache::forget('ranking_results_cidade_air');
        });
    }
}
