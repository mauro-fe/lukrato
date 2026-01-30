<?php
// Incluir o header padrão do admin (com sidebar e top-navbar)
require_once __DIR__ . '/../admin/partials/header.php';
?>

<main class="main-content">
    <div class="cupons-container">
        <!-- Botão Voltar -->
        <a href="<?= BASE_URL ?>sysadmin" class="btn-voltar">
            <i class="fas fa-arrow-left"></i>
            <span>Voltar ao Painel</span>
        </a>

        <!-- Header -->
        <div class="cupons-header">
            <div class="cupons-header-title">
                <div class="cupons-header-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <h1>Gerenciar Cupons de Desconto</h1>
                    <p>Crie e gerencie cupons promocionais para seus clientes</p>
                </div>
            </div>
            <button class="btn-criar-cupom" onclick="abrirModalCriarCupom()">
                <i class="fas fa-plus-circle"></i>
                Criar Novo Cupom
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="cupons-stats" id="cuponsStats" style="display: none;">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalCupons">0</h3>
                    <p>Total de Cupons</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statCuponsAtivos">0</h3>
                    <p>Cupons Ativos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3 id="statTotalUsos">0</h3>
                    <p>Total de Usos</p>
                </div>
            </div>
        </div>

        <!-- Tabela de Cupons -->
        <div class="cupons-table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Lista de Cupons</h2>
            </div>
            <div id="loading" class="loading">
                <i class="fas fa-spinner fa-spin"></i>
                Carregando cupons...
            </div>
            <table class="cupons-table" id="cuponsTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Desconto</th>
                        <th>Tipo</th>
                        <th>Validade</th>
                        <th>Uso</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="cuponsTableBody">
                    <!-- Preenchido via JavaScript -->
                </tbody>
            </table>
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-ticket-alt"></i>
                <h3>Nenhum cupom cadastrado</h3>
                <p>Crie seu primeiro cupom de desconto para começar</p>
            </div>
        </div>
    </div>
</main>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cupons.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<div id="modalCupom" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-plus-circle"></i>
                <span id="modalTitle">Criar Novo Cupom</span>
            </h2>
            <button class="btn-close" onclick="fecharModalCupom()">×</button>
        </div>
        <form id="formCupom">
            <div class="modal-body">
                <div class="form-group">
                    <label for="codigo">Código do Cupom *</label>
                    <input type="text" id="codigo" name="codigo" required placeholder="Ex: PROMO10, BLACKFRIDAY"
                        style="text-transform: uppercase;">
                    <small>Use apenas letras e números, sem espaços</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_desconto">Tipo de Desconto *</label>
                        <select id="tipo_desconto" name="tipo_desconto" required onchange="atualizarPlaceholder()">
                            <option value="percentual">Percentual (%)</option>
                            <option value="fixo">Valor Fixo (R$)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="valor_desconto">Valor do Desconto *</label>
                        <input type="number" id="valor_desconto" name="valor_desconto" required min="0" step="0.01"
                            placeholder="10">
                        <small id="descontoHelp">Desconto em percentual (0-100)</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="valido_ate">Válido Até</label>
                        <input type="date" id="valido_ate" name="valido_ate">
                        <small>Deixe em branco para sem limite</small>
                    </div>

                    <div class="form-group">
                        <label for="limite_uso">Limite de Usos</label>
                        <input type="number" id="limite_uso" name="limite_uso" min="0" value="0"
                            placeholder="0 = Ilimitado">
                        <small>0 = Usos ilimitados</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição (opcional)</label>
                    <textarea id="descricao" name="descricao" placeholder="Ex: Promoção de lançamento"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModalCupom()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Salvar Cupom
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const BASE_URL = '<?= BASE_URL ?>';

    // Função para obter o CSRF Token
    function getCsrfToken() {
        // Tentar do CSRFManager global primeiro
        if (window.CSRFManager) {
            if (typeof window.CSRFManager.getToken === 'function') {
                const token = window.CSRFManager.getToken();
                if (token) return token;
            }
            if (window.CSRFManager.token) {
                return window.CSRFManager.token;
            }
        }

        // Tentar pegar do meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (metaToken) return metaToken;

        // Tentar pegar da sessão
        const sessionToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
        if (sessionToken) return sessionToken;

        console.warn('CSRF Token não encontrado!');
        return '';
    }

    let cupons = [];

    // Carregar cupons ao iniciar
    document.addEventListener('DOMContentLoaded', () => {
        carregarCupons();
    });

    async function carregarCupons() {
        try {
            const response = await fetch(`${BASE_URL}api/cupons`, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                credentials: 'include'
            });

            const data = await response.json();

            if (data.status === 'success') {
                cupons = data.data.cupons;
                renderizarCupons();
            } else {
                throw new Error(data.message || 'Erro ao carregar cupons');
            }
        } catch (error) {
            console.error('Erro ao carregar cupons:', error);
            Swal.fire('Erro', error.message, 'error');
        } finally {
            document.getElementById('loading').style.display = 'none';
        }
    }

    function renderizarCupons() {
        const tbody = document.getElementById('cuponsTableBody');
        const table = document.getElementById('cuponsTable');
        const emptyState = document.getElementById('emptyState');
        const statsDiv = document.getElementById('cuponsStats');

        if (cupons.length === 0) {
            table.style.display = 'none';
            emptyState.style.display = 'block';
            statsDiv.style.display = 'none';
            return;
        }

        // Atualizar estatísticas
        const cuponsAtivos = cupons.filter(c => c.is_valid).length;
        const totalUsos = cupons.reduce((sum, c) => sum + c.uso_atual, 0);

        document.getElementById('statTotalCupons').textContent = cupons.length;
        document.getElementById('statCuponsAtivos').textContent = cuponsAtivos;
        document.getElementById('statTotalUsos').textContent = totalUsos;
        statsDiv.style.display = 'grid';

        table.style.display = 'table';
        emptyState.style.display = 'none';

        tbody.innerHTML = cupons.map(cupom => {
            const statusBadge = cupom.is_valid ?
                '<span class="badge badge-ativo"><i class="fas fa-check-circle"></i> Válido</span>' :
                '<span class="badge badge-inativo"><i class="fas fa-times-circle"></i> Inválido</span>';

            const tipoBadge = cupom.tipo_desconto === 'percentual' ?
                '<span class="badge badge-percentual"><i class="fas fa-percent"></i> Percentual</span>' :
                '<span class="badge badge-fixo"><i class="fas fa-dollar-sign"></i> Fixo</span>';

            let usoBadge = '';
            if (cupom.limite_uso > 0) {
                const percentual = (cupom.uso_atual / cupom.limite_uso) * 100;
                const classe = percentual >= 80 ? 'esgotado' : (percentual >= 50 ? 'limitado' : '');
                usoBadge =
                    `<span class="uso-badge ${classe}"><i class="fas fa-chart-pie"></i> ${cupom.uso_atual}/${cupom.limite_uso}</span>`;
            } else {
                usoBadge =
                    `<span class="uso-badge"><i class="fas fa-infinity"></i> ${cupom.uso_atual} usos</span>`;
            }

            return `
                    <tr>
                        <td><span class="cupom-codigo">${cupom.codigo}</span></td>
                        <td><span class="desconto-valor">${cupom.desconto_formatado}</span></td>
                        <td>${tipoBadge}</td>
                        <td><i class="fas fa-calendar-alt" style="margin-right: 0.375rem; opacity: 0.5;"></i>${cupom.valido_ate}</td>
                        <td>${usoBadge}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn-action btn-detalhes-mobile" onclick="verDetalhesMobile(${cupom.id})" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-ver" onclick="verEstatisticas(${cupom.id})" title="Ver estatísticas">
                                <i class="fas fa-chart-bar"></i> Ver
                            </button>
                            <button class="btn-action btn-excluir" onclick="excluirCupom(${cupom.id}, '${cupom.codigo}')" title="Excluir">
                                <i class="fas fa-trash-alt"></i> Excluir
                            </button>
                        </td>
                    </tr>
                `;
        }).join('');
    }

    function abrirModalCriarCupom() {
        document.getElementById('modalCupom').classList.add('show');
        document.getElementById('formCupom').reset();
        document.getElementById('modalTitle').textContent = 'Criar Novo Cupom';
    }

    function fecharModalCupom() {
        document.getElementById('modalCupom').classList.remove('show');
    }

    function atualizarPlaceholder() {
        const tipo = document.getElementById('tipo_desconto').value;
        const help = document.getElementById('descontoHelp');

        if (tipo === 'percentual') {
            help.textContent = 'Desconto em percentual (0-100)';
            document.getElementById('valor_desconto').placeholder = '10';
        } else {
            help.textContent = 'Valor fixo em reais';
            document.getElementById('valor_desconto').placeholder = '19.90';
        }
    }

    document.getElementById('formCupom').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        // Converter valores
        data.valor_desconto = parseFloat(data.valor_desconto);
        data.limite_uso = parseInt(data.limite_uso) || 0;
        data.ativo = true;

        console.log('Dados do cupom:', data);
        console.log('CSRF Token:', getCsrfToken());

        try {
            const response = await fetch(`${BASE_URL}api/cupons`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                credentials: 'include',
                body: JSON.stringify(data)
            });

            const result = await response.json();
            console.log('Resposta do servidor:', result);

            if (result.status === 'success') {
                Swal.fire('Sucesso!', result.message, 'success');
                fecharModalCupom();
                carregarCupons();
            } else {
                throw new Error(result.message || 'Erro ao criar cupom');
            }
        } catch (error) {
            console.error('Erro:', error);
            Swal.fire('Erro', error.message, 'error');
        }
    });

    async function excluirCupom(id, codigo) {
        const result = await Swal.fire({
            title: 'Confirmar exclusão?',
            text: `Deseja realmente excluir o cupom "${codigo}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const csrfToken = getCsrfToken();
            console.log('🗑️ Excluindo cupom:', id, codigo);
            console.log('🔑 CSRF Token:', csrfToken);

            const response = await fetch(`${BASE_URL}api/cupons`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                credentials: 'include',
                body: JSON.stringify({
                    id: id
                })
            });

            console.log('📡 Status da resposta:', response.status);

            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }

            const data = await response.json();
            console.log('📦 Resposta:', data);

            if (data.status === 'success') {
                Swal.fire('Excluído!', data.message, 'success');
                carregarCupons();
            } else {
                throw new Error(data.message || 'Erro ao excluir cupom');
            }
        } catch (error) {
            console.error('❌ Erro ao excluir:', error);
            Swal.fire('Erro', error.message || 'Erro ao excluir cupom', 'error');
        }
    }

    async function verEstatisticas(cupomId) {
        try {
            const response = await fetch(`${BASE_URL}api/cupons/estatisticas?id=${cupomId}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                credentials: 'include'
            });

            const data = await response.json();

            if (data.status === 'success') {
                const {
                    cupom,
                    estatisticas,
                    usos
                } = data.data;

                let usosHtml = '';
                if (usos.length > 0) {
                    usosHtml =
                        '<div style="max-height: 300px; overflow-y: auto; margin-top: 1rem;"><table style="width: 100%; font-size: 0.9rem;"><thead><tr><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Usuário</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Desconto</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Data</th></tr></thead><tbody>';
                    usos.forEach(uso => {
                        usosHtml +=
                            `<tr><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${uso.usuario}<br><small>${uso.email}</small></td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${uso.desconto_aplicado}</td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${uso.usado_em}</td></tr>`;
                    });
                    usosHtml += '</tbody></table></div>';
                } else {
                    usosHtml =
                        '<p style="text-align: center; color: #999; margin-top: 1rem;">Nenhum uso registrado ainda</p>';
                }

                Swal.fire({
                    title: `Estatísticas: ${cupom.codigo}`,
                    html: `
                            <div style="text-align: left;">
                                <p><strong>Desconto:</strong> ${cupom.desconto_formatado}</p>
                                <p><strong>Usos:</strong> ${cupom.uso_atual} ${cupom.limite_uso > 0 ? '/ ' + cupom.limite_uso : '(ilimitado)'}</p>
                                <hr style="margin: 1rem 0;">
                                <p><strong>Total de Desconto Concedido:</strong> R$ ${estatisticas.total_desconto}</p>
                                <p><strong>Valor Total Original:</strong> R$ ${estatisticas.total_valor_original}</p>
                                ${usosHtml}
                            </div>
                        `,
                    width: 700,
                    confirmButtonText: 'Fechar'
                });
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            Swal.fire('Erro', error.message, 'error');
        }
    }

    function verDetalhesMobile(cupomId) {
        const cupom = cupons.find(c => c.id === cupomId);
        if (!cupom) return;

        const statusText = cupom.is_valid ? '✅ Válido' : '❌ Inválido';
        const tipoText = cupom.tipo_desconto === 'percentual' ? '📊 Percentual' : '💵 Valor Fixo';
        const usoText = cupom.limite_uso > 0 ?
            `${cupom.uso_atual} de ${cupom.limite_uso} usos` :
            `${cupom.uso_atual} usos (ilimitado)`;

        Swal.fire({
            title: cupom.codigo,
            html: `
            <div style="text-align: left; padding: 1rem;">
                <div style="margin-bottom: 1rem; padding: 0.75rem; background: rgba(230, 126, 34, 0.1); border-radius: 8px;">
                    <strong style="color: var(--color-primary);">💰 Desconto:</strong><br>
                    <span style="font-size: 1.5rem; font-weight: bold; color: var(--color-primary);">${cupom.desconto_formatado}</span>
                </div>
                
                <div style="display: grid; gap: 0.75rem;">
                    <div>
                        <strong>📋 Tipo:</strong><br>
                        <span>${tipoText}</span>
                    </div>
                    
                    <div>
                        <strong>📅 Validade:</strong><br>
                        <span>${cupom.valido_ate}</span>
                    </div>
                    
                    <div>
                        <strong>📊 Uso:</strong><br>
                        <span>${usoText}</span>
                    </div>
                    
                    <div>
                        <strong>✅ Status:</strong><br>
                        <span>${statusText}</span>
                    </div>
                    
                    ${cupom.descricao ? `
                    <div>
                        <strong>📝 Descrição:</strong><br>
                        <span>${cupom.descricao}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd; display: flex; gap: 0.5rem; justify-content: center;">
                    <button onclick="verEstatisticas(${cupom.id}); Swal.close();" 
                        style="padding: 0.5rem 1rem; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-chart-bar"></i> Ver Estatísticas
                    </button>
                    <button onclick="excluirCupom(${cupom.id}, '${cupom.codigo}'); Swal.close();" 
                        style="padding: 0.5rem 1rem; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-trash-alt"></i> Excluir
                    </button>
                </div>
            </div>
        `,
            width: 400,
            showConfirmButton: true,
            confirmButtonText: 'Fechar',
            confirmButtonColor: '#e67e22'
        });
    }
</script>