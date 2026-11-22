<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class DocumentAskAgent implements Agent
{
    use Promptable;

    public function __construct(private readonly string $context) {}

    public function instructions(): string
    {
        return <<<INSTRUCTIONS
            Você responde perguntas usando somente os trechos de documentos fornecidos abaixo.
            Se os trechos não contiverem a resposta, diga claramente que não encontrou essa informação.
            Não invente fatos. Responda em português e cite o nome do arquivo que sustenta cada resposta.

            Trechos recuperados:
            {$this->context}
            INSTRUCTIONS;
    }
}
