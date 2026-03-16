<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\UserCategoryRule;
use Application\Services\Infrastructure\LogService;

$isApply = in_array('--apply', $argv ?? [], true);
$userId = null;

foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--user=(\d+)$/', $arg, $matches)) {
        $userId = (int) $matches[1];
    }
}

echo "=== Auditoria de user_category_rules ===" . PHP_EOL;
echo "Data/Hora: " . date('Y-m-d H:i:s') . PHP_EOL;
echo $isApply
    ? "*** MODO APLICAR (--apply) - apenas regras suspeitas serao removidas ***" . PHP_EOL
    : "*** MODO SIMULACAO - nenhuma regra sera removida ***" . PHP_EOL;

if ($userId !== null) {
    echo "Filtro de usuario: {$userId}" . PHP_EOL;
}

echo str_repeat('-', 70) . PHP_EOL;

try {
    $query = UserCategoryRule::query()
        ->with(['categoria', 'subcategoria'])
        ->orderBy('user_id')
        ->orderBy('id');

    if ($userId !== null) {
        $query->where('user_id', $userId);
    }

    $rules = $query->get();

    $removable = [];
    $warmingUp = [];

    foreach ($rules as $rule) {
        $flags = UserCategoryRule::getAuditFlags($rule);
        if ($flags === []) {
            continue;
        }

        $categoryLabel = $rule->categoria?->nome ?? ('categoria_id=' . $rule->categoria_id);
        if ($rule->subcategoria?->nome) {
            $categoryLabel .= ' > ' . $rule->subcategoria->nome;
        }

        $entry = [
            'id' => $rule->id,
            'user_id' => $rule->user_id,
            'pattern' => $rule->pattern,
            'normalized_pattern' => $rule->normalized_pattern,
            'usage_count' => (int) $rule->usage_count,
            'source' => $rule->source,
            'category' => $categoryLabel,
            'flags' => $flags,
        ];

        if (in_array('weak_pattern', $flags, true)) {
            $removable[] = $entry;
            continue;
        }

        $warmingUp[] = $entry;
    }

    echo 'Regras suspeitas para remocao segura: ' . count($removable) . PHP_EOL;
    foreach ($removable as $entry) {
        echo sprintf(
            "  [#%d] user=%d pattern=\"%s\" source=%s usage=%d categoria=%s flags=%s",
            $entry['id'],
            $entry['user_id'],
            $entry['pattern'],
            $entry['source'],
            $entry['usage_count'],
            $entry['category'],
            implode(',', $entry['flags'])
        ) . PHP_EOL;
    }

    echo PHP_EOL . 'Regras em aquecimento (nao removidas): ' . count($warmingUp) . PHP_EOL;
    foreach ($warmingUp as $entry) {
        echo sprintf(
            "  [#%d] user=%d pattern=\"%s\" source=%s usage=%d categoria=%s flags=%s",
            $entry['id'],
            $entry['user_id'],
            $entry['pattern'],
            $entry['source'],
            $entry['usage_count'],
            $entry['category'],
            implode(',', $entry['flags'])
        ) . PHP_EOL;
    }

    if ($isApply && $removable !== []) {
        $ids = array_column($removable, 'id');
        $removed = UserCategoryRule::query()->whereIn('id', $ids)->delete();
        echo PHP_EOL . "Removidas {$removed} regra(s) suspeita(s)." . PHP_EOL;
    } elseif ($isApply) {
        echo PHP_EOL . "Nenhuma regra suspeita para remover." . PHP_EOL;
    }

    LogService::info('cleanup_user_category_rules', [
        'apply' => $isApply,
        'user_id' => $userId,
        'suspicious_count' => count($removable),
        'warming_up_count' => count($warmingUp),
    ]);
} catch (\Throwable $e) {
    echo 'ERRO FATAL: ' . $e->getMessage() . PHP_EOL;
    LogService::error('cleanup_user_category_rules_error', [
        'apply' => $isApply,
        'user_id' => $userId,
        'error' => $e->getMessage(),
    ]);
    exit(1);
}
