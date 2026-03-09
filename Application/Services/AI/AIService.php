    <?php

    declare(strict_types=1);

    namespace Application\Services\AI;

    use Application\Lib\Auth;
    use Application\Services\AI\Contracts\AIProvider;
    use Application\Services\AI\Providers\OllamaProvider;
    use Application\Services\AI\Providers\OpenAIProvider;
    use Application\Services\Infrastructure\CacheService;
    use Application\Services\Infrastructure\LogService;
    use Throwable;

    /**
     * Gateway de IA — ponto único de acesso para todas as funcionalidades de IA.
     *
     * Uso:
     *   $ai = new AIService();
     *   $resposta  = $ai->chat("Qual foi o mês com mais gastos?", $context);
     *   $categoria = $ai->suggestCategory("ifood alimentação");
     *   $analise   = $ai->analyzeSpending($dados, 'fevereiro/2026');
     *
     * Troca de provedor: defina AI_PROVIDER=ollama|openai no .env do PHP.
     * O fallback é sempre seguro: nenhum método lança exceção para o chamador.
     */
    class AIService
    {
        private AIProvider $provider;
        private CacheService $cache;

        public function __construct(?AIProvider $provider = null)
        {
            $this->provider = $provider ?? $this->resolveProvider();
            $this->cache = new CacheService();
        }

        private function resolveProvider(): AIProvider
        {
            $name = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');

            return match ($name) {
                'ollama' => new OllamaProvider(),
                default  => new OpenAIProvider(),
            };
        }

        /**
         * Chat assistente. Nunca lança exceção — retorna mensagem amigável em caso de falha.
         */
        public function chat(string $prompt, array $context = []): string
        {
            $start = microtime(true);
            try {
                $response = $this->provider->chat($prompt, $context);
                $this->logInteraction('chat', $prompt, $response, $start);
                return $response;
            } catch (Throwable $e) {
                $this->logInteraction('chat', $prompt, null, $start, $e->getMessage());
                LogService::error('[AIService] chat() falhou', [
                    'error'    => $e->getMessage(),
                    'provider' => $_ENV['AI_PROVIDER'] ?? 'openai',
                ]);

                return 'O assistente de IA está indisponível no momento. Tente novamente em instantes.';
            }
        }

        /**
         * Sugestão de categoria. Retorna null silenciosamente em caso de falha.
         * Resultados são cacheados por 24h para descrições idênticas.
         */
        public function suggestCategory(string $description, array $availableCategories = []): ?string
        {
            $cacheKey = 'ai:cat:' . md5(mb_strtolower(trim($description)));
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return is_string($cached) ? $cached : null;
            }

            $start = microtime(true);
            try {
                $result = $this->provider->suggestCategory($description, $availableCategories);
                $this->logInteraction('suggest_category', $description, $result, $start);

                if ($result !== null) {
                    $this->cache->set($cacheKey, $result, 86400); // 24h
                }

                return $result;
            } catch (Throwable $e) {
                $this->logInteraction('suggest_category', $description, null, $start, $e->getMessage());
                LogService::error('[AIService] suggestCategory() falhou', [
                    'error'       => $e->getMessage(),
                    'description' => $description,
                ]);

                return null;
            }
        }

        /**
         * Análise de gastos. Retorna array vazio em caso de falha (UI exibe mensagem de indisponibilidade).
         *
         * @return array ['insights' => string[], 'resumo' => string]
         */
        public function analyzeSpending(array $data, string $period = 'último mês'): array
        {
            $start = microtime(true);
            $promptText = "Análise de gastos — período: {$period}";
            try {
                $result = $this->provider->analyzeSpending($data, $period);
                $this->logInteraction('analyze_spending', $promptText, json_encode($result, JSON_UNESCAPED_UNICODE), $start);
                return $result;
            } catch (Throwable $e) {
                $this->logInteraction('analyze_spending', $promptText, null, $start, $e->getMessage());
                LogService::error('[AIService] analyzeSpending() falhou', [
                    'error'  => $e->getMessage(),
                    'period' => $period,
                ]);

                return [];
            }
        }

        // ─── Logging ───────────────────────────────────────────────

        private function logInteraction(string $type, string $prompt, ?string $response, float $startTime, ?string $error = null): void
        {
            try {
                $elapsed = (int) round((microtime(true) - $startTime) * 1000);
                $providerName = strtolower($_ENV['AI_PROVIDER'] ?? 'openai');
                $model = ($this->provider instanceof OpenAIProvider)
                    ? $this->provider->getModel()
                    : ($providerName === 'ollama' ? ($_ENV['OLLAMA_MODEL'] ?? 'llama3') : ($_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini'));

                $meta = ($this->provider instanceof OpenAIProvider)
                    ? $this->provider->getLastMeta()
                    : [];

                $user = Auth::user();
                $userId = $user?->id;

                AiLogService::log([
                    'user_id'           => $userId,
                    'type'              => $type,
                    'prompt'            => $prompt,
                    'response'          => $error ? null : $response,
                    'provider'          => $providerName,
                    'model'             => $model,
                    'tokens_prompt'     => $meta['tokens_prompt'] ?? null,
                    'tokens_completion' => $meta['tokens_completion'] ?? null,
                    'tokens_total'      => $meta['tokens_total'] ?? null,
                    'response_time_ms'  => $elapsed,
                    'success'           => $error === null,
                    'error_message'     => $error,
                ]);
            } catch (Throwable) {
                // Nunca deixar logging quebrar o fluxo principal
            }
        }
    }
