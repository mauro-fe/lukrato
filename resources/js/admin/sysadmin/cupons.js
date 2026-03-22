/**
 * ============================================================================
 * LUKRATO - SysAdmin Cupons Page (Vite Module)
 * ============================================================================
 * Coupon CRUD, statistics, mobile details, eligibility toggles.
 * ============================================================================
 */

import { apiDelete, apiGet, apiPost, getBaseUrl, getErrorMessage } from '../shared/api.js';
import { escapeHtml } from '../shared/utils.js';

const BASE_URL = getBaseUrl();

let cupons = [];

document.addEventListener('DOMContentLoaded', () => {
    carregarCupons();
});

async function carregarCupons() {
    try {
        const data = await apiGet(`${BASE_URL}api/cupons`);

        if (data.success) {
            cupons = data.data.cupons;
            renderizarCupons();
        } else {
            throw new Error(data.message || 'Erro ao carregar cupons');
        }
    } catch (error) {
        console.error('Erro ao carregar cupons:', error);
        LKFeedback.error(getErrorMessage(error, 'Erro ao carregar cupons.'));
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

    const cuponsAtivos = cupons.filter((c) => c.is_valid).length;
    const totalUsos = cupons.reduce((sum, c) => sum + c.uso_atual, 0);

    document.getElementById('statTotalCupons').textContent = cupons.length;
    document.getElementById('statCuponsAtivos').textContent = cuponsAtivos;
    document.getElementById('statTotalUsos').textContent = totalUsos;
    statsDiv.style.display = 'grid';

    table.style.display = 'table';
    emptyState.style.display = 'none';

    tbody.innerHTML = cupons.map((cupom) => {
        const statusBadge = cupom.is_valid
            ? '<span class="badge badge-ativo"><i data-lucide="circle-check"></i> Valido</span>'
            : '<span class="badge badge-inativo"><i data-lucide="x-circle"></i> Invalido</span>';

        const tipoBadge = cupom.tipo_desconto === 'percentual'
            ? '<span class="badge badge-percentual"><i data-lucide="percent"></i> Percentual</span>'
            : '<span class="badge badge-fixo"><i data-lucide="dollar-sign"></i> Fixo</span>';

        let usoBadge = '';
        if (cupom.limite_uso > 0) {
            const percentual = (cupom.uso_atual / cupom.limite_uso) * 100;
            const classe = percentual >= 80 ? 'esgotado' : (percentual >= 50 ? 'limitado' : '');
            usoBadge = `<span class="uso-badge ${classe}"><i data-lucide="pie-chart"></i> ${cupom.uso_atual}/${cupom.limite_uso}</span>`;
        } else {
            usoBadge = `<span class="uso-badge"><i data-lucide="infinity"></i> ${cupom.uso_atual} usos</span>`;
        }

        return `
            <tr>
                <td><span class="cupom-codigo">${escapeHtml(cupom.codigo)}</span></td>
                <td><span class="desconto-valor">${escapeHtml(cupom.desconto_formatado)}</span></td>
                <td>${tipoBadge}</td>
                <td><i data-lucide="calendar-days" style="margin-right: 0.375rem; opacity: 0.5;"></i>${escapeHtml(cupom.valido_ate)}</td>
                <td>${usoBadge}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-action btn-detalhes-mobile" data-action="verDetalhesMobile" data-cupom-id="${cupom.id}" title="Ver detalhes">
                        <i data-lucide="eye"></i>
                    </button>
                    <button class="btn-action btn-ver" data-action="verEstatisticas" data-cupom-id="${cupom.id}" title="Ver estatisticas">
                        <i data-lucide="bar-chart-3"></i> Ver
                    </button>
                    <button class="btn-action btn-excluir" data-action="excluirCupom" data-cupom-id="${cupom.id}" data-cupom-codigo="${escapeHtml(cupom.codigo)}" title="Excluir">
                        <i data-lucide="trash-2"></i> Excluir
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function abrirModalCriarCupom() {
    document.getElementById('modalCupom').classList.add('show');
    document.getElementById('formCupom').reset();
    document.getElementById('hora_valido_ate').value = '23:59';
    document.getElementById('apenas_primeira_assinatura').checked = true;
    document.getElementById('permite_reativacao').checked = false;
    document.getElementById('meses_inatividade_reativacao').value = '3';
    document.getElementById('reativacaoGroup').style.display = 'block';
    document.getElementById('mesesInatividadeGroup').style.display = 'none';
    document.getElementById('modalTitle').textContent = 'Criar Novo Cupom';
}

function fecharModalCupom() {
    document.getElementById('modalCupom').classList.remove('show');
}

function toggleReativacao() {
    const apenasPrimeira = document.getElementById('apenas_primeira_assinatura').checked;
    const reativacaoGroup = document.getElementById('reativacaoGroup');

    reativacaoGroup.style.display = apenasPrimeira ? 'block' : 'none';

    if (!apenasPrimeira) {
        document.getElementById('permite_reativacao').checked = false;
        toggleMesesInatividade();
    }
}

function toggleMesesInatividade() {
    const permiteReativacao = document.getElementById('permite_reativacao').checked;
    const mesesGroup = document.getElementById('mesesInatividadeGroup');

    mesesGroup.style.display = permiteReativacao ? 'block' : 'none';
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

    data.valor_desconto = parseFloat(data.valor_desconto);
    data.limite_uso = parseInt(data.limite_uso, 10) || 0;
    data.ativo = true;
    data.apenas_primeira_assinatura = document.getElementById('apenas_primeira_assinatura').checked;
    data.permite_reativacao = document.getElementById('permite_reativacao').checked;
    data.meses_inatividade_reativacao = parseInt(document.getElementById('meses_inatividade_reativacao').value, 10) || 3;

    try {
        const result = await apiPost(`${BASE_URL}api/cupons`, data);

        if (result.success) {
            LKFeedback.success(result.message, { toast: true });
            fecharModalCupom();
            carregarCupons();
        } else {
            throw new Error(result.message || 'Erro ao criar cupom');
        }
    } catch (error) {
        console.error('Erro ao criar cupom:', error);
        LKFeedback.error(getErrorMessage(error, 'Erro ao criar cupom.'));
    }
});

async function excluirCupom(id, codigo) {
    const result = await LKFeedback.confirm(`Deseja realmente excluir o cupom "${codigo}"?`, {
        title: 'Confirmar exclusao?',
        icon: 'warning',
        isDanger: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const data = await apiDelete(`${BASE_URL}api/cupons`, { id });

        if (data.success) {
            LKFeedback.success(data.message, { toast: true });
            carregarCupons();
        } else {
            throw new Error(data.message || 'Erro ao excluir cupom');
        }
    } catch (error) {
        console.error('Erro ao excluir cupom:', error);
        LKFeedback.error(getErrorMessage(error, 'Erro ao excluir cupom.'));
    }
}

async function verEstatisticas(cupomId) {
    try {
        const data = await apiGet(`${BASE_URL}api/cupons/estatisticas`, { id: cupomId });

        if (!data.success) {
            throw new Error(data.message || 'Erro ao carregar estatisticas');
        }

        const { cupom, estatisticas, usos } = data.data;

        let usosHtml = '';
        if (usos.length > 0) {
            usosHtml = '<div style="max-height: 300px; overflow-y: auto; margin-top: 1rem;"><table style="width: 100%; font-size: 0.9rem;"><thead><tr><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Usuario</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Desconto</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Data</th></tr></thead><tbody>';
            usos.forEach((uso) => {
                usosHtml += `<tr><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${escapeHtml(uso.usuario)}<br><small>${escapeHtml(uso.email)}</small></td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${escapeHtml(uso.desconto_aplicado)}</td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${escapeHtml(uso.usado_em)}</td></tr>`;
            });
            usosHtml += '</tbody></table></div>';
        } else {
            usosHtml = '<p style="text-align: center; color: #999; margin-top: 1rem;">Nenhum uso registrado ainda</p>';
        }

        Swal.fire({
            title: `Estatisticas: ${escapeHtml(cupom.codigo)}`,
            html: `
                <div style="text-align: left;">
                    <p><strong>Desconto:</strong> ${escapeHtml(cupom.desconto_formatado)}</p>
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
    } catch (error) {
        LKFeedback.error(getErrorMessage(error, 'Erro ao carregar estatisticas do cupom.'));
    }
}

function verDetalhesMobile(cupomId) {
    const cupom = cupons.find((c) => c.id === cupomId);
    if (!cupom) return;

    const statusText = cupom.is_valid ? 'Valido' : 'Invalido';
    const tipoText = cupom.tipo_desconto === 'percentual' ? 'Percentual' : 'Valor Fixo';
    const usoText = cupom.limite_uso > 0
        ? `${cupom.uso_atual} de ${cupom.limite_uso} usos`
        : `${cupom.uso_atual} usos (ilimitado)`;

    Swal.fire({
        title: escapeHtml(cupom.codigo),
        html: `
            <div style="text-align: left; padding: 1rem;">
                <div style="margin-bottom: 1rem; padding: 0.75rem; background: rgba(230, 126, 34, 0.1); border-radius: 8px;">
                    <strong style="color: var(--color-primary);">Desconto:</strong><br>
                    <span style="font-size: 1.5rem; font-weight: bold; color: var(--color-primary);">${escapeHtml(cupom.desconto_formatado)}</span>
                </div>

                <div style="display: grid; gap: 0.75rem;">
                    <div>
                        <strong>Tipo:</strong><br>
                        <span>${tipoText}</span>
                    </div>

                    <div>
                        <strong>Validade:</strong><br>
                        <span>${escapeHtml(cupom.valido_ate)}</span>
                    </div>

                    <div>
                        <strong>Uso:</strong><br>
                        <span>${usoText}</span>
                    </div>

                    <div>
                        <strong>Status:</strong><br>
                        <span>${statusText}</span>
                    </div>

                    ${cupom.descricao ? `
                    <div>
                        <strong>Descricao:</strong><br>
                        <span>${escapeHtml(cupom.descricao)}</span>
                    </div>
                    ` : ''}
                </div>

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd; display: flex; gap: 0.5rem; justify-content: center;">
                    <button data-action="verEstatisticasFromSwal" data-cupom-id="${cupom.id}"
                        style="padding: 0.5rem 1rem; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i data-lucide="bar-chart-3"></i> Ver Estatisticas
                    </button>
                    <button data-action="excluirCupomFromSwal" data-cupom-id="${cupom.id}" data-cupom-codigo="${escapeHtml(cupom.codigo)}"
                        style="padding: 0.5rem 1rem; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i data-lucide="trash-2"></i> Excluir
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

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const cupomId = btn.dataset.cupomId ? parseInt(btn.dataset.cupomId, 10) : null;
    const cupomCodigo = btn.dataset.cupomCodigo || '';

    switch (action) {
        case 'abrirModalCriarCupom': abrirModalCriarCupom(); break;
        case 'fecharModalCupom': fecharModalCupom(); break;
        case 'verDetalhesMobile': verDetalhesMobile(cupomId); break;
        case 'verEstatisticas': verEstatisticas(cupomId); break;
        case 'excluirCupom': excluirCupom(cupomId, cupomCodigo); break;
        case 'verEstatisticasFromSwal': Swal.close(); verEstatisticas(cupomId); break;
        case 'excluirCupomFromSwal': Swal.close(); excluirCupom(cupomId, cupomCodigo); break;
    }
});

document.addEventListener('change', (e) => {
    const el = e.target.closest('[data-action]');
    if (!el) return;

    switch (el.dataset.action) {
        case 'atualizarPlaceholder': atualizarPlaceholder(); break;
        case 'toggleReativacao': toggleReativacao(); break;
        case 'toggleMesesInatividade': toggleMesesInatividade(); break;
    }
});
