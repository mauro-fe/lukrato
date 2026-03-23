/**
 * ============================================================================
 * LUKRATO - SysAdmin Communications Page
 * ============================================================================
 */

import { apiGet, apiPost, getBaseUrl, getErrorMessage, logClientError } from '../shared/api.js';
import { debounce, escapeHtml } from '../shared/utils.js';

const BASE = getBaseUrl();

const faToLucide = {
    'fa-bullhorn': 'megaphone',
    'fa-bell': 'bell',
    'fa-paper-plane': 'send',
    'fa-envelope': 'mail',
    'fa-crown': 'crown',
    'fa-star': 'star',
    'fa-gift': 'gift',
    'fa-rocket': 'rocket',
    'fa-tag': 'tag',
    'fa-info-circle': 'info',
    'fa-exclamation-triangle': 'triangle-alert',
    'fa-check-circle': 'circle-check',
    'fa-users': 'users',
    'fa-chart-line': 'line-chart'
};

let currentPage = 1;
let totalPages = 1;
let isInitialized = false;
let previewRequestSeq = 0;
let campaignsRequestSeq = 0;

function lucideIcon(faClass) {
    return faToLucide[faClass] || String(faClass || '').replace('fa-', '');
}

function renderIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function truncateText(value, maxLength = 140) {
    const text = String(value || '').trim();
    if (text.length <= maxLength) {
        return text;
    }

    return `${text.slice(0, maxLength - 1).trimEnd()}...`;
}

function setRefreshLoading(isLoading) {
    const button = document.querySelector('.btn-refresh[data-action="loadCampaigns"]');
    if (!button) return;

    button.disabled = isLoading;
    button.classList.toggle('is-loading', isLoading);
    button.innerHTML = isLoading
        ? '<i data-lucide="loader-2" class="icon-spin"></i>'
        : '<i data-lucide="refresh-cw"></i>';

    renderIcons();
}

function syncScheduleState() {
    const scheduleCheckbox = document.getElementById('scheduleEnabled');
    const scheduleDateGroup = document.getElementById('scheduleDateTimeGroup');
    const scheduledAtInput = document.getElementById('scheduledAt');
    const btnSend = document.getElementById('btnSend');

    if (!scheduleCheckbox || !scheduleDateGroup || !scheduledAtInput || !btnSend) {
        return;
    }

    const isScheduled = scheduleCheckbox.checked;
    scheduleDateGroup.style.display = isScheduled ? 'block' : 'none';
    scheduledAtInput.required = isScheduled;

    if (!isScheduled) {
        scheduledAtInput.value = '';
    }

    btnSend.innerHTML = isScheduled
        ? '<i data-lucide="calendar-clock"></i> Agendar Campanha'
        : '<i data-lucide="send"></i> Enviar Campanha';

    renderIcons();
}

function updateHistorySummary(pagination) {
    const summary = document.getElementById('historySummary');
    if (!summary) return;

    const total = Number(pagination?.total || 0);
    const page = Number(pagination?.current_page || 1);
    const totalPagesValue = Number(pagination?.total_pages || 1);

    if (total <= 0) {
        summary.textContent = 'Acompanhe status, entrega e segmentacao das ultimas campanhas.';
        return;
    }

    summary.textContent = `${total.toLocaleString('pt-BR')} campanha(s) registradas • pagina ${page} de ${totalPagesValue}.`;
}

async function updatePreview() {
    const previewCount = document.getElementById('recipientCount');
    if (!previewCount) return;

    const requestId = ++previewRequestSeq;
    previewCount.textContent = '...';
    previewCount.closest('.preview-count')?.classList.remove('is-error');

    try {
        const response = await apiGet(`${BASE}api/campaigns/preview`, {
            plan: document.getElementById('filterPlan')?.value || '',
            status: document.getElementById('filterStatus')?.value || '',
            days_inactive: document.getElementById('filterDaysInactive')?.value || '',
        });

        if (requestId !== previewRequestSeq) {
            return;
        }

        const payload = response?.data ?? response;
        if (response?.success === false) {
            previewCount.textContent = '?';
            previewCount.closest('.preview-count')?.classList.add('is-error');
            return;
        }

        previewCount.textContent = Number(payload?.count || 0).toLocaleString('pt-BR');
    } catch (error) {
        if (requestId !== previewRequestSeq) {
            return;
        }

        logClientError('[Communications] Erro ao carregar preview', error, 'Falha ao calcular destinatarios');
        previewCount.textContent = '?';
        previewCount.closest('.preview-count')?.classList.add('is-error');
    }
}

function renderEmptyState(message) {
    const list = document.getElementById('campaignsList');
    if (!list) return;

    list.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="megaphone"></i>
            </div>
            <h3>Nenhuma campanha criada</h3>
            <p>${escapeHtml(message)}</p>
        </div>
    `;

    renderIcons();
}

function renderCampaigns(campaigns) {
    const list = document.getElementById('campaignsList');
    if (!list) return;

    if (!campaigns || campaigns.length === 0) {
        renderEmptyState('Crie sua primeira campanha para se comunicar com seus usuarios.');
        return;
    }

    list.innerHTML = campaigns.map((campaign) => {
        const badge = campaign.status_badge || {};
        const isSpinning = campaign.status === 'sending' ? ' icon-spin' : '';
        const excerpt = truncateText(campaign.message, 132);

        let timelineHtml = '';
        if (campaign.was_scheduled && campaign.sent_at) {
            timelineHtml = `
                <div class="campaign-timeline">
                    <span class="timeline-step"><i data-lucide="calendar-clock"></i> Agendada ${campaign.scheduled_at}</span>
                    <span class="timeline-arrow"><i data-lucide="arrow-right"></i></span>
                    <span class="timeline-step done"><i data-lucide="circle-check"></i> Enviada ${campaign.sent_at}</span>
                </div>`;
        } else if (campaign.was_scheduled && campaign.status === 'cancelled') {
            timelineHtml = `
                <div class="campaign-timeline">
                    <span class="timeline-step"><i data-lucide="calendar-clock"></i> Era ${campaign.scheduled_at}</span>
                    <span class="timeline-arrow"><i data-lucide="arrow-right"></i></span>
                    <span class="timeline-step cancelled"><i data-lucide="ban"></i> Cancelada</span>
                </div>`;
        }

        let emailProgressHtml = '';
        if (campaign.send_email && (campaign.emails_sent > 0 || campaign.emails_failed > 0)) {
            const totalEmails = Number(campaign.emails_sent || 0) + Number(campaign.emails_failed || 0);
            const successPct = totalEmails > 0 ? Math.round((Number(campaign.emails_sent || 0) / totalEmails) * 100) : 0;
            emailProgressHtml = `
                <div class="campaign-email-progress">
                    <div class="progress-bar-mini">
                        <div class="progress-fill success" style="width: ${successPct}%"></div>
                    </div>
                    <span class="progress-label">
                        <i data-lucide="mail"></i>
                        ${Number(campaign.emails_sent || 0).toLocaleString('pt-BR')} enviados${campaign.emails_failed > 0 ? ` • ${Number(campaign.emails_failed).toLocaleString('pt-BR')} falharam` : ''}
                    </span>
                </div>`;
        }

        return `
            <div class="campaign-card" data-action="showCampaignDetail" data-campaign-id="${campaign.id}" style="--campaign-color: ${campaign.color}">
                <div class="campaign-card-header">
                    <div class="campaign-icon" style="background-color: ${campaign.color}15; color: ${campaign.color}">
                        <i data-lucide="${lucideIcon(campaign.icon)}"></i>
                    </div>
                    <div class="campaign-info">
                        <h4 class="campaign-title">${escapeHtml(campaign.title)}</h4>
                        <p class="campaign-excerpt">${escapeHtml(excerpt)}</p>
                        <div class="campaign-meta">
                            <span><i data-lucide="users"></i> ${Number(campaign.total_recipients || 0).toLocaleString('pt-BR')}</span>
                            <span><i data-lucide="eye"></i> ${Number(campaign.read_rate || 0)}%</span>
                            <span><i data-lucide="shield"></i> ${escapeHtml(campaign.creator_name || 'Sistema')}</span>
                            <span><i data-lucide="calendar"></i> ${escapeHtml(campaign.created_at || '-')}</span>
                        </div>
                    </div>
                    <div class="campaign-status-col">
                        <div class="campaign-status-badge" style="background-color: ${badge.color}15; color: ${badge.color}; border-color: ${badge.color}30">
                            ${badge.icon ? `<i data-lucide="${badge.icon}" class="${isSpinning}"></i>` : ''}
                            <span>${escapeHtml(badge.label || 'Sem status')}</span>
                        </div>
                        ${campaign.is_scheduled ? `<button class="btn-cancel-schedule" data-action="cancelScheduled" data-campaign-id="${campaign.id}" title="Cancelar agendamento"><i data-lucide="x-circle"></i> Cancelar</button>` : ''}
                    </div>
                </div>
                <div class="campaign-card-footer">
                    ${timelineHtml}
                    ${emailProgressHtml}
                    <div class="campaign-tags">
                        <span class="tag"><i data-lucide="filter"></i> ${escapeHtml(campaign.filters_description || 'Sem filtros')}</span>
                        <span class="tag"><i data-lucide="radio-tower"></i> ${escapeHtml(campaign.channels_description || 'Sem canais')}</span>
                    </div>
                </div>
            </div>`;
    }).join('');

    renderIcons();
}

function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const pageInfo = document.getElementById('pageInfo');
    const btnPrev = document.getElementById('btnPrevPage');
    const btnNext = document.getElementById('btnNextPage');

    if (!container || !pageInfo || !btnPrev || !btnNext) {
        return;
    }

    totalPages = Number(pagination?.total_pages || 1);

    if (totalPages <= 1) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';
    pageInfo.textContent = `Pagina ${pagination.current_page} de ${totalPages}`;
    btnPrev.disabled = pagination.current_page <= 1;
    btnNext.disabled = pagination.current_page >= totalPages;
}

async function loadCampaigns(page = 1) {
    const list = document.getElementById('campaignsList');
    if (!list) return;

    const requestId = ++campaignsRequestSeq;
    setRefreshLoading(true);

    list.innerHTML = `
        <div class="lk-loading-state">
            <i data-lucide="loader-2" class="icon-spin"></i>
            <span>Carregando campanhas...</span>
        </div>
    `;
    renderIcons();

    try {
        const response = await apiGet(`${BASE}api/campaigns`, {
            page,
            per_page: 10
        });

        if (requestId !== campaignsRequestSeq) {
            return;
        }

        if (response?.success === false) {
            const message = response?.message || 'Erro ao carregar campanhas.';
            list.innerHTML = `
                <div class="empty-state">
                    <i data-lucide="circle-alert"></i>
                    <span>${escapeHtml(message)}</span>
                </div>
            `;
            updateHistorySummary(null);
            renderIcons();
            return;
        }

        const payload = response?.data ?? response;
        renderCampaigns(payload?.campaigns || []);
        updatePagination(payload?.pagination || null);
        updateHistorySummary(payload?.pagination || null);
        currentPage = page;
    } catch (error) {
        if (requestId !== campaignsRequestSeq) {
            return;
        }

        logClientError('[Communications] Erro ao carregar campanhas', error, 'Falha ao carregar campanhas');
        list.innerHTML = `
            <div class="empty-state">
                <i data-lucide="circle-alert"></i>
                <span>Erro ao carregar campanhas</span>
            </div>
        `;
        updateHistorySummary(null);
        renderIcons();
    } finally {
        if (requestId === campaignsRequestSeq) {
            setRefreshLoading(false);
        }
    }
}

function changePage(delta) {
    const newPage = currentPage + delta;
    if (newPage >= 1 && newPage <= totalPages) {
        loadCampaigns(newPage);
    }
}

async function showCampaignDetail(id) {
    const modalElement = document.getElementById('campaignDetailModal');
    const body = document.getElementById('campaignDetailBody');
    if (!modalElement || !body) return;

    const modal = new bootstrap.Modal(modalElement);
    body.innerHTML = `
        <div class="text-center py-4">
            <i data-lucide="loader-2" class="icon-spin"></i>
        </div>
    `;
    modal.show();
    renderIcons();

    try {
        const response = await apiGet(`${BASE}api/campaigns/${id}`);

        if (response?.success === false) {
            body.innerHTML = '<div class="text-danger">Erro ao carregar detalhes</div>';
            return;
        }

        const c = response?.data ?? response;
        let timelineHtml = `
            <div class="detail-timeline">
                <div class="timeline-item active">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Criada</span>
                        <span class="timeline-date">${escapeHtml(c.created_at || '-')}</span>
                    </div>
                </div>`;

        if (c.scheduled_at) {
            const isScheduleActive = ['scheduled', 'sending', 'sent', 'partial'].includes(c.status);
            timelineHtml += `
                <div class="timeline-item ${isScheduleActive ? 'active' : 'cancelled'}">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Agendada</span>
                        <span class="timeline-date">${escapeHtml(c.scheduled_at)}</span>
                    </div>
                </div>`;
        }

        if (c.sent_at) {
            timelineHtml += `
                <div class="timeline-item active done">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Enviada</span>
                        <span class="timeline-date">${escapeHtml(c.sent_at)}</span>
                    </div>
                </div>`;
        } else if (c.status === 'cancelled') {
            timelineHtml += `
                <div class="timeline-item cancelled">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Cancelada</span>
                    </div>
                </div>`;
        }

        timelineHtml += '</div>';

        let emailDetailHtml = '';
        if (c.send_email && (c.emails_sent > 0 || c.emails_failed > 0)) {
            const totalEmails = Number(c.emails_sent || 0) + Number(c.emails_failed || 0);
            const successPct = totalEmails > 0 ? Math.round((Number(c.emails_sent || 0) / totalEmails) * 100) : 0;
            emailDetailHtml = `
                <div class="detail-email-progress">
                    <div class="progress-header">
                        <span><i data-lucide="mail"></i> Progresso de E-mails</span>
                        <span>${successPct}% sucesso</span>
                    </div>
                    <div class="progress-bar-detail">
                        <div class="progress-fill success" style="width: ${successPct}%"></div>
                    </div>
                    <div class="progress-legend">
                        <span class="legend-success"><i data-lucide="circle-check"></i> ${Number(c.emails_sent || 0).toLocaleString('pt-BR')} enviados</span>
                        ${c.emails_failed > 0 ? `<span class="legend-fail"><i data-lucide="circle-x"></i> ${Number(c.emails_failed).toLocaleString('pt-BR')} falharam</span>` : ''}
                    </div>
                </div>`;
        }

        body.innerHTML = `
            <div class="campaign-detail">
                <div class="detail-header" style="border-left: 4px solid ${c.color}">
                    <i data-lucide="${lucideIcon(c.icon)}" style="color: ${c.color}"></i>
                    <div>
                        <h4>${escapeHtml(c.title)}</h4>
                        <span class="detail-creator">Por ${escapeHtml(c.creator?.nome || 'Sistema')} em ${escapeHtml(c.created_at || '-')}</span>
                    </div>
                    <div class="detail-status-badge" style="background-color: ${c.status_badge.color}15; color: ${c.status_badge.color}; border-color: ${c.status_badge.color}30">
                        ${c.status_badge.icon ? `<i data-lucide="${c.status_badge.icon}"></i>` : ''}
                        ${escapeHtml(c.status_badge.label)}
                    </div>
                </div>

                ${timelineHtml}

                <div class="detail-message">
                    <strong>Mensagem:</strong>
                    <p>${escapeHtml(c.message).replace(/\n/g, '<br>')}</p>
                    ${c.link ? `<a href="${escapeHtml(c.link)}" target="_blank" rel="noopener noreferrer" class="detail-cta">${escapeHtml(c.link_text || 'Ver link')} <i data-lucide="external-link"></i></a>` : ''}
                </div>

                <div class="detail-stats">
                    <div class="stat">
                        <span class="stat-label">Destinatarios</span>
                        <span class="stat-value">${Number(c.total_recipients || 0).toLocaleString('pt-BR')}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Notificacoes Lidas</span>
                        <span class="stat-value">${Number(c.notifications_read || 0).toLocaleString('pt-BR')} <small>(${Number(c.read_rate || 0)}%)</small></span>
                    </div>
                    ${c.send_email ? `
                        <div class="stat">
                            <span class="stat-label">E-mails OK</span>
                            <span class="stat-value">${Number(c.emails_sent || 0).toLocaleString('pt-BR')}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">E-mails Falhos</span>
                            <span class="stat-value ${c.emails_failed > 0 ? 'text-danger' : ''}">${Number(c.emails_failed || 0).toLocaleString('pt-BR')}</span>
                        </div>
                    ` : ''}
                </div>

                ${emailDetailHtml}

                <div class="detail-meta">
                    <span><i data-lucide="filter"></i> ${escapeHtml(c.filters_description || 'Sem filtros')}</span>
                    <span><i data-lucide="radio-tower"></i> ${escapeHtml(c.channels_description || 'Sem canais')}</span>
                </div>
            </div>
        `;

        renderIcons();
    } catch (error) {
        logClientError('[Communications] Erro ao carregar detalhes da campanha', error, 'Falha ao carregar detalhes');
        body.innerHTML = '<div class="text-danger">Erro ao carregar detalhes</div>';
    }
}

async function handleFormSubmit(event) {
    event.preventDefault();

    const form = document.getElementById('campaignForm');
    const btn = document.getElementById('btnSend');
    if (!form || !btn) return;

    const originalText = btn.innerHTML;
    const title = document.getElementById('campaignTitle')?.value.trim() || '';
    const message = document.getElementById('campaignMessage')?.value.trim() || '';
    const sendNotification = document.getElementById('sendNotification')?.checked === true;
    const sendEmail = document.getElementById('sendEmail')?.checked === true;
    const isScheduled = document.getElementById('scheduleEnabled')?.checked === true;
    const scheduledAt = isScheduled ? (document.getElementById('scheduledAt')?.value || null) : null;

    if (!title || !message) {
        LKFeedback.warning('Preencha o titulo e a mensagem da campanha.');
        return;
    }

    if (!sendNotification && !sendEmail) {
        LKFeedback.warning('Escolha pelo menos um canal de envio (Notificacao ou E-mail).');
        return;
    }

    if (isScheduled && !scheduledAt) {
        LKFeedback.warning('Selecione a data e hora para o agendamento.');
        return;
    }

    const recipientCount = document.getElementById('recipientCount')?.textContent || '0';
    const channelText = [sendNotification ? 'Notificacao' : '', sendEmail ? 'E-mail' : ''].filter(Boolean).join(' + ');
    const confirmMsg = isScheduled
        ? `Campanha sera agendada para ${new Date(scheduledAt).toLocaleString('pt-BR')}.\nDestinatarios estimados: ${recipientCount}. Canais: ${channelText}`
        : `Voce esta prestes a enviar uma campanha para ${recipientCount} usuarios. Canais: ${channelText}`;

    const confirmResult = await LKFeedback.confirm(confirmMsg, {
        title: isScheduled ? 'Confirmar agendamento?' : 'Confirmar envio?',
        icon: 'question',
        confirmButtonText: isScheduled ? 'Sim, agendar!' : 'Sim, enviar!',
        cancelButtonText: 'Cancelar'
    });

    if (!confirmResult.isConfirmed) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = isScheduled
        ? '<i data-lucide="loader-2" class="icon-spin"></i> Agendando...'
        : '<i data-lucide="loader-2" class="icon-spin"></i> Enviando...';
    renderIcons();

    try {
        const response = await apiPost(`${BASE}api/campaigns`, {
            title,
            message,
            type: document.getElementById('campaignType')?.value || 'promo',
            link: document.getElementById('campaignLink')?.value || null,
            link_text: document.getElementById('campaignLinkText')?.value || null,
            send_notification: sendNotification,
            send_email: sendEmail,
            cupom_id: document.getElementById('campaignCupom')?.value || null,
            scheduled_at: scheduledAt || null,
            filters: {
                plan: document.getElementById('filterPlan')?.value || '',
                status: document.getElementById('filterStatus')?.value || '',
                days_inactive: document.getElementById('filterDaysInactive')?.value || null
            }
        });

        if (response?.success === false) {
            LKFeedback.error(response?.message || 'Ocorreu um erro ao enviar a campanha.');
            return;
        }

        const payload = response?.data ?? response;

        if (payload?.scheduled_at) {
            LKFeedback.success(`Campanha agendada para ${payload.scheduled_at}.`, { toast: true });
        } else {
            LKFeedback.success(`${Number(payload?.total_recipients || 0).toLocaleString('pt-BR')} usuarios receberao sua mensagem.${payload?.emails_sent > 0 ? ` E-mails enviados: ${payload.emails_sent}` : ''}${payload?.emails_failed > 0 ? ` E-mails com falha: ${payload.emails_failed}` : ''}`, { toast: true });
        }

        form.reset();
        document.getElementById('titleCount').textContent = '0';
        document.getElementById('sendNotification').checked = true;
        document.getElementById('scheduleEnabled').checked = false;
        syncScheduleState();

        await loadCampaigns(1);
        await updatePreview();
    } catch (error) {
        logClientError('[Communications] Erro ao enviar campanha', error, 'Falha ao enviar campanha');
        LKFeedback.error(getErrorMessage(error, 'Ocorreu um erro de conexao. Tente novamente.'));
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        renderIcons();
    }
}

async function cancelScheduled(id) {
    const result = await LKFeedback.confirm('Deseja cancelar esta campanha agendada?', {
        title: 'Cancelar agendamento?',
        icon: 'warning',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Nao'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await apiPost(`${BASE}api/campaigns/${id}/cancel`);

        if (response?.success === false) {
            LKFeedback.error(response?.message || 'Erro ao cancelar campanha.');
            return;
        }

        LKFeedback.success('Campanha agendada cancelada.', { toast: true });
        await loadCampaigns(currentPage);
    } catch (error) {
        logClientError('[Communications] Erro ao cancelar campanha', error, 'Falha ao cancelar campanha');
        LKFeedback.error(getErrorMessage(error, 'Erro de conexao ao cancelar campanha.'));
    }
}

function setupFormListeners() {
    const titleInput = document.getElementById('campaignTitle');
    const titleCount = document.getElementById('titleCount');

    titleInput?.addEventListener('input', () => {
        if (titleCount) {
            titleCount.textContent = String(titleInput.value.length);
        }
    });

    document.querySelectorAll('.filter-input').forEach((element) => {
        element.addEventListener('change', debouncedPreviewUpdate);
    });

    document.getElementById('scheduleEnabled')?.addEventListener('change', syncScheduleState);
    document.getElementById('campaignForm')?.addEventListener('submit', handleFormSubmit);
}

document.addEventListener('click', (event) => {
    const element = event.target.closest('[data-action]');
    if (!element) return;

    switch (element.dataset.action) {
        case 'updatePreview':
            updatePreview();
            break;
        case 'loadCampaigns':
            loadCampaigns();
            break;
        case 'changePage':
            changePage(parseInt(element.dataset.delta, 10));
            break;
        case 'showCampaignDetail':
            showCampaignDetail(parseInt(element.dataset.campaignId, 10));
            break;
        case 'cancelScheduled':
            event.stopPropagation();
            cancelScheduled(parseInt(element.dataset.campaignId, 10));
            break;
    }
});

function initCommunicationsPage() {
    if (isInitialized) return;
    if (!document.getElementById('campaignForm') || !document.getElementById('campaignsList')) return;

    isInitialized = true;
    setupFormListeners();
    syncScheduleState();
    loadCampaigns();
    updatePreview();
}

const debouncedPreviewUpdate = debounce(() => {
    updatePreview();
}, 300);

document.addEventListener('DOMContentLoaded', initCommunicationsPage);
if (document.readyState !== 'loading') {
    initCommunicationsPage();
}
