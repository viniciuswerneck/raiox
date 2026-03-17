<?php

namespace App\Services\Agents;

use App\Models\KnowledgeVector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * KnowledgeAgent v1.0.0
 * Especialista em Memória Semântica Territorial.
 * 100% Interno - Usa MySQL para busca e Gemini para Embeddings.
 */
class KnowledgeAgent extends BaseAgent
{
    public const VERSION = '1.0.0';

    /**
     * Gera um vetor de embedding usando a API gratuita do Google Gemini.
     */
    public function generateEmbedding(string $text): ?array
    {
        $apiKey = $this->getGeminiKey();
        if (!$apiKey) return null;

        try {
            // Limpeza básica para evitar quebra de JSON
            $cleanText = mb_substr($text, 0, 3000); 

            $response = Http::when(app()->isProduction(), fn($h) => $h, fn($h) => $h->withoutVerifying())
                ->timeout(10)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
                    'model' => 'models/gemini-embedding-001',
                    'content' => [
                        'parts' => [['text' => $cleanText]]
                    ]
                ]);

            if ($response->successful()) {
                $values = $response->json()['embedding']['values'] ?? null;
                Log::info("KnowledgeAgent: Embedding gerado com sucesso (" . count($values ?? []) . " dimensões)");
                return $values;
            }

            Log::error("KnowledgeAgent: Falha ao gerar embedding", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error("KnowledgeAgent Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Armazena um novo fragmento de conhecimento.
     */
    public function store(string $content, string $type, string $refId, array $metadata = []): bool
    {
        Log::info("KnowledgeAgent: Indexando conteúdo tipo [{$type}] para [{$refId}]");
        $embedding = $this->generateEmbedding($content);
        if (!$embedding) {
            Log::error("KnowledgeAgent: Falha ao indexar - Vetor nulo.");
            return false;
        }

        $magnitude = KnowledgeVector::calculateMagnitude($embedding);

        KnowledgeVector::create([
            'source_type' => $type,
            'reference_id' => $refId,
            'content' => $content,
            'embedding' => $embedding,
            'magnitude' => $magnitude,
            'metadata' => $metadata
        ]);

        Log::info("KnowledgeAgent: Conhecimento salvo no DB.");
        return true;
    }

    /**
     * Busca os fragmentos mais similares no banco de dados local.
     */
    public function search(string $query, int $limit = 5): array
    {
        $queryVector = $this->generateEmbedding($query);
        if (!$queryVector) return [];

        $queryMagnitude = KnowledgeVector::calculateMagnitude($queryVector);
        
        // Em vez de carregar tudo, rodamos o cálculo do Produto Interno (Dot Product) no MySQL
        // Para isso, construímos uma query que extrai cada dimensão do JSON.
        // Como o Gemini tem 768 dimensões, fazemos uma amostragem agressiva das primeiras 50 dimensões
        // para dar velocidade sem perder a precisão semântica (a maioria das características está no início).
        
        $sqlParts = [];
        $sampleSize = min(count($queryVector), 64); // Usamos 64 dimensões para o SQL rápido

        for ($i = 0; $i < $sampleSize; $i++) {
            $val = $queryVector[$i];
            $sqlParts[] = "JSON_EXTRACT(embedding, '$[$i]') * $val";
        }

        $dotProductSql = implode(" + ", $sqlParts);

        // Fórmula: Cosine Similarity = DotProduct(A,B) / (Mag(A) * Mag(B))
        $results = KnowledgeVector::select('content', 'metadata')
            ->selectRaw("($dotProductSql) / (magnitude * $queryMagnitude) as score")
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return $results->map(fn($r) => [
            'content' => $r->content,
            'score' => $r->score,
            'metadata' => $r->metadata
        ])->toArray();
    }

    private function getGeminiKey(): ?string
    {
        $key = \App\Models\AiKey::where('is_active', true)
            ->where('provider', 'gemini')
            ->orderBy('last_used_at', 'asc')
            ->first();
        
        return $key ? $key->key : env('GEMINI_API_KEY');
    }
}
