<?php

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Router;
use Application\Core\Exceptions\ValidationException;

use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;

class RateLimitMiddleware
{
    private int $maxAttempts;
    private int $timeWindow;

    private CacheService $cacheService;

    /**
     * Construtor do middleware. Injeta a dependência do CacheService.
     */
    public function __construct(CacheService $cacheService, ?int $maxAttempts = null, ?int $timeWindow = null)
    {
        $this->cacheService = $cacheService;
        $this->maxAttempts = $maxAttempts ?? (int) ($_ENV['RATELIMIT_MAX_ATTEMPTS'] ?? 60);
        $this->timeWindow = $timeWindow ?? (int) ($_ENV['RATELIMIT_TIME_WINDOW'] ?? 60);
    }

    /**
     * Lida com a requisição de entrada para verificar e aplicar a limitação de taxa.
     *
     * @param Request $request A instância da requisição HTTP.
     * @param string $identifier Um identificador único para a taxa (ex: IP do cliente, ID do usuário).
     * @param string $endpoint Identificador do endpoint para separação de limites.
     * @throws ValidationException Se o limite de requisições for excedido.
     */
    public function handle(Request $request, string $identifier, string $endpoint = 'global'): void
    {
        $now = time();
        $cacheKey = "ratelimit:{$endpoint}:{$identifier}";

        // Recupera as tentativas anteriores do cache (Redis)
        $attempts = $this->cacheService->get($cacheKey, []);

        // Limpa as tentativas que estão fora da janela de tempo
        $attempts = array_filter($attempts, fn($time) => ($now - $time) < $this->timeWindow);

        // Verifica se o número de tentativas excede o limite
        if (count($attempts) >= $this->maxAttempts) {
            LogService::persist(
                level: LogLevel::WARNING,
                category: LogCategory::SECURITY,
                message: 'Rate limit excedido',
                context: [
                    'ip'         => $request->ip(),
                    'identifier' => $identifier,
                    'endpoint'   => $endpoint,
                    'uri'        => $_SERVER['REQUEST_URI'] ?? '',
                    'method'     => $_SERVER['REQUEST_METHOD'] ?? '',
                    'attempts'   => count($attempts),
                    'limit'      => $this->maxAttempts,
                    'window'     => $this->timeWindow . 's',
                    'tier'       => 'standard',
                ],
            );

            // API/AJAX: resposta JSON via exception
            if ($request->wantsJson() || $request->isAjax()) {
                throw new ValidationException(['rate_limit' => 'Muitas requisições. Por favor, tente novamente em breve.'], 429);
            }

            // Web: página HTML de erro 429
            Router::handleTooManyRequests($request, $this->timeWindow);
        }

        // Adiciona a tentativa atual
        $attempts[] = $now;

        // Salva as tentativas atualizadas no cache com o TTL da janela de tempo
        $this->cacheService->set($cacheKey, $attempts, $this->timeWindow);

        // Se chegou até aqui, a requisição é permitida.
    }

    /**
     * Gera um identificador padrão para rate limiting (ex: IP do cliente).
     * @param Request $request
     * @return string
     */
    public static function getIdentifier(Request $request): string
    {
        return $request->ip(); // Utiliza o método ip() da classe Request
    }
}
