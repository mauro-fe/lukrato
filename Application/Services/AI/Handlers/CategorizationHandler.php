<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\Categoria;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Helpers\UserCategoryLoader;
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
    private ?AIProvider $provider = null;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
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
            // Buscar categorias do usuário (com subcategorias)
            $categories = $request->meta('categories', []);

            if (empty($categories) && $request->userId) {
                $categories = UserCategoryLoader::load($request->userId);
            }

            if (empty($categories)) {
                $categories = PromptBuilder::defaultCategories();
            }

            $suggested = $this->provider->suggestCategory($description, $categories);

            if ($suggested === null) {
                return AIResponseDTO::fail(
                    'Não foi possível sugerir uma categoria.',
                    IntentType::CATEGORIZE,
                );
            }

            // Tentar resolver IDs (com suporte a "Categoria > Subcategoria")
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
     * Resolve o nome sugerido pelo LLM em IDs reais.
     * Suporta formatos: "Categoria" ou "Categoria > Subcategoria".
     */
    private function resolveResult(string $suggested, ?int $userId): array
    {
        // Parsear formato "Categoria > Subcategoria"
        $categoriaNome = $suggested;
        $subcategoriaNome = null;

        if (str_contains($suggested, '>')) {
            $parts = array_map('trim', explode('>', $suggested, 2));
            $categoriaNome = $parts[0];
            $subcategoriaNome = $parts[1] ?? null;
        }

        $result = [
            'categoria'       => $categoriaNome,
            'subcategoria'    => $subcategoriaNome,
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
            $cat = (clone $query)->where('nome', $categoriaNome)->first();

            // Tentar fuzzy match
            if (!$cat) {
                $allCats = $query->get();
                $bestScore = 0;
                foreach ($allCats as $c) {
                    similar_text(mb_strtolower($c->nome), mb_strtolower($categoriaNome), $percent);
                    if ($percent >= 85 && $percent > $bestScore) {
                        $bestScore = $percent;
                        $cat = $c;
                        $result['categoria'] = $c->nome;
                    }
                }
            }

            if ($cat) {
                $result['categoria_id'] = $cat->id;

                // Resolver subcategoria se informada
                if ($subcategoriaNome !== null) {
                    $sub = Categoria::query()
                        ->where('parent_id', $cat->id)
                        ->where('nome', $subcategoriaNome)
                        ->first();

                    // Fuzzy match para subcategoria
                    if (!$sub) {
                        $allSubs = Categoria::query()
                            ->where('parent_id', $cat->id)
                            ->get();
                        $bestScore = 0;
                        foreach ($allSubs as $s) {
                            similar_text(mb_strtolower($s->nome), mb_strtolower($subcategoriaNome), $percent);
                            if ($percent >= 85 && $percent > $bestScore) {
                                $bestScore = $percent;
                                $sub = $s;
                                $result['subcategoria'] = $s->nome;
                            }
                        }
                    }

                    if ($sub) {
                        $result['subcategoria_id'] = $sub->id;
                    }
                }
            }
        } catch (\Throwable) {
            // Falha silenciosa
        }

        return $result;
    }
}
