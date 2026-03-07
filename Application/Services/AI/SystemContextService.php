<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Services\AI\Collectors\AssinaturasCollector;
use Application\Services\AI\Collectors\ContasCollector;
use Application\Services\AI\Collectors\FinanceiroCollector;
use Application\Services\AI\Collectors\GamificacaoCollector;
use Application\Services\AI\Collectors\MarketingCollector;
use Application\Services\AI\Collectors\MetasOrcamentosCollector;
use Application\Services\AI\Collectors\PlataformaCollector;
use Application\Services\AI\Collectors\UsuariosCollector;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;

/**
 * Orquestra a coleta de metricas do sistema para o contexto da IA.
 * Delega cada dominio a um Collector especializado (SRP / Open-Closed).
 */
class SystemContextService
{
    /** @var ContextCollectorInterface[] */
    private array $collectors;

    public function __construct()
    {
        $this->collectors = [
            new UsuariosCollector(),
            new FinanceiroCollector(),
            new ContasCollector(),
            new AssinaturasCollector(),
            new MetasOrcamentosCollector(),
            new GamificacaoCollector(),
            new MarketingCollector(),
            new PlataformaCollector(),
        ];
    }

    public function gather(): array
    {
        $period = new ContextPeriod();

        $context = [
            'data_atual'    => $period->dataFormatada,
            'mes_atual'     => $period->mesAtual,
            'dia_da_semana' => $period->diaDaSemana,
        ];

        foreach ($this->collectors as $collector) {
            $context = array_merge($context, $collector->collect($period));
        }

        return $context;
    }
}
