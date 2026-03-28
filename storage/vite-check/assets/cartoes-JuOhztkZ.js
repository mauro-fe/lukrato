import { r as k, g as x, e as F, j as b, l as L, f as R, a as D, d as M, c as q } from "./api-Dkfcp6ON.js"; import { a as U } from "./utils-Bj4jxwhy.js"; import { r as I } from "./ui-H2yoVZe7.js"; const p = { BASE_URL: window.LK?.getBase?.() || "/", API_URL: "" }; p.API_URL = p.BASE_URL + "api"; const c = { cartoes: [], filteredCartoes: [], alertas: [], currentView: "grid", currentFilter: "all", searchTerm: "", lastLoadedAt: null, isLoading: !1, isSaving: !1 }, g = {}, s = {
    async getCSRFToken() { try { const a = await k(); if (a) return a } catch (a) { console.warn("Erro ao buscar token fresco, usando fallback:", a) } const e = x(); return e || (window.LK?.getCSRF ? window.LK.getCSRF() : window.CSRF ? window.CSRF : (console.warn("⚠️ Nenhum token CSRF encontrado"), "")) }, getBaseUrl() { return p.BASE_URL }, formatMoney(e) { return U(e) }, formatMoneyInput(e) { return typeof e == "string" && e.includes(",") ? e : typeof e == "number" ? (e / 100).toFixed(2).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".") : new Intl.NumberFormat("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(e || 0) }, parseMoney(e) { return typeof e == "number" ? e : e && parseFloat(e.toString().replace(/[R$\s]/g, "").replace(/\./g, "").replace(",", ".")) || 0 }, escapeHtml(e) { const a = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }; return e.replace(/[&<>"']/g, t => a[t]) }, debounce(e, a) { let t; return function (...r) { const n = () => { clearTimeout(t), e(...r) }; clearTimeout(t), t = setTimeout(n, a) } }, showToast(e, a) { window.Swal ? Swal.fire({ icon: e, title: e === "success" ? "Sucesso!" : "Erro!", text: a, timer: 3e3, showConfirmButton: !1, toast: !0, position: "top-end" }) : alert(a) }, async showConfirmDialog(e, a, t = "Confirmar") {
        return typeof Swal < "u" ? (await Swal.fire({ title: e, text: a, icon: "warning", showCancelButton: !0, confirmButtonColor: "#d33", cancelButtonColor: "#3085d6", confirmButtonText: t, cancelButtonText: "Cancelar", reverseButtons: !0 })).isConfirmed : confirm(`${e}

${a}`)
    }, getBrandIcon(e) { const a = `${p.BASE_URL}assets/img/bandeiras/`; return { visa: `${a}visa.png`, mastercard: `${a}mastercard.png`, elo: `${a}elo.png`, amex: `${a}amex.png`, diners: `${a}diners.png`, discover: `${a}discover.png` }[e?.toLowerCase()] || `${a}default.png` }, getDefaultColor(e) { return { visa: "linear-gradient(135deg, #1A1F71 0%, #2D3A8C 100%)", mastercard: "linear-gradient(135deg, #EB001B 0%, #F79E1B 100%)", elo: "linear-gradient(135deg, #FFCB05 0%, #FFE600 100%)", amex: "linear-gradient(135deg, #006FCF 0%, #0099CC 100%)", diners: "linear-gradient(135deg, #0079BE 0%, #00558C 100%)", discover: "linear-gradient(135deg, #FF6000 0%, #FF8500 100%)" }[e?.toLowerCase()] || "linear-gradient(135deg, #667eea 0%, #764ba2 100%)" }, getAccentColor(e) { return { visa: "#1A1F71", mastercard: "#EB001B", elo: "#00A4E0", amex: "#006FCF", diners: "#0079BE", discover: "#FF6000", hipercard: "#822124" }[e?.toLowerCase()] || "#e67e22" }, resolverCorCartao(e, a) { if (e.cartao?.cor_cartao) return e.cartao.cor_cartao; const t = a || e.cartao_id || e.cartao?.id; if (t) { const o = c.cartoes.find(r => r.id === t); if (o) { const r = o.cor_cartao || o.conta?.instituicao_financeira?.cor_primaria || o.instituicao_cor; return r || s.getAccentColor(o.bandeira) } } return s.getAccentColor(e.cartao?.bandeira) }, getNomeMes(e) { return ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"][e - 1] || "Mês inválido" }, getFreqLabel(e) { return { mensal: "Mensal", bimestral: "Bimestral", trimestral: "Trimestral", semestral: "Semestral", anual: "Anual" }[e] || "Recorrente" }, formatDate(e) { if (!e) return "-"; let a; if (e instanceof Date) a = e; else if (typeof e == "string") if (e.includes("T")) a = new Date(e); else { const t = e.split(" ")[0], [o, r, n] = t.split("-"); a = new Date(o, r - 1, n) } return isNaN(a.getTime()) ? "-" : a.toLocaleDateString("pt-BR") }, formatBandeira(e) { return e ? e.charAt(0).toUpperCase() + e.slice(1).toLowerCase() : "Não informado" }, formatMoneyForCSV(e) { return new Intl.NumberFormat("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(e || 0) }, convertToCSV(e) {
        if (e.length === 0) return ""; const a = Object.keys(e[0]), t = []; t.push(a.join(",")); for (const o of e) { const r = a.map(n => `"${("" + o[n]).replace(/"/g, '""')}"`); t.push(r.join(",")) } return t.join(`
`)
    }, setupLimiteMoneyMask() { const e = document.getElementById("limiteTotal"); if (!e) { console.error("❌ Campo limiteTotal NÃO encontrado!"); return } e.addEventListener("input", function (a) { let t = a.target.value; t = t.replace(/[^\d]/g, ""); const n = ((parseInt(t) || 0) / 100).toFixed(2).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, "."); a.target.value = n }), e.value = "0,00" }
}; function z(e) { return typeof e != "string" ? e : e.startsWith(p.BASE_URL) ? e.slice(p.BASE_URL.length) : e } async function w(e, { method: a = "GET", data: t = null, headers: o = {}, timeout: r = 15e3 } = {}) { return D(z(e), { method: a, headers: o, body: t }, { timeout: r }) } const $ = {
    async loadCartoes() {
        const e = document.getElementById("cartoesGrid"), a = document.getElementById("emptyState"), t = document.getElementById("cartoesContainer"); if (!(!e || !a)) try {
            c.isLoading = !0, e.setAttribute("aria-busy", "true"), t?.setAttribute("aria-busy", "true"), e.innerHTML = `
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
            `, a.style.display = "none"; let o; if (window.lkFetch) { const r = await window.lkFetch.get(`${p.API_URL}/cartoes`, { timeout: 2e4, maxRetries: 2, showLoading: !0, loadingTarget: "#cartoesContainer" }); o = b(r, []) } else o = await w(`${p.API_URL}/cartoes`); c.cartoes = Array.isArray(o) ? o : b(o, []), await $.verificarFaturasPendentes(), c.lastLoadedAt = new Date().toISOString(), g.UI.updateStats(), g.UI.filterCartoes(), await $.carregarAlertas()
        } catch (o) {
            L("[Cartoes] Erro ao carregar cartões", o, "Erro ao carregar cartões"); let r = F(o, "Erro ao carregar cartoes"); o.name === "AbortError" || r.includes("demorou") ? r = "A conexão está lenta. Tente novamente." : navigator.onLine || (r = "Sem conexão com a internet"), s.showToast("error", r), e.innerHTML = `
                <div class="error-state">
                    <i data-lucide="triangle-alert"></i>
                    <p class="error-message">${s.escapeHtml(r)}</p>
                    <button class="btn btn-primary btn-retry" onclick="window.cartoesManager.loadCartoes()">
                        <i data-lucide="refresh-cw"></i> Tentar novamente
                    </button>
                </div>
            `, I()
        } finally { c.isLoading = !1, e.setAttribute("aria-busy", "false"), t?.setAttribute("aria-busy", "false") }
    }, async verificarFaturasPendentes() { c.cartoes.forEach(a => { a.temFaturaPendente = !1 }); const e = c.cartoes.map(async a => { try { const t = await w(`${p.API_URL}/cartoes/${a.id}/faturas-pendentes`), o = b(t, {}), r = o?.meses || b(o, []) || []; a.temFaturaPendente = Array.isArray(r) && r.length > 0 } catch { a.temFaturaPendente = !1 } }); await Promise.all(e) }, async carregarAlertas() { try { let e; if (window.lkFetch) { const a = await window.lkFetch.get(`${p.API_URL}/cartoes/alertas`, { timeout: 1e4, maxRetries: 1, showLoading: !1 }); e = b(a, {}); const t = b(e, {}); c.alertas = t?.alertas || [] } else { e = await w(`${p.API_URL}/cartoes/alertas`, { timeout: 1e4 }); const a = b(e, {}); c.alertas = a?.alertas || [] } $.renderAlertas() } catch (e) { R("[Cartoes] Erro ao carregar alertas", e, "Erro ao carregar alertas"), c.alertas = []; const a = document.getElementById("alertasContainer"); a && (a.style.display = "none") } }, renderAlertas() {
        const e = document.getElementById("alertasContainer"); if (e) {
            if (c.alertas.length === 0) { e.style.display = "none"; return } e.style.display = "block", e.innerHTML = `
            <div class="alertas-list">
                ${c.alertas.map(a => $.criarAlertaHTML(a)).join("")}
            </div>
        `, I()
        }
    }, criarAlertaHTML(e) {
        const a = { vencimento_proximo: "calendar-x", limite_baixo: "triangle-alert" }, t = { critico: "#e74c3c", atencao: "#f39c12" }, o = Object.prototype.hasOwnProperty.call(a, e?.tipo) ? e.tipo : "limite_baixo", r = Object.prototype.hasOwnProperty.call(t, e?.gravidade) ? e.gravidade : "atencao", n = s.escapeHtml(String(e?.nome_cartao || "Cartão")), l = Number(e?.dias_faltando || 0), d = Number(e?.percentual_disponivel || 0), i = Number(e?.valor_fatura || 0), y = Number(e?.limite_disponivel || 0); let h = ""; return o === "vencimento_proximo" ? h = `Fatura de <strong>${n}</strong> vence em <strong>${l} dia(s)</strong> - ${s.formatMoney(i)}` : o === "limite_baixo" && (h = `Limite de <strong>${n}</strong> em <strong>${d.toFixed(1)}%</strong> - ${s.formatMoney(y)} disponível`), `
            <div class="alerta-item alerta-${r}" data-tipo="${o}">
                <div class="alerta-icon" style="color: ${t[r]}">
                    <i data-lucide="${a[o]}"></i>
                </div>
                <div class="alerta-content">
                    <p>${h}</p>
                </div>
                <button class="alerta-dismiss" onclick="cartoesManager.dismissAlerta(this)" title="Dispensar">
                    <i data-lucide="x"></i>
                </button>
            </div>
        `}, dismissAlerta(e) { const a = e.closest(".alerta-item"); a && (a.style.animation = "slideOut 0.3s ease-out forwards", setTimeout(() => { a.remove(); const t = document.getElementById("alertasContainer"); t && t.querySelectorAll(".alerta-item").length === 0 && (t.style.display = "none") }, 300)) }, async loadContasSelect() { const e = document.getElementById("contaVinculada"), a = document.getElementById("contaVinculadaHelp"), t = document.getElementById("cartaoContaEmptyHint"); if (!e) { console.error("❌ Select contaVinculada não encontrado!"); return } try { const o = `${p.API_URL}/contas?only_active=0&with_balances=1`, r = await w(o), n = b(r, {}); let l = []; if (Array.isArray(n) ? l = n : Array.isArray(n?.contas) && (l = n.contas), l.length === 0) return e.disabled = !0, e.innerHTML = '<option value="">Nenhuma conta disponivel</option>', a && (a.textContent = "Crie uma conta antes de vincular um cartao."), t && (t.hidden = !1), console.warn("⚠️ Nenhuma conta encontrada"), 0; const d = l.map(i => { const y = i.instituicao_financeira?.nome || i.instituicao?.nome || i.nome || "Sem instituição", h = s.escapeHtml(i.nome || "Conta sem nome"), v = s.escapeHtml(y), C = parseFloat(i.saldoAtual || i.saldo_atual || i.saldo || i.saldo_inicial || 0), E = s.formatMoney(C); return `<option value="${i.id}">${h} - ${v} - ${E}</option>` }).join(""); return e.disabled = !1, e.innerHTML = '<option value="">Selecione a conta</option>' + d, a && (a.textContent = "Conta onde o pagamento da fatura sera debitado."), t && (t.hidden = !0), l.length } catch (o) { return L("[Cartoes] Erro ao carregar contas", o, "Erro ao carregar contas"), e.disabled = !0, e.innerHTML = '<option value="">Erro ao carregar contas</option>', a && (a.textContent = "Nao foi possivel carregar as contas agora."), t && (t.hidden = !1), 0 } }, async saveCartao() { const e = document.getElementById("formCartao"); if (!e.checkValidity()) { e.reportValidity(); return } const a = document.getElementById("cartaoId").value, t = !!a, o = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="csrf_token"]')?.value || "", r = document.getElementById("limiteTotal").value, n = s.parseMoney(r), l = document.getElementById("cartaoLembreteAviso")?.value || "", d = document.getElementById("contaVinculada"), i = document.getElementById("cartaoCanalInapp"), y = document.getElementById("cartaoCanalEmail"); if (d?.disabled) { s.showToast("error", "Crie uma conta antes de cadastrar um cartao."); return } if (l && !i?.checked && !y?.checked) { s.showToast("error", "Selecione pelo menos um canal para o lembrete."); return } const h = { nome_cartao: document.getElementById("nomeCartao").value.trim(), conta_id: d?.value ? parseInt(d.value, 10) : null, bandeira: document.getElementById("bandeira").value, ultimos_digitos: document.getElementById("ultimosDigitos").value.trim(), limite_total: n, dia_fechamento: document.getElementById("diaFechamento").value || null, dia_vencimento: document.getElementById("diaVencimento").value || null, lembrar_fatura_antes_segundos: l ? parseInt(l) : null, fatura_canal_inapp: l && i?.checked ? 1 : 0, fatura_canal_email: l && y?.checked ? 1 : 0, csrf_token: o }; try { const v = t ? `${p.API_URL}/cartoes/${a}` : `${p.API_URL}/cartoes`, C = await w(v, { method: t ? "PUT" : "POST", data: h }), E = b(C, null); E?.gamification?.achievements && Array.isArray(E.gamification.achievements) && (typeof window.notifyMultipleAchievements == "function" ? window.notifyMultipleAchievements(E.gamification.achievements) : console.error("❌ notifyMultipleAchievements não está disponível")), s.showToast("success", t ? "Cartão atualizado com sucesso!" : "Cartão criado com sucesso!"), g.UI.closeModal(), await $.loadCartoes() } catch (v) { L("[Cartoes] Erro ao salvar cartão", v, "Erro ao salvar cartão"), s.showToast("error", F(v, "Erro ao salvar cartao")) } }, async editCartao(e) { const a = c.cartoes.find(t => t.id === e); a && g.UI.openModal("edit", a) }, async arquivarCartao(e) { const a = c.cartoes.find(o => o.id === e); if (!(!a || !await s.showConfirmDialog("Arquivar Cartão", `Tem certeza que deseja arquivar o cartão "${a.nome_cartao}"? Você poderá restaurá-lo depois na página de Cartões Arquivados.`, "Arquivar"))) try { await w(`${p.API_URL}/cartoes/${e}/archive`, { method: "POST" }), s.showToast("success", "Cartão arquivado com sucesso!"), $.loadCartoes() } catch (o) { L("[Cartoes] Erro ao arquivar cartão", o, "Erro ao arquivar cartão"), s.showToast("error", F(o, "Erro ao arquivar cartao")) } }, async deleteCartao(e) { return $.arquivarCartao(e) }, async carregarFatura(e, a, t) { try { const o = await w(`${p.API_URL}/cartoes/${e}/fatura?mes=${a}&ano=${t}`); return b(o, { itens: [], total: 0, pago: 0, pendente: 0 }) } catch (o) { if (o?.status === 404) return { itens: [], total: 0, pago: 0, pendente: 0 }; throw new Error(F(o, "Erro ao carregar fatura")) } }, async carregarParcelamentosResumo(e, a, t) { const o = await w(`${p.API_URL}/cartoes/${e}/parcelamentos-resumo?mes=${a}&ano=${t}`); return b(o, null) }, async carregarHistoricoFaturas(e, a = 12) { const t = await w(`${p.API_URL}/cartoes/${e}/faturas-historico?limite=${a}`); return b(t, null) }, async pagarParcelasIndividuais(e, a) { try { const t = Array.from(e).map(n => parseInt(n.dataset.id)), o = a.cartao_id || a.cartao?.id; if (!o) throw new Error("ID do cartão não encontrado na fatura"); const r = await w(`${p.API_URL}/cartoes/${o}/parcelas/pagar`, { method: "POST", data: { parcela_ids: t, mes: a.mes, ano: a.ano } }); if (r?.success !== !1) { s.showToast("success", r.message || "Parcelas pagas com sucesso!"); const n = document.querySelector(".modal-fatura-overlay"); n && g.Fatura.fecharModalFatura(n), await $.loadCartoes() } else throw new Error(r.message || "Erro ao pagar parcelas") } catch (t) { s.showToast("error", F(t, "Erro ao processar a operacao do cartao")) } }, async desfazerPagamento(e, a, t) {
        if ((await Swal.fire({
            title: "Desfazer pagamento?", html: `
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 300px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar as parcelas como não pagas</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Tem certeza?</strong></p>
            `, icon: "warning", showCancelButton: !0, confirmButtonText: "Sim, desfazer", cancelButtonText: "Cancelar", confirmButtonColor: "#d33", reverseButtons: !0
        })).isConfirmed) try { const r = await w(`${p.API_URL}/cartoes/${e}/fatura/desfazer-pagamento`, { method: "POST", data: { mes: a, ano: t } }); if (r.success) { s.showToast("success", r.message); const n = document.querySelector(".modal-fatura-overlay"); n && g.Fatura.fecharModalFatura(n), await $.loadCartoes() } else throw new Error(r.message || "Erro ao desfazer pagamento") } catch (r) { s.showToast("error", F(r, "Erro ao processar a operacao do cartao")) }
    }, async desfazerPagamentoParcela(e) {
        if ((await Swal.fire({
            title: "Desfazer pagamento desta parcela?", html: `
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 320px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar esta parcela como não paga</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Deseja continuar?</strong></p>
            `, icon: "warning", showCancelButton: !0, confirmButtonText: "Sim, desfazer", cancelButtonText: "Cancelar", confirmButtonColor: "#d33", reverseButtons: !0
        })).isConfirmed) try { const t = await w(`${p.API_URL}/cartoes/parcelas/${e}/desfazer-pagamento`, { method: "POST" }); if (t.success) { s.showToast("success", t.message); const o = document.querySelector(".modal-fatura-overlay"); o && g.Fatura.fecharModalFatura(o), await $.loadCartoes() } else throw new Error(t.message || "Erro ao desfazer pagamento") } catch (t) { s.showToast("error", F(t, "Erro ao processar a operacao do cartao")) }
    }
}; g.API = $; const N = { all: "Todos", visa: "Visa", mastercard: "Mastercard", elo: "Elo" }, T = (e, a = 0, t = 100) => Math.min(t, Math.max(a, Number(e) || 0)), A = () => !!c.searchTerm || c.currentFilter !== "all", _ = (e, a = "") => s.escapeHtml(String(e ?? a)), H = () => { const e = new Date; return `${e.getFullYear()}-${String(e.getMonth() + 1).padStart(2, "0")}` }, P = e => e?.cor_cartao || e?.conta?.instituicao_financeira?.cor_primaria || e?.instituicao_cor || s.getAccentColor(e?.bandeira), u = {
    setupEventListeners() { document.getElementById("btnNovoCartao")?.addEventListener("click", () => { u.openModal("create") }), document.getElementById("btnNovoCartaoEmpty")?.addEventListener("click", () => { u.openModal("create") }), document.getElementById("btnLimparFiltrosEmpty")?.addEventListener("click", () => { u.clearFilters() }); const e = document.getElementById("modalCartaoOverlay"); e && e.addEventListener("click", t => { t.target === e && u.closeModal() }), document.querySelectorAll("#modalCartaoOverlay .modal-close, #modalCartaoOverlay .modal-close-btn").forEach(t => { t.addEventListener("click", () => u.closeModal()) }), document.getElementById("limiteTotal")?.addEventListener("input", t => { t.target.value = u.formatMoneyInput(t.target.value) }), document.getElementById("ultimosDigitos")?.addEventListener("input", t => { t.target.value = String(t.target.value || "").replace(/\D/g, "").slice(0, 4) }), ["diaFechamento", "diaVencimento"].forEach(t => { document.getElementById(t)?.addEventListener("input", o => { o.target.value = u.normalizeDayValue(o.target.value) }) }), document.addEventListener("keydown", t => { const o = document.getElementById("modalCartaoOverlay"); t.key === "Escape" && o?.classList.contains("active") && u.closeModal() }), document.getElementById("formCartao")?.addEventListener("submit", t => { t.preventDefault(), g.API.saveCartao() }), document.getElementById("cartaoLembreteAviso")?.addEventListener("change", () => { u.syncReminderChannels() }), document.getElementById("btnReload")?.addEventListener("click", () => { g.API.loadCartoes() }); const a = document.getElementById("searchCartoes"); a && a.addEventListener("input", s.debounce(t => { c.searchTerm = String(t.target.value || "").trim().toLowerCase(), u.filterCartoes() }, 250)), document.querySelectorAll(".filter-btn:not(.btn-clear-filters)").forEach(t => { t.addEventListener("click", o => { const r = o.currentTarget; c.currentFilter = r.dataset.filter || "all", u.filterCartoes() }) }), document.getElementById("btnLimparFiltrosCartoes")?.addEventListener("click", () => { u.clearFilters() }), document.querySelectorAll(".view-btn").forEach(t => { t.addEventListener("click", o => { const r = o.currentTarget; c.currentView = r.dataset.view || "grid", u.updateView() }) }), document.getElementById("btnExportar")?.addEventListener("click", () => { u.exportarRelatorio() }), u.syncReminderChannels(), u.updateClearButtons() }, restoreViewPreference() { const e = localStorage.getItem("cartoes_view_mode"); (e === "grid" || e === "list") && (c.currentView = e), u.updateView() }, formatMoneyInput(e) { const a = String(e || "").replace(/[^\d]/g, ""); return ((parseInt(a, 10) || 0) / 100).toFixed(2).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".") }, formatMoneyValue(e) { return (Number(e) || 0).toFixed(2).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".") }, normalizeDayValue(e) { let a = String(e || "").replace(/\D/g, "").slice(0, 2); return a && parseInt(a, 10) > 31 && (a = "31"), a }, setScrollLock(e) { const a = e ? "hidden" : ""; document.body.style.overflow = a, document.documentElement.style.overflow = a }, syncReminderChannels() { const e = document.getElementById("cartaoLembreteAviso"), a = document.getElementById("cartaoCanaisLembrete"); if (!e || !a) return; const t = !!e.value; if (a.style.display = t ? "block" : "none", !t) return; const o = document.getElementById("cartaoCanalInapp"), r = document.getElementById("cartaoCanalEmail"); o && r && !o.checked && !r.checked && (o.checked = !0) }, clearFilters() { const e = document.getElementById("searchCartoes"); e && (e.value = ""), c.searchTerm = "", c.currentFilter = "all", u.filterCartoes() }, updateClearButtons() { const e = A(), a = document.getElementById("btnLimparFiltrosCartoes"), t = document.getElementById("btnLimparFiltrosEmpty"); a && (a.style.display = e ? "" : "none"), t && (t.style.display = e ? "" : "none") }, filterCartoes() { const e = c.searchTerm; c.filteredCartoes = c.cartoes.filter(a => { const t = String(a.nome_cartao || a.nome || "").toLowerCase(), o = String(a.ultimos_digitos || "").toLowerCase(), r = String(a.conta?.nome || "").toLowerCase(), n = String(a.conta?.instituicao_financeira?.nome || "").toLowerCase(), l = !e || t.includes(e) || o.includes(e) || r.includes(e) || n.includes(e), d = c.currentFilter === "all" || String(a.bandeira || "").toLowerCase() === c.currentFilter; return l && d }), u.renderCartoes(), u.renderFilterSummary(), u.updateClearButtons() }, renderCartoes() { const e = document.getElementById("cartoesGrid"), a = document.getElementById("emptyState"); if (!(!e || !a)) { if (e.setAttribute("aria-busy", "false"), u.updateEmptyState(), c.filteredCartoes.length === 0) { e.innerHTML = "", a.style.display = "block", I(); return } a.style.display = "none", e.innerHTML = c.filteredCartoes.map(t => u.createCardHTML(t)).join(""), u.updateView(), u.setupCardActions(), I() } }, updateEmptyState() { const e = document.getElementById("emptyState"), a = e?.querySelector("h3"), t = e?.querySelector("p"), o = document.getElementById("btnLimparFiltrosEmpty"); if (!(!e || !a || !t || !o)) { if (A()) { a.textContent = "Nenhum cartao encontrado", t.textContent = "Revise a busca ou limpe os filtros para voltar a ver os cartoes ativos.", o.style.display = ""; return } a.textContent = "Nenhum cartao cadastrado", t.textContent = "Adicione seu primeiro cartao para acompanhar limite, vencimentos e faturas em tempo real.", o.style.display = "none" } }, createCardHTML(e) {
        const a = parseFloat(e.limite_total) || 0, t = parseFloat(e.limite_disponivel_real ?? e.limite_disponivel) || 0, o = parseFloat(e.limite_utilizado) || Math.max(0, a - t), r = T(e.percentual_uso ?? (a > 0 ? o / a * 100 : 0), 0, 100), n = T(100 - r, 0, 100), l = s.getBrandIcon(e.bandeira), d = P(e) || s.getDefaultColor(e.bandeira), i = r >= 90 ? "is-danger" : r >= 70 ? "is-warning" : "is-safe", y = _(e.conta?.nome, "Conta nao vinculada"), h = _(e.conta?.instituicao_financeira?.nome, "Sem instituicao"), v = _(e.nome_cartao || e.nome, "Cartao"), C = _(e.bandeira, "Cartao"), E = e.dia_fechamento ? `Dia ${e.dia_fechamento}` : "Nao informado", f = e.dia_vencimento ? `Dia ${e.dia_vencimento}` : "Nao informado"; return `
            <div
                class="credit-card"
                data-id="${e.id}"
                data-brand="${String(e.bandeira || "outros").toLowerCase()}"
                style="background: ${d};"
                tabindex="0"
                role="button"
                aria-label="Abrir detalhes do cartao ${v}"
            >
                ${e.temFaturaPendente ? `
                    <div class="card-badge-fatura" title="Fatura pendente">
                        <i data-lucide="circle-alert"></i>
                        Fatura pendente
                    </div>
                `: ""}

                <div class="card-header">
                    <div class="card-brand">
                        <img
                            src="${l}"
                            alt="${C}"
                            class="brand-logo"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';"
                        >
                        <i class="brand-icon-fallback" data-lucide="credit-card" style="display: none;" aria-hidden="true"></i>
                        <div class="card-brand-copy">
                            <span class="card-name">${v}</span>
                            <span class="card-institution">${h}</span>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button
                            type="button"
                            class="lk-info"
                            data-lk-tooltip-title="Exclusao de cartoes"
                            data-lk-tooltip="Para evitar perda de historico e faturas, cartoes so podem ser excluidos apos serem arquivados."
                            aria-label="Ajuda: Exclusao de cartoes"
                        >
                            <i data-lucide="info" aria-hidden="true"></i>
                        </button>

                        <button
                            type="button"
                            class="card-action-btn"
                            onclick="cartoesManager.verFatura(${e.id})"
                            title="Ver fatura"
                        >
                            <i data-lucide="file-text" aria-hidden="true"></i>
                        </button>

                        <button
                            type="button"
                            class="card-action-btn"
                            onclick="cartoesManager.editCartao(${e.id})"
                            title="Editar"
                        >
                            <i data-lucide="pencil" aria-hidden="true"></i>
                        </button>

                        <button
                            type="button"
                            class="card-action-btn"
                            onclick="cartoesManager.arquivarCartao(${e.id})"
                            title="Arquivar"
                        >
                            <i data-lucide="archive" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="card-account">${y}</div>

                <div class="card-number">
                    •••• •••• •••• ${_(e.ultimos_digitos, "0000")}
                </div>

                <div class="card-metrics">
                    <div class="card-metric">
                        <div class="card-label">Fechamento</div>
                        <div class="card-value">${E}</div>
                    </div>
                    <div class="card-metric">
                        <div class="card-label">Vencimento</div>
                        <div class="card-value">${f}</div>
                    </div>
                    <div class="card-metric ${i}">
                        <div class="card-label">Uso</div>
                        <div class="card-value">${r.toFixed(1)}%</div>
                    </div>
                </div>

                <div class="card-limit-summary">
                    <div class="card-limit-row">
                        <span class="card-label">Disponivel</span>
                        <span class="card-value">${s.formatMoney(t)}</span>
                    </div>
                    <div class="limit-bar">
                        <div class="limit-fill ${i}" style="width: ${n}%"></div>
                    </div>
                    <div class="limit-caption">Usado ${s.formatMoney(o)} de ${s.formatMoney(a)}</div>
                </div>
            </div>
        `}, updateStats() { const e = c.cartoes.reduce((a, t) => { const o = parseFloat(t.limite_total) || 0, r = parseFloat(t.limite_disponivel_real ?? t.limite_disponivel) || 0, n = parseFloat(t.limite_utilizado) || Math.max(0, o - r); return a.total += 1, a.limiteTotal += o, a.limiteDisponivel += r, a.limiteUtilizado += n, a }, { total: 0, limiteTotal: 0, limiteDisponivel: 0, limiteUtilizado: 0 }); document.getElementById("totalCartoes").textContent = String(e.total), document.getElementById("statLimiteTotal").textContent = s.formatMoney(e.limiteTotal), document.getElementById("limiteDisponivel").textContent = s.formatMoney(e.limiteDisponivel), document.getElementById("limiteUtilizado").textContent = s.formatMoney(e.limiteUtilizado), u.animateStats() }, animateStats() { document.querySelectorAll(".stat-card").forEach((e, a) => { e.style.animation = "none", setTimeout(() => { e.style.animation = "fadeIn 0.5s ease forwards" }, a * 100) }) }, renderFilterSummary() {
        const e = document.getElementById("cartoesFilterSummary"); if (!e) return; const a = c.cartoes.length, t = c.filteredCartoes.length, o = c.cartoes.filter(i => i.temFaturaPendente).length, r = c.cartoes.filter(i => T(i.percentual_uso) >= 80).length, n = c.lastLoadedAt ? new Date(c.lastLoadedAt).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" }) : null, l = A() ? `Mostrando ${t} de ${a} cartoes com os filtros atuais.` : a ? "Painel consolidado com limite, faturas e cartoes que pedem atencao." : "Cadastre seu primeiro cartao para acompanhar limite e vencimentos aqui.", d = [`<span class="cartoes-summary-pill neutral">${t} visiveis</span>`]; c.currentFilter !== "all" && d.push(`<span class="cartoes-summary-pill accent">Bandeira: ${_(N[c.currentFilter] || c.currentFilter)}</span>`), c.searchTerm && d.push(`<span class="cartoes-summary-pill info">Busca: ${_(c.searchTerm)}</span>`), A() || (d.push(`<span class="cartoes-summary-pill ${o ? "warning" : "success"}">${o} com fatura pendente</span>`), d.push(`<span class="cartoes-summary-pill ${r ? "danger" : "success"}">${r} com uso alto</span>`)), n && d.push(`<span class="cartoes-summary-pill subtle">Atualizado as ${_(n)}</span>`), e.innerHTML = `
            <div class="cartoes-summary-row">
                <div class="cartoes-summary-copy">
                    <i data-lucide="${A() ? "filter" : "sparkles"}"></i>
                    <span>${l}</span>
                </div>
                <div class="cartoes-summary-pills">
                    ${d.join("")}
                </div>
            </div>
        `, I()
    }, updateView() { const e = document.getElementById("cartoesGrid"); e && (e.classList.toggle("list-view", c.currentView === "list"), document.querySelectorAll(".view-btn").forEach(a => { a.classList.toggle("active", a.dataset.view === c.currentView) }), localStorage.setItem("cartoes_view_mode", c.currentView), u.renderFilterSummary()) }, setModalSubmitState(e, a = !1) { const t = document.getElementById("btnSalvarCartao"), o = document.getElementById("cartaoSubmitLabel"); if (!t || !o) return; t.disabled = e, t.setAttribute("aria-busy", e ? "true" : "false"), o.textContent = e ? a ? "Salvando alteracoes..." : "Salvando cartao..." : a ? "Salvar alteracoes" : "Salvar cartao"; const r = t.querySelector("[data-lucide], svg"); r?.getAttribute && (r.setAttribute("data-lucide", e ? "loader-2" : "save"), r.classList.toggle("icon-spin", e)), I() }, async openModal(e = "create", a = null) { const t = document.getElementById("modalCartaoOverlay"), o = document.getElementById("modalCartao"), r = document.getElementById("formCartao"), n = document.getElementById("modalCartaoTitulo"), l = document.getElementById("modalCartaoSubtitle"), d = o?.querySelector(".modal-header"); if (!t || !o || !r || !n || !l) return; if (typeof e != "string") { const h = c.cartoes.find(v => v.id === Number(e)); h ? (a = h, e = "edit") : e = "create" } r.reset(), document.getElementById("cartaoId").value = "", document.getElementById("limiteTotal").value = "0,00", document.getElementById("contaVinculada").value = "", document.getElementById("cartaoCanalInapp").checked = !0, document.getElementById("cartaoCanalEmail").checked = !1, u.syncReminderChannels(); const i = await g.API.loadContasSelect(), y = e === "edit" && !!a; y && a ? (n.textContent = "Editar cartao de credito", l.textContent = "Revise os dados e ajuste limite, vencimento ou conta vinculada.", document.getElementById("cartaoId").value = a.id, document.getElementById("nomeCartao").value = a.nome_cartao || "", document.getElementById("contaVinculada").value = a.conta_id || "", document.getElementById("bandeira").value = a.bandeira || "", document.getElementById("ultimosDigitos").value = a.ultimos_digitos || "", document.getElementById("limiteTotal").value = u.formatMoneyValue(a.limite_total || 0), document.getElementById("diaFechamento").value = a.dia_fechamento || "", document.getElementById("diaVencimento").value = a.dia_vencimento || "", document.getElementById("cartaoLembreteAviso").value = a.lembrar_fatura_antes_segundos || "", document.getElementById("cartaoCanalInapp").checked = a.fatura_canal_inapp !== !1 && a.fatura_canal_inapp !== 0, document.getElementById("cartaoCanalEmail").checked = !!a.fatura_canal_email, d && (d.style.background = P(a))) : (n.textContent = "Novo cartao de credito", l.textContent = i ? "Cadastre o cartao e vincule a conta usada para pagar a fatura." : "Antes de cadastrar um cartao, você precisa ter ao menos uma conta.", d && (d.style.background = "")), u.syncReminderChannels(), u.setModalSubmitState(!1, y), t.classList.add("active"), u.setScrollLock(!0), setTimeout(() => { document.getElementById(i ? "nomeCartao" : "contaVinculada")?.focus() }, 80) }, closeModal() { const e = document.getElementById("modalCartaoOverlay"); if (!e) return; e.classList.remove("active"), u.setScrollLock(!1); const a = document.querySelector("#modalCartao .modal-header"); a && (a.style.background = ""), c.isSaving = !1, u.setModalSubmitState(!1, !1), setTimeout(() => { document.getElementById("formCartao")?.reset(), document.getElementById("cartaoId").value = "", document.getElementById("limiteTotal").value = "0,00", u.syncReminderChannels() }, 180) }, setupCardActions() { document.querySelectorAll(".credit-card").forEach(e => { e.addEventListener("click", a => { if (a.target.closest(".card-action-btn, .lk-info")) return; const t = parseInt(e.dataset.id, 10); Number.isFinite(t) && u.showCardDetails(t) }), e.addEventListener("keydown", a => { if (a.key !== "Enter" && a.key !== " ") return; a.preventDefault(); const t = parseInt(e.dataset.id, 10); Number.isFinite(t) && u.showCardDetails(t) }) }) }, async showCardDetails(e) { const a = c.cartoes.find(t => t.id === e); if (a) { if (window.LK_CardDetail?.open) { window.LK_CardDetail.open(e, a.nome_cartao || a.nome || "Cartao", P(a), H()); return } g.Fatura?.verFatura?.(e) } }, async exportarRelatorio() { if (!c.filteredCartoes?.length) { typeof Swal < "u" && Swal.fire({ toast: !0, position: "top-end", icon: "info", title: "Nenhum cartao para exportar", text: "Adicione cartoes ou altere os filtros.", showConfirmButton: !1, timer: 3e3, timerProgressBar: !0 }); return } try { const { jsPDF: e } = window.jspdf, a = new e, t = new Date, o = t.toLocaleDateString("pt-BR", { month: "long", year: "numeric" }), r = c.filteredCartoes.reduce((f, S) => f + parseFloat(S.limite_total || 0), 0), n = c.filteredCartoes.reduce((f, S) => f + parseFloat((S.limite_disponivel_real ?? S.limite_disponivel) || 0), 0), l = r - n, d = r > 0 ? (l / r * 100).toFixed(1) : 0, i = [230, 126, 34], y = [26, 31, 46], h = [248, 249, 250]; a.setFillColor(...i), a.rect(0, 0, 210, 35, "F"), a.setTextColor(255, 255, 255), a.setFontSize(22), a.setFont(void 0, "bold"), a.text("RELATORIO DE CARTOES DE CREDITO", 105, 15, { align: "center" }), a.setFontSize(10), a.setFont(void 0, "normal"), a.text(`Periodo: ${o}`, 105, 22, { align: "center" }), a.text(`Gerado em: ${t.toLocaleDateString("pt-BR")} as ${t.toLocaleTimeString("pt-BR")}`, 105, 28, { align: "center" }); let v = 45; a.setTextColor(...y), a.setFontSize(14), a.setFont(void 0, "bold"), a.text("RESUMO FINANCEIRO", 14, v), v += 8, a.autoTable({ startY: v, head: [["Indicador", "Valor"]], body: [["Total de Cartoes", c.filteredCartoes.length.toString()], ["Limite Total Combinado", s.formatMoney(r)], ["Limite Utilizado", s.formatMoney(l)], ["Limite Disponivel", s.formatMoney(n)], ["Percentual de Utilizacao", `${d}%`]], theme: "grid", headStyles: { fillColor: i, textColor: [255, 255, 255], fontStyle: "bold", halign: "left" }, columnStyles: { 0: { cellWidth: 100, fontStyle: "bold" }, 1: { cellWidth: 86, halign: "right" } }, styles: { fontSize: 10, cellPadding: 5 }, alternateRowStyles: { fillColor: h } }), v = a.lastAutoTable.finalY + 15, a.setFontSize(14), a.setFont(void 0, "bold"), a.text("DETALHAMENTO POR CARTAO", 14, v), v += 5; const C = c.filteredCartoes.map(f => { const S = f.limite_disponivel_real ?? f.limite_disponivel ?? 0, B = f.limite_total > 0 ? ((f.limite_total - S) / f.limite_total * 100).toFixed(1) : 0; return [f.nome_cartao, s.formatBandeira(f.bandeira), `**** ${f.ultimos_digitos}`, s.formatMoney(f.limite_total), s.formatMoney(S), `${B}%`, f.ativo ? "Ativo" : "Inativo"] }); a.autoTable({ startY: v, head: [["Cartao", "Bandeira", "Final", "Limite Total", "Disponivel", "Uso", "Status"]], body: C, theme: "grid", headStyles: { fillColor: i, textColor: [255, 255, 255], fontStyle: "bold", halign: "center" }, columnStyles: { 0: { cellWidth: 40 }, 1: { cellWidth: 25, halign: "center" }, 2: { cellWidth: 25, halign: "center" }, 3: { cellWidth: 28, halign: "right" }, 4: { cellWidth: 28, halign: "right" }, 5: { cellWidth: 18, halign: "center" }, 6: { cellWidth: 22, halign: "center" } }, styles: { fontSize: 9, cellPadding: 4 }, alternateRowStyles: { fillColor: h } }); const E = a.internal.getNumberOfPages(); for (let f = 1; f <= E; f++)a.setPage(f), a.setFontSize(8), a.setTextColor(128, 128, 128), a.text(`Pagina ${f} de ${E} | Lukrato - Sistema de Gestao Financeira`, 105, 287, { align: "center" }); a.save(`relatorio_cartoes_${t.toISOString().split("T")[0]}.pdf`), s.showToast("success", "Relatorio exportado com sucesso") } catch (e) { console.error("Erro ao exportar:", e), s.showToast("error", "Erro ao exportar relatorio") } }
}; g.UI = u; const m = {
    verFatura(e, a = null, t = null) { const o = new Date; a = a || o.getMonth() + 1, t = t || o.getFullYear(), window.location.href = `${p.BASE_URL}faturas?cartao_id=${e}&mes=${a}&ano=${t}` }, mostrarModalFatura(e, a = null, t = null, o = null) { const r = document.querySelector(".modal-fatura-overlay"); r && r.remove(); const n = m.criarModalFatura(e, a, t, o); document.body.appendChild(n), I(), setTimeout(() => { n.classList.add("show") }, 10), n.addEventListener("click", l => { l.target === n && m.fecharModalFatura(n) }), n.querySelector(".btn-fechar-fatura")?.addEventListener("click", () => { m.fecharModalFatura(n) }), requestAnimationFrame(() => { m.setupParcelaSelection(n, e) }), n.querySelector(".btn-pagar-fatura")?.addEventListener("click", () => { m.pagarParcelasSelecionadas(e) }) }, setupParcelaSelection(e, a) { const t = e.querySelector("#selectAllParcelas"), o = e.querySelectorAll(".parcela-checkbox"), r = e.querySelector("#totalSelecionado"); if (e.dataset.parcelasConfigured === "true") return; e.dataset.parcelasConfigured = "true"; const n = () => { let l = 0; o.forEach(d => { d.checked && (l += parseFloat(d.dataset.valor)) }), r && (r.textContent = s.formatMoney(l)) }; t && t.addEventListener("change", l => { o.forEach(d => { d.checked = l.target.checked }), n() }), o.forEach(l => { l.addEventListener("change", () => { if (n(), t) { const d = Array.from(o).every(i => i.checked); t.checked = d } }) }), n() }, async pagarParcelasSelecionadas(e) { const a = document.querySelectorAll(".parcela-checkbox:checked"); if (a.forEach((r, n) => { }), a.length === 0) { await Swal.fire({ icon: "warning", title: "Atenção", text: "Selecione pelo menos uma parcela para pagar." }); return } let t = 0; a.forEach(r => { const n = parseFloat(r.dataset.valor); t += n }), await s.showConfirmDialog("Confirmar Pagamento", `Deseja pagar ${a.length} parcela(s) no valor total de ${s.formatMoney(t)}?`) && await g.API.pagarParcelasIndividuais(a, e) }, criarModalFatura(e, a = null, t = null, o = null) { const r = s.resolverCorCartao(e, o), n = document.createElement("div"); return n.className = "modal-fatura-overlay", n.innerHTML = `<div class="modal-fatura-container" style="--card-accent: ${r};">${m.criarConteudoModal(e, a, t, o)}</div>`, n }, criarConteudoModal(e, a = null, t = null, o = null) {
        const r = o || e.cartao_id || e.cartao?.id; if (t && t.pago) return m.criarConteudoModalFaturaPaga(e, t, a, r); const n = (e.itens || []).filter(i => !i.pago).length, l = (e.itens || []).filter(i => i.pago).length, d = e.cartao?.bandeira ? s.getBrandIcon(e.cartao.bandeira) : null; return `
                <div class="modal-fatura-header">
                    <div class="header-top-row">
                        <div class="header-card-identity">
                            ${d ? `<img src="${d}" alt="${e.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">` : ""}
                            <div class="header-card-text">
                                <span class="cartao-nome">${e.cartao.nome}</span>
                                <span class="cartao-numero">•••• ${e.cartao.ultimos_digitos}</span>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button class="btn-historico-toggle" onclick="cartoesManager.toggleHistoricoFatura(${r})" title="Ver histórico">
                                <i data-lucide="history"></i>
                            </button>
                            <button class="btn-fechar-fatura" title="Fechar">
                                <i data-lucide="x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="header-nav-row">
                        <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${r}, ${e.mes}, ${e.ano}, -1)" title="Mês anterior">
                            <i data-lucide="chevron-left"></i>
                        </button>
                        <span class="fatura-periodo">${s.getNomeMes(e.mes)} ${e.ano}</span>
                        <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${r}, ${e.mes}, ${e.ano}, 1)" title="Próximo mês">
                            <i data-lucide="chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-fatura-body">
                    ${n === 0 && l === 0 ? `
                        <div class="fatura-empty">
                            <div class="empty-icon-wrap">
                                <i data-lucide="inbox"></i>
                            </div>
                            <h3>Nenhum lançamento</h3>
                            <p>Não há compras registradas neste mês.</p>
                        </div>
                    `: n === 0 && l > 0 ? `
                        <!-- Todas as parcelas já foram pagas -->
                        <div class="fatura-totalmente-paga">
                            <div class="status-paga-header">
                                <div class="status-paga-icon"><i data-lucide="circle-check"></i></div>
                                <h3>Fatura Paga</h3>
                                <p>Todos os lançamentos deste mês foram pagos</p>
                            </div>

                            <div class="fatura-parcelas-pagas-completa">
                                <div class="secao-titulo-bar">
                                    <span class="secao-titulo-text"><i data-lucide="receipt"></i> Itens Pagos</span>
                                    <span class="secao-titulo-count">${l}</span>
                                </div>
                                <div class="lancamentos-lista">
                                    ${(e.itens || []).filter(i => i.pago).map(i => m.renderItemPago(i)).join("")}
                                </div>
                            </div>
                        </div>
                    `: `
                        <div class="fatura-resumo-principal">
                            <div class="resumo-item resumo-valor-principal">
                                <span class="resumo-label">Total a pagar</span>
                                <strong class="resumo-valor">${s.formatMoney(e.total)}</strong>
                            </div>
                            <div class="resumo-item resumo-vencimento">
                                <span class="resumo-label">Vencimento</span>
                                <strong class="resumo-data">${s.formatDate(e.vencimento)}</strong>
                            </div>
                        </div>

                        <div class="fatura-parcelas">
                            <div class="secao-titulo-bar">
                                <label class="checkbox-custom secao-titulo-check">
                                    <input type="checkbox" id="selectAllParcelas">
                                    <span class="checkmark"></span>
                                    <span class="secao-titulo-text">Pendentes</span>
                                </label>
                                <span class="secao-titulo-count">${n}</span>
                            </div>
                            <div class="lancamentos-lista">
                                ${(e.itens || []).filter(i => !i.pago).map(i => `
                                    <div class="lancamento-item">
                                        <label class="checkbox-custom">
                                            <input type="checkbox" class="parcela-checkbox" data-id="${i.id}" data-valor="${i.valor}">
                                            <span class="checkmark"></span>
                                        </label>
                                        <div class="lanc-info">
                                            <span class="lanc-desc">
                                                ${s.escapeHtml(i.descricao)}
                                                ${m.renderBadgeRecorrente(i)}
                                            </span>
                                            ${i.data_compra ? `<span class="lanc-data-compra"><i data-lucide="shopping-cart"></i> ${s.formatDate(i.data_compra)}</span>` : ""}
                                        </div>
                                        <span class="lanc-valor">${s.formatMoney(i.valor)}</span>
                                    </div>
                                `).join("")}
                            </div>
                        </div>

                        ${l > 0 ? `
                            <div class="fatura-parcelas-pagas">
                                <div class="secao-titulo-bar">
                                    <span class="secao-titulo-text"><i data-lucide="circle-check"></i> Pagos</span>
                                    <span class="secao-titulo-count">${l}</span>
                                </div>
                                <div class="lancamentos-lista">
                                    ${(e.itens || []).filter(i => i.pago).map(i => m.renderItemPago(i)).join("")}
                                </div>
                            </div>
                        `: ""}
                    `}
                </div>

                ${n > 0 ? `
                    <div class="modal-fatura-footer">
                        <div class="footer-info">
                            <span class="footer-label">Total selecionado</span>
                            <strong class="footer-valor" id="totalSelecionado">${s.formatMoney(e.total)}</strong>
                        </div>
                        <button class="btn btn-primary btn-pagar-fatura" id="btnPagarSelecionadas">
                            <i data-lucide="check-circle"></i>
                            Pagar Selecionadas
                        </button>
                    </div>
                `: ""}
        `}, renderItemPago(e) {
        return `
            <div class="lancamento-item lancamento-pago">
                <div class="lanc-info">
                    <span class="lanc-desc">
                        ${s.escapeHtml(e.descricao)}
                        ${m.renderBadgeRecorrente(e)}
                    </span>
                    ${e.data_compra ? `<span class="lanc-data-compra"><i data-lucide="shopping-cart"></i> ${s.formatDate(e.data_compra)}</span>` : ""}
                    <span class="lanc-data-pagamento">
                        <i data-lucide="calendar-check"></i>
                        Pago em ${s.formatDate(e.data_pagamento || e.data)}
                    </span>
                </div>
                <div class="lanc-right">
                    <span class="lanc-valor">${s.formatMoney(e.valor)}</span>
                    <button class="btn-desfazer-parcela" 
                        onclick="cartoesManager.desfazerPagamentoParcela(${e.id})"
                        title="Desfazer pagamento desta parcela">
                        <i data-lucide="undo-2"></i>
                        Desfazer
                    </button>
                </div>
            </div>
        `}, renderBadgeRecorrente(e) { if (!e.recorrente) return ""; const a = s.getFreqLabel(e.recorrencia_freq); return `<span class="badge-recorrente" title="Assinatura ${a.toLowerCase()}"><i data-lucide="refresh-cw"></i> ${a}</span>` }, fecharModalFatura(e) { e.classList.remove("show"), setTimeout(() => { e.remove() }, 300) }, async pagarFatura(e) {
        if (!await s.showConfirmDialog("Confirmar Pagamento", `Deseja pagar a fatura de ${s.formatMoney(e.total)}?

Esta ação criará um lançamento de despesa na conta vinculada e liberará o limite do cartão.`, "Sim, Pagar")) return; const t = document.querySelector(".btn-pagar-fatura"), o = t ? t.innerHTML : ""; try { t && (t.disabled = !0, t.innerHTML = '<i data-lucide="loader-2" class="icon-spin"></i> Processando...', I(), t.style.opacity = "0.6", t.style.cursor = "not-allowed"); const r = await q(`${p.API_URL}/cartoes/${e.cartao.id}/fatura/pagar`, { mes: e.mes, ano: e.ano }), n = b(r, null); n?.gamification?.achievements && Array.isArray(n.gamification.achievements) && (typeof window.notifyMultipleAchievements == "function" ? window.notifyMultipleAchievements(n.gamification.achievements) : console.error("❌ notifyMultipleAchievements não está disponível")), s.showToast("success", `Fatura paga com sucesso! ${n?.itens_pagos ?? ""} parcela(s) quitada(s).`); const l = document.querySelector(".modal-fatura-overlay"); l && m.fecharModalFatura(l), g.API.loadCartoes() } catch (r) { console.error("❌ Erro ao pagar fatura:", r), t && (t.disabled = !1, t.innerHTML = o, t.style.opacity = "1", t.style.cursor = "pointer"), s.showToast("error", F(r, "Erro ao pagar fatura")) }
    }, criarConteudoModalFaturaPaga(e, a, t, o) {
        const r = o || e.cartao_id || e.cartao?.id, n = (e.itens || []).filter(i => i.pago).length, l = e.cartao?.bandeira ? s.getBrandIcon(e.cartao.bandeira) : null, d = a?.data_pagamento || (e.itens || []).find(i => i.pago && i.data_pagamento)?.data_pagamento || null; return `
            <div class="modal-fatura-header modal-fatura-header--paga">
                <div class="header-top-row">
                    <div class="header-card-identity">
                        ${l ? `<img src="${l}" alt="${e.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">` : ""}
                        <div class="header-card-text">
                            <span class="cartao-nome">${e.cartao.nome}</span>
                            <span class="cartao-numero">•••• ${e.cartao.ultimos_digitos}</span>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn-fechar-fatura" title="Fechar">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
                <div class="header-nav-row">
                    <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${r}, ${e.mes}, ${e.ano}, -1)" title="Mês anterior">
                        <i data-lucide="chevron-left"></i>
                    </button>
                    <span class="fatura-periodo">${s.getNomeMes(e.mes)} ${e.ano}</span>
                    <button class="btn-nav-mes" onclick="cartoesManager.navegarMes(${r}, ${e.mes}, ${e.ano}, 1)" title="Próximo mês">
                        <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="modal-fatura-body">
                <div class="fatura-totalmente-paga">
                    <div class="status-paga-header">
                        <div class="status-paga-icon"><i data-lucide="circle-check"></i></div>
                        <h3>Fatura Paga</h3>
                        <p>
                            ${d ? `Pago em ${s.formatDate(d)} &bull; ` : ""}
                            ${s.formatMoney(a.valor)}
                        </p>
                    </div>

                    <div class="fatura-parcelas-pagas-completa">
                        <div class="secao-titulo-bar">
                            <span class="secao-titulo-text"><i data-lucide="receipt"></i> Itens Pagos</span>
                            <div class="secao-titulo-right">
                                <span class="secao-titulo-count">${n}</span>
                                <button class="btn-desfazer-todas" 
                                    onclick="cartoesManager.desfazerPagamento(${r}, ${e.mes}, ${e.ano})"
                                    title="Desfazer pagamento de todas as parcelas">
                                    <i data-lucide="undo-2"></i>
                                    Desfazer Todas
                                </button>
                            </div>
                        </div>
                        <div class="lancamentos-lista">
                            ${(e.itens || []).filter(i => i.pago).map(i => m.renderItemPago(i)).join("")}
                        </div>
                    </div>
                </div>
            </div>
        `}, async navegarMes(e, a, t, o) { let r = a + o, n = t; r > 12 ? (r = 1, n++) : r < 1 && (r = 12, n--); try { const [l, d, i] = await Promise.all([M(`${p.API_URL}/cartoes/${e}/fatura`, { mes: r, ano: n }).catch(() => null), M(`${p.API_URL}/cartoes/${e}/parcelamentos-resumo`, { mes: r, ano: n }).catch(() => null), M(`${p.API_URL}/cartoes/${e}/fatura/status`, { mes: r, ano: n }).catch(() => null)]); if (!l) throw new Error("Erro ao carregar fatura"); const y = l.data || l; let h = null, v = null; d && (h = d.data || d), i && (v = i.data || i); const C = document.querySelector(".modal-fatura-container"); if (C) { const E = s.resolverCorCartao(y, e); C.style.setProperty("--card-accent", E); const f = m.criarConteudoModal(y, h, v, e); C.innerHTML = f, I(), C.querySelector(".btn-fechar-fatura")?.addEventListener("click", () => { const B = document.querySelector(".modal-fatura-overlay"); m.fecharModalFatura(B) }), C.querySelector(".btn-pagar-fatura")?.addEventListener("click", () => { m.pagarParcelasSelecionadas(y) }); const S = document.querySelector(".modal-fatura-overlay"); requestAnimationFrame(() => { m.setupParcelaSelection(S, y) }) } } catch (l) { console.error("❌ Erro ao navegar entre meses:", l), s.showToast("error", F(l, "Erro ao carregar fatura")) } }, async toggleHistoricoFatura(e) { try { const a = document.querySelector(".modal-fatura-container"); if (!a) return; if (a.querySelector(".historico-faturas")) { const o = new Date, r = o.getMonth() + 1, n = o.getFullYear(), [l, d, i] = await Promise.all([g.API.carregarFatura(e, r, n), g.API.carregarParcelamentosResumo(e, r, n).catch(() => null), M(`${p.API_URL}/cartoes/${e}/fatura/status`, { mes: r, ano: n }).then(h => b(h, null)).catch(() => null)]), y = m.criarConteudoModal(l, d, i, e); a.innerHTML = y, I(), m.adicionarEventListenersModal(l) } else { const o = await g.API.carregarHistoricoFaturas(e), r = m.criarConteudoHistorico(o, e); a.innerHTML = r, I(), m.adicionarEventListenersModal(null) } } catch (a) { console.error("❌ Erro ao alternar histórico:", a), s.showToast("error", "Erro ao carregar histórico") } }, criarConteudoHistorico(e, a) {
        return `
            <div class="modal-fatura-header">
                <div class="header-info">
                    <div class="cartao-info">
                        <span class="cartao-nome">${e.cartao.nome}</span>
                        <span class="cartao-subtitulo">Histórico de Faturas Pagas</span>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn-historico-toggle" onclick="cartoesManager.toggleHistoricoFatura(${a})" title="Voltar para fatura atual">
                        <i data-lucide="arrow-left"></i>
                    </button>
                    <button class="btn-fechar-fatura" title="Fechar">
                        <i data-lucide="x"></i>
                    </button>
                </div>
            </div>

            <div class="modal-fatura-body historico-faturas">
                ${e.historico.length === 0 ? `
                    <div class="fatura-empty">
                        <i data-lucide="receipt"></i>
                        <h3>Nenhuma fatura paga</h3>
                        <p>Você ainda não pagou nenhuma fatura neste cartão.</p>
                    </div>
                `: `
                    <div class="historico-lista">
                        ${e.historico.map(t => `
                            <div class="historico-item">
                                <div class="historico-periodo">
                                    <i data-lucide="calendar-check"></i>
                                    <div class="periodo-info">
                                        <strong>${t.mes_nome} ${t.ano}</strong>
                                        <span class="historico-data-pag">Pago em ${s.formatDate(t.data_pagamento)}</span>
                                    </div>
                                </div>
                                <div class="historico-detalhes">
                                    <div class="historico-valor">
                                        ${s.formatMoney(t.total)}
                                    </div>
                                    <div class="historico-qtd">
                                        ${t.quantidade_lancamentos} lançamento${t.quantidade_lancamentos !== 1 ? "s" : ""}
                                    </div>
                                </div>
                            </div>
                        `).join("")}
                    </div>
                `}
            </div>
        `}, adicionarEventListenersModal(e) { const a = document.querySelector(".modal-fatura-container"); a && (a.querySelector(".btn-fechar-fatura")?.addEventListener("click", () => { const t = document.querySelector(".modal-fatura-overlay"); m.fecharModalFatura(t) }), e && a.querySelector(".btn-pagar-fatura")?.addEventListener("click", () => { m.pagarFatura(e) })) }
}; g.Fatura = m; const V = async () => { u.setupEventListeners(), u.restoreViewPreference(), await g.API.loadCartoes() }; window.cartoesManager = { openModal: (e = "create", a = null) => u.openModal(e, a), closeModal: () => u.closeModal(), editCartao: e => g.API.editCartao(e), arquivarCartao: e => g.API.arquivarCartao(e), deleteCartao: e => g.API.deleteCartao(e), exportarRelatorio: () => u.exportarRelatorio(), mostrarModalFatura: (e, a, t) => m.mostrarModalFatura(e, a, t), verFatura: e => m.verFatura(e), fecharModalFatura: () => m.fecharModalFatura(), navegarMes: (e, a, t, o) => m.navegarMes(e, a, t, o), pagarFatura: (e, a, t) => m.pagarFatura(e, a, t), pagarParcelasSelecionadas: (e, a) => m.pagarParcelasSelecionadas(e, a), toggleHistoricoFatura: e => m.toggleHistoricoFatura(e), dismissAlerta: e => g.API.dismissAlerta(e), loadCartoes: () => g.API.loadCartoes(), desfazerPagamento: (e, a, t) => g.API.desfazerPagamento(e, a, t), desfazerPagamentoParcela: e => g.API.desfazerPagamentoParcela(e) }; window.__CARTOES_MANAGER_INITIALIZED__ || (window.__CARTOES_MANAGER_INITIALIZED__ = !0, document.addEventListener("DOMContentLoaded", () => V()));
