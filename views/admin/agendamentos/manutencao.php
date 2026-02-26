<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">

<style>
.maintenance-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    padding: 2rem;
}

.maintenance-card {
    background: var(--color-surface, #fff);
    border-radius: 1.25rem;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    padding: 3rem 2.5rem;
    max-width: 520px;
    width: 100%;
    text-align: center;
    animation: fadeInUp 0.6s ease-out;
}

.maintenance-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--color-primary-light, #e8f0fe) 0%, var(--color-primary-lighter, #f0f4ff) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.maintenance-icon svg {
    width: 36px;
    height: 36px;
    color: var(--color-primary, #4f46e5);
}

.maintenance-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text, #1a1a2e);
    margin: 0 0 0.75rem;
}

.maintenance-text {
    font-size: 1rem;
    color: var(--color-text-muted, #6b7280);
    line-height: 1.6;
    margin: 0 0 1.5rem;
}

.maintenance-hint {
    font-size: 0.875rem;
    color: var(--color-text-muted, #9ca3af);
    margin: 0 0 2rem;
    padding: 0.75rem 1rem;
    background: var(--color-surface-alt, #f9fafb);
    border-radius: 0.75rem;
    border: 1px solid var(--color-border, #e5e7eb);
}

.maintenance-hint i {
    width: 16px;
    height: 16px;
    vertical-align: -2px;
    margin-right: 4px;
    color: var(--color-primary, #4f46e5);
}

.maintenance-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--color-primary, #4f46e5);
    color: #fff;
    border: none;
    border-radius: 0.75rem;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.maintenance-btn:hover {
    background: var(--color-primary-dark, #4338ca);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    color: #fff;
    text-decoration: none;
}

.maintenance-btn svg {
    width: 18px;
    height: 18px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 480px) {
    .maintenance-card {
        padding: 2rem 1.5rem;
    }
    .maintenance-title {
        font-size: 1.25rem;
    }
}
</style>

<section class="maintenance-container">
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i data-lucide="hard-hat"></i>
        </div>

        <h1 class="maintenance-title">Estamos melhorando esta página!</h1>

        <p class="maintenance-text">
            A área de <strong>Agendamentos</strong> está passando por melhorias para oferecer uma experiência ainda melhor para você.
        </p>

        <div class="maintenance-hint">
            <i data-lucide="lightbulb"></i>
            Enquanto isso, você pode gerenciar seus lançamentos futuros diretamente pela página de <strong>Lançamentos</strong>.
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
