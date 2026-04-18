import{h as Y,b as W,g as X,c as $,d as E,e as y}from"./api-Bz3e_1Ao.js";import{b as Q,r as Z,a as ee}from"./finance-CgaDv1sH.js";import{r as ae,a as te,b as z,c as re,d as N,e as oe,f as R}from"./faturas-osFPSmt_.js";import{r as S}from"./ui-H2yoVZe7.js";import{c as se,p as ne,f as ie}from"./ui-preferences-CPWe3C-7.js";function le(e){return{house:"#f97316",utensils:"#ef4444",car:"#3b82f6",lightbulb:"#eab308","heart-pulse":"#ef4444","graduation-cap":"#6366f1",shirt:"#ec4899",clapperboard:"#a855f7","credit-card":"#0ea5e9",smartphone:"#6366f1","shopping-cart":"#f97316",coins:"#eab308",briefcase:"#3b82f6",laptop:"#06b6d4","trending-up":"#22c55e",gift:"#ec4899",banknote:"#22c55e",trophy:"#f59e0b",wallet:"#14b8a6",tag:"#94a3b8","pie-chart":"#8b5cf6","piggy-bank":"#ec4899",plane:"#0ea5e9","gamepad-2":"#a855f7",baby:"#f472b6",dog:"#92400e",wrench:"#64748b",church:"#6366f1",dumbbell:"#ef4444",music:"#a855f7","book-open":"#3b82f6",scissors:"#ec4899","building-2":"#64748b",landmark:"#3b82f6",receipt:"#14b8a6"}[e]||"#f97316"}const b={BASE_URL:Y(),ENDPOINTS:{parcelamentos:ae(),categorias:ee(),contas:Z(),cartoes:Q()},TIMEOUTS:{alert:5e3,successMessage:2e3}},s={};function q(e){const a=document.getElementById(e);return a?(window.LK?.modalSystem?.prepareBootstrapModal(a,{scope:"page"}),a):null}function ce(){s.loadingEl=document.getElementById("loadingParcelamentos"),s.containerEl=document.getElementById("parcelamentosContainer"),s.emptyStateEl=document.getElementById("emptyState"),s.detailPageEl=document.getElementById("faturaDetalhePage"),s.detailPageShell=document.getElementById("faturaDetalheShell"),s.detailPageLoading=document.getElementById("faturaDetalheLoading"),s.detailPageContent=document.getElementById("faturaDetalheContent"),s.detailPageTitle=document.getElementById("faturaDetalheTitle"),s.detailPageSubtitle=document.getElementById("faturaDetalheSubtitle"),s.filtroStatus=document.getElementById("filtroStatus"),s.filtroCartao=document.getElementById("filtroCartao"),s.filtroAno=document.getElementById("filtroAno"),s.filtroMes=document.getElementById("filtroMes"),s.btnFiltrar=document.getElementById("btnFiltrar"),s.btnLimparFiltros=document.getElementById("btnLimparFiltros"),s.filtersContainer=document.querySelector(".filters-modern"),s.filtersBody=document.getElementById("filtersBody"),s.toggleFilters=document.getElementById("toggleFilters"),s.activeFilters=document.getElementById("activeFilters"),s.modalPagarFatura=q("modalPagarFatura"),s.modalEditarItemFatura=q("modalEditarItemFatura")}const i={parcelamentos:[],cartoes:[],faturaAtual:null,currentDetailId:null,sortColumn:"data_compra",sortDirection:"asc",filtros:{status:"",cartao_id:"",ano:new Date().getFullYear(),mes:""},anosCarregados:!1},u={},c={formatMoney(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e||0)},formatDate(e){return e?new Date(e+"T00:00:00").toLocaleDateString("pt-BR"):""},parseMoney(e){return e&&parseFloat(e.replace(/[^\d,]/g,"").replace(",","."))||0},showAlert(e,a,t="danger"){e&&(e.className=`alert alert-${t}`,e.textContent=a,e.style.display="block",setTimeout(()=>{e.style.display="none"},b.TIMEOUTS.alert))},getCSRFToken(){return X()},escapeHtml(e){if(!e)return"";const a=document.createElement("div");return a.textContent=e,a.innerHTML},buildUrl(e,a={}){const t=e.startsWith("http")?e:b.BASE_URL+e.replace(/^\//,""),r=Object.entries(a).filter(([o,n])=>n!=null&&n!=="").map(([o,n])=>`${o}=${encodeURIComponent(n)}`);return r.length>0?`${t}?${r.join("&")}`:t},async apiRequest(e,a={}){const t=e.startsWith("http")?e:b.BASE_URL+e.replace(/^\//,"");try{return await W(t,{...a,headers:{"X-CSRF-Token":this.getCSRFToken(),...a.headers}})}catch(r){throw console.error("Erro na requisição:",r),r}},debounce(e,a){let t;return function(...o){const n=()=>{clearTimeout(t),e(...o)};clearTimeout(t),t=setTimeout(n,a)}},calcularDiferencaDias(e,a){const t=new Date(e+"T00:00:00"),r=new Date(a+"T00:00:00");return Math.floor((t-r)/(1e3*60*60*24))}},de={async listarParcelamentos(e={}){const a={status:e.status,cartao_id:e.cartao_id,ano:e.ano,mes:e.mes},t=c.buildUrl(b.ENDPOINTS.parcelamentos,a);return await c.apiRequest(t)},async listarCartoes(){return await c.apiRequest(b.ENDPOINTS.cartoes)},async buscarParcelamento(e){const a=parseInt(e,10);if(isNaN(a))throw new Error("ID inválido");return await c.apiRequest(R(a))},async criarParcelamento(e){return await c.apiRequest(b.ENDPOINTS.parcelamentos,{method:"POST",body:JSON.stringify(e)})},async cancelarParcelamento(e){return await c.apiRequest(R(e),{method:"DELETE"})},async toggleItemFatura(e,a,t){return await c.apiRequest(oe(e,a),{method:"POST",body:JSON.stringify({pago:t})})},async atualizarItemFatura(e,a,t){return await c.apiRequest(N(e,a),{method:"PUT",body:JSON.stringify(t)})},async excluirItemFatura(e,a){return await c.apiRequest(N(e,a),{method:"DELETE"})},async excluirParcelamentoDoItem(e,a){return await c.apiRequest(re(e,a),{method:"DELETE"})},async pagarFaturaCompleta(e,a,t,r=null){const o={mes:a,ano:t};return r&&(o.conta_id=r),await c.apiRequest(z(e),{method:"POST",body:JSON.stringify(o)})},async pagarFaturaParcial(e,a,t,r,o){return await c.apiRequest(z(e),{method:"POST",body:JSON.stringify({mes:a,ano:t,conta_id:r,valor_parcial:o})})},async desfazerPagamentoFatura(e,a,t){return await c.apiRequest(te(e),{method:"POST",body:JSON.stringify({mes:a,ano:t})})},async listarContas(){return await c.apiRequest(`${b.ENDPOINTS.contas}?with_balances=1`)}};u.API=de;const V=(e,a=0,t=100)=>Math.min(t,Math.max(a,Number(e)||0)),h=(e,a="")=>c.escapeHtml(String(e??a)),ue=(e,a=0)=>`${(Number(e)||0).toLocaleString("pt-BR",{minimumFractionDigits:a,maximumFractionDigits:a})}%`,_=(e,a)=>`data-lk-tooltip-title="${h(e)}" data-lk-tooltip="${h(a)}"`,U=/(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))/,me={renderParcelamentos(e){if(!Array.isArray(e)||e.length===0){this.showEmpty();return}s.emptyStateEl.style.display="none",s.containerEl.style.display="grid";const a=document.createDocumentFragment();e.forEach(t=>{const r=this.createParcelamentoCard(t);a.appendChild(r)}),s.containerEl.innerHTML="",s.containerEl.appendChild(a),S()},createParcelamentoCard(e){const a=e.progresso||0,t=e.parcelas_pendentes||0,r=e.parcelas_pagas||0,o=r+t,n=this.getDueMeta(e),l=this.getStatusMeta(e.status,a,n),d=document.createElement("div");d.className=`parcelamento-card surface-card surface-card--interactive surface-card--clip status-${e.status}`,d.dataset.id=e.id,d.style.setProperty("--fatura-accent",this.getAccentColorSolid(e.cartao));const m=this.getStatusBadge(e.status,a,n),p=e.mes_referencia||"",g=e.ano_referencia||"";return d.innerHTML=this.createCardHTML({parc:e,statusBadge:m,mes:p,ano:g,itensPendentes:t,itensPagos:r,totalItens:o,progresso:a,dueMeta:n,statusMeta:l}),d},attachCardEventListeners(){},getAccentColorSolid(e){const t={visa:"#1A1F71",mastercard:"#EB001B",elo:"#FFCB05",amex:"#006FCF",diners:"#0079BE",discover:"#FF6000",hipercard:"#B11116"}[e?.bandeira?.toLowerCase()]||"#3b82f6",r=String(e?.cor_cartao||e?.conta?.instituicao_financeira?.cor_primaria||t).trim();return r?/gradient/i.test(r)?r.match(U)?.[1]||t:/^var\(/i.test(r)||U.test(r)?r:t:t},getBandeiraIcon(e){return{visa:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71"/><text x="24" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">VISA</text></svg>',mastercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71" opacity="0"/><circle cx="19" cy="16" r="10" fill="#EB001B" opacity=".85"/><circle cx="29" cy="16" r="10" fill="#F79E1B" opacity=".85"/></svg>',elo:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#000"/><text x="24" y="20" text-anchor="middle" font-size="13" font-weight="bold" fill="#FFCB05" font-family="sans-serif">elo</text></svg>',amex:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#006FCF"/><text x="24" y="20" text-anchor="middle" font-size="9" font-weight="bold" fill="#fff" font-family="sans-serif">AMEX</text></svg>',hipercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#B11116"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">HIPER</text></svg>',diners:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#0079BE"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">DINERS</text></svg>'}[e]||'<i data-lucide="credit-card"></i>'},getDueMeta(e){let a=e.data_vencimento;if(!a&&e.cartao?.dia_vencimento&&e.descricao){const m=e.descricao.match(/(\d{1,2})\/(\d{4})/);if(m){const p=m[1].padStart(2,"0"),g=m[2],v=String(e.cartao.dia_vencimento).padStart(2,"0");a=`${g}-${p}-${v}`}}if(!a)return{hasDate:!1,label:"A definir",helper:"Sem data de vencimento informada",detailClass:"",isVencida:!1,isProxima:!1};const t=c.formatDate(a),r=new Date;r.setHours(0,0,0,0);const o=new Date(`${a}T00:00:00`),n=e.status!=="paga"&&e.status!=="concluido"&&e.status!=="cancelado",l=n&&o<r,d=n&&!l&&o-r<=4320*60*1e3;return{hasDate:!0,raw:a,label:t,helper:l?"Vencimento expirado":d?"Vence em breve":"Dentro do prazo",detailClass:l?"is-danger":d?"is-warning":"",isVencida:l,isProxima:d}},getStatusMeta(e,a=null,t=null){const r=V(a);return e==="cancelado"?{badgeClass:"badge-cancelado",progressClass:"is-muted",icon:"ban",label:"Cancelada",shortLabel:"Cancelada",hint:"Sem cobranca ativa",tooltip:"Esta fatura foi cancelada e nao entra mais no acompanhamento ativo."}:r>=100||e==="paga"||e==="concluido"?{badgeClass:"badge-paga",progressClass:"is-safe",icon:"circle-check",label:"Paga",shortLabel:"Liquidada",hint:"Pagamento concluido",tooltip:"O valor desta fatura ja foi quitado integralmente."}:t?.isVencida?{badgeClass:"badge-alerta",progressClass:"is-danger",icon:"triangle-alert",label:"Vencida",shortLabel:"Em atraso",hint:"Regularize esta fatura",tooltip:"A fatura passou do vencimento e merece prioridade para evitar juros."}:t?.isProxima?{badgeClass:"badge-alerta",progressClass:"is-warning",icon:"clock-3",label:"Vence em breve",shortLabel:"Vence logo",hint:"Priorize o pagamento",tooltip:"O vencimento esta proximo. Vale organizar o pagamento desta fatura."}:r>0?{badgeClass:"badge-parcial",progressClass:"is-warning",icon:"loader-2",label:"Pagamento parcial",shortLabel:"Parcial",hint:"Parte do valor ja foi paga",tooltip:"A fatura segue aberta, mas ja possui pagamentos registrados."}:{badgeClass:"badge-pendente",progressClass:"is-safe",icon:"clock-3",label:"Pendente",shortLabel:"No prazo",hint:"Aguardando pagamento",tooltip:"A fatura segue aberta e ainda esta dentro do prazo normal de pagamento."}},getResumoPrincipal(e,a,t,r,o,n){const l=e.total_estornos&&e.total_estornos>0,d=n>0?`${o} de ${n} itens pagos`:"Sem itens consolidados",m=a.hasDate&&a.helper!=="Dentro do prazo"?`<span class="fatura-card-due-tag ${a.detailClass}">${h(a.helper)}</span>`:"";return`
            <div class="fatura-card-main">
                <span class="resumo-label">Valor total da fatura</span>
                <strong class="resumo-valor">${c.formatMoney(e.valor_total)}</strong>
                <div class="fatura-card-due-line ${a.detailClass}">
                    <span class="fatura-card-due-copy">Vencimento ${h(a.label)}</span>
                    ${m}
                </div>
                ${l?`
                    <p class="fatura-card-note">
                        Inclui ${c.formatMoney(e.total_estornos)} em estornos no fechamento.
                    </p>
                `:""}
            </div>

            <div class="fatura-card-details">
                <div class="fatura-card-detail ${a.detailClass}" ${_("Vencimento",a.hasDate?`Data prevista para pagamento desta fatura: ${a.label}.`:"A fatura ainda nao possui data de vencimento consolidada.")}>
                    <span class="fatura-card-detail-label">Vencimento</span>
                    <strong class="fatura-card-detail-value">${h(a.label)}</strong>
                    <span class="fatura-card-detail-meta">${h(a.helper)}</span>
                </div>

                <div class="fatura-card-detail ${t.progressClass}" ${_("Progresso de pagamento",n>0?`${o} de ${n} itens ja foram pagos nesta fatura.`:"Ainda nao existem itens suficientes para calcular o progresso de pagamento.")}>
                    <span class="fatura-card-detail-label">Pagamento</span>
                    <strong class="fatura-card-detail-value">${n>0?`${o}/${n}`:"--"}</strong>
                    <span class="fatura-card-detail-meta">${h(d)}</span>
                </div>
            </div>
        `},getProgressoSection(e,a,t,r,o){const n=V(r),l=n>0?Math.max(n,8):0;return e===0?`
                <div class="parc-progress-section is-empty">
                    <div class="parc-progress-header">
                        <span class="parc-progress-text">Sem itens suficientes para medir o pagamento</span>
                        <span class="parc-progress-percent">--</span>
                    </div>
                    <div class="parc-progress-bar">
                        <div class="parc-progress-fill ${o.progressClass}" style="width: 0%"></div>
                    </div>
                </div>
            `:`
            <div class="parc-progress-section ${o.progressClass}">
                <div class="parc-progress-header">
                    <span class="parc-progress-text">Pagamento ${ue(n)}</span>
                    <span class="parc-progress-percent">${h(o.shortLabel)}</span>
                </div>
                <div class="parc-progress-bar">
                    <div class="parc-progress-fill ${o.progressClass}" style="width: ${l}%"></div>
                </div>
                <div class="parc-progress-foot">
                    <span>${t} de ${e} itens pagos</span>
                    <span>${a} em aberto</span>
                </div>
            </div>
        `},getStatusBadge(e,a=null,t=null){const r=this.getStatusMeta(e,a,t);return`
            <span
                class="parc-card-badge ${r.badgeClass}"
                ${_(r.label,r.tooltip)}>
                <i data-lucide="${r.icon}" style="width:12px;height:12px"></i>
                ${h(r.label)}
            </span>
        `},createCardHTML({parc:e,statusBadge:a,mes:t,ano:r,itensPendentes:o,itensPagos:n,totalItens:l,progresso:d,dueMeta:m,statusMeta:p}){const g=this.getResumoPrincipal(e,m,p,o,n,l),v=this.getProgressoSection(l,o,n,d,p),w=Number.parseInt(String(e.cartao?.id??e.cartao_id??0),10)||0,P=$("importacoes",{import_target:"cartao",...w>0?{cartao_id:w}:{}}),A=e.cartao&&(e.cartao.nome||e.cartao.bandeira)||"Cartao",I=e.cartao?.conta?.instituicao_financeira?.nome||"Sem instituicao",f=e.cartao?.ultimos_digitos?`Final ${e.cartao.ultimos_digitos}`:"",C=this.getAccentColorSolid(e.cartao),L=e.cartao?.bandeira?.toLowerCase()||"outros",B=this.getBandeiraIcon(L),D=t&&r?`${t}/${r}`:"Fatura atual",K=[h(I),f?h(f):""].filter(Boolean).join(" - "),G=$(`faturas/${e.id}`);return`
            <div class="fatura-card-shell" style="--fatura-accent:${C};">
                <div class="fatura-card-top">
                    <div class="fatura-card-media">
                        <div class="fatura-card-brand" aria-hidden="true">
                            ${B}
                        </div>
                    </div>

                    <div class="fatura-card-head">
                        <div class="fatura-card-title-wrap">
                            <span class="fatura-card-title">${h(A)}</span>
                            <span class="fatura-card-subtitle">${h(I)}</span>
                        </div>
                        <div class="fatura-card-meta">
                            <span class="fatura-card-period" ${_("Periodo da fatura","Competencia consolidada desta fatura para acompanhar fechamento e vencimento.")}>
                                <i data-lucide="calendar-days"></i>
                                <span>${h(D)}</span>
                            </span>
                            ${a}
                        </div>
                    </div>
                </div>

                <div class="fatura-list-info">
                    <span class="list-cartao-nome">${h(A)}</span>
                    <span class="list-periodo">${h(D)}</span>
                    <span class="list-cartao-numero">${K}</span>
                </div>

                <div class="fatura-resumo-principal">${g}</div>
                ${v}
                <div class="fatura-status-col">${a}</div>
                <div class="parc-card-actions">
                    <a
                        class="parc-btn parc-btn-import"
                        href="${h(P)}"
                        data-no-transition="true"
                        title="Importar esta fatura/cartão"
                    >
                        <i data-lucide="upload"></i>
                        <span>Importar fatura</span>
                    </a>
                    <a
                        class="parc-btn parc-btn-view"
                        href="${h(G)}"
                        data-no-transition="true">
                        <i data-lucide="eye"></i>
                        <span>Ver detalhes</span>
                    </a>
                </div>
            </div>
        `}},pe={getDetalhesTarget(){return s.detailPageContent||null},isDetailPageMode(){return!!(s.detailPageEl&&s.detailPageContent)},setDetailPageLoading(e){this.isDetailPageMode()&&(s.detailPageLoading&&(s.detailPageLoading.hidden=!e,s.detailPageLoading.style.display=e?"flex":"none"),s.detailPageContent&&(s.detailPageContent.hidden=e))},updateDetailPageMeta(e){if(!this.isDetailPageMode())return;const a=e.cartao?.nome||e.cartao?.bandeira||"Cartao",t=e.mes_referencia&&e.ano_referencia?`${this.getNomeMesCompleto(e.mes_referencia)} de ${e.ano_referencia}`:e.descricao||`Fatura #${e.id}`,r=e.data_vencimento?c.formatDate(e.data_vencimento):"a definir",o=String(e.status||"pendente").replace(/_/g," ").replace(/\b\w/g,n=>n.toUpperCase());if(s.detailPageTitle&&(s.detailPageTitle.textContent=`${a} - ${t}`),s.detailPageSubtitle&&(s.detailPageSubtitle.textContent=`Vencimento ${r} - Status ${o}.`),s.detailPageShell){const n=this.getAccentColorSolid(e.cartao);s.detailPageShell.style.setProperty("--card-accent",n)}document.title=`${a} - ${t} | Lukrato`},renderDetailPageState({title:e="Fatura indisponivel",message:a="Nao foi possivel carregar os detalhes desta fatura."}={}){if(!this.isDetailPageMode())return;const t=this.getDetalhesTarget();t&&(this.setDetailPageLoading(!1),s.detailPageTitle&&(s.detailPageTitle.textContent=e),s.detailPageSubtitle&&(s.detailPageSubtitle.textContent=a),t.innerHTML=`
            <div class="fat-detail-empty">
                <div class="fat-detail-empty__icon">
                    <i data-lucide="receipt-text"></i>
                </div>
                <h3>${c.escapeHtml(e)}</h3>
                <p>${c.escapeHtml(a)}</p>
                <a class="btn btn-primary" href="${c.escapeHtml($("faturas"))}" data-no-transition="true">
                    Voltar para faturas
                </a>
            </div>
        `,S())},async showDetalhes(e){const a=this.getDetalhesTarget();if(!a){window.location.href=$(`faturas/${e}`);return}this.setDetailPageLoading(!0);try{const t=await u.API.buscarParcelamento(e),r=E(t,null);if(!r){this.renderDetailPageState({title:"Fatura indisponivel",message:"Esta fatura nao esta mais disponivel para consulta."});return}i.faturaAtual=r,i.currentDetailId=r.id,a.innerHTML=this.renderDetalhes(r),this.updateDetailPageMeta(r),this.setDetailPageLoading(!1),S(),this.attachDetalhesEventListeners(r.id)}catch(t){if(console.error("Erro ao abrir detalhes:",t),t?.status===404){this.renderDetailPageState({title:"Fatura nao encontrada",message:"Ela pode ter sido removida ou voce nao tem mais acesso a este registro."});return}this.renderDetailPageState({title:"Erro ao carregar fatura",message:y(t,"Nao foi possivel carregar os detalhes desta fatura.")})}},attachDetalhesEventListeners(e){const a=this.getDetalhesTarget();if(!a)return;a.querySelectorAll(".th-sortable").forEach(l=>{l.addEventListener("click",()=>{const d=l.dataset.sort;i.sortColumn===d?i.sortDirection=i.sortDirection==="asc"?"desc":"asc":(i.sortColumn=d,i.sortDirection="asc"),i.faturaAtual&&(a.innerHTML=this.renderDetalhes(i.faturaAtual),S(),this.attachDetalhesEventListeners(e))})}),a.querySelectorAll(".btn-pagar, .btn-desfazer").forEach(l=>{l.addEventListener("click",async d=>{const m=parseInt(d.currentTarget.dataset.lancamentoId,10),p=d.currentTarget.dataset.pago==="true";await this.toggleParcelaPaga(e,m,!p)})}),a.querySelectorAll(".btn-editar").forEach(l=>{l.addEventListener("click",async d=>{const m=parseInt(d.currentTarget.dataset.lancamentoId,10),p=d.currentTarget.dataset.descricao||"",g=parseFloat(d.currentTarget.dataset.valor)||0;await this.editarItemFatura(e,m,p,g)})}),a.querySelectorAll(".btn-excluir").forEach(l=>{l.addEventListener("click",async d=>{const m=parseInt(d.currentTarget.dataset.lancamentoId,10),p=d.currentTarget.dataset.ehParcelado==="true",g=parseInt(d.currentTarget.dataset.totalParcelas)||1;await this.excluirItemFatura(e,m,p,g)})})},renderDetalhes(e){const a=e.progresso||0,{valorPago:t,valorRestante:r}=this.calcularValores(e),o=e.parcelas_pendentes>0&&r>0;return`
            ${this.renderDetalhesHeader(e,o,r)}
            ${this.renderDetalhesGrid(e,a)}
            ${this.renderDetalhesProgresso(e,a,t,r)}
            ${this.renderParcelasTabela(e)}
        `},calcularValores(e){let a=0,t=e.valor_total;return e.parcelas&&e.parcelas.length>0&&(a=e.parcelas.filter(r=>r.pago).reduce((r,o)=>r+parseFloat(o.valor_parcela||o.valor||0),0),t=e.parcelas.filter(r=>!r.pago).reduce((r,o)=>r+parseFloat(o.valor_parcela||o.valor||0),0)),{valorPago:a,valorRestante:t}},renderDetalhesHeader(e,a,t){let r="/";e.data_vencimento?r=c.formatDate(e.data_vencimento):e.mes_referencia&&e.ano_referencia&&(r=`${this.getNomeMes(e.mes_referencia)}/${e.ano_referencia}`);const o=e.parcelas_pendentes===0&&e.parcelas_pagas>0;return`
            <div class="detalhes-header">
                <div class="detalhes-header-content" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">Vencimento</span>
                        <h3 class="detalhes-title" style="margin: 0;">${r}</h3>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        ${a?`
                            <button class="btn-pagar-fatura" 
                                    onclick="window.abrirModalPagarFatura(${e.id}, ${t})"
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="credit-card"></i>
                                <span class="btn-text-desktop">Pagar Fatura</span>
                                <span class="btn-text-mobile">Pagar</span>
                            </button>
                        `:""}
                        ${o?`
                            <button class="btn-reverter-fatura" 
                                    onclick="window.reverterPagamentoFaturaGlobal(${e.id})"
                                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="undo-2"></i>
                                <span class="btn-text-desktop">Reverter Pagamento</span>
                                <span class="btn-text-mobile">Reverter</span>
                            </button>
                        `:""}
                    </div>
                </div>
            </div>
        `},renderDetalhesGrid(e,a){const t=e.parcelas_pagas+e.parcelas_pendentes,r=e.total_estornos&&e.total_estornos>0;return`
            <div class="detalhes-grid">
                <div class="detalhes-item">
                    <span class="detalhes-label">💵 Valor Total a Pagar</span>
                    <span class="detalhes-value detalhes-value-highlight">${c.formatMoney(e.valor_total)}</span>
                </div>
                ${r?`
                <div class="detalhes-item">
                    <span class="detalhes-label">↩️ Estornos/Créditos</span>
                    <span class="detalhes-value" style="color: #10b981;">- ${c.formatMoney(e.total_estornos)}</span>
                </div>
                `:""}
                <div class="detalhes-item">
                    <span class="detalhes-label">📦 Itens</span>
                    <span class="detalhes-value">${t} itens</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">📊 Tipo</span>
                    <span class="detalhes-value">💸 Despesas${r?" + ↩️ Estornos":""}</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">🎯 Status</span>
                    <span class="detalhes-value">${this.getStatusBadge(e.status,a)}</span>
                </div>
                ${e.cartao?`
                    <div class="detalhes-item">
                        <span class="detalhes-label">💳 Cartão</span>
                        <span class="detalhes-value">${e.cartao.bandeira} ${e.cartao.nome?"- "+c.escapeHtml(e.cartao.nome):""}</span>
                    </div>
                `:""}
            </div>
        `},renderDetalhesProgresso(e,a,t,r){const o=e.parcelas_pagas+e.parcelas_pendentes;return`
            <div class="detalhes-progresso">
                <div class="progresso-info">
                    <span><strong>${e.parcelas_pagas}</strong> de <strong>${o}</strong> itens pagos</span>
                    <span class="progresso-percent"><strong>${Math.round(a)}%</strong></span>
                </div>
                <div class="progresso-barra">
                    <div class="progresso-fill" style="width: ${a}%"></div>
                </div>
                <div class="progresso-valores">
                    <span class="valor-pago">✅ Pago: ${c.formatMoney(t)}</span>
                    <span class="valor-restante">⏳ Restante: ${c.formatMoney(r)}</span>
                </div>
            </div>
        `},renderParcelasTabela(e){const a=o=>i.sortColumn===o?i.sortDirection==="asc"?'<i data-lucide="arrow-up" class="sort-icon active"></i>':'<i data-lucide="arrow-down" class="sort-icon active"></i>':'<i data-lucide="arrow-up-down" class="sort-icon"></i>',t=this.sortParcelas(e.parcelas||[]);let r=`
            <h4 class="parcelas-titulo">📋 Lista de Itens</h4>
            
            <!-- Tabela Desktop -->
            <div class="parcelas-container parcelas-desktop">
                <table class="parcelas-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="th-sortable" data-sort="descricao">Descrição ${a("descricao")}</th>
                            <th class="th-sortable" data-sort="data_compra">Data Compra ${a("data_compra")}</th>
                            <th class="th-sortable" data-sort="valor">Valor ${a("valor")}</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
        `;return t.length>0?t.forEach((o,n)=>{r+=this.renderParcelaRow(o,n,e.descricao)}):r+=`
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem;">
                        <p style="color: #6b7280;">Nenhuma parcela encontrada</p>
                    </td>
                </tr>
            `,r+=`
                    </tbody>
                </table>
            </div>
            
            <!-- Cards Mobile -->
            <div class="parcelas-container parcelas-mobile">
        `,e.parcelas&&e.parcelas.length>0?this.sortParcelas(e.parcelas).forEach((n,l)=>{r+=this.renderParcelaCard(n,l,e.descricao)}):r+=`
                <div class="parcela-card-empty">
                    <p>Nenhuma parcela encontrada</p>
                </div>
            `,r+="</div>",r},sortParcelas(e){if(!e||e.length===0)return[];const a=[...e],t=i.sortDirection==="asc"?1:-1,r=i.sortColumn;return a.sort((o,n)=>{if(r==="descricao"){const l=(o.descricao||"").toLowerCase(),d=(n.descricao||"").toLowerCase();return l.localeCompare(d)*t}if(r==="data_compra"){const l=o.data_compra||"0000-00-00",d=n.data_compra||"0000-00-00";return l.localeCompare(d)*t}if(r==="valor"){const l=parseFloat(o.valor_parcela||o.valor||0),d=parseFloat(n.valor_parcela||n.valor||0);return(l-d)*t}return 0}),a},renderParcelaCard(e,a,t){const r=e.pago,o=e.tipo==="estorno",n=r?"parcela-paga":"parcela-pendente",l=r?"✅ Paga":"⏳ Pendente",d=r?"parcela-card-paga":"",m=`${this.getNomeMes(e.mes_referencia)}/${e.ano_referencia}`,p=`parcela-card-${e.id||a}`;let g=e.descricao||t;g=g.replace(/\s*\(\d+\/\d+\)\s*$/,"");let v="";if(e.categoria){const w=e.categoria.icone||"tag",P=e.categoria.nome||e.categoria;v=`<i data-lucide="${w}" style="width:14px;height:14px;display:inline-block;vertical-align:middle;color:${le(w)}"></i> ${c.escapeHtml(P)}`}return o?`
                <div class="parcela-card" id="${p}" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%); border-color: rgba(16, 185, 129, 0.4);">
                    <div class="parcela-card-header">
                        <span class="parcela-numero" style="color: #10b981;">↩️ Estorno</span>
                        <span class="parcela-paga" style="background: #10b981;">✅ Creditado</span>
                    </div>
                    <div class="parcela-card-body">
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Descrição</span>
                            <span class="parcela-card-value" style="color: #10b981;">${c.escapeHtml(g)}</span>
                        </div>
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Crédito na Fatura</span>
                            <span class="parcela-card-value parcela-valor" style="color: #10b981; font-weight: 600;">
                                - ${c.formatMoney(Math.abs(e.valor_parcela))}
                            </span>
                        </div>
                    </div>
                    ${e.id?`
                    <div class="parcela-card-footer">
                        <div class="btn-group-parcela">
                            <button class="btn-toggle-parcela btn-excluir"
                                data-lancamento-id="${e.id}"
                                data-eh-parcelado="false"
                                data-total-parcelas="1"
                                title="Excluir estorno">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                    `:""}
                </div>
            `:`
            <div class="parcela-card ${d}" id="${p}">
                <div class="parcela-card-header">
                    <span class="parcela-numero">${e.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);margin-right:3px;"></i> Recorrente':`${e.numero_parcela||a+1}/${e.total_parcelas||1}`}</span>
                    <span class="${n}">${l}</span>
                </div>
                <div class="parcela-card-body">
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Descrição</span>
                        <span class="parcela-card-value">${c.escapeHtml(g)}${e.recorrente?' <span class="badge-recorrente" title="Assinatura recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</span>
                    </div>
                    ${e.data_compra?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Compra</span>
                        <span class="parcela-card-value"><i data-lucide="shopping-cart" style="margin-right: 4px; font-size: 0.75rem;"></i>${c.formatDate(e.data_compra)}</span>
                    </div>
                    `:""}
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Valor</span>
                        <span class="parcela-card-value parcela-valor">${c.formatMoney(e.valor_parcela)}</span>
                    </div>
                </div>
                
                <!-- Detalhes expandíveis -->
                <div class="parcela-card-detalhes" id="detalhes-${p}" style="display: none;">
                    ${v?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Categoria</span>
                        <span class="parcela-card-value">${v}</span>
                    </div>
                    `:""}
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Mês/Ano</span>
                        <span class="parcela-card-value">${m}</span>
                    </div>
                    ${r&&e.data_pagamento?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Pagamento</span>
                        <span class="parcela-card-value">${e.data_pagamento}</span>
                    </div>
                    `:""}
                    ${e.id?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">ID do Item</span>
                        <span class="parcela-card-value">#${e.id}</span>
                    </div>
                    `:""}
                </div>
                
                <div class="parcela-card-footer">
                  
                    ${this.renderParcelaButton(e,r)}
                </div>
            </div>
        `},renderParcelaRow(e,a,t){const r=e.pago,o=e.tipo==="estorno",n=r?"tr-paga":"";let l=e.descricao||t;l=l.replace(/\s*\(\d+\/\d+\)\s*$/,""),e.categoria&&(l=e.categoria.nome||e.categoria);const d=e.data_compra?c.formatDate(e.data_compra):"-";return o?`
                <tr class="tr-estorno" style="background: rgba(16, 185, 129, 0.1);">
                    <td data-label="#">
                        <span class="parcela-numero" style="color: #10b981;">↩️</span>
                    </td>
                    <td data-label="Descrição" class="td-descricao">
                        <div class="parcela-desc" style="color: #10b981;">${c.escapeHtml(l)}</div>
                    </td>
                    <td data-label="Data Compra">
                        <span style="color: #10b981; font-size: 0.85rem;">${d}</span>
                    </td>
                    <td data-label="Valor">
                        <span class="parcela-valor" style="color: #10b981; font-weight: 600;">
                            - ${c.formatMoney(Math.abs(e.valor_parcela))}
                        </span>
                    </td>
                    <td data-label="Ação" class="td-acoes">
                        <div class="btn-group-parcela" style="justify-content: flex-end; gap: 0.5rem;">
                            <span style="color: #10b981; font-size: 0.85rem;">Estorno aplicado</span>
                            ${e.id?`
                            <button class="btn-toggle-parcela btn-excluir"
                                data-lancamento-id="${e.id}"
                                data-eh-parcelado="false"
                                data-total-parcelas="1"
                                title="Excluir estorno">
                                <i data-lucide="trash-2"></i>
                            </button>
                            `:""}
                        </div>
                    </td>
                </tr>
            `:`
            <tr class="${n}">
                <td data-label="#">
                    <span class="parcela-numero">${e.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);"></i>':`${e.numero_parcela}/${e.total_parcelas}`}</span>
                </td>
                <td data-label="Descrição" class="td-descricao">
                    <div class="parcela-desc">${c.escapeHtml(l)}${e.recorrente?' <span class="badge-recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</div>
                </td>
                <td data-label="Data Compra">
                    <span style="font-size: 0.85rem; color: #9ca3af;">${d}</span>
                </td>
                <td data-label="Valor">
                    <span class="parcela-valor">${c.formatMoney(e.valor_parcela)}</span>
                </td>
                <td data-label="Ação" class="td-acoes">
                    ${this.renderParcelaButton(e,r)}
                </td>
            </tr>
        `},renderParcelaButton(e,a){if(a)return`
                <div class="btn-group-parcela">
                    <span class="badge-pago" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                        <i data-lucide="check"></i> Pago
                    </span>
                </div>
            `;{const t=e.total_parcelas>1;return`
                <div class="btn-group-parcela">
                    <button class="btn-toggle-parcela btn-editar" 
                        data-lancamento-id="${e.id}"
                        data-descricao="${c.escapeHtml(e.descricao||"")}"
                        data-valor="${e.valor_parcela||0}"
                        title="Editar item">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="btn-toggle-parcela btn-excluir" 
                        data-lancamento-id="${e.id}"
                        data-eh-parcelado="${t}"
                        data-total-parcelas="${e.total_parcelas||1}"
                        title="Excluir item">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            `}},getNomeMes(e){return["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"][e-1]||e},getNomeMesCompleto(e){return["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][e-1]||e}};let F=null,x=null,T=null;function O(){return{modalEl:document.getElementById("modalDeleteFaturaItemScope"),formEl:document.getElementById("deleteFaturaItemScopeForm"),titleEl:document.getElementById("modalDeleteFaturaItemScopeLabel"),subtitleEl:document.getElementById("deleteFaturaItemScopeModalSubtitle"),leadEl:document.getElementById("deleteFaturaItemScopeModalLead"),hintEl:document.getElementById("deleteFaturaItemScopeModalHint"),optionsEl:document.getElementById("deleteFaturaItemScopeOptions"),confirmButtonEl:document.getElementById("btnConfirmDeleteFaturaItemScope")}}function fe(){const{formEl:e,optionsEl:a}=O();e?.reset();const t=a?.querySelector('input[value="item"]');t&&(t.checked=!0),a&&(a.hidden=!1)}function ge(e=1){const{titleEl:a,subtitleEl:t,leadEl:r,hintEl:o,optionsEl:n,confirmButtonEl:l}=O(),d=Number(e)>1;if(a&&(a.textContent="Excluir item da fatura"),t&&(t.textContent=d?`Este item faz parte de um parcelamento de ${e}x.`:"Revise a exclusão antes de confirmar."),r&&(r.textContent=d?"Escolha se deseja remover apenas esta parcela ou o parcelamento completo.":"Esta ação não pode ser desfeita."),o&&(o.textContent=d?"Excluir todo o parcelamento remove todas as parcelas vinculadas a esta compra.":"O item será removido permanentemente da fatura."),l&&(l.textContent=d?"Continuar":"Excluir item"),n){n.hidden=!d;const p=n.querySelector('[data-delete-fatura-scope-title="item"]'),g=n.querySelector('[data-delete-fatura-scope-text="item"]'),v=n.querySelector('[data-delete-fatura-scope-title="parcelamento"]'),w=n.querySelector('[data-delete-fatura-scope-text="parcelamento"]');p&&(p.textContent="Apenas esta parcela"),g&&(g.textContent="Remove somente o item atual da fatura."),v&&(v.textContent=`Todo o parcelamento (${e} parcelas)`),w&&(w.textContent="Remove todas as parcelas vinculadas a esta compra parcelada.")}const m=n?.querySelector('input[value="item"]');m&&(m.checked=!0)}function he(){const e=O();return F?{modal:F,...e}:!e.modalEl||!window.bootstrap?.Modal?null:(window.LK?.modalSystem?.prepareBootstrapModal(e.modalEl,{scope:"page"}),e.modalEl.dataset.bound||(e.modalEl.dataset.bound="1",e.formEl?.addEventListener("submit",a=>{a.preventDefault(),T={scope:e.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked')?.value||"item"},F?.hide()}),e.modalEl.addEventListener("hidden.bs.modal",()=>{const a=x,t=T;x=null,T=null,fe(),typeof a=="function"&&a(t||null)})),F=window.bootstrap.Modal.getOrCreateInstance(e.modalEl,{backdrop:!0,keyboard:!0,focus:!0}),{modal:F,...e})}function ve(e=1){const a=he();return a?(typeof x=="function"&&x(null),x=null,T=null,ge(e),new Promise(t=>{x=t,a.modal.show(),requestAnimationFrame(()=>{(Number(e)>1?a.optionsEl?.querySelector('input[name="deleteFaturaItemScopeOption"]:checked'):a.confirmButtonEl)?.focus?.()})})):Promise.resolve(null)}const ye={async toggleParcelaPaga(e,a,t){try{const r=t?"pagar":"desfazer pagamento";if(!(await Swal.fire({title:t?"Marcar como pago?":"Desfazer pagamento?",text:`Deseja realmente ${r} este item?`,icon:"question",showCancelButton:!0,confirmButtonColor:t?"#10b981":"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:t?"Sim, marcar como pago":"Sim, desfazer",cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Processando...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await u.API.toggleItemFatura(e,a,t),await Swal.fire({icon:"success",title:"Sucesso!",text:t?"Item marcado como pago":"Pagamento desfeito",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}}),await u.App.refreshAfterMutation(e)}catch(r){console.error("Erro ao alternar status:",r),Swal.fire({icon:"error",title:"Erro",text:y(r,"Erro ao processar operação"),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999")}})}},async editarItemFatura(e,a,t,r){const o=s.modalEditarItemFatura||document.getElementById("modalEditarItemFatura");if(!o){console.error("Modal de edição não encontrado");return}window.LK?.modalSystem?.prepareBootstrapModal(o,{scope:"page"}),document.getElementById("editItemFaturaId").value=e,document.getElementById("editItemId").value=a,document.getElementById("editItemDescricao").value=t,document.getElementById("editItemValor").value=r.toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}),bootstrap.Modal.getOrCreateInstance(o,{backdrop:!0,keyboard:!0,focus:!0}).show()},async salvarItemFatura(){const e=document.getElementById("editItemFaturaId").value,a=document.getElementById("editItemId").value,t=document.getElementById("editItemDescricao").value.trim(),r=document.getElementById("editItemValor").value;if(!t){Swal.fire({icon:"warning",title:"Atenção",text:"Informe a descrição do item.",timer:2e3,showConfirmButton:!1});return}const o=parseFloat(r.replace(/\./g,"").replace(",","."))||0;if(o<=0){Swal.fire({icon:"warning",title:"Atenção",text:"Informe um valor válido.",timer:2e3,showConfirmButton:!1});return}try{const n=s.modalEditarItemFatura||document.getElementById("modalEditarItemFatura"),l=bootstrap.Modal.getInstance(n);l&&l.hide(),Swal.fire({title:"Atualizando item...",html:"Aguarde enquanto salvamos as alterações.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading()}}),await u.API.atualizarItemFatura(e,a,{descricao:t,valor:o}),await Swal.fire({icon:"success",title:"Item Atualizado!",text:"O item foi atualizado com sucesso.",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1}),await u.App.refreshAfterMutation(e)}catch(n){console.error("Erro ao editar item:",n),Swal.fire({icon:"error",title:"Erro",text:y(n,"Não foi possível atualizar o item."),heightAuto:!1})}},async excluirItemFatura(e,a,t,r){try{const o="Excluir Item?",n="Deseja realmente excluir este item da fatura?",l="Sim, excluir item";if(t&&r>1){const m=await ve(r);if(!m?.scope)return;if(m.scope==="parcelamento")return await this.excluirParcelamentoCompleto(e,a,r)}if(!(await Swal.fire({title:o,text:n,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:l,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const m=document.querySelector(".swal2-container");m&&(m.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Excluindo...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const m=document.querySelector(".swal2-container");m&&(m.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await u.API.excluirItemFatura(e,a),await Swal.fire({icon:"success",title:"Excluído!",text:"Item removido da fatura.",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const m=document.querySelector(".swal2-container");m&&(m.style.zIndex="99999")}}),await u.App.refreshAfterMutation(e)}catch(o){console.error("Erro ao excluir item:",o),Swal.fire({icon:"error",title:"Erro",text:y(o,"Não foi possível excluir o item."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}})}},async excluirParcelamentoCompleto(e,a,t){if((await Swal.fire({title:"Excluir Parcelamento Completo?",html:`
                <p>Deseja realmente excluir <strong>todas as ${t} parcelas</strong> deste parcelamento?</p>
                <p style="color: #ef4444; margin-top: 1rem;"><i data-lucide="triangle-alert"></i> Esta ação não pode ser desfeita!</p>
            `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:`Sim, excluir ${t} parcelas`,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999"),S()}})).isConfirmed){Swal.fire({title:"Excluindo parcelamento...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});try{const o=await u.API.excluirParcelamentoDoItem(e,a);await Swal.fire({icon:"success",title:"Parcelamento Excluído!",text:o.message||`${t} parcelas removidas.`,timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}}),await u.App.refreshAfterMutation(e)}catch(o){console.error("Erro ao excluir parcelamento:",o),Swal.fire({icon:"error",title:"Erro",text:y(o,"Não foi possível excluir o parcelamento."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const n=document.querySelector(".swal2-container");n&&(n.style.zIndex="99999")}})}}},async pagarFaturaCompleta(e,a){try{Swal.fire({title:"Carregando...",html:"Buscando informações da fatura e contas disponíveis.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const f=document.querySelector(".swal2-container");f&&(f.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const[t,r]=await Promise.all([u.API.buscarParcelamento(e),u.API.listarContas()]),o=E(t,null),n=E(r,[]);if(!o?.cartao)throw new Error("Dados da fatura incompletos");const l=o.cartao.id,d=o.cartao.conta_id||null,p=(o.descricao||"").match(/(\d+)\/(\d+)/),g=p?p[1]:null,v=p?p[2]:null;if(!g||!v)throw new Error("Não foi possível identificar o mês/ano da fatura");let w="";if(Array.isArray(n)&&n.length>0)n.forEach(f=>{const C=f.saldoAtual??f.saldo_atual??f.saldo??0,L=c.formatMoney(C),B=f.id===d,D=C>=a;w+=`<option value="${f.id}" ${B?"selected":""} ${D?"":'style="color: #dc2626;"'}>
                        ${c.escapeHtml(f.nome)} - ${L}${B?" (vinculada ao cartão)":""}
                    </option>`});else throw new Error("Nenhuma conta disponível para débito");const P=await Swal.fire({title:"Pagar Fatura Completa?",html:`
                    <p>Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                    <div style="margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="font-size: 0.875rem; color: #047857; margin-bottom: 0.5rem;">Valor Total:</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${c.formatMoney(a)}</div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; text-align: left; margin-bottom: 0.5rem; color: #374151; font-weight: 500;">
                            <i data-lucide="landmark"></i> Conta para débito:
                        </label>
                        <select id="swalContaSelect" class="swal2-select" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                            ${w}
                        </select>
                    </div>
                    <p style="color: #6b7280; font-size: 0.875rem;">O valor será debitado da conta selecionada.</p>
                `,icon:"question",showCancelButton:!0,confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="check"></i> Sim, pagar tudo',cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const f=document.querySelector(".swal2-container");f&&(f.style.zIndex="99999"),S()},preConfirm:()=>{const f=document.getElementById("swalContaSelect"),C=f?parseInt(f.value):null;return C?{contaId:C}:(Swal.showValidationMessage("Selecione uma conta para débito"),!1)}});if(!P.isConfirmed)return;const A=P.value.contaId;Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento de todos os itens.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const f=document.querySelector(".swal2-container");f&&(f.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const I=await u.API.pagarFaturaCompleta(l,parseInt(g),parseInt(v),A);if(!I.success)throw new Error(I.message||"Erro ao processar pagamento");await Swal.fire({icon:"success",title:"Fatura Paga!",html:`
                    <p>${I.message||"Fatura paga com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(E(I,{})?.valor_pago||a)}
                        </div>
                    </div>
                    <div style="color: #059669;">
                        <i data-lucide="circle-check" style="font-size: 2rem;"></i>
                    </div>
                `,timer:3e3,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const f=document.querySelector(".swal2-container");f&&(f.style.zIndex="99999"),S()}}),await u.App.refreshAfterMutation(e)}catch(t){console.error("Erro ao pagar fatura completa:",t),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:y(t,"Não foi possível processar o pagamento. Tente novamente."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")}})}}},be={showLoading(){s.loadingEl.style.display="flex",s.containerEl.style.display="none",s.emptyStateEl.style.display="none"},hideLoading(){s.loadingEl.style.display="none"},showEmpty(){s.containerEl.style.display="none",s.emptyStateEl.style.display="block"},...me,...pe,...ye};u.UI=be;const H={async init(){try{if(this.attachEventListeners(),this.isDetailPage()){await this.carregarDetalhePagina();return}this.initViewToggle(),this.aplicarFiltrosURL(),await this.carregarCartoes(),await this.carregarParcelamentos()}catch(e){console.error("Erro ao inicializar:",e),Swal.fire({icon:"error",title:"Erro de Inicializacao",text:"Nao foi possivel carregar a pagina. Tente recarregar."})}},isDetailPage(){return!!(s.detailPageEl&&s.detailPageContent)},isListPage(){return!!(s.containerEl&&s.loadingEl&&s.emptyStateEl)},async carregarDetalhePagina(){const e=Number.parseInt(String(s.detailPageEl?.dataset.faturaId??""),10);if(!Number.isInteger(e)||e<=0){u.UI.renderDetailPageState({title:"Fatura invalida",message:"Nao foi possivel identificar a fatura solicitada."});return}i.currentDetailId=e,await u.UI.showDetalhes(e)},async refreshAfterMutation(e=null){const a=Number.parseInt(String(e??i.currentDetailId??i.faturaAtual?.id??0),10);if(this.isListPage()){await this.carregarParcelamentos();return}!Number.isInteger(a)||a<=0||this.isDetailPage()&&(i.currentDetailId=a,await u.UI.showDetalhes(a))},goToIndex(){window.location.href=$("faturas")},initViewToggle(){const e=document.querySelector(".view-toggle"),a=s.containerEl;if(!e||!a)return;const t=e.querySelectorAll(".view-btn"),r=localStorage.getItem("faturas_view_mode")||"grid";r==="list"&&a.classList.add("list-view"),this.updateViewToggleState(t,r);const o=document.getElementById("faturasListHeader");r==="list"&&o&&o.classList.add("visible"),t.forEach(n=>{n.addEventListener("click",()=>{const l=n.dataset.view;l==="list"?(a.classList.add("list-view"),o&&o.classList.add("visible")):(a.classList.remove("list-view"),o&&o.classList.remove("visible")),localStorage.setItem("faturas_view_mode",l),this.updateViewToggleState(t,l)})})},updateViewToggleState(e,a){e.forEach(t=>{t.dataset.view===a?t.classList.add("active"):t.classList.remove("active")})},aplicarFiltrosURL(){const e=new URLSearchParams(window.location.search);if(e.has("cartao_id")&&(i.filtros.cartao_id=e.get("cartao_id"),s.filtroCartao&&(s.filtroCartao.value=i.filtros.cartao_id)),e.has("mes")&&e.has("ano")&&(i.filtros.mes=parseInt(e.get("mes"),10),i.filtros.ano=parseInt(e.get("ano"),10),window.monthPicker)){const a=new Date(i.filtros.ano,i.filtros.mes-1);window.monthPicker.setDate(a)}e.has("status")&&(i.filtros.status=e.get("status"),s.filtroStatus&&(s.filtroStatus.value=i.filtros.status))},async carregarCartoes(){try{const e=await u.API.listarCartoes(),a=E(e,[]);i.cartoes=Array.isArray(a)?a:[],this.preencherSelectCartoes(),this.sincronizarFiltrosComSelects()}catch(e){console.error("Erro ao carregar cartoes:",e)}},sincronizarFiltrosComSelects(){s.filtroStatus&&i.filtros.status&&(s.filtroStatus.value=i.filtros.status),s.filtroCartao&&i.filtros.cartao_id&&(s.filtroCartao.value=i.filtros.cartao_id),s.filtroAno&&i.filtros.ano&&(s.filtroAno.value=i.filtros.ano),s.filtroMes&&i.filtros.mes&&(s.filtroMes.value=i.filtros.mes)},preencherSelectCartoes(){s.filtroCartao&&(s.filtroCartao.innerHTML='<option value="">Todos os cartoes</option>',i.cartoes.forEach(e=>{const a=document.createElement("option");a.value=e.id;const t=e.nome_cartao||e.nome||e.bandeira||"Cartao",r=e.ultimos_digitos?` •••• ${e.ultimos_digitos}`:"";a.textContent=t+r,s.filtroCartao.appendChild(a)}))},preencherSelectAnos(e=[]){if(!s.filtroAno)return;const a=s.filtroAno.value,t=new Date().getFullYear();if(s.filtroAno.innerHTML='<option value="">Todos os anos</option>',e.length>0){const r=[...e].sort((o,n)=>o-n);r.includes(t)||(r.push(t),r.sort((o,n)=>o-n)),r.forEach(o=>{const n=document.createElement("option");n.value=o,n.textContent=o,s.filtroAno.appendChild(n)})}else{const r=document.createElement("option");r.value=t,r.textContent=t,s.filtroAno.appendChild(r)}a?s.filtroAno.value=a:(s.filtroAno.value=t,i.filtros.ano=t),this.sincronizarFiltrosComSelects()},extrairAnosDisponiveis(e){const a=new Set;return e.forEach(t=>{const o=(t.descricao||"").match(/(\d{1,2})\/(\d{4})/);if(o&&a.add(parseInt(o[2],10)),t.data_vencimento){const n=new Date(t.data_vencimento).getFullYear();a.add(n)}}),Array.from(a)},async carregarParcelamentos(){u.UI.showLoading();try{const e=await u.API.listarParcelamentos({status:i.filtros.status||"",cartao_id:i.filtros.cartao_id||"",mes:i.filtros.mes||"",ano:i.filtros.ano||""}),a=E(e,{}),t=a?.faturas||[];if(i.parcelamentos=t,!i.anosCarregados){const r=a?.anos_disponiveis||this.extrairAnosDisponiveis(t);this.preencherSelectAnos(r),i.anosCarregados=!0}u.UI.renderParcelamentos(t)}catch(e){console.error("Erro ao carregar parcelamentos:",e),u.UI.showEmpty(),Swal.fire({icon:"error",title:"Erro ao carregar",text:y(e,"Nao foi possivel carregar os parcelamentos")})}finally{u.UI.hideLoading()}},async cancelarParcelamento(e){try{await u.API.cancelarParcelamento(e),await Swal.fire({icon:"success",title:"Cancelado",text:"Parcelamento cancelado com sucesso",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1}),await this.carregarParcelamentos()}catch(a){console.error("Erro ao cancelar:",a),Swal.fire({icon:"error",title:"Erro ao cancelar",text:y(a,"Nao foi possivel cancelar o parcelamento")})}},attachEventListeners(){s.toggleFilters&&s.toggleFilters.addEventListener("click",r=>{r.stopPropagation(),this.toggleFilters()});const e=document.querySelector(".filters-header");e&&e.addEventListener("click",()=>{this.toggleFilters()}),s.btnFiltrar&&s.btnFiltrar.addEventListener("click",()=>{this.aplicarFiltros()}),s.btnLimparFiltros&&s.btnLimparFiltros.addEventListener("click",()=>{this.limparFiltros()}),[s.filtroStatus,s.filtroCartao,s.filtroAno,s.filtroMes].forEach(r=>{r&&r.addEventListener("keypress",o=>{o.key==="Enter"&&this.aplicarFiltros()})});const a=document.getElementById("btnSalvarItemFatura");a&&a.addEventListener("click",()=>{u.UI.salvarItemFatura()});const t=document.getElementById("formEditarItemFatura");t&&t.addEventListener("submit",r=>{r.preventDefault(),u.UI.salvarItemFatura()})},toggleFilters(){s.filtersContainer&&s.filtersContainer.classList.toggle("collapsed")},aplicarFiltros(){i.filtros.status=s.filtroStatus?.value||"",i.filtros.cartao_id=s.filtroCartao?.value||"",i.filtros.ano=s.filtroAno?.value||"",i.filtros.mes=s.filtroMes?.value||"",this.atualizarBadgesFiltros(),this.carregarParcelamentos()},limparFiltros(){s.filtroStatus&&(s.filtroStatus.value=""),s.filtroCartao&&(s.filtroCartao.value=""),s.filtroAno&&(s.filtroAno.value=""),s.filtroMes&&(s.filtroMes.value=""),i.filtros={status:"",cartao_id:"",ano:"",mes:""},this.atualizarBadgesFiltros(),this.carregarParcelamentos()},atualizarBadgesFiltros(){if(!s.activeFilters)return;const e=[],a=["","Janeiro","Fevereiro","Marco","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];if(i.filtros.status){const t={pendente:"Pendente",parcial:"Parcial",paga:"Paga",cancelado:"Cancelado"};e.push({key:"status",label:t[i.filtros.status]||i.filtros.status})}if(i.filtros.cartao_id){const t=i.cartoes.find(o=>o.id==i.filtros.cartao_id),r=t?t.nome_cartao||t.nome:"Cartao";e.push({key:"cartao_id",label:r})}i.filtros.ano&&e.push({key:"ano",label:String(i.filtros.ano)}),i.filtros.mes&&e.push({key:"mes",label:a[i.filtros.mes]}),e.length>0?(s.activeFilters.style.display="flex",s.activeFilters.innerHTML=e.map(t=>`
                <span class="filter-badge">
                    ${t.label}
                    <button class="filter-badge-remove" data-filter="${t.key}" title="Remover filtro">
                        <i data-lucide="x"></i>
                    </button>
                </span>
            `).join(""),window.lucide&&lucide.createIcons(),s.activeFilters.querySelectorAll(".filter-badge-remove").forEach(t=>{t.addEventListener("click",r=>{const o=r.currentTarget.dataset.filter;this.removerFiltro(o)})})):(s.activeFilters.style.display="none",s.activeFilters.innerHTML="")},removerFiltro(e){i.filtros[e]="";const a={status:s.filtroStatus,cartao_id:s.filtroCartao,ano:s.filtroAno,mes:s.filtroMes};a[e]&&(a[e].value=""),this.atualizarBadgesFiltros(),this.carregarParcelamentos()}};u.App=H;const k={instance:null,faturaId:null,valorTotal:null,cartaoId:null,mes:null,ano:null,contas:[],contaPadraoId:null,init(){const e=s.modalPagarFatura||document.getElementById("modalPagarFatura");e&&(window.LK?.modalSystem?.prepareBootstrapModal(e,{scope:"page"}),this.instance=bootstrap.Modal.getOrCreateInstance(e,{backdrop:!0,keyboard:!0,focus:!0}),this.attachEvents())},attachEvents(){document.getElementById("btnPagarTotal")?.addEventListener("click",()=>{this.instance.hide(),u.UI.pagarFaturaCompleta(this.faturaId,this.valorTotal)}),document.getElementById("btnPagarParcial")?.addEventListener("click",()=>{this.mostrarFormularioParcial()}),document.getElementById("btnVoltarEscolha")?.addEventListener("click",()=>{this.mostrarEscolha()}),document.getElementById("btnConfirmarPagamento")?.addEventListener("click",()=>{this.confirmarPagamentoParcial()});const e=document.getElementById("valorPagamentoParcial");e&&(e.addEventListener("input",a=>{let t=a.target.value.replace(/\D/g,"");if(t===""){a.target.value="";return}t=(parseInt(t)/100).toFixed(2),a.target.value=parseFloat(t).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}),e.addEventListener("focus",a=>{a.target.select()})),document.querySelectorAll(".btn-opcao-pagamento").forEach(a=>{a.addEventListener("mouseenter",()=>{a.style.transform="translateY(-2px)",a.style.boxShadow="0 8px 25px rgba(0,0,0,0.2)"}),a.addEventListener("mouseleave",()=>{a.style.transform="translateY(0)",a.style.boxShadow="none"})})},async abrir(e,a){this.faturaId=e,this.valorTotal=a,document.getElementById("pagarFaturaId").value=e,document.getElementById("pagarFaturaValorTotal").value=a,document.getElementById("valorTotalDisplay").textContent=c.formatMoney(a),document.getElementById("valorTotalInfo").textContent=`Valor total da fatura: ${c.formatMoney(a)}`,document.getElementById("valorPagamentoParcial").value=c.formatMoney(a).replace("R$ ",""),this.mostrarEscolha(),await this.carregarDados(),this.instance.show()},async carregarDados(){try{const[e,a]=await Promise.all([u.API.buscarParcelamento(this.faturaId),u.API.listarContas()]),t=E(e,null);if(this.contas=E(a,[]),!t?.cartao)throw new Error("Dados da fatura incompletos");this.cartaoId=t.cartao.id,this.contaPadraoId=t.cartao.conta_id||null;const o=(t.descricao||"").match(/(\d+)\/(\d+)/);this.mes=o?parseInt(o[1]):null,this.ano=o?parseInt(o[2]):null,this.popularSelectContas()}catch(e){console.error("Erro ao carregar dados:",e),Swal.fire({icon:"error",title:"Erro",text:y(e,"Erro ao carregar dados da fatura.")})}},popularSelectContas(){const e=document.getElementById("contaPagamentoFatura");if(e){if(e.innerHTML="",!Array.isArray(this.contas)||this.contas.length===0){e.innerHTML='<option value="">Nenhuma conta disponível</option>';return}this.contas.forEach(a=>{const t=a.saldoAtual??a.saldo_atual??a.saldo??0,r=c.formatMoney(t),o=a.id===this.contaPadraoId,n=document.createElement("option");n.value=a.id,n.textContent=`${a.nome} - ${r}${o?" (vinculada ao cartão)":""}`,o&&(n.selected=!0),e.appendChild(n)})}},mostrarEscolha(){document.getElementById("pagarFaturaEscolha").style.display="block",document.getElementById("pagarFaturaFormParcial").style.display="none",document.getElementById("pagarFaturaFooter").style.display="none"},mostrarFormularioParcial(){document.getElementById("pagarFaturaEscolha").style.display="none",document.getElementById("pagarFaturaFormParcial").style.display="block",document.getElementById("pagarFaturaFooter").style.display="flex",setTimeout(()=>{const e=document.getElementById("valorPagamentoParcial");e&&(e.focus(),e.select())},100)},async confirmarPagamentoParcial(){const e=document.getElementById("valorPagamentoParcial").value,a=document.getElementById("contaPagamentoFatura").value,t=parseFloat(e.replace(/\./g,"").replace(",","."))||0;if(t<=0){Swal.fire({icon:"warning",title:"Valor inválido",text:"Digite um valor válido para o pagamento.",timer:2e3,showConfirmButton:!1});return}if(t>this.valorTotal){Swal.fire({icon:"warning",title:"Valor inválido",text:`O valor não pode ser maior que ${c.formatMoney(this.valorTotal)}`,timer:2e3,showConfirmButton:!1});return}if(!a){Swal.fire({icon:"warning",title:"Conta não selecionada",text:"Selecione uma conta para débito.",timer:2e3,showConfirmButton:!1});return}if(!this.cartaoId||!this.mes||!this.ano){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos. Tente novamente."});return}this.instance.hide(),Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>Swal.showLoading()});try{const r=await u.API.pagarFaturaParcial(this.cartaoId,this.mes,this.ano,parseInt(a,10),t);if(!r.success)throw new Error(y(r,"Erro ao processar pagamento"));await Swal.fire({icon:"success",title:"Pagamento Realizado!",html:`
                    <p>${r.message||"Pagamento efetuado com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor pago:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(t)}
                        </div>
                    </div>
                `,timer:3e3,showConfirmButton:!1}),await u.App.refreshAfterMutation(this.faturaId)}catch(r){console.error("Erro ao pagar fatura:",r),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:y(r,"Não foi possível processar o pagamento. Tente novamente.")})}}};async function we(e){const a=i.faturaAtual;if(!a||!a.cartao||!a.mes_referencia||!a.ano_referencia){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos para reverter o pagamento."});return}if((await Swal.fire({title:"Desfazer Pagamento?",html:`
            <p>Você está prestes a <strong>reverter o pagamento</strong> de todos os itens desta fatura.</p>
            <div style="margin: 1rem 0; padding: 0.75rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                    <i data-lucide="triangle-alert"></i> 
                    O lançamento de pagamento será excluído e o valor voltará para a conta.
                </p>
            </div>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="undo-2"></i> Sim, reverter',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{Swal.fire({title:"Revertendo pagamento...",html:"Aguarde enquanto processamos a reversão.",allowOutsideClick:!1,didOpen:()=>Swal.showLoading()});const r=a.cartao.id,o=a.mes_referencia,n=a.ano_referencia,l=await u.API.desfazerPagamentoFatura(r,o,n);if(l.success)await Swal.fire({icon:"success",title:"Pagamento Revertido!",html:`
                    <p>${l.message||"O pagamento foi revertido com sucesso."}</p>
                    <p style="color: #059669; margin-top: 0.5rem;">
                        <i data-lucide="circle-check"></i> 
                        ${E(l,{})?.itens_revertidos||0} item(s) voltou(aram) para pendente.
                    </p>
                `,timer:3e3,showConfirmButton:!1,didOpen:()=>{window.lucide&&lucide.createIcons()}}),await u.App.refreshAfterMutation(e);else throw new Error(y(l,"Erro ao reverter pagamento"))}catch(r){console.error("Erro ao reverter pagamento:",r),Swal.fire({icon:"error",title:"Erro",text:y(r,"Não foi possível reverter o pagamento.")})}}u.ModalPagarFatura=k;const Ee={toggleFaturasHero:"faturasHero",toggleFaturasFiltros:"faturasFilters",toggleFaturasViewToggle:"faturasViewToggle"},j={toggleFaturasHero:!0,toggleFaturasFiltros:!0,toggleFaturasViewToggle:!0},Ie={...j,toggleFaturasFiltros:!1,toggleFaturasViewToggle:!1};async function Ce(){return ie("faturas")}async function Se(e){await ne("faturas",e)}const M=se({storageKey:"lk_faturas_prefs",sectionMap:Ee,completeDefaults:j,essentialDefaults:Ie,loadPreferences:Ce,savePreferences:Se,modal:{overlayId:"faturasCustomizeModalOverlay",openButtonId:"btnCustomizeFaturas",closeButtonId:"btnCloseCustomizeFaturas",saveButtonId:"btnSaveCustomizeFaturas",presetEssentialButtonId:"btnPresetEssencialFaturas",presetCompleteButtonId:"btnPresetCompletoFaturas"}});function J(){M.init()}u.Customize={init:J,open:M.open,close:M.close};window.abrirModalPagarFatura=(e,a)=>k.abrir(e,a);window.reverterPagamentoFaturaGlobal=we;window.__LK_PARCELAMENTOS_LOADER__||(window.__LK_PARCELAMENTOS_LOADER__=!0,document.addEventListener("DOMContentLoaded",()=>{ce(),document.getElementById("faturaDetalhePage")||J(),H.init(),k.init()}));
