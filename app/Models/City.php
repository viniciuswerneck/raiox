<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'wiki_json' => 'array',
        'raw_ibge_data' => 'array',
        'population' => 'integer',
        'average_income' => 'decimal:2',
        'sanitation_rate' => 'decimal:2',
    ];

    public function neighborhoods()
    {
        return $this->hasMany(Neighborhood::class);
    }
}
