<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Models\AiConversation;

/**
 * Serviço de estado de conversação para fluxos multi-turno.
 *
 * Permite que o chat conduza coleta de dados em múltiplas mensagens,
 * sem precisar que o usuário forneça tudo em uma única frase.
 *
 * Estados:
 *   - idle:                Conversa normal, sem fluxo ativo
 *   - collecting_entity:   Coletando campos para criar uma entidade (lancamento, meta, etc.)
 *   - awaiting_selection:  Aguardando seleção de opção (ex: qual cartão, qual conta)
 *
 * O state_data (JSON) armazena dados parciais do fluxo ativo.
 */
class ConversationStateService
{
    /**
     * Obtém o estado atual de uma conversação.
     *
     * @return array{state: string, data: array}
     */
    public static function getState(int $conversationId): array
    {
        $conversation = AiConversation::find($conversationId);

        if (!$conversation) {
            return ['state' => 'idle', 'data' => []];
        }

        return [
            'state' => $conversation->state ?? 'idle',
            'data'  => $conversation->state_data ?? [],
        ];
    }

    /**
     * Verifica se a conversação está em um fluxo ativo (não-idle).
     */
    public static function isActive(int $conversationId): bool
    {
        $state = self::getState($conversationId);
        return $state['state'] !== 'idle';
    }

    /**
     * Inicia um fluxo de coleta de entidade.
     *
     * @param int    $conversationId
     * @param string $entityType      lancamento|meta|orcamento|categoria|subcategoria
     * @param array  $partialData     Dados já extraídos da primeira mensagem
     * @param array  $missingFields   Campos que faltam
     */
    public static function startEntityCollection(
        int $conversationId,
        string $entityType,
        array $partialData,
        array $missingFields
    ): void {
        self::setState($conversationId, 'collecting_entity', [
            'entity_type'    => $entityType,
            'partial_data'   => $partialData,
            'missing_fields' => $missingFields,
            'started_at'     => now()->toDateTimeString(),
            'attempts'       => 0,
        ]);
    }

    /**
     * Atualiza os dados parciais durante a coleta.
     * Retorna true se não faltam mais campos.
     *
     * @param int   $conversationId
     * @param array $newData        Novos dados extraídos da mensagem atual
     * @return array{complete: bool, data: array, missing: array}
     */
    public static function updateEntityCollection(int $conversationId, array $newData): array
    {
        $current = self::getState($conversationId);

        if ($current['state'] !== 'collecting_entity') {
            return ['complete' => false, 'data' => [], 'missing' => []];
        }

        $stateData = $current['data'];
        $partial   = array_merge($stateData['partial_data'] ?? [], $newData);
        $missing   = $stateData['missing_fields'] ?? [];

        // Recalcular quais campos ainda faltam
        $stillMissing = [];
        foreach ($missing as $field) {
            if (!isset($partial[$field]) || $partial[$field] === '' || $partial[$field] === null) {
                $stillMissing[] = $field;
            }
        }

        $stateData['partial_data']   = $partial;
        $stateData['missing_fields'] = $stillMissing;
        $stateData['attempts']       = ($stateData['attempts'] ?? 0) + 1;

        // Limite de tentativas para evitar loop infinito
        if ($stateData['attempts'] >= 5) {
            self::clearState($conversationId);
            return ['complete' => false, 'data' => $partial, 'missing' => $stillMissing, 'aborted' => true];
        }

        // Se coleta completa, limpar estado
        if (empty($stillMissing)) {
            self::clearState($conversationId);
            return ['complete' => true, 'data' => $partial, 'missing' => []];
        }

        // Salvar estado atualizado
        self::setState($conversationId, 'collecting_entity', $stateData);

        return ['complete' => false, 'data' => $partial, 'missing' => $stillMissing];
    }

    /**
     * Inicia um fluxo de seleção (ex: qual cartão, qual conta).
     *
     * @param int    $conversationId
     * @param string $selectionType  'card'|'account'
     * @param array  $options        Lista de opções [{id, nome, ...}]
     * @param array  $pendingData    Dados da entidade aguardando seleção
     */
    public static function startSelection(
        int $conversationId,
        string $selectionType,
        array $options,
        array $pendingData
    ): void {
        self::setState($conversationId, 'awaiting_selection', [
            'selection_type' => $selectionType,
            'options'        => $options,
            'pending_data'   => $pendingData,
            'started_at'     => now()->toDateTimeString(),
        ]);
    }

    /**
     * Tenta resolver uma seleção a partir da mensagem do usuário.
     *
     * @param int    $conversationId
     * @param string $message        Mensagem do usuário (ex: "nubank", "1", "o primeiro")
     * @return array|null            Opção selecionada ou null se não conseguiu resolver
     */
    public static function resolveSelection(int $conversationId, string $message): ?array
    {
        $current = self::getState($conversationId);

        if ($current['state'] !== 'awaiting_selection') {
            return null;
        }

        $stateData = $current['data'];
        $options = $stateData['options'] ?? [];
        $normalized = mb_strtolower(trim($message));

        // Tentar por número ("1", "2", "3")
        if (preg_match('/^(\d+)$/', $normalized, $m)) {
            $index = (int) $m[1] - 1;
            if (isset($options[$index])) {
                $selected = $options[$index];
                self::clearState($conversationId);
                return array_merge($stateData['pending_data'] ?? [], $selected);
            }
        }

        // Tentar por nome (fuzzy match)
        foreach ($options as $option) {
            $optionName = mb_strtolower($option['nome'] ?? '');
            if ($optionName !== '' && (str_contains($normalized, $optionName) || str_contains($optionName, $normalized))) {
                self::clearState($conversationId);
                return array_merge($stateData['pending_data'] ?? [], $option);
            }
        }

        // Tentar ordinal ("primeiro", "segundo", "último")
        $ordinals = [
            'primeir' => 0,
            'segund' => 1,
            'terceir' => 2,
            'quart' => 3,
            'quint' => 4,
            'últim' => -1,
            'ultim' => -1,
        ];
        foreach ($ordinals as $prefix => $index) {
            if (preg_match('/\b' . preg_quote($prefix, '/') . '/iu', $normalized)) {
                $realIndex = $index === -1 ? count($options) - 1 : $index;
                if (isset($options[$realIndex])) {
                    $selected = $options[$realIndex];
                    self::clearState($conversationId);
                    return array_merge($stateData['pending_data'] ?? [], $selected);
                }
            }
        }

        return null;
    }

    /**
     * Limpa o estado da conversação (volta para idle).
     */
    public static function clearState(int $conversationId): void
    {
        self::setState($conversationId, 'idle', []);
    }

    /**
     * Verifica se o estado expirou (máx 5 minutos de inatividade).
     */
    public static function isExpired(int $conversationId): bool
    {
        $current = self::getState($conversationId);

        if ($current['state'] === 'idle') {
            return false;
        }

        $startedAt = $current['data']['started_at'] ?? null;
        if ($startedAt === null) {
            return true;
        }

        $elapsed = now()->diffInMinutes(new \DateTime($startedAt));
        return $elapsed > 5;
    }

    /**
     * Obtém a próxima pergunta para o fluxo de coleta.
     *
     * @param array $missingFields Campos que ainda faltam
     * @param string $entityType   Tipo da entidade
     * @return string              Pergunta formatada para o usuário
     */
    public static function getNextQuestion(array $missingFields, string $entityType): string
    {
        if (empty($missingFields)) {
            return '';
        }

        $field = $missingFields[0]; // Perguntar um campo por vez

        $questions = match ($entityType) {
            'lancamento' => [
                'valor'     => 'Qual o **valor** do lançamento? (ex: 150, R$ 200,00)',
                'descricao' => 'Qual a **descrição**? (ex: "conta de luz", "almoço no restaurante")',
                'tipo'      => 'É uma **despesa** ou **receita**?',
                'data'      => 'Qual a **data**? (ex: hoje, ontem, 15/03)',
                'cartao_credito_id' => 'Em qual **cartão** deseja registrar?',
            ],
            'meta' => [
                'titulo'    => 'Qual o **nome** da meta? (ex: "Viagem para Europa", "Carro novo")',
                'valor_alvo' => 'Qual o **valor alvo**? (ex: R$ 5.000)',
            ],
            'orcamento' => [
                'valor_limite'  => 'Qual o **valor limite** do orçamento? (ex: R$ 800)',
                'categoria_id'  => 'Qual **categoria** deseja limitar no orçamento? (ex: Alimentação, Transporte)',
            ],
            'categoria' => [
                'nome' => 'Qual o **nome** da categoria?',
                'tipo' => 'Qual o **tipo**? (receita, despesa, transferência ou ambas)',
            ],
            'subcategoria' => [
                'nome' => 'Qual o **nome** da subcategoria?',
            ],
            'conta' => [
                'nome' => 'Qual o **nome** da conta? (ex: "Nubank", "Conta Itaú")',
            ],
            default => [],
        };

        $question = $questions[$field] ?? "Qual o valor de **{$field}**?";

        $remaining = count($missingFields) - 1;
        if ($remaining > 0) {
            $question .= "\n_(falta" . ($remaining > 1 ? 'm' : '') . " mais {$remaining} campo" . ($remaining > 1 ? 's' : '') . ")_";
        }

        return $question;
    }

    // ─── Internal ───────────────────────────────────────────────

    private static function setState(int $conversationId, string $state, array $data): void
    {
        AiConversation::where('id', $conversationId)->update([
            'state'      => $state,
            'state_data' => !empty($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
