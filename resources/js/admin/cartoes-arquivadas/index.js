/**
 * Cartoes Arquivados - Vite Module
 * Gerencia a pagina de cartoes de credito arquivados.
 */

import '../../../css/admin/cartoes-shared/index.css';
import '../../../css/admin/cartoes-arquivadas/index.css';
import { apiFetch, getBaseUrl, getErrorMessage } from '../shared/api.js';
import { escapeHtml, formatMoney } from '../shared/utils.js';
import { toastSuccess, toastError, refreshIcons } from '../shared/ui.js';

const BASE = getBaseUrl();

let rows = [];

const grid = () => document.getElementById('archivedGrid');
const totalArquivados = () => document.getElementById('totalArquivados');
const limiteTotal = () => document.getElementById('limiteTotal');

function formatMoneyBR(value) {
    return formatMoney(value).replace('R$\u00a0', '').replace('R$ ', '');
}

function getDefaultColor(bandeira) {
    const colors = {
        visa: '#1a1f71',
        mastercard: '#eb001b',
        elo: '#ffcb05',
        amex: '#006fcf',
        hipercard: '#d9001b'
    };
    return colors[String(bandeira || '').toLowerCase()] || '#e67e22';
}

function updateStats(items) {
    const totalEl = totalArquivados();
    if (totalEl) {
        totalEl.textContent = items.length;
    }

    const total = items.reduce((sum, item) => sum + parseFloat(item.limite_total || 0), 0);
    const limiteEl = limiteTotal();
    if (limiteEl) {
        limiteEl.textContent = `R$ ${formatMoneyBR(total)}`;
    }
}

function renderEmpty() {
    const el = grid();
    if (!el) {
        return;
    }

    el.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">
                <i data-lucide="credit-card" style="color: white;"></i>
            </div>
            <h3>Nenhum cartao arquivado</h3>
            <p>Voce nao possui cartoes arquivados no momento</p>
        </div>
    `;
    refreshIcons();
}

function renderCartoes(items) {
    const el = grid();
    if (!el) {
        return;
    }

    if (!items.length) {
        renderEmpty();
        return;
    }

    el.innerHTML = items.map((cartao) => {
        const nome = escapeHtml(cartao.nome_cartao || 'Sem nome');
        const bandeira = String(cartao.bandeira || 'Desconhecida').toLowerCase();
        const limite = formatMoneyBR(cartao.limite_total || 0);
        const disponivel = formatMoneyBR(cartao.limite_disponivel || 0);
        const ultimos = cartao.ultimos_digitos || '0000';
        const cor =
            cartao.conta?.instituicao_financeira?.cor_primaria ||
            cartao.instituicao_cor ||
            cartao.cor_cartao ||
            getDefaultColor(bandeira);

        const bandeirasLogos = {
            visa: `${BASE}assets/img/bandeiras/visa.png`,
            mastercard: `${BASE}assets/img/bandeiras/mastercard.png`,
            elo: `${BASE}assets/img/bandeiras/elo.png`,
            amex: `${BASE}assets/img/bandeiras/amex.png`,
            hipercard: `${BASE}assets/img/bandeiras/hipercard.png`
        };

        const logoSrc = bandeirasLogos[bandeira] || '';
        const brandHTML = logoSrc
            ? `<img src="${logoSrc}" alt="${bandeira}" class="brand-logo">`
            : '<i data-lucide="credit-card" class="brand-icon-fallback"></i>';

        return `
            <div class="credit-card surface-card surface-card--interactive surface-card--clip" data-brand="${bandeira}" data-id="${cartao.id}" style="background: ${cor}">
                <div class="card-header">
                    <div class="card-brand">
                        ${brandHTML}
                        <span class="card-name">${nome}</span>
                    </div>
                    <div class="card-actions">
                        <button class="card-action-btn" onclick="handleRestore(${cartao.id})" title="Restaurar">
                            <i data-lucide="undo-2"></i>
                        </button>
                        <button class="card-action-btn" onclick="handleHardDelete(${cartao.id}, '${nome.replace(/'/g, "\\'")}')" title="Excluir permanentemente">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
                <div class="card-number">**** **** **** ${ultimos}</div>
                <div class="card-footer">
                    <div class="card-holder">
                        <div class="card-label">Limite Disponivel</div>
                        <div class="card-value">R$ ${disponivel}</div>
                    </div>
                    <div class="card-limit">
                        <div class="card-label">Limite Total</div>
                        <div class="card-value">R$ ${limite}</div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    refreshIcons();
}

async function requestAPI(path, options = {}) {
    const url = `${BASE}api/${path}`.replace(/\/{2,}/g, '/').replace(':/', '://');

    try {
        return await apiFetch(url, {
            credentials: 'same-origin',
            ...options
        });
    } catch (error) {
        if (error?.status !== 404) {
            throw error;
        }

        const fallback = `${BASE}index.php/api/${path}`.replace(/\/{2,}/g, '/').replace(':/', '://');
        return apiFetch(fallback, {
            credentials: 'same-origin',
            ...options
        });
    }
}

async function load() {
    const el = grid();
    if (!el) {
        return;
    }

    try {
        el.setAttribute('aria-busy', 'true');
        const data = await requestAPI('cartoes?archived=1');
        rows = Array.isArray(data) ? data : (data?.data || []);
        updateStats(rows);
        renderCartoes(rows);
    } catch (error) {
        console.error(error);
        el.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon"><i data-lucide="triangle-alert"></i></div>
                <h3>Erro ao carregar</h3>
                <p>${escapeHtml(getErrorMessage(error, 'Nao foi possivel carregar os cartoes arquivados.'))}</p>
            </div>
        `;
        refreshIcons();
    } finally {
        el.setAttribute('aria-busy', 'false');
    }
}

window.handleRestore = async function handleRestore(id) {
    const cartao = rows.find((item) => item.id === id);
    const nome = cartao ? cartao.nome_cartao : 'este cartao';

    const result = await window.Swal.fire({
        title: 'Restaurar Cartao',
        html: `Deseja restaurar o cartao <strong>${nome}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="undo-2"></i> Sim, restaurar',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        confirmButtonColor: '#2ecc71',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await requestAPI(`cartoes/${id}/restore`, { method: 'POST' });
        toastSuccess('O cartao foi restaurado com sucesso.');
        await load();
    } catch (error) {
        console.error(error);
        toastError(getErrorMessage(error, 'Falha ao restaurar.'));
    }
};

window.handleHardDelete = async function handleHardDelete(id, nome = '') {
    const cartao = rows.find((item) => item.id === id);
    const nomeCartao = cartao ? cartao.nome_cartao : nome || 'este cartao';

    const result = await window.Swal.fire({
        title: 'Excluir permanentemente?',
        html: `Tem certeza que deseja excluir <strong>${nomeCartao}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta acao nao pode ser desfeita!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="trash-2"></i> Sim, excluir',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await requestAPI(`cartoes/${id}/delete`, {
            method: 'POST',
            body: { force: false }
        });

        if (response?.success === false && response?.errors?.requires_confirmation) {
            const totalLancamentos = response?.data?.total_lancamentos || 0;
            const totalFaturas = response?.data?.total_faturas || 0;
            const totalItens = response?.data?.total_itens || 0;
            const totalGeral = totalLancamentos + totalFaturas + totalItens;

            let detalhes = '';
            if (totalGeral > 0) {
                detalhes = '<ul style="text-align:left; margin-top: 1rem; margin-bottom: 1rem;">';
                if (totalLancamentos > 0) detalhes += `<li><b>${totalLancamentos}</b> lancamento(s)</li>`;
                if (totalFaturas > 0) detalhes += `<li><b>${totalFaturas}</b> fatura(s)</li>`;
                if (totalItens > 0) detalhes += `<li><b>${totalItens}</b> item(ns) de fatura</li>`;
                detalhes += '</ul>';
            } else {
                detalhes = `<p style="margin: 1rem 0; white-space: pre-line;">${response.message || 'Nenhum dado vinculado encontrado'}</p>`;
            }

            const confirm = await window.Swal.fire({
                title: 'Excluir cartao e TODOS os dados vinculados?',
                html: `<div style="text-align:left; padding: 1rem;">
                    <p style="margin-bottom: 1rem;">O cartao <b>${nomeCartao.replace(/</g, '&lt;')}</b> possui os seguintes dados vinculados:</p>
                    ${detalhes}
                    <p style="margin-top: 1rem; color: #dc3545; font-weight: 600;">Ao excluir o cartao, TODOS esses dados serao excluidos permanentemente!</p>
                    <p style="margin-top: 0.5rem;">Esta acao nao pode ser desfeita. Deseja continuar?</p>
                </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i data-lucide="trash-2"></i> Sim, excluir tudo',
                cancelButtonText: '<i data-lucide="x"></i> Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            });

            if (!confirm.isConfirmed) {
                return;
            }

            const deleteResponse = await requestAPI(`cartoes/${id}/delete`, {
                method: 'POST',
                body: { force: true }
            });

            if (!deleteResponse?.success) {
                throw new Error(deleteResponse?.message || 'Erro ao excluir');
            }

            const totalExcluido =
                (deleteResponse.data?.deleted_lancamentos || 0) +
                (deleteResponse.data?.deleted_faturas || 0) +
                (deleteResponse.data?.deleted_itens || 0);

            await window.Swal.fire({
                icon: 'success',
                title: 'Excluido!',
                html: `<p><b>${nomeCartao}</b> e todos os dados vinculados foram excluidos permanentemente.</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9em; color: #6c757d;">Total de registros excluidos: ${totalExcluido}</p>`,
                timer: 3000,
                showConfirmButton: false
            });

            await load();
            return;
        }

        if (response?.success === false) {
            throw new Error(response?.message || 'Erro ao excluir');
        }

        toastSuccess('Cartao excluido com sucesso.');
        await load();
    } catch (error) {
        console.error(error);
        toastError(getErrorMessage(error, 'Falha ao excluir.'));
    }
};

load();
