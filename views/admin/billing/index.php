<!-- views/admin/billing/index.php -->
<div class="lk-card">
    <h2 class="mb-2">Seu plano</h2>
    <?php if ($user->anuncios_desativados): ?>
    <p>Plano: <strong>Sem anúncios</strong> — renova em <?= htmlspecialchars($user->plano_renova_em ?? '—') ?></p>
    <?php else: ?>
    <p>Plano: <strong>Gratuito</strong> — com anúncios</p>
    <?php endif; ?>

    <button id="btnAssinar" class="btn btn-primary mt-3">Remover anúncios (R$ 12/mês)</button>
</div>

<script>
document.getElementById('btnAssinar')?.addEventListener('click', async () => {
    const resp = await fetch('<?= BASE_URL ?>api/billing/pagarme/checkout', {
        method: 'POST'
    });
    const json = await resp.json();
    if (json?.success && json.data?.checkout_url) {
        window.location.href = json.data.checkout_url;
    } else {
        alert(json?.message || 'Falha ao iniciar cobrança');
    }
});
</script>