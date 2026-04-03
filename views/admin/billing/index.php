<!-- ============================================================================
     BILLING PAGE - LUKRATO PRO
     Página de planos e assinaturas (Refatorado)
     ============================================================================ -->

<?php
// =============================================================================
// PROCESSAMENTO DOS PLANOS
// =============================================================================
$planItems = [];
if (isset($plans) && is_iterable($plans)) {
    foreach ($plans as $plan) {
        $serialized = is_array($plan)
            ? $plan
            : (method_exists($plan, 'toArray') ? $plan->toArray() : (array) $plan);

        if (empty($serialized['code'])) continue;

        $metadata = $serialized['metadados'] ?? [];
        if (!is_array($metadata) && $metadata !== null) {
            $metadata = json_decode((string) $metadata, true) ?: [];
        }

        $planItems[] = [
            'id'          => $serialized['id'] ?? null,
            'code'        => (string) $serialized['code'],
            'name'        => $serialized['nome'] ?? ($serialized['name'] ?? (string) $serialized['code']),
            'price_cents' => (int) ($serialized['preco_centavos'] ?? 0),
            'interval'    => $serialized['intervalo'] ?? 'month',
            'active'      => (bool) ($serialized['ativo'] ?? true),
            'metadata'    => is_array($metadata) ? $metadata : [],
        ];
    }
}

$currentPlanCode = $currentPlanCode ?? ($user?->planoAtual()?->code ?? null);
?>

<div class="billing-page">
<?php include __DIR__ . '/sections/header.php'; ?>
<?php include __DIR__ . '/sections/plans-grid.php'; ?>
<?php include __DIR__ . '/sections/customize-modal.php'; ?>
</div>

<!-- Modal de Pagamento -->
<?php include __DIR__ . '/../partials/modals/modal-pagamento.php'; ?>

<!-- JS carregado via Vite (loadPageJs) -->
