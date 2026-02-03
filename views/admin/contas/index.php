<div class="cont-page">
    <!-- ==================== ESTATÍSTICAS ==================== -->
    <div class="stats-grid pt-5" id="statsContainer" role="region" aria-label="Estatísticas das contas">
        <div class="stat-card" data-aos="flip-left">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div>
                <div class="stat-value" id="totalContas" aria-live="polite">0</div>
                <div class="stat-label">Total de Contas</div>
            </div>
        </div>

        <div class="stat-card" data-aos="flip-right">
            <div class="stat-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <div class="stat-value" id="saldoTotal" aria-live="polite">R$ 0,00</div>
                <div class="stat-label">Saldo Total</div>
            </div>
        </div>

    </div>

    <!-- ==================== LISTA DE CONTAS ==================== -->
    <div class="lk-accounts-wrap" data-aos="fade-up">
        <!-- Header com ações -->
        <div class="lk-acc-header">
            <div class="lk-acc-actions">
                <button class="btn btn-primary" id="btnNovaConta" aria-label="Criar nova conta">
                    <i class="fas fa-plus"></i> Nova Conta
                </button>

                <button class="btn btn-ghost" id="btnReload" aria-label="Recarregar contas" title="Atualizar lista">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <div class="lk-acc-right">
                <!-- View Toggle -->
                <div class="view-toggle" id="viewToggle">
                    <button class="view-btn active" data-view="grid" title="Visualização em cards">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="view-btn" data-view="list" title="Visualização em lista">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
                
                <a class="btn btn-light" href="<?= BASE_URL ?>contas/arquivadas" aria-label="Ver contas arquivadas">
                    <i class="fas fa-archive"></i> Arquivadas
                </a>
            </div>
        </div>

        <!-- Cards das contas -->
        <div class="lk-card">
            <div class="acc-grid" id="accountsGrid" aria-live="polite" aria-busy="false">
                <!-- Skeleton loader inicial -->
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
                <div class="acc-skeleton" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAIS ==================== -->
<?php include __DIR__ . '/../partials/modals/modal_contas.php'; ?>
<?php include __DIR__ . '/../partials/modals/modal_lancamento_v2.php'; ?>

<!-- ==================== SCRIPTS ==================== -->
<style>
    /* Estilos customizados para SweetAlert2 */
    .swal2-popup .swal2-actions {
        gap: 1rem;
    }

    .swal2-popup .swal-confirm-btn,
    .swal2-popup .swal2-confirm {
        background-color: #e67e22 !important;
        color: white !important;
        border: none !important;
        padding: 0.75rem 2rem !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 1rem !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3) !important;
    }

    .swal2-popup .swal-confirm-btn:hover,
    .swal2-popup .swal2-confirm:hover {
        background-color: #d35400 !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(230, 126, 34, 0.4) !important;
    }

    .swal2-popup .swal-cancel-btn,
    .swal2-popup .swal2-cancel {
        background-color: #6c757d !important;
        color: white !important;
        border: none !important;
        padding: 0.75rem 2rem !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 1rem !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3) !important;
    }

    .swal2-popup .swal-cancel-btn:hover,
    .swal2-popup .swal2-cancel:hover {
        background-color: #5a6268 !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(108, 117, 125, 0.4) !important;
    }

    .swal2-popup .swal2-styled:focus {
        box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.25) !important;
    }

    /* Garantir que ícones nos botões fiquem visíveis */
    .swal2-popup .swal2-styled i {
        color: white !important;
        margin-right: 0.5rem;
    }
</style>
<script>
    window.BASE_URL = '<?= BASE_URL ?>';

    // Desabilitar auto-inicialização do Bootstrap ANTES do Bootstrap carregar
    document.addEventListener('DOMContentLoaded', function() {
        // Desabilitar completamente a inicialização automática do Bootstrap
        if (typeof bootstrap !== 'undefined') {
            // Sobrescrever o inicializador automático do Bootstrap
            const style = document.createElement('style');
            style.textContent = '[data-bs-toggle] { pointer-events: none !important; }';
            document.head.appendChild(style);
        }
    });

    // Capturar TODOS os erros relacionados ao Bootstrap
    const originalError = console.error;
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('backdrop') || message.includes('Bootstrap') || message.includes('modal')) {
            console.warn('⚠️ Erro do Bootstrap suprimido:', ...args);
            return;
        }
        originalError.apply(console, args);
    };

    // Interceptar erros não capturados do JavaScript
    window.onerror = function(message, source, lineno, colno, error) {
        if (message && (message.includes('backdrop') || message.includes('Bootstrap'))) {
            console.warn('⚠️ Erro suprimido:', message);
            return true; // Prevenir erro
        }
        return false;
    };

    // Garantir que apenas o sistema moderno seja usado
    (function() {
        'use strict';

        // Limpar cache
        if ('caches' in window) {
            caches.keys().then(keys => keys.forEach(key => caches.delete(key)));
        }

        // Prevenir carregamento de scripts antigos
        const oldScriptPattern = /admin-contas-index/;
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.tagName === 'SCRIPT' && oldScriptPattern.test(node.src)) {
                        console.warn('🚫 Bloqueado script antigo:', node.src);
                        node.remove();
                    }
                });
            });
        });

        observer.observe(document.documentElement, {
            childList: true,
            subtree: true
        });

    })();
</script>
<script
    src="<?= BASE_URL ?>assets/js/contas-manager.js?v=<?= md5_file(__DIR__ . '/../../../public/assets/js/contas-manager.js') ?>">
</script>