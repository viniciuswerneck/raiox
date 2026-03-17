<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeVector extends Model
{
    protected $fillable = [
        'source_type',
        'reference_id',
        'content',
        'embedding',
        'magnitude',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Calcula a similaridade de cosseno com outro vetor (via PHP).
     * Em produção com muitos dados, isso deve ser feito via SQL/Database Engine,
     * mas para o Raio-X inicial, o PHP lida bem com centenas de vetores.
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;
        
        foreach ($vec1 as $i => $val) {
            $dotProduct += $val * ($vec2[$i] ?? 0);
            $normA += $val * $val;
            $normB += ($vec2[$i] ?? 0) * ($vec2[$i] ?? 0);
        }
        
        $divisor = sqrt($normA) * sqrt($normB);
        return $divisor == 0 ? 0 : $dotProduct / $divisor;
    }

    /**
     * Auxilia no cálculo de magnitude de um vetor.
     */
    public static function calculateMagnitude(array $vector): float
    {
        $sum = 0;
        foreach ($vector as $val) {
            $sum += $val * $val;
        }
        return sqrt($sum);
    }
}
