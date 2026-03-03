<?php
// CSS: public/assets/css/modules/support-button.css (carregado via header.php)
// JS:  resources/js/admin/global/support-button.js (carregado via Vite bundle)
// Variáveis: $supportName, $supportEmail, $supportTel, $supportDdd (via BaseController::renderAdmin)

$supportName  = $supportName  ?? '';
$supportEmail = $supportEmail ?? '';
$supportTel   = $supportTel   ?? '';
$supportDdd   = $supportDdd   ?? '';
?>

<a href="#" class="lk-support-button" title="Fale com o Suporte"
    data-support-name="<?= htmlspecialchars($supportName, ENT_QUOTES, 'UTF-8') ?>"
    data-support-email="<?= htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8') ?>"
    data-support-tel="<?= htmlspecialchars($supportTel, ENT_QUOTES, 'UTF-8') ?>"
    data-support-cod="<?= htmlspecialchars($supportDdd, ENT_QUOTES, 'UTF-8') ?>"
    onclick="openSupportModal(this); return false;">
    <i data-lucide="headphones"></i>
</a>