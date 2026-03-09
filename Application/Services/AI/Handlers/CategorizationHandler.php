<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\Categoria;
use Application\Services\AI\AIService;
use Application\Services\AI\PromptBuilder;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\Infrastructure\CacheService;

/**
 * Handler para sugestão automática de categoria/subcategoria.
 * Abordagem híbrida: rules primeiro (0 tokens), LLM como fallback.
 */
class CategorizationHandler implements AIHandlerInterface
{
    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::CATEGORIZE;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $description = trim($request->message);

        if (mb_strlen($description) < 2) {
            return AIResponseDTO::fail(
                'Descrição muito curta para categorização.',
                IntentType::CATEGORIZE,
            );
        }

        // Checar cache
        $cacheKey = 'ai:cat_full:' . md5(mb_strtolower($description) . ':' . ($request->userId ?? 0));
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return AIResponseDTO::fromCache(
                "Categoria sugerida: {$cached['categoria']}",
                $cached,
                IntentType::CATEGORIZE,
            );
        }

        // Pass 1: Rule Engine (0 tokens)
        $ruleResult = CategoryRuleEngine::match($description, $request->userId);

        if ($ruleResult !== null) {
            $this->cache->set($cacheKey, $ruleResult, 86400);

            $msg = $ruleResult['subcategoria']
                ? "Categoria sugerida: {$ruleResult['categoria']} > {$ruleResult['subcategoria']}"
                : "Categoria sugerida: {$ruleResult['categoria']}";

            return AIResponseDTO::fromRule($msg, $ruleResult, IntentType::CATEGORIZE);
        }

        // Pass 2: LLM fallback
        return $this->resolveWithAI($description, $request);
    }

    /**
     * Resolve categoria usando LLM como fallback.
     */
    private function resolveWithAI(string $description, AIRequestDTO $request): AIResponseDTO
    {
        try {
            // Buscar categorias do usuário
            $categories = $request->meta('categories', []);

            if (empty($categories)) {
                $categories = $this->getUserCategories($request->userId);
            }

            if (empty($categories)) {
                $categories = PromptBuilder::defaultCategories();
            }

            $ai = new AIService();
            $suggested = $ai->suggestCategory($description, $categories);

            if ($suggested === null) {
                return AIResponseDTO::fail(
                    'Não foi possível sugerir uma categoria.',
                    IntentType::CATEGORIZE,
                );
            }

            // Tentar resolver IDs
            $result = $this->resolveResult($suggested, $request->userId);

            // Cachear resultado
            $cacheKey = 'ai:cat_full:' . md5(mb_strtolower($description) . ':' . ($request->userId ?? 0));
            $this->cache->set($cacheKey, $result, 86400);

            $msg = $result['subcategoria']
                ? "Categoria sugerida: {$result['categoria']} > {$result['subcategoria']}"
                : "Categoria sugerida: {$result['categoria']}";

            return AIResponseDTO::fromLLM($msg, $result, IntentType::CATEGORIZE);
        } catch (\Throwable $e) {
            return AIResponseDTO::fail(
                'Erro ao sugerir categoria: ' . $e->getMessage(),
                IntentType::CATEGORIZE,
            );
        }
    }

    /**
     * Busca nomes das categorias do usuário.
     */
    private function getUserCategories(?int $userId): array
    {
        if ($userId === null) {
            return [];
        }

        try {
            return Categoria::query()
                ->whereNull('parent_id')
                ->where(function ($q) use ($userId) {
                    $q->whereNull('user_id')->orWhere('user_id', $userId);
                })
                ->pluck('nome')
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Resolve o nome sugerido pelo LLM em IDs reais.
     */
    private function resolveResult(string $suggested, ?int $userId): array
    {
        $result = [
            'categoria'       => $suggested,
            'subcategoria'    => null,
            'categoria_id'    => null,
            'subcategoria_id' => null,
            'confidence'      => 'ai',
        ];

        try {
            $query = Categoria::query()
                ->whereNull('parent_id');

            if ($userId !== null) {
                $query->where(function ($q) use ($userId) {
                    $q->whereNull('user_id')->orWhere('user_id', $userId);
                });
            }

            // Tentar match exato primeiro
            $cat = (clone $query)->where('nome', $suggested)->first();

            // Tentar fuzzy match
            if (!$cat) {
                $allCats = $query->get();
                foreach ($allCats as $c) {
                    similar_text(mb_strtolower($c->nome), mb_strtolower($suggested), $percent);
                    if ($percent >= 70) {
                        $cat = $c;
                        $result['categoria'] = $c->nome;
                        break;
                    }
                }
            }

            if ($cat) {
                $result['categoria_id'] = $cat->id;
            }
        } catch (\Throwable) {
            // Falha silenciosa
        }

        return $result;
    }
}
