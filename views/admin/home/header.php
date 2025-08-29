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
            <a href="<?= BASE_URL ?>lancamentos" class="nav-item <?= $active('lancamentos') ?>"
                <?= $aria('lancamentos') ?>>
                <i class="fas fa-exchange-alt"></i><span>Lançamentos</span>
            </a>
            <a href="<?= BASE_URL ?>relatorios" class="nav-item <?= $active('relatorios') ?>" aria-label="Relatórios"
                <?= $aria('relatorios') ?>>
                <i class="fas fa-chart-bar"></i><span>Relatórios</span>
            </a>
            <a href="<?= $base ?>admin/<?= $u ?>/config" class="nav-item <?= $active('config') ?>"
                <?= $aria('config') ?>>
                <i class="fas fa-cog"></i><span>Config</span>
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

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">

        <div class="container-fluid py-4 lk-page">
            <div class="lk-modal" id="modalReceita" role="dialog" aria-labelledby="modalReceitaTitle"
                aria-hidden="true">
                <div class="lk-modal-backdrop"></div>
                <div class="lk-modal-content">
                    <div class="lk-modal-header">
                        <h2 id="modalReceitaTitle">Nova Receita</h2>
                        <button class="lk-modal-close" aria-label="Fechar modal"><i class="fas fa-times"></i></button>
                    </div>
                    <form class="lk-modal-body" id="formReceita">
                        <div class="form-group">
                            <label for="receitaData">Data</label>
                            <input type="date" id="receitaData" class="form-input" data-field="data" required>
                        </div>
                        <div class="form-group">
                            <label for="receitaCategoria">Categoria</label>
                            <select id="receitaCategoria" class="form-select" data-field="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="receitaContaSelect">Conta</label>
                            <select id="receitaContaSelect" class="form-select">
                                <option value="">Selecione uma conta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="receitaDescricao">Descrição</label>
                            <input type="text" id="receitaDescricao" class="form-input" data-field="descricao"
                                placeholder="Descrição da receita" required>
                        </div>
                        <div class="form-group">
                            <label for="receitaObservacao">Observação (opcional)</label>
                            <input type="text" id="receitaObservacao" class="form-input" data-field="observacao"
                                placeholder="Detalhe, nota interna...">
                        </div>
                        <div class="form-group">
                            <label for="receitaValor">Valor</label>
                            <input type="text" id="receitaValor" class="form-input money-mask" data-field="valor"
                                placeholder="R$ 0,00" required>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="receitaPago"><span class="checkbox-custom"></span>Foi
                                recebido?
                            </label>
                        </div>
                    </form>
                    <div class="lk-modal-footer">
                        <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formReceita" class="btn btn-primary">Salvar Receita</button>
                    </div>
                </div>
            </div>

            <div class="lk-modal" id="modalDespesa" role="dialog" aria-labelledby="modalDespesaTitle"
                aria-hidden="true">
                <div class="lk-modal-backdrop"></div>
                <div class="lk-modal-content">
                    <div class="lk-modal-header">
                        <h2 id="modalDespesaTitle">Nova Despesa</h2>
                        <button class="lk-modal-close" aria-label="Fechar modal"><i class="fas fa-times"></i></button>
                    </div>
                    <form class="lk-modal-body" id="formDespesa">
                        <div class="form-group">
                            <label for="despesaData">Data</label>
                            <input type="date" id="despesaData" class="form-input" data-field="data" required>
                        </div>
                        <div class="form-group">
                            <label for="despesaCategoria">Categoria</label>
                            <select id="despesaCategoria" class="form-select" data-field="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="despesaContaSelect">Conta</label>
                            <select id="despesaContaSelect" class="form-select">
                                <option value="">Selecione uma conta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="despesaDescricao">Descrição</label>
                            <input type="text" id="despesaDescricao" class="form-input" data-field="descricao"
                                placeholder="Descrição da despesa">
                        </div>
                        <div class="form-group">
                            <label for="despesaObservacao">Observação (opcional)</label>
                            <input type="text" id="despesaObservacao" class="form-input" data-field="observacao"
                                placeholder="Detalhe, nota interna...">
                        </div>
                        <div class="form-group">
                            <label for="despesaValor">Valor</label>
                            <input type="text" id="despesaValor" class="form-input money-mask" data-field="valor"
                                placeholder="R$ 0,00" required>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="despesaPago"><span class="checkbox-custom"></span>Foi pago?
                            </label>
                        </div>
                    </form>
                    <div class="lk-modal-footer">
                        <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formDespesa" class="btn btn-primary">Salvar Despesa</button>
                    </div>
                </div>
            </div>

            <div class="lk-modal" id="modalDespesaCartao" role="dialog" aria-labelledby="modalDespesaCartaoTitle"
                aria-hidden="true">
                <div class="lk-modal-backdrop"></div>
                <div class="lk-modal-content">
                    <div class="lk-modal-header">
                        <h2 id="modalDespesaCartaoTitle">Nova Despesa no Cartão</h2>
                        <button class="lk-modal-close" aria-label="Fechar modal"><i class="fas fa-times"></i></button>
                    </div>
                    <form class="lk-modal-body" id="formDespesaCartao">
                        <div class="form-group">
                            <label for="despesaCartaoData">Data da Compra</label>
                            <input type="date" id="despesaCartaoData" class="form-input" data-field="data" required>
                        </div>
                        <div class="form-group">
                            <label for="despesaCartaoCartao">Cartão</label>
                            <select id="despesaCartaoCartao" class="form-select">
                                <option value="">Selecione um cartão</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="despesaCartaoCategoria">Categoria</label>
                            <select id="despesaCartaoCategoria" class="form-select" data-field="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="despesaCartaoDescricao">Descrição</label>
                            <input type="text" id="despesaCartaoDescricao" class="form-input" data-field="descricao"
                                placeholder="Descrição da compra">
                        </div>
                        <div class="form-group">
                            <label for="despesaCartaoValor">Valor</label>
                            <input type="text" id="despesaCartaoValor" class="form-input money-mask" data-field="valor"
                                placeholder="R$ 0,00" required>
                        </div>
                        <div class="form-group">
                            <label for="despesaCartaoParcelas">Parcelas</label>
                            <select id="despesaCartaoParcelas" class="form-select">
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
                    </form>
                    <div class="lk-modal-footer">
                        <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formDespesaCartao" class="btn btn-primary">Salvar Despesa</button>
                    </div>
                </div>
            </div>

            <div class="lk-modal" id="modalTransferencia" role="dialog" aria-labelledby="modalTransferenciaTitle"
                aria-hidden="true">
                <div class="lk-modal-backdrop"></div>
                <div class="lk-modal-content">
                    <div class="lk-modal-header">
                        <h2 id="modalTransferenciaTitle">Nova Transferência</h2>
                        <button class="lk-modal-close" aria-label="Fechar modal"><i class="fas fa-times"></i></button>
                    </div>
                    <form class="lk-modal-body" id="formTransferencia">
                        <div class="form-group">
                            <label for="transferenciaData">Data</label>
                            <input type="date" id="transferenciaData" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="transferenciaOrigem">Conta de Origem</label>
                            <select id="transferenciaOrigem" class="form-select" required>
                                <option value="">Selecione a conta de origem</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transferenciaDestino">Conta de Destino</label>
                            <select id="transferenciaDestino" class="form-select" required>
                                <option value="">Selecione a conta de destino</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transferenciaValor">Valor</label>
                            <input type="text" id="transferenciaValor" class="form-input money-mask"
                                placeholder="R$ 0,00" required>
                        </div>
                        <div class="form-group">
                            <label for="transferenciaObservacao">Observação</label>
                            <input type="text" id="transferenciaObservacao" class="form-input"
                                placeholder="Observação (opcional)">
                        </div>
                    </form>
                    <div class="lk-modal-footer">
                        <button type="button" class="btn btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formTransferencia" class="btn btn-primary">Fazer
                            Transferência</button>
                    </div>
                </div>
            </div>

            <div class="lk-modal" id="monthPickerModal" aria-hidden="true" role="dialog"
                aria-labelledby="monthPickerTitle">
                <div class="lk-modal-backdrop" data-close-month></div>
                <div class="lk-modal-content" role="document">
                    <div class="lk-modal-header" style="gap:12px;">
                        <h2 id="monthPickerTitle" style="margin-right:auto;">Escolher data</h2>
                        <div class="month-picker-nav" aria-live="polite">
                            <button class="month-nav-btn" id="mpPrev" aria-label="Mês anterior"><i
                                    class="fas fa-chevron-left"></i></button>
                            <span id="mpLabel" class="mp-label"
                                style="min-width:170px;text-align:center;font-weight:700;"></span>
                            <button class="month-nav-btn" id="mpNext" aria-label="Próximo mês"><i
                                    class="fas fa-chevron-right"></i></button>
                        </div>
                        <button class="lk-modal-close" data-close-month aria-label="Fechar"><i
                                class="fas fa-times"></i></button>
                    </div>
                    <div class="lk-modal-body">
                        <div class="calendar">
                            <div class="calendar-weekdays">
                                <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                            </div>
                            <div class="calendar-grid" id="calendarGrid"></div>
                        </div>
                    </div>
                    <div class="lk-modal-footer">
                        <button class="btn btn-ghost" data-close-month>Cancelar</button>
                        <button class="btn btn-primary" id="mpConfirm">Usar mês</button>
                    </div>
                </div>
            </div>

            <?php loadPageJs('admin-home-header'); ?>

            <?php loadPageJs(); ?>
        </div>
        <script>
            (function fixBaseUrl() {
                const meta = document.querySelector('meta[name="base-url"]')?.content || '';
                let base = meta;

                if (!base) {
                    const m = location.pathname.match(/^(.*\/public\/)/);
                    base = m ? (location.origin + m[1]) : (location.origin + '/');
                }
                if (base && !/\/public\/?$/.test(base)) {
                    const m2 = location.pathname.match(/^(.*\/public\/)/);
                    if (m2) base = location.origin + m2[1];
                }
                window.BASE_URL = base.replace(/\/?$/, '/');
                console.log('[Lukrato] BASE_URL =', window.BASE_URL);
            })();
        </script>