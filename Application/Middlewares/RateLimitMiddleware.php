<?php

namespace Application\Middlewares; // <--- ADICIONADO O NAMESPACE AQUI

use Application\Core\Request; // Para receber a instância de Request
use Application\Core\Exceptions\ValidationException;

use Application\Services\CacheService; // Para usar o Redis para persistir o estado

class RateLimitMiddleware
{
    private const MAX_ATTEMPTS = 60; // Número máximo de requisições
    private const TIME_WINDOW = 60; // Janela de tempo em segundos

    private CacheService $cacheService;

    /**
     * Construtor do middleware. Injeta a dependência do CacheService.
     */
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Lida com a requisição de entrada para verificar e aplicar a limitação de taxa.
     *
     * @param Request $request A instância da requisição HTTP.
     * @param string $identifier Um identificador único para a taxa (ex: IP do cliente, ID do usuário).
     * @throws ValidationException Se o limite de requisições for excedido.
     */
    public function handle(Request $request, string $identifier): void
    {
        $now = time();
        $cacheKey = "ratelimit:{$identifier}";

        // Recupera as tentativas anteriores do cache (Redis)
        $attempts = $this->cacheService->get($cacheKey, []);

        // Limpa as tentativas que estão fora da janela de tempo
        $attempts = array_filter($attempts, fn($time) => ($now - $time) < self::TIME_WINDOW);

        // Verifica se o número de tentativas excede o limite
        if (count($attempts) >= self::MAX_ATTEMPTS) {
            // TODO: Integrar LogService aqui para registrar ataques de rate limit
            // LogService::warning('Rate limit exceeded', ['identifier' => $identifier, 'ip' => $request->ip()]);
            throw new ValidationException(['rate_limit' => 'Muitas requisições. Por favor, tente novamente em breve.'], 429); // 429 Too Many Requests
        }

        // Adiciona a tentativa atual
        $attempts[] = $now;

        // Salva as tentativas atualizadas no cache com o TTL da janela de tempo
        $this->cacheService->set($cacheKey, $attempts, self::TIME_WINDOW);

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
