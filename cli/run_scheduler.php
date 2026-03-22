<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Infrastructure\SchedulerTaskRunner;

$runner = new SchedulerTaskRunner();
$lock = new SchedulerExecutionLock();
$args = $argv;
array_shift($args);

$command = $args[0] ?? 'run';
$task = $args[1] ?? 'all';
$json = in_array('--json', $args, true);
$options = [
    'no_email' => in_array('--no-email', $args, true),
    'preview' => in_array('--preview', $args, true),
];

try {
    if ($command === 'run') {
        $lock->acquire('scheduler');
    }

    $result = match ($command) {
        'list' => $runner->listTasks(),
        'health' => $runner->health(),
        'debug' => $runner->debug(),
        'help' => buildHelpPayload($runner),
        'run' => $task === 'all'
            ? $runner->runAll($options)
            : $runner->runTask($task, $options),
        default => throw new InvalidArgumentException(
            "Comando invalido: {$command}" . PHP_EOL . renderUsageText($runner)
        ),
    };

    if ($json) {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    } else {
        renderResult($command, $result);
    }

    exit(resolveExitCode($command, $result));
} catch (Throwable $exception) {
    fwrite(STDERR, '[scheduler] Erro fatal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
} finally {
    $lock->release();
}

/**
 * @param array<string, mixed> $result
 */
function renderResult(string $command, array $result): void
{
    switch ($command) {
        case 'help':
            echo $result['usage'] . PHP_EOL;
            return;

        case 'list':
            echo "Tarefas disponiveis:" . PHP_EOL;
            foreach ($result as $name => $task) {
                echo " - {$name}: {$task['description']} ({$task['recommended_schedule']})" . PHP_EOL;
            }
            return;

        case 'health':
            echo "[scheduler] status={$result['status']} env={$result['environment']} tasks={$result['task_count']}" . PHP_EOL;
            return;

        case 'debug':
            echo "[scheduler] Debug" . PHP_EOL;
            echo " - env: {$result['health']['environment']}" . PHP_EOL;
            echo " - sapi: {$result['health']['php_sapi']}" . PHP_EOL;
            echo " - base_url: " . ($result['base_url'] ?? '[null]') . PHP_EOL;
            echo " - tasks: " . implode(', ', array_keys($result['tasks'])) . PHP_EOL;
            return;

        default:
            if (array_key_exists('results', $result)) {
                echo "[scheduler] executadas={$result['executed']} sucesso={$result['successful']} falhas={$result['failed']}" . PHP_EOL;
                foreach ($result['results'] as $taskResult) {
                    $status = ($taskResult['success'] ?? false) ? 'OK' : 'FAIL';
                    echo " - {$taskResult['task']}: {$status}" . PHP_EOL;
                }
                return;
            }

            $status = ($result['success'] ?? false) ? 'OK' : 'FAIL';
            echo "[scheduler] {$result['task']} => {$status}" . PHP_EOL;
    }
}

/**
 * @param array<string, mixed> $result
 */
function resolveExitCode(string $command, array $result): int
{
    if ($command === 'run') {
        if (array_key_exists('results', $result)) {
            return ($result['failed'] ?? 0) > 0 ? 1 : 0;
        }

        return ($result['success'] ?? false) ? 0 : 1;
    }

    return 0;
}

/**
 * @return array<string, mixed>
 */
function buildHelpPayload(SchedulerTaskRunner $runner): array
{
    return [
        'usage' => renderUsageText($runner),
    ];
}

function renderUsageText(SchedulerTaskRunner $runner): string
{
    $lines = [
        'Uso:',
        '  php cli/run_scheduler.php help',
        '  php cli/run_scheduler.php list',
        '  php cli/run_scheduler.php health',
        '  php cli/run_scheduler.php debug',
        '  php cli/run_scheduler.php run all [--json]',
        '  php cli/run_scheduler.php run <tarefa> [--json] [--preview] [--no-email]',
        '',
        'Tarefas:',
    ];

    foreach ($runner->listTasks() as $name => $task) {
        $lines[] = sprintf(
            '  - %s: %s (%s)',
            $name,
            $task['description'],
            $task['recommended_schedule']
        );
    }

    $lines[] = '';
    $lines[] = 'Observacoes:';
    $lines[] = '  - Scheduler HTTP foi desativado; use apenas este runner CLI.';
    $lines[] = '  - Execucoes `run` usam lock global para evitar concorrencia.';

    return implode(PHP_EOL, $lines);
}
