<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class City extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'wiki_json' => 'array',
        'real_estate_json' => 'array',
        'raw_ibge_data' => 'array',
        'population' => 'integer',
        'average_income' => 'decimal:2',
        'sanitation_rate' => 'decimal:2',
        'stats_cache' => 'array',
        'last_calculated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($city) {
            if (empty($city->slug)) {
                $city->slug = Str::slug($city->name . '-' . $city->uf);
            }
        });
    }

    public function neighborhoods()
    {
        return $this->hasMany(Neighborhood::class);
    }
}
