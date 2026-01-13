<?php

namespace Application\Services;

use Application\Services\LogService;

/**
 * Circuit Breaker para API do Asaas
 * Evita sobrecarregar o serviço em caso de falhas e protege o sistema
 * 
 * Estados:
 * - CLOSED: Funcionando normalmente
 * - OPEN: Falhas detectadas, requests bloqueados
 * - HALF_OPEN: Testando se voltou ao normal
 */
class CircuitBreakerService
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private const FAILURE_THRESHOLD = 5;      // Falhas para abrir circuito
    private const SUCCESS_THRESHOLD = 2;      // Sucessos para fechar circuito
    private const TIMEOUT = 60;               // Segundos antes de tentar novamente

    private string $serviceName;
    private string $stateFile;

    public function __construct(string $serviceName = 'asaas')
    {
        $this->serviceName = $serviceName;
        $cacheDir = BASE_PATH . '/storage/cache/circuit_breaker';

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $this->stateFile = $cacheDir . '/' . $serviceName . '.json';
    }

    /**
     * Verifica se pode fazer requisição
     */
    public function canExecute(): bool
    {
        $state = $this->getState();

        if ($state['status'] === self::STATE_CLOSED) {
            return true;
        }

        if ($state['status'] === self::STATE_OPEN) {
            // Verificar se já passou o timeout
            if (time() - $state['opened_at'] >= self::TIMEOUT) {
                // Tentar half-open
                $this->setState(self::STATE_HALF_OPEN);
                return true;
            }
            return false;
        }

        // HALF_OPEN: permitir tentativa
        return true;
    }

    /**
     * Registra sucesso
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state['status'] === self::STATE_HALF_OPEN) {
            $state['success_count']++;

            if ($state['success_count'] >= self::SUCCESS_THRESHOLD) {
                // Voltar ao normal
                $this->setState(self::STATE_CLOSED);

                if (class_exists(LogService::class)) {
                    LogService::info("Circuit Breaker [{$this->serviceName}] FECHADO - Serviço recuperado");
                }
                return;
            }

            $this->saveState($state);
        } elseif ($state['status'] === self::STATE_CLOSED) {
            // Resetar contador de falhas
            if ($state['failure_count'] > 0) {
                $state['failure_count'] = 0;
                $this->saveState($state);
            }
        }
    }

    /**
     * Registra falha
     */
    public function recordFailure(\Throwable $e): void
    {
        $state = $this->getState();
        $state['failure_count']++;
        $state['last_failure'] = [
            'message' => $e->getMessage(),
            'time' => time(),
        ];

        if ($state['status'] === self::STATE_HALF_OPEN) {
            // Voltou a falhar, abrir novamente
            $this->setState(self::STATE_OPEN);

            if (class_exists(LogService::class)) {
                LogService::warning("Circuit Breaker [{$this->serviceName}] ABERTO novamente - Serviço ainda instável");
            }
            return;
        }

        if ($state['failure_count'] >= self::FAILURE_THRESHOLD && $state['status'] === self::STATE_CLOSED) {
            // Abrir circuito
            $state['status'] = self::STATE_OPEN;
            $state['opened_at'] = time();
            $state['success_count'] = 0;

            if (class_exists(LogService::class)) {
                LogService::error("Circuit Breaker [{$this->serviceName}] ABERTO - Muitas falhas detectadas", [
                    'failures' => $state['failure_count'],
                    'threshold' => self::FAILURE_THRESHOLD,
                ]);
            }
        }

        $this->saveState($state);
    }

    /**
     * Executa função com proteção de circuit breaker
     */
    public function execute(callable $callback)
    {
        if (!$this->canExecute()) {
            throw new \RuntimeException(
                "Serviço {$this->serviceName} temporariamente indisponível. " .
                    "Circuito aberto devido a falhas recentes. Tente novamente em instantes."
            );
        }

        try {
            $result = $callback();
            $this->recordSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    private function getState(): array
    {
        $default = [
            'status' => self::STATE_CLOSED,
            'failure_count' => 0,
            'success_count' => 0,
            'opened_at' => 0,
            'last_failure' => null,
        ];

        if (!file_exists($this->stateFile)) {
            return $default;
        }

        $data = json_decode(file_get_contents($this->stateFile), true);
        return is_array($data) ? array_merge($default, $data) : $default;
    }

    private function saveState(array $state): void
    {
        file_put_contents($this->stateFile, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);
    }

    private function setState(string $status): void
    {
        $state = $this->getState();
        $state['status'] = $status;

        if ($status === self::STATE_CLOSED) {
            $state['failure_count'] = 0;
            $state['success_count'] = 0;
            $state['opened_at'] = 0;
        } elseif ($status === self::STATE_OPEN) {
            $state['opened_at'] = time();
            $state['success_count'] = 0;
        } elseif ($status === self::STATE_HALF_OPEN) {
            $state['success_count'] = 0;
        }

        $this->saveState($state);
    }

    /**
     * Retorna estado atual
     */
    public function getStatus(): array
    {
        return $this->getState();
    }

    /**
     * Força fechar o circuito (admin)
     */
    public function forceClose(): void
    {
        $this->setState(self::STATE_CLOSED);

        if (class_exists(LogService::class)) {
            LogService::info("Circuit Breaker [{$this->serviceName}] FORÇADO para FECHADO manualmente");
        }
    }
}
