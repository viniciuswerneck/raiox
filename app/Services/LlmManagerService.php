<?php

namespace App\Services;

/**
 * LlmManagerService v2.0.0
 * O Maestro das IAs - Agora operando via LlmRouterService para rotação exaustiva.
 */
class LlmManagerService
{
    protected LlmRouterService $router;

    public function __construct(LlmRouterService $router)
    {
        $this->router = $router;
    }

    /**
     * Envia uma mensagem para a IA com lógica de fallback automático.
     */
    public function chat(array $messages, string $profile = 'fast', array $context = [])
    {
        return $this->router->chat($messages, $profile, $context);
    }
}
