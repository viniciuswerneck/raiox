<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TextReviserService
{
    protected LlmManagerService $llm;

    public function __construct(LlmManagerService $llm)
    {
        $this->llm = $llm;
    }

    private const REVISION_PROMPT = <<<'PROMPT'
Você é um assistente especializado em revisão de textos informativos sobre bairros e regiões urbanas do Brasil.
Revise, corrija e melhore o texto fornecido. MÍNIMO DE QUATRO PARÁGRAFOS extensos e detalhados, separados obrigatoriamente por \n\n.
PROMPT;

    /**
     * Revisa o campo 'historia' usando o roteador de IA.
     */
    public function reviseHistoria(array $aiSummary): array
    {
        $originalHistoria = $aiSummary['historia'] ?? '';
        if (empty($originalHistoria)) {
            return $aiSummary;
        }

        $response = $this->llm->chat([
            ['role' => 'system', 'content' => self::REVISION_PROMPT],
            ['role' => 'user', 'content' => $originalHistoria],
        ], 'fast', ['agent_name' => 'TextReviser', 'agent_version' => '2.0.0']);

        $revised = $response['choices'][0]['message']['content'] ?? null;

        if ($revised) {
            $revised = $this->enforceMinimumParagraphs($revised, $originalHistoria);
            $aiSummary['historia'] = $revised;
            Log::info('TextReviser: Narrativa revisada via LlmRouter.');
        }

        return $aiSummary;
    }

    private function enforceMinimumParagraphs(string $revised, string $original): string
    {
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $revised)));
        if (count($paragraphs) >= 4) {
            return $revised;
        }

        $lines = array_filter(array_map('trim', explode("\n", $revised)));
        if (count($lines) >= 4) {
            $chunkSize = (int) ceil(count($lines) / 4);
            $chunks = array_chunk(array_values($lines), $chunkSize);

            return implode("\n\n", array_map(fn ($c) => implode(' ', $c), $chunks));
        }

        return $revised ?: $original;
    }
}
