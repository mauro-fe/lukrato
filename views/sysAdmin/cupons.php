<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cupons - Lukrato</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/sysadmin-modern.css">
    <style>
        .cupons-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
        }

        .cupons-header h1 {
            margin: 0;
            color: var(--color-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-criar-cupom {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-criar-cupom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(var(--color-primary-rgb), 0.3);
        }

        .cupons-table-container {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
            overflow: hidden;
        }

        .cupons-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cupons-table thead {
            background: var(--color-surface-muted);
        }

        .cupons-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--color-text);
            border-bottom: 2px solid var(--glass-border);
        }

        .cupons-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--glass-border);
            color: var(--color-text-muted);
        }

        .cupons-table tbody tr:hover {
            background: var(--color-surface-hover);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-ativo {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .badge-inativo {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .badge-percentual {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .badge-fixo {
            background: rgba(168, 85, 247, 0.1);
            color: #a855f7;
        }

        .btn-action {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin: 0 0.25rem;
        }

        .btn-excluir {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .btn-excluir:hover {
            background: #ef4444;
            color: white;
        }

        .btn-ver {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .btn-ver:hover {
            background: #3b82f6;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--color-surface);
            border-radius: var(--radius-xl);
            border: 2px solid var(--glass-border);
            box-shadow: var(--shadow-xl);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--color-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-close {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--color-surface-muted);
            color: var(--color-text-muted);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.25rem;
        }

        .btn-close:hover {
            background: var(--color-danger);
            color: white;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--color-text);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            background: var(--color-surface-muted);
            color: var(--color-text);
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: var(--color-text-muted);
            font-size: 0.85rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(var(--color-primary-rgb), 0.3);
        }

        .btn-secondary {
            background: var(--color-surface-muted);
            color: var(--color-text);
        }

        .btn-secondary:hover {
            background: var(--color-surface-hover);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--color-text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--color-text-muted);
        }

        .uso-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-sm);
            background: var(--color-surface-muted);
            font-size: 0.85rem;
        }

        .uso-badge.limitado {
            background: rgba(251, 191, 36, 0.1);
            color: #fbbf24;
        }

        .uso-badge.esgotado {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
    </style>
</head>

<body>
    <div class="sysadmin-container">
        <!-- Header -->
        <div class="cupons-header">
            <h1>
                <i class="fas fa-ticket-alt"></i>
                Gerenciar Cupons de Desconto
            </h1>
            <button class="btn-criar-cupom" onclick="abrirModalCriarCupom()">
                <i class="fas fa-plus"></i>
                Criar Novo Cupom
            </button>
        </div>

        <!-- Tabela de Cupons -->
        <div class="cupons-table-container">
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

    <!-- Modal Criar/Editar Cupom -->
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
                        <input type="text" id="codigo" name="codigo" required
                            placeholder="Ex: PROMO10, BLACKFRIDAY" style="text-transform: uppercase;">
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
        const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';

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
                        'X-CSRF-Token': CSRF_TOKEN
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

            if (cupons.length === 0) {
                table.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }

            table.style.display = 'table';
            emptyState.style.display = 'none';

            tbody.innerHTML = cupons.map(cupom => {
                const statusBadge = cupom.is_valid ?
                    '<span class="badge badge-ativo">Válido</span>' :
                    '<span class="badge badge-inativo">Inválido</span>';

                const tipoBadge = cupom.tipo_desconto === 'percentual' ?
                    '<span class="badge badge-percentual">Percentual</span>' :
                    '<span class="badge badge-fixo">Fixo</span>';

                let usoBadge = '';
                if (cupom.limite_uso > 0) {
                    const percentual = (cupom.uso_atual / cupom.limite_uso) * 100;
                    const classe = percentual >= 80 ? 'esgotado' : (percentual >= 50 ? 'limitado' : '');
                    usoBadge = `<span class="uso-badge ${classe}">${cupom.uso_atual}/${cupom.limite_uso}</span>`;
                } else {
                    usoBadge = `<span class="uso-badge">${cupom.uso_atual} usos</span>`;
                }

                return `
                    <tr>
                        <td><strong>${cupom.codigo}</strong></td>
                        <td><strong>${cupom.desconto_formatado}</strong></td>
                        <td>${tipoBadge}</td>
                        <td>${cupom.valido_ate}</td>
                        <td>${usoBadge}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn-action btn-ver" onclick="verEstatisticas(${cupom.id})" title="Ver estatísticas">
                                <i class="fas fa-chart-bar"></i>
                            </button>
                            <button class="btn-action btn-excluir" onclick="excluirCupom(${cupom.id}, '${cupom.codigo}')" title="Excluir">
                                <i class="fas fa-trash"></i>
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

            try {
                const response = await fetch(`${BASE_URL}api/cupons`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'include',
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Sucesso!', result.message, 'success');
                    fecharModalCupom();
                    carregarCupons();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
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
                const response = await fetch(`${BASE_URL}api/cupons`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'include',
                    body: JSON.stringify({ id })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    Swal.fire('Excluído!', data.message, 'success');
                    carregarCupons();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire('Erro', error.message, 'error');
            }
        }

        async function verEstatisticas(cupomId) {
            try {
                const response = await fetch(`${BASE_URL}api/cupons/estatisticas?id=${cupomId}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.status === 'success') {
                    const { cupom, estatisticas, usos } = data.data;

                    let usosHtml = '';
                    if (usos.length > 0) {
                        usosHtml = '<div style="max-height: 300px; overflow-y: auto; margin-top: 1rem;"><table style="width: 100%; font-size: 0.9rem;"><thead><tr><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Usuário</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Desconto</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Data</th></tr></thead><tbody>';
                        usos.forEach(uso => {
                            usosHtml += `<tr><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${uso.usuario}<br><small>${uso.email}</small></td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${uso.desconto_aplicado}</td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${uso.usado_em}</td></tr>`;
                        });
                        usosHtml += '</tbody></table></div>';
                    } else {
                        usosHtml = '<p style="text-align: center; color: #999; margin-top: 1rem;">Nenhum uso registrado ainda</p>';
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
    </script>
</body>

</html>
