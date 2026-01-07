<!-- FAB -->
<?php
// Verificar se estamos na página de contas
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isContasPage = strpos($currentUri, '/contas') !== false && strpos($currentUri, '/contas/arquivadas') === false;
?>

<?php if ($isContasPage): ?>
    <div class="fab-container">
        <button class="fab" id="fabButton" aria-label="Adicionar transação" onclick="lancamentoGlobalManager.openModal()">
            <i class="fas fa-plus"></i>
        </button>
    </div>
<?php endif; ?>