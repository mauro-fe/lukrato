import{m as ee,j as B,q as ae,g as W,e as F,d as M,l as T,n as te,b as oe,h as re,k as A,i as ne,c as se}from"./api-DpYnTMaG.js";import{a as ie}from"./utils-Bj4jxwhy.js";import{r as S}from"./ui-H2yoVZe7.js";import{g as le,a as ce,h as de,i as K,j as me,k as ue,l as G,m as H,b as pe}from"./faturas-osFPSmt_.js";import{i as ge,j as fe,b as V,r as ve,k as ye}from"./finance-CgaDv1sH.js";import{c as he,p as Ce,f as be}from"./ui-preferences-CUuIZRMg.js";const J={BASE_URL:ee()},c={cartoes:[],filteredCartoes:[],alertas:[],currentView:"grid",currentFilter:"all",searchTerm:"",lastLoadedAt:null,isLoading:!1,isSaving:!1,previewMeta:null},C={},s={async getCSRFToken(){try{const a=await ae();if(a)return a}catch(a){console.warn("Erro ao buscar token fresco, usando fallback:",a)}const e=W();return e||(console.warn("⚠️ Nenhum token CSRF encontrado"),"")},getBaseUrl(){return J.BASE_URL},formatMoney(e){return ie(e)},formatMoneyInput(e){return typeof e=="string"&&e.includes(",")?e:typeof e=="number"?(e/100).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,"."):new Intl.NumberFormat("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}).format(e||0)},parseMoney(e){return typeof e=="number"?e:e&&parseFloat(e.toString().replace(/[R$\s]/g,"").replace(/\./g,"").replace(",","."))||0},escapeHtml(e){const a={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"};return e.replace(/[&<>"']/g,t=>a[t])},debounce(e,a){let t;return function(...r){const n=()=>{clearTimeout(t),e(...r)};clearTimeout(t),t=setTimeout(n,a)}},showToast(e,a){window.Swal?Swal.fire({icon:e,title:e==="success"?"Sucesso!":"Erro!",text:a,timer:3e3,showConfirmButton:!1,toast:!0,position:"top-end"}):alert(a)},async showConfirmDialog(e,a,t="Confirmar"){return typeof Swal<"u"?(await Swal.fire({title:e,text:a,icon:"warning",showCancelButton:!0,confirmButtonColor:"#d33",cancelButtonColor:"#3085d6",confirmButtonText:t,cancelButtonText:"Cancelar",reverseButtons:!0})).isConfirmed:confirm(`${e}

${a}`)},getBrandIcon(e){return{visa:B("img/bandeiras/visa.png"),mastercard:B("img/bandeiras/mastercard.png"),elo:B("img/bandeiras/elo.png"),amex:B("img/bandeiras/amex.png"),diners:B("img/bandeiras/diners.png"),discover:B("img/bandeiras/discover.png")}[e?.toLowerCase()]||B("img/bandeiras/default.png")},getDefaultColor(e){return{visa:"linear-gradient(135deg, #1A1F71 0%, #2D3A8C 100%)",mastercard:"linear-gradient(135deg, #EB001B 0%, #F79E1B 100%)",elo:"linear-gradient(135deg, #FFCB05 0%, #FFE600 100%)",amex:"linear-gradient(135deg, #006FCF 0%, #0099CC 100%)",diners:"linear-gradient(135deg, #0079BE 0%, #00558C 100%)",discover:"linear-gradient(135deg, #FF6000 0%, #FF8500 100%)"}[e?.toLowerCase()]||"linear-gradient(135deg, #667eea 0%, #764ba2 100%)"},getAccentColor(e){return{visa:"#1A1F71",mastercard:"#EB001B",elo:"#00A4E0",amex:"#006FCF",diners:"#0079BE",discover:"#FF6000",hipercard:"#822124"}[e?.toLowerCase()]||"#e67e22"},resolverCorCartao(e,a){if(e.cartao?.cor_cartao)return e.cartao.cor_cartao;const t=a||e.cartao_id||e.cartao?.id;if(t){const o=c.cartoes.find(r=>r.id===t);if(o){const r=o.cor_cartao||o.conta?.instituicao_financeira?.cor_primaria||o.instituicao_cor;return r||s.getAccentColor(o.bandeira)}}return s.getAccentColor(e.cartao?.bandeira)},getNomeMes(e){return["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][e-1]||"Mês inválido"},getFreqLabel(e){return{mensal:"Mensal",bimestral:"Bimestral",trimestral:"Trimestral",semestral:"Semestral",anual:"Anual"}[e]||"Recorrente"},formatDate(e){if(!e)return"-";let a;if(e instanceof Date)a=e;else if(typeof e=="string")if(e.includes("T"))a=new Date(e);else{const t=e.split(" ")[0],[o,r,n]=t.split("-");a=new Date(o,r-1,n)}return isNaN(a.getTime())?"-":a.toLocaleDateString("pt-BR")},formatBandeira(e){return e?e.charAt(0).toUpperCase()+e.slice(1).toLowerCase():"Não informado"},formatMoneyForCSV(e){return new Intl.NumberFormat("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}).format(e||0)},convertToCSV(e){if(e.length===0)return"";const a=Object.keys(e[0]),t=[];t.push(a.join(","));for(const o of e){const r=a.map(n=>`"${(""+o[n]).replace(/"/g,'""')}"`);t.push(r.join(","))}return t.join(`
`)},setupLimiteMoneyMask(){const e=document.getElementById("limiteTotal");if(!e){console.error("❌ Campo limiteTotal NÃO encontrado!");return}e.addEventListener("input",function(a){let t=a.target.value;t=t.replace(/[^\d]/g,"");const n=((parseInt(t)||0)/100).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,".");a.target.value=n}),e.value="0,00"}};function Ee(e){if(typeof e!="string")return e;const a=[J.BASE_URL,re()].filter(Boolean);for(const t of a)if(e.startsWith(t))return e.slice(t.length);return e}async function w(e,{method:a="GET",data:t=null,headers:o={},timeout:r=15e3}={}){return oe(Ee(e),{method:a,headers:o,body:t},{timeout:r})}function we(e){if(c.previewMeta=e?.is_demo?e:null,c.previewMeta){window.LKDemoPreviewBanner?.show(c.previewMeta);return}window.LKDemoPreviewBanner?.hide()}const h={async loadCartoes(){const e=document.getElementById("cartoesGrid"),a=document.getElementById("emptyState"),t=document.getElementById("cartoesContainer");if(!(!e||!a))try{c.isLoading=!0,e.setAttribute("aria-busy","true"),t?.setAttribute("aria-busy","true"),delete e.dataset.cardCount,e.innerHTML=`
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
            `,a.style.display="none";const o=await w(`${V()}?preview=1`),r=M(o,{});we(r?.meta),c.cartoes=Array.isArray(r)?r:Array.isArray(r?.cartoes)?r.cartoes:[],c.previewMeta||await h.verificarFaturasPendentes(),c.lastLoadedAt=new Date().toISOString(),C.UI.updateStats(),C.UI.filterCartoes(),c.previewMeta?(c.alertas=[],h.renderAlertas()):await h.carregarAlertas()}catch(o){T("[Cartoes] Erro ao carregar cartões",o,"Erro ao carregar cartões");let r=F(o,"Erro ao carregar cartoes");o.name==="AbortError"||r.includes("demorou")?r="A conexão está lenta. Tente novamente.":navigator.onLine||(r="Sem conexão com a internet"),s.showToast("error",r),e.innerHTML=`
                <div class="error-state">
                    <i data-lucide="triangle-alert"></i>
                    <p class="error-message">${s.escapeHtml(r)}</p>
                    <button class="btn btn-primary btn-retry" onclick="window.cartoesManager.loadCartoes()">
                        <i data-lucide="refresh-cw"></i> Tentar novamente
                    </button>
                </div>
            `,S()}finally{c.isLoading=!1,e.setAttribute("aria-busy","false"),t?.setAttribute("aria-busy","false")}},async verificarFaturasPendentes(){c.cartoes.forEach(a=>{a.temFaturaPendente=!1});const e=c.cartoes.map(async a=>{try{const t=await w(me(a.id)),o=M(t,{}),r=o?.meses||M(o,[])||[];a.temFaturaPendente=Array.isArray(r)&&r.length>0}catch{a.temFaturaPendente=!1}});await Promise.all(e)},async carregarAlertas(){try{const e=await w(ye(),{timeout:1e4}),a=M(e,{});c.alertas=a?.alertas||[],h.renderAlertas()}catch(e){te("[Cartoes] Erro ao carregar alertas",e,"Erro ao carregar alertas"),c.alertas=[];const a=document.getElementById("alertasContainer");a&&(a.style.display="none")}},renderAlertas(){const e=document.getElementById("alertasContainer");if(e){if(c.alertas.length===0){e.style.display="none";return}e.style.display="block",e.innerHTML=`
            <div class="alertas-list">
                ${c.alertas.map(a=>h.criarAlertaHTML(a)).join("")}
            </div>
        `,S()}},criarAlertaHTML(e){const a={vencimento_proximo:"calendar-x",limite_baixo:"triangle-alert"},t={critico:"#e74c3c",atencao:"#f39c12"},o=Object.prototype.hasOwnProperty.call(a,e?.tipo)?e.tipo:"limite_baixo",r=Object.prototype.hasOwnProperty.call(t,e?.gravidade)?e.gravidade:"atencao",n=s.escapeHtml(String(e?.nome_cartao||"Cartão")),l=Number(e?.dias_faltando||0),m=Number(e?.percentual_disponivel||0),i=Number(e?.valor_fatura||0),f=Number(e?.limite_disponivel||0);let v="";return o==="vencimento_proximo"?v=`Fatura de <strong>${n}</strong> vence em <strong>${l} dia(s)</strong> - ${s.formatMoney(i)}`:o==="limite_baixo"&&(v=`Limite de <strong>${n}</strong> em <strong>${m.toFixed(1)}%</strong> - ${s.formatMoney(f)} disponível`),`
            <div class="alerta-item alerta-${r}" data-tipo="${o}">
                <div class="alerta-icon" style="color: ${t[r]}">
                    <i data-lucide="${a[o]}"></i>
                </div>
                <div class="alerta-content">
                    <p>${v}</p>
                </div>
                <button class="alerta-dismiss" onclick="cartoesManager.dismissAlerta(this)" title="Dispensar">
                    <i data-lucide="x"></i>
                </button>
            </div>
        `},dismissAlerta(e){const a=e.closest(".alerta-item");a&&(a.style.animation="slideOut 0.3s ease-out forwards",setTimeout(()=>{a.remove();const t=document.getElementById("alertasContainer");t&&t.querySelectorAll(".alerta-item").length===0&&(t.style.display="none")},300))},async loadContasSelect(){const e=document.getElementById("contaVinculada"),a=document.getElementById("contaVinculadaHelp"),t=document.getElementById("cartaoContaEmptyHint");if(!e){console.error("❌ Select contaVinculada não encontrado!");return}try{const o=`${ve()}?only_active=0&with_balances=1`,r=await w(o),n=M(r,{});let l=[];if(Array.isArray(n)?l=n:Array.isArray(n?.contas)&&(l=n.contas),l.length===0)return e.disabled=!0,e.innerHTML='<option value="">Nenhuma conta disponivel</option>',a&&(a.textContent="Crie uma conta antes de vincular um cartao."),t&&(t.hidden=!1),console.warn("⚠️ Nenhuma conta encontrada"),0;const m=l.map(i=>{const f=i.instituicao_financeira?.nome||i.instituicao?.nome||i.nome||"Sem instituição",v=s.escapeHtml(i.nome||"Conta sem nome"),y=s.escapeHtml(f),b=parseFloat(i.saldoAtual||i.saldo_atual||i.saldo||i.saldo_inicial||0),g=s.formatMoney(b);return`<option value="${i.id}">${v} - ${y} - ${g}</option>`}).join("");return e.disabled=!1,e.innerHTML='<option value="">Selecione a conta</option>'+m,a&&(a.textContent="Conta onde o pagamento da fatura sera debitado."),t&&(t.hidden=!0),l.length}catch(o){return T("[Cartoes] Erro ao carregar contas",o,"Erro ao carregar contas"),e.disabled=!0,e.innerHTML='<option value="">Erro ao carregar contas</option>',a&&(a.textContent="Nao foi possivel carregar as contas agora."),t&&(t.hidden=!1),0}},async saveCartao(){const e=document.getElementById("formCartao");if(!e.checkValidity()){e.reportValidity();return}const a=document.getElementById("cartaoId").value,t=!!a,o=W(),r=document.getElementById("limiteTotal").value,n=s.parseMoney(r),l=document.getElementById("cartaoLembreteAviso")?.value||"",m=document.getElementById("contaVinculada"),i=document.getElementById("cartaoCanalInapp"),f=document.getElementById("cartaoCanalEmail");if(m?.disabled){s.showToast("error","Crie uma conta antes de cadastrar um cartao.");return}if(l&&!i?.checked&&!f?.checked){s.showToast("error","Selecione pelo menos um canal para o lembrete.");return}const v={nome_cartao:document.getElementById("nomeCartao").value.trim(),conta_id:m?.value?parseInt(m.value,10):null,bandeira:document.getElementById("bandeira").value,ultimos_digitos:document.getElementById("ultimosDigitos").value.trim(),limite_total:n,dia_fechamento:document.getElementById("diaFechamento").value||null,dia_vencimento:document.getElementById("diaVencimento").value||null,lembrar_fatura_antes_segundos:l?parseInt(l):null,fatura_canal_inapp:l&&i?.checked?1:0,fatura_canal_email:l&&f?.checked?1:0,csrf_token:o};try{const y=t?fe(a):V(),b=await w(y,{method:t?"PUT":"POST",data:v}),g=M(b,null);g?.gamification?.achievements&&Array.isArray(g.gamification.achievements)&&(typeof window.notifyMultipleAchievements=="function"?window.notifyMultipleAchievements(g.gamification.achievements):console.error("❌ notifyMultipleAchievements não está disponível")),s.showToast("success",t?"Cartão atualizado com sucesso!":"Cartão criado com sucesso!"),C.UI.closeModal(),await h.loadCartoes()}catch(y){T("[Cartoes] Erro ao salvar cartão",y,"Erro ao salvar cartão"),s.showToast("error",F(y,"Erro ao salvar cartao"))}},async editCartao(e){const a=c.cartoes.find(t=>t.id===e);if(a){if(a.is_demo){s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para editar.");return}C.UI.openModal("edit",a)}},async arquivarCartao(e){const a=c.cartoes.find(o=>o.id===e);if(!a)return;if(a.is_demo){s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para arquivar ou editar.");return}if(await s.showConfirmDialog("Arquivar Cartão",`Tem certeza que deseja arquivar o cartão "${a.nome_cartao}"? Você poderá restaurá-lo depois na página de Cartões Arquivados.`,"Arquivar"))try{await w(ge(e),{method:"POST"}),s.showToast("success","Cartão arquivado com sucesso!"),h.loadCartoes()}catch(o){T("[Cartoes] Erro ao arquivar cartão",o,"Erro ao arquivar cartão"),s.showToast("error",F(o,"Erro ao arquivar cartao"))}},async deleteCartao(e){return h.arquivarCartao(e)},async carregarFatura(e,a,t){try{const o=await w(`${K(e)}?mes=${a}&ano=${t}`);return M(o,{itens:[],total:0,pago:0,pendente:0})}catch(o){if(o?.status===404)return{itens:[],total:0,pago:0,pendente:0};throw new Error(F(o,"Erro ao carregar fatura"))}},async carregarParcelamentosResumo(e,a,t){const o=await w(`${G(e)}?mes=${a}&ano=${t}`);return M(o,null)},async carregarHistoricoFaturas(e,a=12){const t=await w(`${ue(e)}?limite=${a}`);return M(t,null)},async pagarParcelasIndividuais(e,a){try{const t=Array.from(e).map(n=>parseInt(n.dataset.id)),o=a.cartao_id||a.cartao?.id;if(!o)throw new Error("ID do cartão não encontrado na fatura");const r=await w(de(o),{method:"POST",data:{parcela_ids:t,mes:a.mes,ano:a.ano}});if(r?.success!==!1){s.showToast("success",r.message||"Parcelas pagas com sucesso!");const n=document.querySelector(".modal-fatura-overlay");n&&C.Fatura.fecharModalFatura(n),await h.loadCartoes()}else throw new Error(r.message||"Erro ao pagar parcelas")}catch(t){s.showToast("error",F(t,"Erro ao processar a operacao do cartao"))}},async desfazerPagamento(e,a,t){if((await Swal.fire({title:"Desfazer pagamento?",html:`
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 300px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar as parcelas como não pagas</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Tem certeza?</strong></p>
            `,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, desfazer",cancelButtonText:"Cancelar",confirmButtonColor:"#d33",reverseButtons:!0})).isConfirmed)try{const r=await w(ce(e),{method:"POST",data:{mes:a,ano:t}});if(r.success){s.showToast("success",r.message);const n=document.querySelector(".modal-fatura-overlay");n&&C.Fatura.fecharModalFatura(n),await h.loadCartoes()}else throw new Error(r.message||"Erro ao desfazer pagamento")}catch(r){s.showToast("error",F(r,"Erro ao processar a operacao do cartao"))}},async desfazerPagamentoParcela(e){if((await Swal.fire({title:"Desfazer pagamento desta parcela?",html:`
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 320px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar esta parcela como não paga</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Deseja continuar?</strong></p>
            `,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, desfazer",cancelButtonText:"Cancelar",confirmButtonColor:"#d33",reverseButtons:!0})).isConfirmed)try{const t=await w(le(e),{method:"POST"});if(t.success){s.showToast("success",t.message);const o=document.querySelector(".modal-fatura-overlay");o&&C.Fatura.fecharModalFatura(o),await h.loadCartoes()}else throw new Error(t.message||"Erro ao desfazer pagamento")}catch(t){s.showToast("error",F(t,"Erro ao processar a operacao do cartao"))}}};C.API=h;const Se={all:"Todos",visa:"Visa",mastercard:"Mastercard",elo:"Elo"},x=(e,a=0,t=100)=>Math.min(t,Math.max(a,Number(e)||0)),_=()=>!!c.searchTerm||c.currentFilter!=="all",$=(e,a="")=>s.escapeHtml(String(e??a)),L=(e,a)=>`data-lk-tooltip-title="${$(e)}" data-lk-tooltip="${$(a)}"`,N=(e,a=1)=>`${(Number(e)||0).toLocaleString("pt-BR",{minimumFractionDigits:a,maximumFractionDigits:a})}%`,O=/(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))/,Me=e=>e?.cor_cartao||e?.conta?.instituicao_financeira?.cor_primaria||e?.instituicao_cor||s.getAccentColor(e?.bandeira),U=e=>{const a=s.getAccentColor(e?.bandeira),t=String(e?.cor_cartao||e?.conta?.instituicao_financeira?.cor_primaria||e?.instituicao_cor||a).trim();return t?/gradient/i.test(t)?t.match(O)?.[1]||a:/^var\(/i.test(t)||O.test(t)?t:a:a},Ie=e=>e>=80?{className:"is-danger",label:"Uso elevado",summary:"Perto do limite",tooltip:"Este cartao já consumiu boa parte do limite. Vale revisar a fatura antes do fechamento."}:e>=50?{className:"is-warning",label:"Uso em atencao",summary:"Acompanhe o uso",tooltip:"O cartao ja passou da metade do limite. Vale acompanhar as proximas compras."}:{className:"is-safe",label:"Uso saudavel",summary:"Dentro do limite",tooltip:"O limite ainda esta folgado para compras, assinaturas e despesas do ciclo atual."},k=e=>e?.is_demo===!0,j=()=>{s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para abrir fatura ou editar.")},d={setupEventListeners(){document.getElementById("btnNovoCartao")?.addEventListener("click",()=>{d.openModal("create")}),document.getElementById("btnNovoCartaoEmpty")?.addEventListener("click",()=>{d.openModal("create")}),document.getElementById("btnLimparFiltrosEmpty")?.addEventListener("click",()=>{d.clearFilters()});const e=document.getElementById("modalCartaoOverlay");e&&(window.LK?.modalSystem?.prepareOverlay(e,{scope:"page"}),e.addEventListener("click",t=>{t.target===e&&d.closeModal()})),document.querySelectorAll("#modalCartaoOverlay .modal-close, #modalCartaoOverlay .modal-close-btn").forEach(t=>{t.addEventListener("click",()=>d.closeModal())}),document.getElementById("limiteTotal")?.addEventListener("input",t=>{t.target.value=d.formatMoneyInput(t.target.value)}),document.getElementById("ultimosDigitos")?.addEventListener("input",t=>{t.target.value=String(t.target.value||"").replace(/\D/g,"").slice(0,4)}),["diaFechamento","diaVencimento"].forEach(t=>{document.getElementById(t)?.addEventListener("input",o=>{o.target.value=d.normalizeDayValue(o.target.value)})}),document.addEventListener("keydown",t=>{const o=document.getElementById("modalCartaoOverlay");t.key==="Escape"&&o?.classList.contains("active")&&d.closeModal()}),document.getElementById("formCartao")?.addEventListener("submit",t=>{t.preventDefault(),C.API.saveCartao()}),document.getElementById("cartaoLembreteAviso")?.addEventListener("change",()=>{d.syncReminderChannels()}),document.getElementById("btnReload")?.addEventListener("click",()=>{C.API.loadCartoes()});const a=document.getElementById("searchCartoes");a&&a.addEventListener("input",s.debounce(t=>{c.searchTerm=String(t.target.value||"").trim().toLowerCase(),d.filterCartoes()},250)),document.querySelectorAll(".filter-btn:not(.btn-clear-filters)").forEach(t=>{t.addEventListener("click",o=>{const r=o.currentTarget;c.currentFilter=r.dataset.filter||"all",d.filterCartoes()})}),document.getElementById("btnLimparFiltrosCartoes")?.addEventListener("click",()=>{d.clearFilters()}),document.querySelectorAll(".view-btn").forEach(t=>{t.addEventListener("click",o=>{const r=o.currentTarget;c.currentView=r.dataset.view||"grid",d.updateView()})}),document.getElementById("btnExportar")?.addEventListener("click",()=>{d.exportarRelatorio()}),d.syncReminderChannels(),d.updateClearButtons()},restoreViewPreference(){const e=localStorage.getItem("cartoes_view_mode");(e==="grid"||e==="list")&&(c.currentView=e),d.updateView()},formatMoneyInput(e){const a=String(e||"").replace(/[^\d]/g,"");return((parseInt(a,10)||0)/100).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,".")},formatMoneyValue(e){return(Number(e)||0).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,".")},normalizeDayValue(e){let a=String(e||"").replace(/\D/g,"").slice(0,2);return a&&parseInt(a,10)>31&&(a="31"),a},setScrollLock(e){if(document.body.classList.toggle("lk-page-modal-open",!!e),document.body.classList.toggle("lk-any-modal-open",!!e),window.LK?.modalSystem)return;const a=e?"hidden":"";document.body.style.overflow=a,document.documentElement.style.overflow=a},syncReminderChannels(){const e=document.getElementById("cartaoLembreteAviso"),a=document.getElementById("cartaoCanaisLembrete");if(!e||!a)return;const t=!!e.value;if(a.style.display=t?"block":"none",!t)return;const o=document.getElementById("cartaoCanalInapp"),r=document.getElementById("cartaoCanalEmail");o&&r&&!o.checked&&!r.checked&&(o.checked=!0)},clearFilters(){const e=document.getElementById("searchCartoes");e&&(e.value=""),c.searchTerm="",c.currentFilter="all",d.filterCartoes()},updateClearButtons(){const e=_(),a=document.getElementById("btnLimparFiltrosCartoes"),t=document.getElementById("btnLimparFiltrosEmpty");a&&(a.style.display=e?"":"none"),t&&(t.style.display=e?"":"none")},filterCartoes(){const e=c.searchTerm;c.filteredCartoes=c.cartoes.filter(a=>{const t=String(a.nome_cartao||a.nome||"").toLowerCase(),o=String(a.ultimos_digitos||"").toLowerCase(),r=String(a.conta?.nome||"").toLowerCase(),n=String(a.conta?.instituicao_financeira?.nome||"").toLowerCase(),l=!e||t.includes(e)||o.includes(e)||r.includes(e)||n.includes(e),m=c.currentFilter==="all"||String(a.bandeira||"").toLowerCase()===c.currentFilter;return l&&m}),d.renderCartoes(),d.renderFilterSummary(),d.updateClearButtons()},renderCartoes(){const e=document.getElementById("cartoesGrid"),a=document.getElementById("emptyState");if(!e||!a)return;if(d.closeCardMenu(),e.setAttribute("aria-busy","false"),e.dataset.cardCount=String(c.filteredCartoes.length),d.updateEmptyState(),c.filteredCartoes.length===0){e.innerHTML="",a.style.display="block",S();return}a.style.display="none";const t=d.getCriticalCardId(c.filteredCartoes);e.innerHTML=c.filteredCartoes.map(o=>d.createCardHTML(o,{criticalCardId:t})).join(""),d.updateView(),d.setupCardActions(),S()},getCriticalCardId(e=c.filteredCartoes){let a=null,t=-1;return e.forEach(o=>{const r=parseFloat(o.limite_total)||0,n=parseFloat(o.limite_disponivel_real??o.limite_disponivel)||0,l=parseFloat(o.limite_utilizado)||Math.max(0,r-n),m=x(o.percentual_uso??(r>0?l/r*100:0),0,100);if(!(!!o.temFaturaPendente||m>=80))return;const f=(o.temFaturaPendente?200:0)+m;f>t&&(t=f,a=o.id)}),a},updateEmptyState(){const e=document.getElementById("emptyState"),a=e?.querySelector("h3"),t=e?.querySelector("p"),o=document.getElementById("btnLimparFiltrosEmpty");if(!(!e||!a||!t||!o)){if(_()){a.textContent="Nenhum cartão encontrado",t.textContent="Revise a busca ou limpe os filtros para voltar a ver os cartões ativos.",o.style.display="";return}a.textContent="Nenhum cartão cadastrado",t.textContent="Adicione seu primeiro cartão para acompanhar limite, vencimentos e faturas em tempo real.",o.style.display="none"}},createCardHTML(e,a={}){const t=parseFloat(e.limite_total)||0,o=parseFloat(e.limite_disponivel_real??e.limite_disponivel)||0,r=parseFloat(e.limite_utilizado)||Math.max(0,t-o),n=x(e.percentual_uso??(t>0?r/t*100:0),0,100),l=x(100-n,0,100),m=n>0?Math.max(n,8):0,i=s.getBrandIcon(e.bandeira),f=U(e),v=Ie(n),y=$(e.conta?.nome,"Conta não vinculada"),b=$(e.conta?.instituicao_financeira?.nome,"Sem instituição"),g=$(e.nome_cartao||e.nome,"Cartão"),p=$(s.formatBandeira(e.bandeira),"Cartão"),E=e.temFaturaPendente?"Fatura pendente":"Sem pendências",I=e.temFaturaPendente?"Pendente":"Em dia",D=e.dia_fechamento?`Dia ${e.dia_fechamento}`:"A definir",P=e.dia_vencimento?`Dia ${e.dia_vencimento}`:"A definir",q=l>0?`${N(l,0)} do limite ainda livre`:"Limite comprometido",z=String(a.criticalCardId??"")===String(e.id??""),X=k(e)?`<span class="card-meta-chip card-meta-chip--status is-ok" ${L("Cartão de exemplo","Esse cartão existe só para demonstrar como o painel funciona.")}>
                    <i data-lucide="flask-conical"></i>
                    Exemplo
               </span>`:"",Q=n>=80?`<span class="card-meta-chip card-meta-chip--usage ${v.className}" ${L(v.label,v.tooltip)}>
                    <i data-lucide="triangle-alert"></i>
                    Uso alto
               </span>`:"";return`
            <article
                class="credit-card surface-card surface-card--interactive surface-card--clip ${z?"is-critical":""}"
                data-id="${e.id}"
                data-brand="${String(e.bandeira||"outros").toLowerCase()}"
                style="--card-accent:${f};"
                tabindex="0"
                role="button"
                aria-label="Abrir detalhes do cartao ${g}, ${N(n)} do limite usado"
            >
                <div class="card-media">
                    <div class="card-brand-mark">
                        <img
                            src="${i}"
                            alt="${p}"
                            class="brand-logo"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                        >
                        <i class="brand-icon-fallback" data-lucide="credit-card" style="display: none;" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="card-header">
                    <div class="card-card-badges">
                        <span class="card-meta-chip card-meta-chip--status ${e.temFaturaPendente?"is-pending":"is-ok"}" ${L(E,e.temFaturaPendente?"Há uma fatura aberta para este cartão que merece acompanhamento ou pagamento.":"Sem pendências abertas para o ciclo atual deste cartão.")}>
                            <i data-lucide="${e.temFaturaPendente?"circle-alert":"badge-check"}"></i>
                            ${I}
                        </span>
                        ${X}
                        ${Q}
                    </div>
                </div>

                <div class="card-content">
                    <h3 class="card-name">${g}</h3>
                    <p class="card-institution">${b}</p>
                    <div class="card-subline">
                        <span class="card-last-digits">Final ${$(e.ultimos_digitos,"0000")}</span>
                        <span class="card-subline-dot" aria-hidden="true"></span>
                        <span class="card-account" ${L("Conta vinculada","Conta usada como referencia para organizar o pagamento da fatura deste cartao.")}>${y}</span>
                    </div>
                </div>

                <div class="card-actions">
                    ${k(e)?`
                    <span class="card-meta-chip card-meta-chip--brand" ${L("Somente visualização","Esse cartão de exemplo nao abre menu nem fatura.")}>
                        <i data-lucide="eye"></i>
                        Visual
                    </span>`:`<button
                        type="button"
                        class="card-overflow-btn"
                        data-card-interactive
                        onclick="event.stopPropagation(); cartoesManager.moreCartao(${e.id}, event)"
                        aria-label="Mais acoes"
                        ${L("Mais acoes","Abra o menu para ver a fatura, editar ou arquivar este cartao.")}>
                        <i data-lucide="more-horizontal" aria-hidden="true"></i>
                    </button>`}
                </div>

                <div class="card-limit-panel">
                    <span class="card-balance-caption">Limite disponivel</span>
                    <strong class="card-limit-available ${o<0?"is-negative":""}">${s.formatMoney(o)}</strong>
                    <p class="card-limit-total">de ${s.formatMoney(t)} de limite total</p>
                </div>

                <div class="card-details">
                    <div class="card-detail-item">
                        <span class="card-detail-label">Fechamento</span>
                        <strong class="card-detail-value">${D}</strong>
                    </div>
                    <div class="card-detail-item">
                        <span class="card-detail-label">Vencimento</span>
                        <strong class="card-detail-value">${P}</strong>
                    </div>
                </div>

                <div class="card-progress">
                    <div class="card-progress-head">
                        <span>${N(n)} usado</span>
                        <span>${v.summary}</span>
                    </div>
                    <div class="limit-bar" aria-hidden="true">
                        <span class="limit-fill ${v.className}" style="width: ${m}%"></span>
                    </div>
                    <div class="card-progress-foot">
                        <span>Já utilizado ${s.formatMoney(r)}</span>
                        <span>${q}</span>
                    </div>
                </div>
            </article>
        `},updateStats(){const e=c.cartoes.reduce((a,t)=>{const o=parseFloat(t.limite_total)||0,r=parseFloat(t.limite_disponivel_real??t.limite_disponivel)||0,n=parseFloat(t.limite_utilizado)||Math.max(0,o-r);return a.total+=1,a.limiteTotal+=o,a.limiteDisponivel+=r,a.limiteUtilizado+=n,a},{total:0,limiteTotal:0,limiteDisponivel:0,limiteUtilizado:0});document.getElementById("totalCartoes").textContent=String(e.total),document.getElementById("statLimiteTotal").textContent=s.formatMoney(e.limiteTotal),document.getElementById("limiteDisponivel").textContent=s.formatMoney(e.limiteDisponivel),document.getElementById("limiteUtilizado").textContent=s.formatMoney(e.limiteUtilizado),d.animateStats()},animateStats(){document.querySelectorAll(".stat-card").forEach((e,a)=>{e.style.animation="none",setTimeout(()=>{e.style.animation="fadeIn 0.5s ease forwards"},a*100)})},renderFilterSummary(){const e=document.getElementById("cartoesFilterSummary");if(!e)return;const a=c.cartoes.length,t=c.filteredCartoes.length,o=c.cartoes.filter(i=>i.temFaturaPendente).length,r=c.cartoes.filter(i=>x(i.percentual_uso)>=80).length,n=c.lastLoadedAt?new Date(c.lastLoadedAt).toLocaleTimeString("pt-BR",{hour:"2-digit",minute:"2-digit"}):null,l=_()?`Mostrando ${t} de ${a} cartões.`:a?"Resumo com limite, faturas e cartões em atenção.":"Cadastre seu primeiro cartão para acompanhar limite e vencimentos aqui.",m=[`<span class="cartoes-summary-pill neutral">${t} visíveis</span>`];c.currentFilter!=="all"&&m.push(`<span class="cartoes-summary-pill accent">Bandeira: ${$(Se[c.currentFilter]||c.currentFilter)}</span>`),c.searchTerm&&m.push(`<span class="cartoes-summary-pill info">Busca: ${$(c.searchTerm)}</span>`),_()||(m.push(`<span class="cartoes-summary-pill ${o?"warning":"success"}">${o} com fatura pendente</span>`),m.push(`<span class="cartoes-summary-pill ${r?"danger":"success"}">${r} com uso alto</span>`)),n&&m.push(`<span class="cartoes-summary-pill subtle">Atualizado às ${$(n)}</span>`),e.innerHTML=`
            <div class="cartoes-summary-row">
                <div class="cartoes-summary-copy">
                    <i data-lucide="${_()?"filter":"sparkles"}"></i>
                    <span>${l}</span>
                </div>
                <div class="cartoes-summary-pills">
                    ${m.join("")}
                </div>
            </div>
        `,S()},updateView(){const e=document.getElementById("cartoesGrid");e&&(e.classList.toggle("list-view",c.currentView==="list"),document.querySelectorAll(".view-btn").forEach(a=>{a.classList.toggle("active",a.dataset.view===c.currentView)}),localStorage.setItem("cartoes_view_mode",c.currentView),d.renderFilterSummary())},setModalSubmitState(e,a=!1){const t=document.getElementById("btnSalvarCartao"),o=document.getElementById("cartaoSubmitLabel");if(!t||!o)return;t.disabled=e,t.setAttribute("aria-busy",e?"true":"false"),o.textContent=e?a?"Salvando alterações...":"Salvando cartão...":a?"Salvar alterações":"Salvar cartão";const r=t.querySelector("[data-lucide], svg");r?.getAttribute&&(r.setAttribute("data-lucide",e?"loader-2":"save"),r.classList.toggle("icon-spin",e)),S()},async openModal(e="create",a=null){const t=document.getElementById("modalCartaoOverlay"),o=document.getElementById("modalCartao"),r=document.getElementById("formCartao"),n=document.getElementById("modalCartaoTitulo"),l=document.getElementById("modalCartaoSubtitle");if(!t||!o||!r||!n||!l)return;if(typeof e!="string"){const f=c.cartoes.find(v=>v.id===Number(e));f?(a=f,e="edit"):e="create"}r.reset(),document.getElementById("cartaoId").value="",document.getElementById("limiteTotal").value="0,00",document.getElementById("contaVinculada").value="",document.getElementById("cartaoCanalInapp").checked=!0,document.getElementById("cartaoCanalEmail").checked=!1,d.syncReminderChannels();const m=await C.API.loadContasSelect(),i=e==="edit"&&!!a;i&&a?(n.textContent="Editar cartão de crédito",l.textContent="Revise os dados e ajuste limite, vencimento ou conta vinculada.",document.getElementById("cartaoId").value=a.id,document.getElementById("nomeCartao").value=a.nome_cartao||"",document.getElementById("contaVinculada").value=a.conta_id||"",document.getElementById("bandeira").value=a.bandeira||"",document.getElementById("ultimosDigitos").value=a.ultimos_digitos||"",document.getElementById("limiteTotal").value=d.formatMoneyValue(a.limite_total||0),document.getElementById("diaFechamento").value=a.dia_fechamento||"",document.getElementById("diaVencimento").value=a.dia_vencimento||"",document.getElementById("cartaoLembreteAviso").value=a.lembrar_fatura_antes_segundos||"",document.getElementById("cartaoCanalInapp").checked=a.fatura_canal_inapp!==!1&&a.fatura_canal_inapp!==0,document.getElementById("cartaoCanalEmail").checked=!!a.fatura_canal_email,o.style.setProperty("--surface-modal-accent",Me(a))):(n.textContent="Novo cartão de crédito",l.textContent=m?"Cadastre o cartão e vincule a conta usada para pagar a fatura.":"Antes de cadastrar um cartão, você precisa ter ao menos uma conta.",o.style.removeProperty("--surface-modal-accent")),d.syncReminderChannels(),d.setModalSubmitState(!1,i),t.classList.add("active"),d.setScrollLock(!0),setTimeout(()=>{document.getElementById(m?"nomeCartao":"contaVinculada")?.focus()},80)},closeModal(){const e=document.getElementById("modalCartaoOverlay");e&&(e.classList.remove("active"),d.setScrollLock(!1),document.getElementById("modalCartao")?.style.removeProperty("--surface-modal-accent"),c.isSaving=!1,d.setModalSubmitState(!1,!1),setTimeout(()=>{document.getElementById("formCartao")?.reset(),document.getElementById("cartaoId").value="",document.getElementById("limiteTotal").value="0,00",d.syncReminderChannels()},180))},setupCardActions(){document.querySelectorAll(".credit-card").forEach(e=>{e.addEventListener("click",a=>{if(a.target.closest("[data-card-interactive], .card-context-menu"))return;const t=parseInt(e.dataset.id,10);Number.isFinite(t)&&d.showCardDetails(t)}),e.addEventListener("keydown",a=>{if(a.key!=="Enter"&&a.key!==" ")return;a.preventDefault();const t=parseInt(e.dataset.id,10);Number.isFinite(t)&&d.showCardDetails(t)})})},closeCardMenu(){document.querySelector(".card-context-menu")?.remove(),typeof d._cardMenuCleanup=="function"&&(d._cardMenuCleanup(),d._cardMenuCleanup=null)},showCardMenu(e,a){a&&(a.stopPropagation(),a.preventDefault());const t=document.querySelector(".card-context-menu");if(t&&t.dataset.cartaoId===String(e)){d.closeCardMenu();return}d.closeCardMenu();const o=document.createElement("div");o.className="card-context-menu",o.dataset.cartaoId=String(e);const r=c.cartoes.find(g=>g.id===e);if(k(r)){j();return}const n=r?.temFaturaPendente?"Pagar fatura":"Ver fatura",l=r?.temFaturaPendente?"wallet":"file-text",m=`${s.getBaseUrl()}importacoes?import_target=cartao&cartao_id=${e}`;o.style.setProperty("--card-accent",U(r)),o.innerHTML=`
            <button type="button" class="card-context-item" data-card-menu-action="invoice">
                <i data-lucide="${l}"></i>
                <span>${n}</span>
            </button>
            <button type="button" class="card-context-item" data-card-menu-action="import-ofx">
                <i data-lucide="upload"></i>
                <span>Importar fatura</span>
            </button>
            <button type="button" class="card-context-item" data-card-menu-action="edit">
                <i data-lucide="pencil"></i>
                <span>Editar</span>
            </button>
            <button type="button" class="card-context-item danger" data-card-menu-action="archive">
                <i data-lucide="archive"></i>
                <span>Arquivar</span>
            </button>
        `,document.body.appendChild(o),S();const i=a?.target?.closest(".card-overflow-btn"),f=()=>{if(!i)return;const g=i.getBoundingClientRect(),p=o.offsetWidth||188,E=o.offsetHeight||156,I=12,D=Math.min(window.innerWidth-p-I,Math.max(I,g.right-p)),P=g.bottom+8,q=g.top-E-8,z=P+E<=window.innerHeight-I?P:Math.max(I,q);o.style.left=`${D}px`,o.style.top=`${z}px`};requestAnimationFrame(f),o.querySelectorAll("[data-card-menu-action]").forEach(g=>{g.addEventListener("click",p=>{switch(p.stopPropagation(),g.dataset.cardMenuAction){case"invoice":window.cartoesManager?.verFatura?.(e);break;case"import-ofx":window.location.href=m;break;case"edit":window.cartoesManager?.editCartao?.(e);break;case"archive":window.cartoesManager?.arquivarCartao?.(e);break}d.closeCardMenu()})});const v=g=>{!o.contains(g.target)&&!g.target.closest(".card-overflow-btn")&&d.closeCardMenu()},y=g=>{g.key==="Escape"&&d.closeCardMenu()},b=()=>f();document.addEventListener("click",v),document.addEventListener("keydown",y),window.addEventListener("resize",b),window.addEventListener("scroll",b,!0),d._cardMenuCleanup=()=>{document.removeEventListener("click",v),document.removeEventListener("keydown",y),window.removeEventListener("resize",b),window.removeEventListener("scroll",b,!0)}},async showCardDetails(e){const a=c.cartoes.find(t=>t.id===e);if(a){if(k(a)){j();return}window.location.href=`${s.getBaseUrl()}cartoes/${e}`}},async exportarRelatorio(){if(!c.filteredCartoes?.length){typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"info",title:"Nenhum cartao para exportar",text:"Adicione cartoes ou altere os filtros.",showConfirmButton:!1,timer:3e3,timerProgressBar:!0});return}try{const{jsPDF:e}=window.jspdf,a=new e,t=new Date,o=t.toLocaleDateString("pt-BR",{month:"long",year:"numeric"}),r=c.filteredCartoes.reduce((p,E)=>p+parseFloat(E.limite_total||0),0),n=c.filteredCartoes.reduce((p,E)=>p+parseFloat((E.limite_disponivel_real??E.limite_disponivel)||0),0),l=r-n,m=r>0?(l/r*100).toFixed(1):0,i=[230,126,34],f=[26,31,46],v=[248,249,250];a.setFillColor(...i),a.rect(0,0,210,35,"F"),a.setTextColor(255,255,255),a.setFontSize(22),a.setFont(void 0,"bold"),a.text("RELATORIO DE CARTOES DE CREDITO",105,15,{align:"center"}),a.setFontSize(10),a.setFont(void 0,"normal"),a.text(`Periodo: ${o}`,105,22,{align:"center"}),a.text(`Gerado em: ${t.toLocaleDateString("pt-BR")} as ${t.toLocaleTimeString("pt-BR")}`,105,28,{align:"center"});let y=45;a.setTextColor(...f),a.setFontSize(14),a.setFont(void 0,"bold"),a.text("RESUMO FINANCEIRO",14,y),y+=8,a.autoTable({startY:y,head:[["Indicador","Valor"]],body:[["Total de Cartoes",c.filteredCartoes.length.toString()],["Limite Total Combinado",s.formatMoney(r)],["Limite Utilizado",s.formatMoney(l)],["Limite Disponivel",s.formatMoney(n)],["Percentual de Utilizacao",`${m}%`]],theme:"grid",headStyles:{fillColor:i,textColor:[255,255,255],fontStyle:"bold",halign:"left"},columnStyles:{0:{cellWidth:100,fontStyle:"bold"},1:{cellWidth:86,halign:"right"}},styles:{fontSize:10,cellPadding:5},alternateRowStyles:{fillColor:v}}),y=a.lastAutoTable.finalY+15,a.setFontSize(14),a.setFont(void 0,"bold"),a.text("DETALHAMENTO POR CARTAO",14,y),y+=5;const b=c.filteredCartoes.map(p=>{const E=p.limite_disponivel_real??p.limite_disponivel??0,I=p.limite_total>0?((p.limite_total-E)/p.limite_total*100).toFixed(1):0;return[p.nome_cartao,s.formatBandeira(p.bandeira),`**** ${p.ultimos_digitos}`,s.formatMoney(p.limite_total),s.formatMoney(E),`${I}%`,p.ativo?"Ativo":"Inativo"]});a.autoTable({startY:y,head:[["Cartao","Bandeira","Final","Limite Total","Disponivel","Uso","Status"]],body:b,theme:"grid",headStyles:{fillColor:i,textColor:[255,255,255],fontStyle:"bold",halign:"center"},columnStyles:{0:{cellWidth:40},1:{cellWidth:25,halign:"center"},2:{cellWidth:25,halign:"center"},3:{cellWidth:28,halign:"right"},4:{cellWidth:28,halign:"right"},5:{cellWidth:18,halign:"center"},6:{cellWidth:22,halign:"center"}},styles:{fontSize:9,cellPadding:4},alternateRowStyles:{fillColor:v}});const g=a.internal.getNumberOfPages();for(let p=1;p<=g;p++)a.setPage(p),a.setFontSize(8),a.setTextColor(128,128,128),a.text(`Pagina ${p} de ${g} | Lukrato - Sistema de Gestao Financeira`,105,287,{align:"center"});a.save(`relatorio_cartoes_${t.toISOString().split("T")[0]}.pdf`),s.showToast("success","Relatorio exportado com sucesso")}catch(e){console.error("Erro ao exportar:",e),s.showToast("error","Erro ao exportar relatorio")}}};C.UI=d;const u={verFatura(e,a=null,t=null){const o=new Date;a=a||o.getMonth()+1,t=t||o.getFullYear(),window.location.href=se("faturas",{cartao_id:e,mes:a,ano:t})},mostrarModalFatura(e,a=null,t=null,o=null){const r=document.querySelector(".modal-fatura-overlay");r&&r.remove();const n=u.criarModalFatura(e,a,t,o);window.LK?.modalSystem?window.LK.modalSystem.prepareOverlay(n,{scope:"page"}):document.body.appendChild(n),S(),setTimeout(()=>{n.classList.add("show")},10),n.addEventListener("click",l=>{l.target===n&&u.fecharModalFatura(n)}),n.querySelector(".btn-fechar-fatura")?.addEventListener("click",()=>{u.fecharModalFatura(n)}),requestAnimationFrame(()=>{u.setupParcelaSelection(n,e)}),n.querySelector(".btn-pagar-fatura")?.addEventListener("click",()=>{u.pagarParcelasSelecionadas(e)})},setupParcelaSelection(e,a){const t=e.querySelector("#selectAllParcelas"),o=e.querySelectorAll(".parcela-checkbox"),r=e.querySelector("#totalSelecionado");if(e.dataset.parcelasConfigured==="true")return;e.dataset.parcelasConfigured="true";const n=()=>{let l=0;o.forEach(m=>{m.checked&&(l+=parseFloat(m.dataset.valor))}),r&&(r.textContent=s.formatMoney(l))};t&&t.addEventListener("change",l=>{o.forEach(m=>{m.checked=l.target.checked}),n()}),o.forEach(l=>{l.addEventListener("change",()=>{if(n(),t){const m=Array.from(o).every(i=>i.checked);t.checked=m}})}),n()},async pagarParcelasSelecionadas(e){const a=document.querySelectorAll(".parcela-checkbox:checked");if(a.forEach((r,n)=>{}),a.length===0){await Swal.fire({icon:"warning",title:"Atenção",text:"Selecione pelo menos uma parcela para pagar."});return}let t=0;a.forEach(r=>{const n=parseFloat(r.dataset.valor);t+=n}),await s.showConfirmDialog("Confirmar Pagamento",`Deseja pagar ${a.length} parcela(s) no valor total de ${s.formatMoney(t)}?`)&&await C.API.pagarParcelasIndividuais(a,e)},criarModalFatura(e,a=null,t=null,o=null){const r=s.resolverCorCartao(e,o),n=document.createElement("div");return n.className="modal-fatura-overlay",n.innerHTML=`<div class="modal-fatura-container" style="--card-accent: ${r};">${u.criarConteudoModal(e,a,t,o)}</div>`,n},criarConteudoModal(e,a=null,t=null,o=null){const r=o||e.cartao_id||e.cartao?.id;if(t&&t.pago)return u.criarConteudoModalFaturaPaga(e,t,a,r);const n=(e.itens||[]).filter(i=>!i.pago).length,l=(e.itens||[]).filter(i=>i.pago).length,m=e.cartao?.bandeira?s.getBrandIcon(e.cartao.bandeira):null;return`
                <div class="modal-fatura-header">
                    <div class="header-top-row">
                        <div class="header-card-identity">
                            ${m?`<img src="${m}" alt="${e.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">`:""}
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
                    ${n===0&&l===0?`
                        <div class="fatura-empty">
                            <div class="empty-icon-wrap">
                                <i data-lucide="inbox"></i>
                            </div>
                            <h3>Nenhum lançamento</h3>
                            <p>Não há compras registradas neste mês.</p>
                        </div>
                    `:n===0&&l>0?`
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
                                    ${(e.itens||[]).filter(i=>i.pago).map(i=>u.renderItemPago(i)).join("")}
                                </div>
                            </div>
                        </div>
                    `:`
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
                                ${(e.itens||[]).filter(i=>!i.pago).map(i=>`
                                    <div class="lancamento-item">
                                        <label class="checkbox-custom">
                                            <input type="checkbox" class="parcela-checkbox" data-id="${i.id}" data-valor="${i.valor}">
                                            <span class="checkmark"></span>
                                        </label>
                                        <div class="lanc-info">
                                            <span class="lanc-desc">
                                                ${s.escapeHtml(i.descricao)}
                                                ${u.renderBadgeRecorrente(i)}
                                            </span>
                                            ${i.data_compra?`<span class="lanc-data-compra"><i data-lucide="shopping-cart"></i> ${s.formatDate(i.data_compra)}</span>`:""}
                                        </div>
                                        <span class="lanc-valor">${s.formatMoney(i.valor)}</span>
                                    </div>
                                `).join("")}
                            </div>
                        </div>

                        ${l>0?`
                            <div class="fatura-parcelas-pagas">
                                <div class="secao-titulo-bar">
                                    <span class="secao-titulo-text"><i data-lucide="circle-check"></i> Pagos</span>
                                    <span class="secao-titulo-count">${l}</span>
                                </div>
                                <div class="lancamentos-lista">
                                    ${(e.itens||[]).filter(i=>i.pago).map(i=>u.renderItemPago(i)).join("")}
                                </div>
                            </div>
                        `:""}
                    `}
                </div>

                ${n>0?`
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
                `:""}
        `},renderItemPago(e){return`
            <div class="lancamento-item lancamento-pago">
                <div class="lanc-info">
                    <span class="lanc-desc">
                        ${s.escapeHtml(e.descricao)}
                        ${u.renderBadgeRecorrente(e)}
                    </span>
                    ${e.data_compra?`<span class="lanc-data-compra"><i data-lucide="shopping-cart"></i> ${s.formatDate(e.data_compra)}</span>`:""}
                    <span class="lanc-data-pagamento">
                        <i data-lucide="calendar-check"></i>
                        Pago em ${s.formatDate(e.data_pagamento||e.data)}
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
        `},renderBadgeRecorrente(e){if(!e.recorrente)return"";const a=s.getFreqLabel(e.recorrencia_freq);return`<span class="badge-recorrente" title="Assinatura ${a.toLowerCase()}"><i data-lucide="refresh-cw"></i> ${a}</span>`},fecharModalFatura(e){e.classList.remove("show"),setTimeout(()=>{e.remove()},300)},async pagarFatura(e){if(!await s.showConfirmDialog("Confirmar Pagamento",`Deseja pagar a fatura de ${s.formatMoney(e.total)}?

Esta ação criará um lançamento de despesa na conta vinculada e liberará o limite do cartão.`,"Sim, Pagar"))return;const t=document.querySelector(".btn-pagar-fatura"),o=t?t.innerHTML:"";try{t&&(t.disabled=!0,t.innerHTML='<i data-lucide="loader-2" class="icon-spin"></i> Processando...',S(),t.style.opacity="0.6",t.style.cursor="not-allowed");const r=await ne(pe(e.cartao.id),{mes:e.mes,ano:e.ano}),n=M(r,null);n?.gamification?.achievements&&Array.isArray(n.gamification.achievements)&&(typeof window.notifyMultipleAchievements=="function"?window.notifyMultipleAchievements(n.gamification.achievements):console.error("❌ notifyMultipleAchievements não está disponível")),s.showToast("success",`Fatura paga com sucesso! ${n?.itens_pagos??""} parcela(s) quitada(s).`);const l=document.querySelector(".modal-fatura-overlay");l&&u.fecharModalFatura(l),C.API.loadCartoes()}catch(r){console.error("❌ Erro ao pagar fatura:",r),t&&(t.disabled=!1,t.innerHTML=o,t.style.opacity="1",t.style.cursor="pointer"),s.showToast("error",F(r,"Erro ao pagar fatura"))}},criarConteudoModalFaturaPaga(e,a,t,o){const r=o||e.cartao_id||e.cartao?.id,n=(e.itens||[]).filter(i=>i.pago).length,l=e.cartao?.bandeira?s.getBrandIcon(e.cartao.bandeira):null,m=a?.data_pagamento||(e.itens||[]).find(i=>i.pago&&i.data_pagamento)?.data_pagamento||null;return`
            <div class="modal-fatura-header modal-fatura-header--paga">
                <div class="header-top-row">
                    <div class="header-card-identity">
                        ${l?`<img src="${l}" alt="${e.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">`:""}
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
                            ${m?`Pago em ${s.formatDate(m)} &bull; `:""}
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
                            ${(e.itens||[]).filter(i=>i.pago).map(i=>u.renderItemPago(i)).join("")}
                        </div>
                    </div>
                </div>
            </div>
        `},async navegarMes(e,a,t,o){let r=a+o,n=t;r>12?(r=1,n++):r<1&&(r=12,n--);try{const[l,m,i]=await Promise.all([A(K(e),{mes:r,ano:n}).catch(()=>null),A(G(e),{mes:r,ano:n}).catch(()=>null),A(H(e),{mes:r,ano:n}).catch(()=>null)]);if(!l)throw new Error("Erro ao carregar fatura");const f=l.data||l;let v=null,y=null;m&&(v=m.data||m),i&&(y=i.data||i);const b=document.querySelector(".modal-fatura-container");if(b){const g=s.resolverCorCartao(f,e);b.style.setProperty("--card-accent",g);const p=u.criarConteudoModal(f,v,y,e);b.innerHTML=p,S(),b.querySelector(".btn-fechar-fatura")?.addEventListener("click",()=>{const I=document.querySelector(".modal-fatura-overlay");u.fecharModalFatura(I)}),b.querySelector(".btn-pagar-fatura")?.addEventListener("click",()=>{u.pagarParcelasSelecionadas(f)});const E=document.querySelector(".modal-fatura-overlay");requestAnimationFrame(()=>{u.setupParcelaSelection(E,f)})}}catch(l){console.error("❌ Erro ao navegar entre meses:",l),s.showToast("error",F(l,"Erro ao carregar fatura"))}},async toggleHistoricoFatura(e){try{const a=document.querySelector(".modal-fatura-container");if(!a)return;if(a.querySelector(".historico-faturas")){const o=new Date,r=o.getMonth()+1,n=o.getFullYear(),[l,m,i]=await Promise.all([C.API.carregarFatura(e,r,n),C.API.carregarParcelamentosResumo(e,r,n).catch(()=>null),A(H(e),{mes:r,ano:n}).then(v=>M(v,null)).catch(()=>null)]),f=u.criarConteudoModal(l,m,i,e);a.innerHTML=f,S(),u.adicionarEventListenersModal(l)}else{const o=await C.API.carregarHistoricoFaturas(e),r=u.criarConteudoHistorico(o,e);a.innerHTML=r,S(),u.adicionarEventListenersModal(null)}}catch(a){console.error("❌ Erro ao alternar histórico:",a),s.showToast("error","Erro ao carregar histórico")}},criarConteudoHistorico(e,a){return`
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
                ${e.historico.length===0?`
                    <div class="fatura-empty">
                        <i data-lucide="receipt"></i>
                        <h3>Nenhuma fatura paga</h3>
                        <p>Você ainda não pagou nenhuma fatura neste cartão.</p>
                    </div>
                `:`
                    <div class="historico-lista">
                        ${e.historico.map(t=>`
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
                                        ${t.quantidade_lancamentos} lançamento${t.quantidade_lancamentos!==1?"s":""}
                                    </div>
                                </div>
                            </div>
                        `).join("")}
                    </div>
                `}
            </div>
        `},adicionarEventListenersModal(e){const a=document.querySelector(".modal-fatura-container");a&&(a.querySelector(".btn-fechar-fatura")?.addEventListener("click",()=>{const t=document.querySelector(".modal-fatura-overlay");u.fecharModalFatura(t)}),e&&a.querySelector(".btn-pagar-fatura")?.addEventListener("click",()=>{u.pagarFatura(e)}))}};C.Fatura=u;const $e={toggleCartoesKpis:"cartoesKpis",toggleCartoesToolbar:"cartoesToolbar"},Y={toggleCartoesKpis:!0,toggleCartoesToolbar:!0},Fe={...Y,toggleCartoesKpis:!1,toggleCartoesToolbar:!1};async function Be(){return be("cartoes")}async function Le(e){await Ce("cartoes",e)}const R=he({storageKey:"lk_cartoes_prefs",sectionMap:$e,completeDefaults:Y,essentialDefaults:Fe,loadPreferences:Be,savePreferences:Le,modal:{overlayId:"cartoesCustomizeModalOverlay",openButtonId:"btnCustomizeCartoes",closeButtonId:"btnCloseCustomizeCartoes",saveButtonId:"btnSaveCustomizeCartoes",presetEssentialButtonId:"btnPresetEssencialCartoes",presetCompleteButtonId:"btnPresetCompletoCartoes"}});function Z(){R.init()}C.Customize={init:Z,open:R.open,close:R.close};const _e=async()=>{const e=document.getElementById("cardDetailPage");if(!e)return!1;const a=Number.parseInt(String(e.dataset.cardId||""),10);if(!Number.isInteger(a)||a<=0){const o=document.getElementById("cardDetailPageLoading"),r=document.getElementById("cardDetailPageError"),n=document.getElementById("cardDetailPageSubtitle");return o&&(o.hidden=!0,o.style.display="none"),r&&(r.hidden=!1,r.innerHTML="<p>Cartão inválido para consulta de detalhes.</p>"),n&&(n.textContent="Verifique o link e tente novamente."),!0}const t=e.dataset.currentMonth||`${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,"0")}`;if(!window.LK_CardDetail?.renderPage){const o=document.getElementById("cardDetailPageLoading"),r=document.getElementById("cardDetailPageError"),n=document.getElementById("cardDetailPageSubtitle");return o&&(o.hidden=!0,o.style.display="none"),r&&(r.hidden=!1,r.innerHTML="<p>Não foi possível inicializar os detalhes deste cartão.</p>"),n&&(n.textContent="Atualize a página para tentar novamente."),!0}return await window.LK_CardDetail.renderPage({cardId:a,currentMonth:t,mountId:"cardDetailPageContent",loadingId:"cardDetailPageLoading",errorId:"cardDetailPageError",titleId:"cardDetailPageTitle",subtitleId:"cardDetailPageSubtitle"}),!0},Pe=async()=>{await _e()||(Z(),d.setupEventListeners(),d.restoreViewPreference(),await h.loadCartoes())},Te=e=>c.cartoes.find(t=>t.id===e)?.is_demo?(s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para abrir a fatura."),!0):!1;window.cartoesManager={openModal:(e="create",a=null)=>d.openModal(e,a),closeModal:()=>d.closeModal(),moreCartao:(e,a)=>d.showCardMenu(e,a),editCartao:e=>h.editCartao(e),arquivarCartao:e=>h.arquivarCartao(e),deleteCartao:e=>h.deleteCartao(e),exportarRelatorio:()=>d.exportarRelatorio(),mostrarModalFatura:(e,a,t)=>u.mostrarModalFatura(e,a,t),verFatura:e=>{Te(e)||u.verFatura(e)},fecharModalFatura:()=>u.fecharModalFatura(),navegarMes:(e,a,t,o)=>u.navegarMes(e,a,t,o),pagarFatura:(e,a,t)=>u.pagarFatura(e,a,t),pagarParcelasSelecionadas:(e,a)=>u.pagarParcelasSelecionadas(e,a),toggleHistoricoFatura:e=>u.toggleHistoricoFatura(e),dismissAlerta:e=>h.dismissAlerta(e),loadCartoes:()=>h.loadCartoes(),desfazerPagamento:(e,a,t)=>h.desfazerPagamento(e,a,t),desfazerPagamentoParcela:e=>h.desfazerPagamentoParcela(e)};window.__CARTOES_MANAGER_INITIALIZED__||(window.__CARTOES_MANAGER_INITIALIZED__=!0,document.addEventListener("DOMContentLoaded",()=>Pe()));
