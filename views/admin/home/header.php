<?php
$pageTitle = $pageTitle ?? 'Painel Administrativo';
$username = $username ?? 'usuário';
$menu = $menu ?? '';

// Fix the undefined variables
$u = 'admin'; // Assuming a placeholder for the user/admin segment
$base = BASE_URL; // Use the defined constant
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?></title>

    <meta name="base-url" content="<?= rtrim(BASE_URL, '/') . '/' ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <?php loadPageCss(); ?>
    <?php loadPageCss('admin-home-header'); ?>
</head>

<body class="g-sidenav-show bg-gray-100">
    <?php
    $active = function (string $key) use ($menu) {
        return (!empty($menu) && $menu === $key) ? 'active' : '';
    };
    $aria = function (string $key) use ($menu) {
        return (!empty($menu) && $menu === $key) ? ' aria-current="page"' : '';
    };
    ?>

    <aside class="sidebar no-glass" id="sidebar-main">
        <div class="sidebar-header">
            <a class="logo" href="<?= BASE_URL ?>/dashboard" aria-label="Ir para o Dashboard">
                <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato">
            </a>
        </div>

        <button id="sidebarToggle" class="sidebar-toggle" aria-label="Abrir/Fechar menu">
            <i class="fas fa-bars"></i>
        </button>

        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>dashboard" class="nav-item <?= $active('dashboard') ?>" <?= $aria('dashboard') ?>>
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>contas" class="nav-item <?= $active('contas') ?>" <?= $aria('contas') ?>>
                <i class="fas fa-chart-bar"></i><span>Contas</span>
            </a>
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                <?= $aria('lancamentos') ?>>
                <i class="fas fa-exchange-alt"></i><span>Lançamentos</span>
            </a>
            <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios') ?>"
                <?= $aria('relatorios') ?>>
                <i class="fas fa-chart-bar"></i><span>Relatórios</span>
            </a>
            <a href="<?= BASE_URL ?>perfil" class="nav-item <?= $active('perfil') ?>" <?= $aria('perfil') ?>>
                <i class="fas fa-user-circle"></i><span>Perfil</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/config" class="nav-item <?= $active('config') ?>"
                <?= $aria('config') ?>>
                <i class="fas fa-cog"></i><span>Config</span>
            </a>

            <!-- Botão de sair -->
            <a id="btn-logout" class="nav-item" href="<?= BASE_URL ?>logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>



        </nav>

        <div class="fab-container">
            <button class="fab" id="fabButton" aria-label="Adicionar transação" aria-haspopup="true"
                aria-expanded="false">
                <i class="fas fa-plus"></i>
            </button>
            <div class="fab-menu" id="fabMenu" role="menu">
                <button class="fab-menu-item" data-open-modal="receita" role="menuitem"><i
                        class="fas fa-arrow-up"></i><span>Receita</span></button>
                <button class="fab-menu-item" data-open-modal="despesa" role="menuitem"><i
                        class="fas fa-arrow-down"></i><span>Despesa</span></button>
                <button class="fab-menu-item" data-open-modal="despesa-cartao" role="menuitem"><i
                        class="fas fa-credit-card"></i><span>Despesa Cartão</span></button>
                <button class="fab-menu-item" data-open-modal="transferencia" role="menuitem"><i
                        class="fas fa-exchange-alt"></i><span>Transferência</span></button>
            </div>
        </div>
    </aside>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg pt-0">

        <div class="container-fluid lk-page">
            <!-- Modal Único de Lançamento -->
            <div class="lkh-modal" id="modalLancamento" role="dialog" aria-labelledby="modalLancamentoTitle"
                aria-hidden="true">
                <div class="lkh-modal-backdrop"></div>
                <div class="lkh-modal-content">
                    <div class="lkh-modal-header">
                        <h2 id="modalLancamentoTitle">Novo Lançamento</h2>
                        <button class="lkh-modal-close" aria-label="Fechar modal"><i class="fas fa-times"></i></button>
                    </div>

                    <form class="lkh-modal-body" id="formLancamento" novalidate>
                        <div class="form-group">
                            <label for="lanTipo">Tipo</label>
                            <select id="lanTipo" class="form-select" required>
                                <option value="despesa">Despesa</option>
                                <option value="receita">Receita</option>
                                <!-- Futuro: <option value="despesa_cartao" disabled>Despesa no Cartão</option>
                       <option value="transferencia" disabled>Transferência</option> -->
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="lanData">Data</label>
                            <input type="date" id="lanData" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label for="lanCategoria">Categoria</label>
                            <select id="lanCategoria" class="form-select" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>

                        <!-- Campos futuros (mantidos ocultos por enquanto) -->
                        <div class="form-group" id="grpConta" style="display:none;">
                            <label for="lanConta">Conta</label>
                            <select id="lanConta" class="form-select">
                                <option value="">Selecione uma conta</option>
                            </select>
                        </div>
                        <div class="form-group" id="grpCartao" style="display:none;">
                            <label for="lanCartao">Cartão</label>
                            <select id="lanCartao" class="form-select">
                                <option value="">Selecione um cartão</option>
                            </select>
                        </div>
                        <div class="form-group" id="grpParcelas" style="display:none;">
                            <label for="lanParcelas">Parcelas</label>
                            <select id="lanParcelas" class="form-select">
                                <option value="1">1x (à vista)</option>
                                <option value="2">2x sem juros</option>
                                <option value="3">3x sem juros</option>
                                <option value="4">4x sem juros</option>
                                <option value="5">5x sem juros</option>
                                <option value="6">6x sem juros</option>
                                <option value="7">7x sem juros</option>
                                <option value="8">8x sem juros</option>
                                <option value="9">9x sem juros</option>
                                <option value="10">10x sem juros</option>
                                <option value="11">11x sem juros</option>
                                <option value="12">12x sem juros</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="lanDescricao">Descrição</label>
                            <input type="text" id="lanDescricao" class="form-input"
                                placeholder="Descrição do lançamento" />
                        </div>

                        <div class="form-group">
                            <label for="lanObservacao">Observação (opcional)</label>
                            <input type="text" id="lanObservacao" class="form-input"
                                placeholder="Detalhe, nota interna..." />
                        </div>

                        <div class="form-group">
                            <label for="lanValor">Valor</label>
                            <input type="text" id="lanValor" class="form-input money-mask" placeholder="R$ 0,00"
                                required />
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="lanPago"><span class="checkbox-custom"></span>
                                <span id="lanPagoLabel">Foi pago?</span>
                            </label>
                        </div>
                    </form>

                    <div class="lkh-modal-footer">
                        <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formLancamento" class="btn btn-primary">Salvar</button>
                    </div>
                </div>
            </div>


            <?php loadPageJs('admin-home-header'); ?>

            <?php loadPageJs(); ?>
        </div>


        <script>
            (() => {
                const $ = (s, sc = document) => sc.querySelector(s);

                // ---- FAB: abrir/fechar menu (horizontal à direita)
                const fab = $('#fabButton');
                const menu = $('#fabMenu');
                fab?.addEventListener('click', () => {
                    const open = !menu.classList.contains('active');
                    fab.classList.toggle('active', open);
                    fab.setAttribute('aria-expanded', String(open));
                    menu.classList.toggle('active', open);
                });

                // ---- Normaliza a "key" dos itens do FAB para abrir SEMPRE o mesmo modal (#modalLancamento)
                // Ex.: receita, despesa, despesa-cartao, transferencia -> "lancamento"
                const KEY_MAP = {
                    receita: 'lancamento',
                    despesa: 'lancamento',
                    'despesa-cartao': 'lancamento',
                    transferencia: 'lancamento',
                    lancamento: 'lancamento'
                };

                function toKey(raw) {
                    const k = String(raw || '').trim().toLowerCase();
                    return KEY_MAP[k] || k;
                }

                function modalIdFromKey(key) {
                    // gera "modalLancamento" a partir de "lancamento"
                    return 'modal' + String(key || '').replace(/(^|-)(\w)/g, (_, __, b) => b.toUpperCase());
                }

                // ---- Centraliza o modal de Lançamento (remove estilos de canto, mostra backdrop)
                function centerLancModal(m) {
                    if (!m || m.id !== 'modalLancamento') return;
                    // limpa qualquer inline remanescente
                    m.removeAttribute('style');

                    const content = m.querySelector('.lkh-modal-content');
                    const backdrop = m.querySelector('.lkh-modal-backdrop');

                    if (content) content.removeAttribute('style'); // volta a obedecer ao CSS padrão (flex center)
                    if (backdrop) backdrop.style.display = ''; // backdrop visível
                }

                // ---- Abrir modal a partir de uma key vinda do FAB
                function openModalByKey(rawKey) {
                    const key = toKey(rawKey);
                    const id = modalIdFromKey(key); // "lancamento" -> "modalLancamento"
                    const m = document.getElementById(id);
                    if (!m) return;

                    if (id === 'modalLancamento') centerLancModal(m);

                    m.classList.add('active');
                    m.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';

                    if (id === 'modalLancamento') {
                        // re-centraliza no próximo frame caso algum script altere algo
                        requestAnimationFrame(() => centerLancModal(m));
                    }
                }

                // ---- Liga os itens do FAB (menu horizontal à direita)
                document.querySelectorAll('.fab-menu-item[data-open-modal]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const raw = btn.getAttribute(
                            'data-open-modal'); // receita | despesa | despesa-cartao | transferencia
                        openModalByKey(raw);
                        // fecha menu do FAB
                        menu.classList.remove('active');
                        fab.classList.remove('active');
                        fab.setAttribute('aria-expanded', 'false');
                    });
                });

                // ---- Fechar modal (X / backdrop / data-dismiss)
                document.addEventListener('click', (e) => {
                    if (
                        e.target.closest('.lkh-modal-close,[data-dismiss="modal"]') ||
                        e.target.classList.contains('lkh-modal-backdrop')
                    ) {
                        const m = e.target.closest('.lkh-modal') || document.querySelector('.lkh-modal.active');
                        if (m) {
                            m.classList.remove('active');
                            m.setAttribute('aria-hidden', 'true');
                            if (!document.querySelector('.lkh-modal.active')) document.body.style.overflow = '';
                        }
                    }
                });

                // ---- ESC fecha o modal
                document.addEventListener('keydown', (e) => {
                    if (e.key !== 'Escape') return;
                    const top = document.querySelector('.lkh-modal.active');
                    if (top) {
                        e.preventDefault();
                        top.classList.remove('active');
                        top.setAttribute('aria-hidden', 'true');
                        if (!document.querySelector('.lkh-modal.active')) document.body.style.overflow = '';
                    }
                });
            })();
        </script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.addEventListener('click', function(e) {
                    const a = e.target.closest('#btn-logout');
                    if (!a) return;

                    e.preventDefault();
                    const url = a.getAttribute('href');
                    if (!url) return;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Deseja realmente sair?',
                            text: 'Sua sessão será encerrada.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sim, sair',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#e74c3c'
                        }).then(result => {
                            if (result.isConfirmed) window.location.href = url;
                        });
                    } else {
                        if (confirm('Deseja realmente sair?')) window.location.href = url;
                    }
                });
            });
        </script>