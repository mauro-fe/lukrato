<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Container\ApplicationContainer;
use Application\Services\AI\Collectors\AssinaturasCollector;
use Application\Services\AI\Collectors\CategoriasCollector;
use Application\Services\AI\Collectors\ContasCollector;
use Application\Services\AI\Collectors\FaturasCollector;
use Application\Services\AI\Collectors\FinanceiroCollector;
use Application\Services\AI\Collectors\LancamentosCollector;
use Application\Services\AI\Collectors\GamificacaoCollector;
use Application\Services\AI\Collectors\LogsCollector;
use Application\Services\AI\Collectors\MarketingCollector;
use Application\Services\AI\Collectors\MetasOrcamentosCollector;
use Application\Services\AI\Collectors\PlataformaCollector;
use Application\Services\AI\Collectors\SegurancaCollector;
use Application\Services\AI\Collectors\UsuariosCollector;
use Application\Services\AI\Collectors\WebhooksCollector;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Application\Services\Infrastructure\CacheService;

/**
 * Orquestra a coleta de metricas do sistema para o contexto da IA.
 * Delega cada dominio a um Collector especializado (SRP / Open-Closed).
 * Resultado cacheado por 5 minutos para evitar 14 queries SQL por mensagem.
 */
class SystemContextService
{
    private const CACHE_TTL = 300; // 5 minutos

    /** @var ContextCollectorInterface[] */
    private array $collectors;
    private CacheService $cache;

    /**
     * @param array<int, ContextCollectorInterface>|null $collectors
     */
    public function __construct(?CacheService $cache = null, ?array $collectors = null)
    {
        $this->cache = ApplicationContainer::resolveOrNew($cache, CacheService::class);
        $this->collectors = $collectors ?? [
            // Dados core
            new UsuariosCollector(),
            new FinanceiroCollector(),
            new LancamentosCollector(),
            new ContasCollector(),
            new FaturasCollector(),
            new CategoriasCollector(),

            // Negócio
            new AssinaturasCollector(),
            new MetasOrcamentosCollector(),
            new GamificacaoCollector(),
            new MarketingCollector(),

            // Operacional
            new LogsCollector(),
            new WebhooksCollector(),
            new SegurancaCollector(),

            // Plataforma
            new PlataformaCollector(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function gather(?int $userId = null): array
    {
        $scope    = $userId ? "user:{$userId}" : 'admin';
        $cacheKey = "ai:system_context:{$scope}:" . date('Y-m-d-H') . ':' . (int) (date('i') / 5);

        return $this->cache->remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $period = new ContextPeriod();

            $context = [
                'data_atual'    => $period->dataFormatada,
                'mes_atual'     => $period->mesAtual,
                'dia_da_semana' => $period->diaDaSemana,
            ];

            foreach ($this->collectors as $collector) {
                $context = array_merge($context, $collector->collect($period, $userId));
            }

            return $context;
        });
    }
}
