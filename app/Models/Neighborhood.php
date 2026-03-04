<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Neighborhood extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'wiki_json' => 'array',
        'real_estate_json' => 'array',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
