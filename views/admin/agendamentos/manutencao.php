<style>
.maintenance-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 70vh;
}

.maintenance-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 70vh;
    background: var(--color-bg);
}

.maintenance-card {
    background: var(--color-surface);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    padding: var(--spacing-5);
    width: 100%;
    text-align: center;
    animation: fadeInUp 0.7s cubic-bezier(.68, -0.55, .27, 1.55);
    position: relative;
}

.maintenance-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 2rem;
    background: var(--color-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-md);
}

.maintenance-icon i {
    font-size: 3rem;
    color: var(--color-primary);
}

.maintenance-title {
    font-size: var(--font-size-2xl);
    font-weight: 800;
    color: var(--color-text);
    margin-bottom: 1rem;
    letter-spacing: -1px;
}

.maintenance-text {
    font-size: var(--font-size-lg);
    color: var(--color-text-muted);
    line-height: 1.7;
    margin-bottom: 2rem;
}

.maintenance-hint {
    font-size: var(--font-size-base);
    color: var(--color-text-muted);
    margin-bottom: 2.5rem;
    padding: 1rem 1.5rem;
    background: var(--color-surface-muted);
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-card-border);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.maintenance-hint i {
    font-size: 1.2rem;
    color: var(--color-primary);
}

.maintenance-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    padding: 1rem 2.5rem;
    background: var(--color-primary);
    color: var(--color-text) !important;
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--font-size-lg);
    font-weight: 700;
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.2s;
    box-shadow: var(--shadow-md);
    text-decoration: none;
    margin-top: 1.5rem;
}

.maintenance-btn:hover {
    background: var(--color-primary-dark, #d35400);
    transform: translateY(-2px) scale(1.03);
    box-shadow: var(--shadow-lg);
    color: #fff;
}

.maintenance-btn i {
    font-size: 1.3rem;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 600px) {
    .maintenance-card {
        padding: 2rem 1rem;
        border-radius: var(--radius-md);
    }

    .maintenance-title {
        font-size: var(--font-size-base);
    }

    .maintenance-btn {
        padding: 0.8rem 1.2rem;
        font-size: var(--font-size-base);
    }
}

.maintenance-title {
    font-size: 1.3rem;
}

.maintenance-btn {
    padding: 0.8rem 1.2rem;
    font-size: 1rem;
}
</style>

<section class="maintenance-container">
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i data-lucide="hard-hat"></i>
        </div>
        <h1 class="maintenance-title">Estamos melhorando esta página!</h1>
        <p class="maintenance-text">
            A área de <strong>Agendamentos</strong> está passando por melhorias para oferecer uma experiência ainda
            melhor para você.<br>
            <span style="color:#6366f1;font-weight:600">Fique tranquilo, seus dados estão seguros.</span>
        </p>
        <div class="maintenance-hint">
            <i data-lucide="lightbulb"></i>
            Enquanto isso, você pode gerenciar seus lançamentos futuros diretamente pela página de
            <strong>Lançamentos</strong>.
        </div>
        <a href="<?= BASE_URL ?>lancamentos" class="maintenance-btn">
            <i data-lucide="arrow-right"></i>
            Ir para Lançamentos
        </a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
