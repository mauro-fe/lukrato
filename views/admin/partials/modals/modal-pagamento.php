<?php

/**
 * Modal de Pagamento.
 *
 * Variáveis esperadas (vindas do BillingController):
 * @var \Application\Models\Usuario $user
 * @var string  $cpfValue
 * @var string  $telefoneValue
 * @var string  $cepValue
 * @var string  $enderecoValue
 * @var string  $cpfDigits
 * @var string  $phoneDigits
 * @var string  $cepDigits
 * @var bool    $pixDataComplete
 * @var bool    $boletoDataComplete
 */
?>

<?php include __DIR__ . '/modal-pagamento/sections/modal-shell-open.php'; ?>
<?php include __DIR__ . '/modal-pagamento/sections/hidden-fields.php'; ?>
<?php include __DIR__ . '/modal-pagamento/sections/coupon-section.php'; ?>
<?php include __DIR__ . '/modal-pagamento/sections/credit-card-section.php'; ?>
<?php include __DIR__ . '/modal-pagamento/sections/pix-section.php'; ?>
<?php include __DIR__ . '/modal-pagamento/sections/boleto-section.php'; ?>
<?php include __DIR__ . '/modal-pagamento/sections/form-actions.php'; ?>
<?php include __DIR__ . '/modal-pagamento/sections/modal-shell-close.php'; ?>

<!-- modal-pagamento.js carregado via Vite (importado pelo billing module) -->
