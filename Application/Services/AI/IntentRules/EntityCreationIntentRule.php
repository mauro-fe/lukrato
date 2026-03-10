<?php

declare(strict_types=1);

namespace Application\Services\AI\IntentRules;

use Application\DTO\AI\IntentResult;
use Application\Enums\AI\IntentType;

/**
 * Detecta intenção explícita de criar entidades financeiras.
 *
 * Padrões cobertos:
 *  - "cria/adiciona/registra um lançamento de 100 de luz"
 *  - "quero criar uma meta de 5000 para viagem"
 *  - "define orçamento de 800 para alimentação"
 *  - "cria categoria Pets tipo despesa"
 *  - "adiciona subcategoria Ração em Pets"
 */
class EntityCreationIntentRule implements IntentRuleInterface
{
    /**
     * Verbos de criação explícita.
     */
    private const CREATION_VERBS =
    'cri(?:ar?|e|a)|adicionar?|registrar?|lan[çc]ar?|definir?|estabelecer?|cadastrar?|incluir?|inserir?|anota[r]?|bota[r]?|coloca[r]?|faz(?:er)?|quero\s+(?:criar|adicionar|registrar|definir)';

    /**
     * Entidades reconhecidas e seus padrões.
     */
    private const ENTITY_PATTERNS = [
        // Subcategoria (mais específico, antes de categoria e lancamento)
        'subcategoria' => 'subcategoria|sub[\s-]?categoria',
        // Categoria (antes de lancamento para evitar match com "tipo despesa")
        'categoria'  => 'categoria',
        // Lançamento / despesa / receita
        'lancamento' => 'lan[çc]amento|gasto|despesa|receita|conta\s+(?:a\s+pagar|de\s+luz|de\s+[áa]gua)|pagamento',
        // Meta / objetivo
        'meta'       => 'meta|objetivo|goal|meta\s+financeira',
        // Orçamento / budget / limite
        'orcamento'  => 'or[çc]amento|budget|limite\s+(?:mensal|de\s+gasto)',
    ];

    public function match(string $message, bool $isWhatsApp = false): ?IntentResult
    {
        $normalized = mb_strtolower(trim($message));

        // Precisa ter verbo de criação + nome de entidade
        $verbPattern = '/(?:' . self::CREATION_VERBS . ')/iu';

        if (!preg_match($verbPattern, $normalized)) {
            return null;
        }

        // Verificar se alguma entidade é mencionada
        foreach (self::ENTITY_PATTERNS as $entity => $pattern) {
            if (preg_match('/(?:' . $pattern . ')/iu', $normalized)) {
                // Verbo + entidade = alta confiança
                return IntentResult::medium(IntentType::CREATE_ENTITY, 0.9, ['entity' => $entity]);
            }
        }

        return null;
    }

    /**
     * Detecta qual tipo de entidade o usuário quer criar.
     * Usado pelo EntityCreationHandler após o intent ser detectado.
     */
    public static function detectEntityType(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));

        // Ordem importa: subcategoria antes de categoria
        foreach (self::ENTITY_PATTERNS as $entity => $pattern) {
            if (preg_match('/(?:' . $pattern . ')/iu', $normalized)) {
                return $entity;
            }
        }

        return null;
    }
}
