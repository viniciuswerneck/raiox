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
        'real_estate_json',
    ];

    protected $casts = [
        'raw_ibge_data' => 'array',
        'pois_json' => 'array',
        'climate_json' => 'array',
        'wiki_json' => 'array',
        'real_estate_json' => 'array',
        'populacao' => 'integer',
        'idhm' => 'float',
        'lat' => 'float',
        'lng' => 'float',
    ];
}
