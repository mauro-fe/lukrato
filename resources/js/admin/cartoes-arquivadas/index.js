/**
 * Cartões Arquivados — Vite Module
 * Gerencia a página de cartões de crédito arquivados
 */

import { getBaseUrl, getCSRFToken, apiFetch } from '../shared/api.js';
import { escapeHtml, formatMoney } from '../shared/utils.js';
import { toastSuccess, toastError, showConfirm, refreshIcons } from '../shared/ui.js';

// ── CONFIG ──────────────────────────────────────────────────────────────────
const BASE = getBaseUrl();

// ── STATE ───────────────────────────────────────────────────────────────────
let _rows = [];

// ── DOM ─────────────────────────────────────────────────────────────────────
const grid = () => document.getElementById('archivedGrid');
const totalArquivados = () => document.getElementById('totalArquivados');
const limiteTotal = () => document.getElementById('limiteTotal');

// ── UTILS ───────────────────────────────────────────────────────────────────
function formatMoneyBR(v) {
    return formatMoney(v).replace('R$\u00a0', '').replace('R$ ', '');
}

function getDefaultColor(bandeira) {
    const colors = {
        'visa': '#1a1f71', 'mastercard': '#eb001b', 'elo': '#ffcb05',
        'amex': '#006fcf', 'hipercard': '#d9001b'
    };
    return colors[bandeira?.toLowerCase()] || '#e67e22';
}

// ── RENDER ──────────────────────────────────────────────────────────────────
function updateStats(rows) {
    const el = totalArquivados();
    if (el) el.textContent = rows.length;
    const total = rows.reduce((sum, c) => sum + parseFloat(c.limite_total || 0), 0);
    const limEl = limiteTotal();
    if (limEl) limEl.textContent = 'R$ ' + formatMoneyBR(total);
}

function renderEmpty() {
    const g = grid();
    if (!g) return;
    g.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">
                <i data-lucide="credit-card" style="color: white;"></i>
            </div>
            <h3>Nenhum cartão arquivado</h3>
            <p>Você não possui cartões arquivados no momento</p>
        </div>`;
    refreshIcons();
}

function renderCartoes(rows) {
    const g = grid();
    if (!g) return;
    if (!rows.length) { renderEmpty(); return; }

    g.innerHTML = rows.map(c => {
        const nome = escapeHtml(c.nome_cartao || 'Sem nome');
        const bandeira = (c.bandeira || 'Desconhecida').toLowerCase();
        const limite = formatMoneyBR(c.limite_total || 0);
        const disponivel = formatMoneyBR(c.limite_disponivel || 0);
        const ultimos = c.ultimos_digitos || '0000';
        const cor = c.conta?.instituicao_financeira?.cor_primaria || c.instituicao_cor || c.cor_cartao || getDefaultColor(bandeira);
        const id = c.id;

        const bandeirasLogos = {
            'visa': `${BASE}assets/img/bandeiras/visa.png`,
            'mastercard': `${BASE}assets/img/bandeiras/mastercard.png`,
            'elo': `${BASE}assets/img/bandeiras/elo.png`,
            'amex': `${BASE}assets/img/bandeiras/amex.png`,
            'hipercard': `${BASE}assets/img/bandeiras/hipercard.png`,
        };
        const logoSrc = bandeirasLogos[bandeira] || '';
        const brandHTML = logoSrc
            ? `<img src="${logoSrc}" alt="${bandeira}" class="brand-logo">`
            : `<i data-lucide="credit-card" class="brand-icon-fallback"></i>`;

        return `
        <div class="credit-card" data-brand="${bandeira}" data-id="${id}" style="background: ${cor}">
            <div class="card-header">
                <div class="card-brand">
                    ${brandHTML}
                    <span class="card-name">${nome}</span>
                </div>
                <div class="card-actions">
                    <button class="card-action-btn" onclick="handleRestore(${id})" title="Restaurar">
                        <i data-lucide="undo-2"></i>
                    </button>
                    <button class="card-action-btn" onclick="handleHardDelete(${id}, '${nome.replace(/'/g, "\\'")}')" title="Excluir permanentemente">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
            <div class="card-number">**** **** **** ${ultimos}</div>
            <div class="card-footer">
                <div class="card-holder">
                    <div class="card-label">Limite Disponível</div>
                    <div class="card-value">R$ ${disponivel}</div>
                </div>
                <div class="card-limit">
                    <div class="card-label">Limite Total</div>
                    <div class="card-value">R$ ${limite}</div>
                </div>
            </div>
        </div>`;
    }).join('');

    refreshIcons();
}

// ── API ─────────────────────────────────────────────────────────────────────
async function safeJson(res) {
    try { return await res.json(); } catch { return null; }
}

async function fetchAPI(path, opts = {}) {
    const url = `${BASE}api/${path}`.replace(/\/{2,}/g, '/').replace(':/', '://');
    let res = await fetch(url, opts);
    if (res.status === 404) {
        const fallback = `${BASE}index.php/api/${path}`.replace(/\/{2,}/g, '/').replace(':/', '://');
        res = await fetch(fallback, opts);
    }
    return res;
}

async function load() {
    const g = grid();
    if (!g) return;
    try {
        g.setAttribute('aria-busy', 'true');
        const res = await fetchAPI('cartoes?archived=1');
        if (!res.ok) throw new Error('Falha ao carregar cartões arquivados');
        const data = await safeJson(res);
        _rows = Array.isArray(data) ? data : (data?.data || []);
        updateStats(_rows);
        renderCartoes(_rows);
    } catch (err) {
        console.error(err);
        g.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon"><i data-lucide="triangle-alert"></i></div>
                <h3>Erro ao carregar</h3>
                <p>${err.message || 'Não foi possível carregar os cartões arquivados'}</p>
            </div>`;
        refreshIcons();
    } finally {
        g.setAttribute('aria-busy', 'false');
    }
}

// ── ACTIONS ─────────────────────────────────────────────────────────────────
window.handleRestore = async function (id) {
    const cartao = _rows.find(c => c.id === id);
    const nome = cartao ? cartao.nome_cartao : 'este cartão';

    const result = await window.Swal.fire({
        title: 'Restaurar Cartão',
        html: `Deseja restaurar o cartão <strong>${nome}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="undo-2"></i> Sim, restaurar',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        confirmButtonColor: '#2ecc71',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    });
    if (!result.isConfirmed) return;

    try {
        const csrf = getCSRFToken();
        const res = await fetchAPI(`cartoes/${id}/restore`, {
            method: 'POST', credentials: 'same-origin',
            headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {}
        });
        if (!res.ok) throw new Error('Falha ao restaurar');
        toastSuccess('O cartão foi restaurado com sucesso.');
        await load();
    } catch (err) {
        console.error(err);
        toastError(err.message || 'Falha ao restaurar.');
    }
};

window.handleHardDelete = async function (id, nome = '') {
    const cartao = _rows.find(c => c.id === id);
    const nomeCartao = cartao ? cartao.nome_cartao : nome || 'este cartão';

    const ok = await window.Swal.fire({
        title: 'Excluir permanentemente?',
        html: `Tem certeza que deseja excluir <strong>${nomeCartao}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta ação não pode ser desfeita!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i data-lucide="trash-2"></i> Sim, excluir',
        cancelButtonText: '<i data-lucide="x"></i> Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    });
    if (!ok.isConfirmed) return;

    try {
        const csrf = getCSRFToken();
        const headers = {
            'Content-Type': 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
        };

        const res = await fetchAPI(`cartoes/${id}/delete`, {
            method: 'POST', credentials: 'same-origin', headers,
            body: JSON.stringify({ force: false })
        });
        const data = await safeJson(res);

        if (res.status === 422 && data?.status === 'confirm_delete') {
            const totalLancamentos = data?.total_lancamentos || 0;
            const totalFaturas = data?.total_faturas || 0;
            const totalItens = data?.total_itens || 0;

            let detalhes = '';
            const totalGeral = totalLancamentos + totalFaturas + totalItens;
            if (totalGeral > 0) {
                detalhes = '<ul style="text-align:left; margin-top: 1rem; margin-bottom: 1rem;">';
                if (totalLancamentos > 0) detalhes += `<li><b>${totalLancamentos}</b> lançamento(s)</li>`;
                if (totalFaturas > 0) detalhes += `<li><b>${totalFaturas}</b> fatura(s)</li>`;
                if (totalItens > 0) detalhes += `<li><b>${totalItens}</b> item(ns) de fatura</li>`;
                detalhes += '</ul>';
            } else {
                detalhes = `<p style="margin: 1rem 0; white-space: pre-line;">${data.message || 'Nenhum dado vinculado encontrado'}</p>`;
            }

            const confirm = await window.Swal.fire({
                title: 'Excluir cartão e TODOS os dados vinculados?',
                html: `<div style="text-align:left; padding: 1rem;">
                    <p style="margin-bottom: 1rem;">O cartão <b>${nomeCartao.replace(/</g, '&lt;')}</b> possui os seguintes dados vinculados:</p>
                    ${detalhes}
                    <p style="margin-top: 1rem; color: #dc3545; font-weight: 600;">⚠️ Ao excluir o cartão, TODOS esses dados serão excluídos permanentemente!</p>
                    <p style="margin-top: 0.5rem;">Esta ação não pode ser desfeita. Deseja continuar?</p>
                </div>`,
                icon: 'warning', showCancelButton: true,
                confirmButtonText: '<i data-lucide="trash-2"></i> Sim, excluir tudo',
                cancelButtonText: '<i data-lucide="x"></i> Cancelar',
                confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', reverseButtons: true
            });
            if (!confirm.isConfirmed) return;

            const delRes = await fetchAPI(`cartoes/${id}/delete`, {
                method: 'POST', credentials: 'same-origin', headers,
                body: JSON.stringify({ force: true })
            });
            const delData = await safeJson(delRes);
            if (!delRes.ok || !delData.success) throw new Error(delData?.message || 'Erro ao excluir');

            const totalExcluido = (delData.deleted_lancamentos || 0) + (delData.deleted_faturas || 0) + (delData.deleted_itens || 0);
            await window.Swal.fire({
                icon: 'success', title: 'Excluído!',
                html: `<p><b>${nomeCartao}</b> e todos os dados vinculados foram excluídos permanentemente.</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9em; color: #6c757d;">Total de registros excluídos: ${totalExcluido}</p>`,
                timer: 3000, showConfirmButton: false
            });
            await load();
            return;
        }

        if (!res.ok) throw new Error(data?.message || 'Erro ao excluir');

        toastSuccess('Cartão excluído com sucesso.');
        await load();
    } catch (err) {
        console.error(err);
        toastError(err.message || 'Falha ao excluir.');
    }
};

// ── INIT ────────────────────────────────────────────────────────────────────
load();
