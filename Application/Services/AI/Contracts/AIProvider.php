<?php

declare(strict_types=1);

namespace Application\Services\AI\Contracts;

interface AIProvider
{
    /**
     * Envia uma mensagem para o chat assistente com contexto opcional.
     * Em caso de falha, retorna uma mensagem de erro amigável (nunca lança exceção).
     */
    public function chat(string $prompt, array $context = []): string;

    /**
     * Sugere uma categoria para a descrição de um lançamento.
     * Retorna null em caso de falha ou categoria não encontrada.
     */
    public function suggestCategory(string $description, array $availableCategories = []): ?string;

    /**
     * Analisa um conjunto de lançamentos agregados e retorna insights.
     * Retorna array vazio em caso de falha.
     *
     * @param  array  $data    Lista de ['categoria', 'total', 'count', 'mes']
     * @return array           ['insights' => string[], 'resumo' => string]
     */
    public function analyzeSpending(array $data, string $period = 'último mês'): array;

    /**
     * Retorna o nome do modelo em uso.
     */
    public function getModel(): string;

    /**
     * Retorna metadados da última chamada (tokens_prompt, tokens_completion, tokens_total).
     */
    public function getLastMeta(): array;
}
