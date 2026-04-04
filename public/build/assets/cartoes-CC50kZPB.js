import{r as K,g as W,e as M,j as w,l as P,f as G,a as J,d as B,c as Y}from"./api-CiEmwEpk.js";import{a as X}from"./utils-Bj4jxwhy.js";import{r as I}from"./ui-H2yoVZe7.js";import{c as Z,p as Q,f as ee}from"./ui-preferences-CsiHVLYn.js";const y={BASE_URL:window.LK?.getBase?.()||"/",API_URL:""};y.API_URL=y.BASE_URL+"api";const l={cartoes:[],filteredCartoes:[],alertas:[],currentView:"grid",currentFilter:"all",searchTerm:"",lastLoadedAt:null,isLoading:!1,isSaving:!1,previewMeta:null},f={},s={async getCSRFToken(){try{const a=await K();if(a)return a}catch(a){console.warn("Erro ao buscar token fresco, usando fallback:",a)}const e=W();return e||(window.LK?.getCSRF?window.LK.getCSRF():window.CSRF?window.CSRF:(console.warn("⚠️ Nenhum token CSRF encontrado"),""))},getBaseUrl(){return y.BASE_URL},formatMoney(e){return X(e)},formatMoneyInput(e){return typeof e=="string"&&e.includes(",")?e:typeof e=="number"?(e/100).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,"."):new Intl.NumberFormat("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}).format(e||0)},parseMoney(e){return typeof e=="number"?e:e&&parseFloat(e.toString().replace(/[R$\s]/g,"").replace(/\./g,"").replace(",","."))||0},escapeHtml(e){const a={"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"};return e.replace(/[&<>"']/g,t=>a[t])},debounce(e,a){let t;return function(...r){const n=()=>{clearTimeout(t),e(...r)};clearTimeout(t),t=setTimeout(n,a)}},showToast(e,a){window.Swal?Swal.fire({icon:e,title:e==="success"?"Sucesso!":"Erro!",text:a,timer:3e3,showConfirmButton:!1,toast:!0,position:"top-end"}):alert(a)},async showConfirmDialog(e,a,t="Confirmar"){return typeof Swal<"u"?(await Swal.fire({title:e,text:a,icon:"warning",showCancelButton:!0,confirmButtonColor:"#d33",cancelButtonColor:"#3085d6",confirmButtonText:t,cancelButtonText:"Cancelar",reverseButtons:!0})).isConfirmed:confirm(`${e}

${a}`)},getBrandIcon(e){const a=`${y.BASE_URL}assets/img/bandeiras/`;return{visa:`${a}visa.png`,mastercard:`${a}mastercard.png`,elo:`${a}elo.png`,amex:`${a}amex.png`,diners:`${a}diners.png`,discover:`${a}discover.png`}[e?.toLowerCase()]||`${a}default.png`},getDefaultColor(e){return{visa:"linear-gradient(135deg, #1A1F71 0%, #2D3A8C 100%)",mastercard:"linear-gradient(135deg, #EB001B 0%, #F79E1B 100%)",elo:"linear-gradient(135deg, #FFCB05 0%, #FFE600 100%)",amex:"linear-gradient(135deg, #006FCF 0%, #0099CC 100%)",diners:"linear-gradient(135deg, #0079BE 0%, #00558C 100%)",discover:"linear-gradient(135deg, #FF6000 0%, #FF8500 100%)"}[e?.toLowerCase()]||"linear-gradient(135deg, #667eea 0%, #764ba2 100%)"},getAccentColor(e){return{visa:"#1A1F71",mastercard:"#EB001B",elo:"#00A4E0",amex:"#006FCF",diners:"#0079BE",discover:"#FF6000",hipercard:"#822124"}[e?.toLowerCase()]||"#e67e22"},resolverCorCartao(e,a){if(e.cartao?.cor_cartao)return e.cartao.cor_cartao;const t=a||e.cartao_id||e.cartao?.id;if(t){const o=l.cartoes.find(r=>r.id===t);if(o){const r=o.cor_cartao||o.conta?.instituicao_financeira?.cor_primaria||o.instituicao_cor;return r||s.getAccentColor(o.bandeira)}}return s.getAccentColor(e.cartao?.bandeira)},getNomeMes(e){return["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][e-1]||"Mês inválido"},getFreqLabel(e){return{mensal:"Mensal",bimestral:"Bimestral",trimestral:"Trimestral",semestral:"Semestral",anual:"Anual"}[e]||"Recorrente"},formatDate(e){if(!e)return"-";let a;if(e instanceof Date)a=e;else if(typeof e=="string")if(e.includes("T"))a=new Date(e);else{const t=e.split(" ")[0],[o,r,n]=t.split("-");a=new Date(o,r-1,n)}return isNaN(a.getTime())?"-":a.toLocaleDateString("pt-BR")},formatBandeira(e){return e?e.charAt(0).toUpperCase()+e.slice(1).toLowerCase():"Não informado"},formatMoneyForCSV(e){return new Intl.NumberFormat("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}).format(e||0)},convertToCSV(e){if(e.length===0)return"";const a=Object.keys(e[0]),t=[];t.push(a.join(","));for(const o of e){const r=a.map(n=>`"${(""+o[n]).replace(/"/g,'""')}"`);t.push(r.join(","))}return t.join(`
`)},setupLimiteMoneyMask(){const e=document.getElementById("limiteTotal");if(!e){console.error("❌ Campo limiteTotal NÃO encontrado!");return}e.addEventListener("input",function(a){let t=a.target.value;t=t.replace(/[^\d]/g,"");const n=((parseInt(t)||0)/100).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,".");a.target.value=n}),e.value="0,00"}};function ae(e){return typeof e!="string"?e:e.startsWith(y.BASE_URL)?e.slice(y.BASE_URL.length):e}async function $(e,{method:a="GET",data:t=null,headers:o={},timeout:r=15e3}={}){return J(ae(e),{method:a,headers:o,body:t},{timeout:r})}function te(e){if(l.previewMeta=e?.is_demo?e:null,l.previewMeta){window.LKDemoPreviewBanner?.show(l.previewMeta);return}window.LKDemoPreviewBanner?.hide()}const S={async loadCartoes(){const e=document.getElementById("cartoesGrid"),a=document.getElementById("emptyState"),t=document.getElementById("cartoesContainer");if(!(!e||!a))try{l.isLoading=!0,e.setAttribute("aria-busy","true"),t?.setAttribute("aria-busy","true"),e.innerHTML=`
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
                <div class="lk-skeleton lk-skeleton--card"></div>
            `,a.style.display="none";let o;if(window.lkFetch){const n=await window.lkFetch.get(`${y.API_URL}/cartoes?preview=1`,{timeout:2e4,maxRetries:2,showLoading:!0,loadingTarget:"#cartoesContainer"});o=w(n,[])}else o=await $(`${y.API_URL}/cartoes?preview=1`);const r=w(o,{});te(r?.meta),l.cartoes=Array.isArray(r)?r:Array.isArray(r?.cartoes)?r.cartoes:[],l.previewMeta||await S.verificarFaturasPendentes(),l.lastLoadedAt=new Date().toISOString(),f.UI.updateStats(),f.UI.filterCartoes(),l.previewMeta?(l.alertas=[],S.renderAlertas()):await S.carregarAlertas()}catch(o){P("[Cartoes] Erro ao carregar cartões",o,"Erro ao carregar cartões");let r=M(o,"Erro ao carregar cartoes");o.name==="AbortError"||r.includes("demorou")?r="A conexão está lenta. Tente novamente.":navigator.onLine||(r="Sem conexão com a internet"),s.showToast("error",r),e.innerHTML=`
                <div class="error-state">
                    <i data-lucide="triangle-alert"></i>
                    <p class="error-message">${s.escapeHtml(r)}</p>
                    <button class="btn btn-primary btn-retry" onclick="window.cartoesManager.loadCartoes()">
                        <i data-lucide="refresh-cw"></i> Tentar novamente
                    </button>
                </div>
            `,I()}finally{l.isLoading=!1,e.setAttribute("aria-busy","false"),t?.setAttribute("aria-busy","false")}},async verificarFaturasPendentes(){l.cartoes.forEach(a=>{a.temFaturaPendente=!1});const e=l.cartoes.map(async a=>{try{const t=await $(`${y.API_URL}/cartoes/${a.id}/faturas-pendentes`),o=w(t,{}),r=o?.meses||w(o,[])||[];a.temFaturaPendente=Array.isArray(r)&&r.length>0}catch{a.temFaturaPendente=!1}});await Promise.all(e)},async carregarAlertas(){try{let e;if(window.lkFetch){const a=await window.lkFetch.get(`${y.API_URL}/cartoes/alertas`,{timeout:1e4,maxRetries:1,showLoading:!1});e=w(a,{});const t=w(e,{});l.alertas=t?.alertas||[]}else{e=await $(`${y.API_URL}/cartoes/alertas`,{timeout:1e4});const a=w(e,{});l.alertas=a?.alertas||[]}S.renderAlertas()}catch(e){G("[Cartoes] Erro ao carregar alertas",e,"Erro ao carregar alertas"),l.alertas=[];const a=document.getElementById("alertasContainer");a&&(a.style.display="none")}},renderAlertas(){const e=document.getElementById("alertasContainer");if(e){if(l.alertas.length===0){e.style.display="none";return}e.style.display="block",e.innerHTML=`
            <div class="alertas-list">
                ${l.alertas.map(a=>S.criarAlertaHTML(a)).join("")}
            </div>
        `,I()}},criarAlertaHTML(e){const a={vencimento_proximo:"calendar-x",limite_baixo:"triangle-alert"},t={critico:"#e74c3c",atencao:"#f39c12"},o=Object.prototype.hasOwnProperty.call(a,e?.tipo)?e.tipo:"limite_baixo",r=Object.prototype.hasOwnProperty.call(t,e?.gravidade)?e.gravidade:"atencao",n=s.escapeHtml(String(e?.nome_cartao||"Cartão")),c=Number(e?.dias_faltando||0),u=Number(e?.percentual_disponivel||0),i=Number(e?.valor_fatura||0),p=Number(e?.limite_disponivel||0);let C="";return o==="vencimento_proximo"?C=`Fatura de <strong>${n}</strong> vence em <strong>${c} dia(s)</strong> - ${s.formatMoney(i)}`:o==="limite_baixo"&&(C=`Limite de <strong>${n}</strong> em <strong>${u.toFixed(1)}%</strong> - ${s.formatMoney(p)} disponível`),`
            <div class="alerta-item alerta-${r}" data-tipo="${o}">
                <div class="alerta-icon" style="color: ${t[r]}">
                    <i data-lucide="${a[o]}"></i>
                </div>
                <div class="alerta-content">
                    <p>${C}</p>
                </div>
                <button class="alerta-dismiss" onclick="cartoesManager.dismissAlerta(this)" title="Dispensar">
                    <i data-lucide="x"></i>
                </button>
            </div>
        `},dismissAlerta(e){const a=e.closest(".alerta-item");a&&(a.style.animation="slideOut 0.3s ease-out forwards",setTimeout(()=>{a.remove();const t=document.getElementById("alertasContainer");t&&t.querySelectorAll(".alerta-item").length===0&&(t.style.display="none")},300))},async loadContasSelect(){const e=document.getElementById("contaVinculada"),a=document.getElementById("contaVinculadaHelp"),t=document.getElementById("cartaoContaEmptyHint");if(!e){console.error("❌ Select contaVinculada não encontrado!");return}try{const o=`${y.API_URL}/contas?only_active=0&with_balances=1`,r=await $(o),n=w(r,{});let c=[];if(Array.isArray(n)?c=n:Array.isArray(n?.contas)&&(c=n.contas),c.length===0)return e.disabled=!0,e.innerHTML='<option value="">Nenhuma conta disponivel</option>',a&&(a.textContent="Crie uma conta antes de vincular um cartao."),t&&(t.hidden=!1),console.warn("⚠️ Nenhuma conta encontrada"),0;const u=c.map(i=>{const p=i.instituicao_financeira?.nome||i.instituicao?.nome||i.nome||"Sem instituição",C=s.escapeHtml(i.nome||"Conta sem nome"),h=s.escapeHtml(p),b=parseFloat(i.saldoAtual||i.saldo_atual||i.saldo||i.saldo_inicial||0),v=s.formatMoney(b);return`<option value="${i.id}">${C} - ${h} - ${v}</option>`}).join("");return e.disabled=!1,e.innerHTML='<option value="">Selecione a conta</option>'+u,a&&(a.textContent="Conta onde o pagamento da fatura sera debitado."),t&&(t.hidden=!0),c.length}catch(o){return P("[Cartoes] Erro ao carregar contas",o,"Erro ao carregar contas"),e.disabled=!0,e.innerHTML='<option value="">Erro ao carregar contas</option>',a&&(a.textContent="Nao foi possivel carregar as contas agora."),t&&(t.hidden=!1),0}},async saveCartao(){const e=document.getElementById("formCartao");if(!e.checkValidity()){e.reportValidity();return}const a=document.getElementById("cartaoId").value,t=!!a,o=document.querySelector('meta[name="csrf-token"]')?.content||document.querySelector('input[name="csrf_token"]')?.value||"",r=document.getElementById("limiteTotal").value,n=s.parseMoney(r),c=document.getElementById("cartaoLembreteAviso")?.value||"",u=document.getElementById("contaVinculada"),i=document.getElementById("cartaoCanalInapp"),p=document.getElementById("cartaoCanalEmail");if(u?.disabled){s.showToast("error","Crie uma conta antes de cadastrar um cartao.");return}if(c&&!i?.checked&&!p?.checked){s.showToast("error","Selecione pelo menos um canal para o lembrete.");return}const C={nome_cartao:document.getElementById("nomeCartao").value.trim(),conta_id:u?.value?parseInt(u.value,10):null,bandeira:document.getElementById("bandeira").value,ultimos_digitos:document.getElementById("ultimosDigitos").value.trim(),limite_total:n,dia_fechamento:document.getElementById("diaFechamento").value||null,dia_vencimento:document.getElementById("diaVencimento").value||null,lembrar_fatura_antes_segundos:c?parseInt(c):null,fatura_canal_inapp:c&&i?.checked?1:0,fatura_canal_email:c&&p?.checked?1:0,csrf_token:o};try{const h=t?`${y.API_URL}/cartoes/${a}`:`${y.API_URL}/cartoes`,b=await $(h,{method:t?"PUT":"POST",data:C}),v=w(b,null);v?.gamification?.achievements&&Array.isArray(v.gamification.achievements)&&(typeof window.notifyMultipleAchievements=="function"?window.notifyMultipleAchievements(v.gamification.achievements):console.error("❌ notifyMultipleAchievements não está disponível")),s.showToast("success",t?"Cartão atualizado com sucesso!":"Cartão criado com sucesso!"),f.UI.closeModal(),await S.loadCartoes()}catch(h){P("[Cartoes] Erro ao salvar cartão",h,"Erro ao salvar cartão"),s.showToast("error",M(h,"Erro ao salvar cartao"))}},async editCartao(e){const a=l.cartoes.find(t=>t.id===e);if(a){if(a.is_demo){s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para editar.");return}f.UI.openModal("edit",a)}},async arquivarCartao(e){const a=l.cartoes.find(o=>o.id===e);if(!a)return;if(a.is_demo){s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para arquivar ou editar.");return}if(await s.showConfirmDialog("Arquivar Cartão",`Tem certeza que deseja arquivar o cartão "${a.nome_cartao}"? Você poderá restaurá-lo depois na página de Cartões Arquivados.`,"Arquivar"))try{await $(`${y.API_URL}/cartoes/${e}/archive`,{method:"POST"}),s.showToast("success","Cartão arquivado com sucesso!"),S.loadCartoes()}catch(o){P("[Cartoes] Erro ao arquivar cartão",o,"Erro ao arquivar cartão"),s.showToast("error",M(o,"Erro ao arquivar cartao"))}},async deleteCartao(e){return S.arquivarCartao(e)},async carregarFatura(e,a,t){try{const o=await $(`${y.API_URL}/cartoes/${e}/fatura?mes=${a}&ano=${t}`);return w(o,{itens:[],total:0,pago:0,pendente:0})}catch(o){if(o?.status===404)return{itens:[],total:0,pago:0,pendente:0};throw new Error(M(o,"Erro ao carregar fatura"))}},async carregarParcelamentosResumo(e,a,t){const o=await $(`${y.API_URL}/cartoes/${e}/parcelamentos-resumo?mes=${a}&ano=${t}`);return w(o,null)},async carregarHistoricoFaturas(e,a=12){const t=await $(`${y.API_URL}/cartoes/${e}/faturas-historico?limite=${a}`);return w(t,null)},async pagarParcelasIndividuais(e,a){try{const t=Array.from(e).map(n=>parseInt(n.dataset.id)),o=a.cartao_id||a.cartao?.id;if(!o)throw new Error("ID do cartão não encontrado na fatura");const r=await $(`${y.API_URL}/cartoes/${o}/parcelas/pagar`,{method:"POST",data:{parcela_ids:t,mes:a.mes,ano:a.ano}});if(r?.success!==!1){s.showToast("success",r.message||"Parcelas pagas com sucesso!");const n=document.querySelector(".modal-fatura-overlay");n&&f.Fatura.fecharModalFatura(n),await S.loadCartoes()}else throw new Error(r.message||"Erro ao pagar parcelas")}catch(t){s.showToast("error",M(t,"Erro ao processar a operacao do cartao"))}},async desfazerPagamento(e,a,t){if((await Swal.fire({title:"Desfazer pagamento?",html:`
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 300px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar as parcelas como não pagas</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Tem certeza?</strong></p>
            `,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, desfazer",cancelButtonText:"Cancelar",confirmButtonColor:"#d33",reverseButtons:!0})).isConfirmed)try{const r=await $(`${y.API_URL}/cartoes/${e}/fatura/desfazer-pagamento`,{method:"POST",data:{mes:a,ano:t}});if(r.success){s.showToast("success",r.message);const n=document.querySelector(".modal-fatura-overlay");n&&f.Fatura.fecharModalFatura(n),await S.loadCartoes()}else throw new Error(r.message||"Erro ao desfazer pagamento")}catch(r){s.showToast("error",M(r,"Erro ao processar a operacao do cartao"))}},async desfazerPagamentoParcela(e){if((await Swal.fire({title:"Desfazer pagamento desta parcela?",html:`
                <p>Esta ação irá:</p>
                <ul style="text-align: left; margin: 1rem auto; max-width: 320px;">
                    <li>✅ Devolver o valor à conta</li>
                    <li>✅ Marcar esta parcela como não paga</li>
                    <li>✅ Reduzir o limite disponível do cartão</li>
                </ul>
                <p><strong>Deseja continuar?</strong></p>
            `,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, desfazer",cancelButtonText:"Cancelar",confirmButtonColor:"#d33",reverseButtons:!0})).isConfirmed)try{const t=await $(`${y.API_URL}/cartoes/parcelas/${e}/desfazer-pagamento`,{method:"POST"});if(t.success){s.showToast("success",t.message);const o=document.querySelector(".modal-fatura-overlay");o&&f.Fatura.fecharModalFatura(o),await S.loadCartoes()}else throw new Error(t.message||"Erro ao desfazer pagamento")}catch(t){s.showToast("error",M(t,"Erro ao processar a operacao do cartao"))}}};f.API=S;const oe={all:"Todos",visa:"Visa",mastercard:"Mastercard",elo:"Elo"},R=(e,a=0,t=100)=>Math.min(t,Math.max(a,Number(e)||0)),A=()=>!!l.searchTerm||l.currentFilter!=="all",L=(e,a="")=>s.escapeHtml(String(e??a)),_=(e,a)=>`data-lk-tooltip-title="${L(e)}" data-lk-tooltip="${L(a)}"`,T=(e,a=1)=>`${(Number(e)||0).toLocaleString("pt-BR",{minimumFractionDigits:a,maximumFractionDigits:a})}%`,q=/(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))/,re=()=>{const e=new Date;return`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,"0")}`},z=e=>e?.cor_cartao||e?.conta?.instituicao_financeira?.cor_primaria||e?.instituicao_cor||s.getAccentColor(e?.bandeira),U=e=>{const a=s.getAccentColor(e?.bandeira),t=String(e?.cor_cartao||e?.conta?.instituicao_financeira?.cor_primaria||e?.instituicao_cor||a).trim();return t?/gradient/i.test(t)?t.match(q)?.[1]||a:/^var\(/i.test(t)||q.test(t)?t:a:a},ne=e=>e>=80?{className:"is-danger",label:"Uso elevado",summary:"Perto do limite",tooltip:"Este cartao ja consumiu boa parte do limite. Vale revisar a fatura antes do fechamento."}:e>=50?{className:"is-warning",label:"Uso em atencao",summary:"Acompanhe o uso",tooltip:"O cartao ja passou da metade do limite. Vale acompanhar as proximas compras."}:{className:"is-safe",label:"Uso saudavel",summary:"Dentro do limite",tooltip:"O limite ainda esta folgado para compras, assinaturas e despesas do ciclo atual."},x=e=>e?.is_demo===!0,N=()=>{s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para abrir fatura ou editar.")},d={setupEventListeners(){document.getElementById("btnNovoCartao")?.addEventListener("click",()=>{d.openModal("create")}),document.getElementById("btnNovoCartaoEmpty")?.addEventListener("click",()=>{d.openModal("create")}),document.getElementById("btnLimparFiltrosEmpty")?.addEventListener("click",()=>{d.clearFilters()});const e=document.getElementById("modalCartaoOverlay");e&&(window.LK?.modalSystem?.prepareOverlay(e,{scope:"page"}),e.addEventListener("click",t=>{t.target===e&&d.closeModal()})),document.querySelectorAll("#modalCartaoOverlay .modal-close, #modalCartaoOverlay .modal-close-btn").forEach(t=>{t.addEventListener("click",()=>d.closeModal())}),document.getElementById("limiteTotal")?.addEventListener("input",t=>{t.target.value=d.formatMoneyInput(t.target.value)}),document.getElementById("ultimosDigitos")?.addEventListener("input",t=>{t.target.value=String(t.target.value||"").replace(/\D/g,"").slice(0,4)}),["diaFechamento","diaVencimento"].forEach(t=>{document.getElementById(t)?.addEventListener("input",o=>{o.target.value=d.normalizeDayValue(o.target.value)})}),document.addEventListener("keydown",t=>{const o=document.getElementById("modalCartaoOverlay");t.key==="Escape"&&o?.classList.contains("active")&&d.closeModal()}),document.getElementById("formCartao")?.addEventListener("submit",t=>{t.preventDefault(),f.API.saveCartao()}),document.getElementById("cartaoLembreteAviso")?.addEventListener("change",()=>{d.syncReminderChannels()}),document.getElementById("btnReload")?.addEventListener("click",()=>{f.API.loadCartoes()});const a=document.getElementById("searchCartoes");a&&a.addEventListener("input",s.debounce(t=>{l.searchTerm=String(t.target.value||"").trim().toLowerCase(),d.filterCartoes()},250)),document.querySelectorAll(".filter-btn:not(.btn-clear-filters)").forEach(t=>{t.addEventListener("click",o=>{const r=o.currentTarget;l.currentFilter=r.dataset.filter||"all",d.filterCartoes()})}),document.getElementById("btnLimparFiltrosCartoes")?.addEventListener("click",()=>{d.clearFilters()}),document.querySelectorAll(".view-btn").forEach(t=>{t.addEventListener("click",o=>{const r=o.currentTarget;l.currentView=r.dataset.view||"grid",d.updateView()})}),document.getElementById("btnExportar")?.addEventListener("click",()=>{d.exportarRelatorio()}),d.syncReminderChannels(),d.updateClearButtons()},restoreViewPreference(){const e=localStorage.getItem("cartoes_view_mode");(e==="grid"||e==="list")&&(l.currentView=e),d.updateView()},formatMoneyInput(e){const a=String(e||"").replace(/[^\d]/g,"");return((parseInt(a,10)||0)/100).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,".")},formatMoneyValue(e){return(Number(e)||0).toFixed(2).replace(".",",").replace(/\B(?=(\d{3})+(?!\d))/g,".")},normalizeDayValue(e){let a=String(e||"").replace(/\D/g,"").slice(0,2);return a&&parseInt(a,10)>31&&(a="31"),a},setScrollLock(e){if(window.LK?.modalSystem)return;const a=e?"hidden":"";document.body.style.overflow=a,document.documentElement.style.overflow=a},syncReminderChannels(){const e=document.getElementById("cartaoLembreteAviso"),a=document.getElementById("cartaoCanaisLembrete");if(!e||!a)return;const t=!!e.value;if(a.style.display=t?"block":"none",!t)return;const o=document.getElementById("cartaoCanalInapp"),r=document.getElementById("cartaoCanalEmail");o&&r&&!o.checked&&!r.checked&&(o.checked=!0)},clearFilters(){const e=document.getElementById("searchCartoes");e&&(e.value=""),l.searchTerm="",l.currentFilter="all",d.filterCartoes()},updateClearButtons(){const e=A(),a=document.getElementById("btnLimparFiltrosCartoes"),t=document.getElementById("btnLimparFiltrosEmpty");a&&(a.style.display=e?"":"none"),t&&(t.style.display=e?"":"none")},filterCartoes(){const e=l.searchTerm;l.filteredCartoes=l.cartoes.filter(a=>{const t=String(a.nome_cartao||a.nome||"").toLowerCase(),o=String(a.ultimos_digitos||"").toLowerCase(),r=String(a.conta?.nome||"").toLowerCase(),n=String(a.conta?.instituicao_financeira?.nome||"").toLowerCase(),c=!e||t.includes(e)||o.includes(e)||r.includes(e)||n.includes(e),u=l.currentFilter==="all"||String(a.bandeira||"").toLowerCase()===l.currentFilter;return c&&u}),d.renderCartoes(),d.renderFilterSummary(),d.updateClearButtons()},renderCartoes(){const e=document.getElementById("cartoesGrid"),a=document.getElementById("emptyState");if(!(!e||!a)){if(d.closeCardMenu(),e.setAttribute("aria-busy","false"),d.updateEmptyState(),l.filteredCartoes.length===0){e.innerHTML="",a.style.display="block",I();return}a.style.display="none",e.innerHTML=l.filteredCartoes.map(t=>d.createCardHTML(t)).join(""),d.updateView(),d.setupCardActions(),I()}},updateEmptyState(){const e=document.getElementById("emptyState"),a=e?.querySelector("h3"),t=e?.querySelector("p"),o=document.getElementById("btnLimparFiltrosEmpty");if(!(!e||!a||!t||!o)){if(A()){a.textContent="Nenhum cartao encontrado",t.textContent="Revise a busca ou limpe os filtros para voltar a ver os cartoes ativos.",o.style.display="";return}a.textContent="Nenhum cartao cadastrado",t.textContent="Adicione seu primeiro cartao para acompanhar limite, vencimentos e faturas em tempo real.",o.style.display="none"}},createCardHTML(e){const a=parseFloat(e.limite_total)||0,t=parseFloat(e.limite_disponivel_real??e.limite_disponivel)||0,o=parseFloat(e.limite_utilizado)||Math.max(0,a-t),r=R(e.percentual_uso??(a>0?o/a*100:0),0,100),n=R(100-r,0,100),c=r>0?Math.max(r,8):0,u=s.getBrandIcon(e.bandeira),i=U(e),p=ne(r),C=L(e.conta?.nome,"Conta nao vinculada"),h=L(e.conta?.instituicao_financeira?.nome,"Sem instituicao"),b=L(e.nome_cartao||e.nome,"Cartao"),v=L(s.formatBandeira(e.bandeira),"Cartao"),g=e.temFaturaPendente?"Fatura pendente":"Sem pendencias",E=e.dia_fechamento?`Dia ${e.dia_fechamento}`:"A definir",F=e.dia_vencimento?`Dia ${e.dia_vencimento}`:"A definir";e.temFaturaPendente,e.temFaturaPendente,e.temFaturaPendente;const k=n>0?`${T(n,0)} do limite ainda livre`:"Limite comprometido",V=x(e)?`<span class="card-meta-chip card-meta-chip--status is-ok" ${_("Cartao de exemplo","Esse cartao existe so para demonstrar como o painel funciona.")}>
                    <i data-lucide="flask-conical"></i>
                    Exemplo
               </span>`:"",j=r>=50?`<span class="card-meta-chip card-meta-chip--usage ${p.className}" ${_(p.label,p.tooltip)}>
                    <i data-lucide="${r>=80?"triangle-alert":"activity"}"></i>
                    ${p.label}
               </span>`:"";return`
            <article
                class="credit-card surface-card surface-card--interactive surface-card--clip"
                data-id="${e.id}"
                data-brand="${String(e.bandeira||"outros").toLowerCase()}"
                style="--card-accent:${i};"
                tabindex="0"
                role="button"
                aria-label="Abrir detalhes do cartao ${b}, ${T(r)} do limite usado"
            >
                <div class="card-media">
                    <div class="card-brand-mark">
                        <img
                            src="${u}"
                            alt="${v}"
                            class="brand-logo"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                        >
                        <i class="brand-icon-fallback" data-lucide="credit-card" style="display: none;" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="card-header">
                    <div class="card-card-badges">
                        <span class="card-meta-chip card-meta-chip--brand" ${_(`Bandeira ${v}`,"Rede de processamento usada por este cartao para compras e parcelamentos.")}>
                            <i data-lucide="badge-check"></i>
                            ${v}
                        </span>
                        <span class="card-meta-chip card-meta-chip--status ${e.temFaturaPendente?"is-pending":"is-ok"}" ${_(g,e.temFaturaPendente?"Ha uma fatura aberta para este cartao que merece acompanhamento ou pagamento.":"Sem pendencias abertas para o ciclo atual deste cartao.")}>
                            <i data-lucide="${e.temFaturaPendente?"circle-alert":"badge-check"}"></i>
                            ${g}
                        </span>
                        ${V}
                        ${j}
                    </div>
                </div>

                <div class="card-content">
                    <h3 class="card-name">${b}</h3>
                    <p class="card-institution">${h}</p>
                    <div class="card-subline">
                        <span class="card-last-digits">Final ${L(e.ultimos_digitos,"0000")}</span>
                        <span class="card-subline-dot" aria-hidden="true"></span>
                        <span class="card-account" ${_("Conta vinculada","Conta usada como referencia para organizar o pagamento da fatura deste cartao.")}>${C}</span>
                    </div>
                </div>

                <div class="card-actions">
                    ${x(e)?`
                    <span class="card-meta-chip card-meta-chip--brand" ${_("Somente visualizacao","Esse cartao de exemplo nao abre menu nem fatura.")}>
                        <i data-lucide="eye"></i>
                        Visual
                    </span>`:`<button
                        type="button"
                        class="card-overflow-btn"
                        data-card-interactive
                        onclick="event.stopPropagation(); cartoesManager.moreCartao(${e.id}, event)"
                        aria-label="Mais acoes"
                        ${_("Mais acoes","Abra o menu para ver a fatura, editar ou arquivar este cartao.")}>
                        <i data-lucide="more-horizontal" aria-hidden="true"></i>
                    </button>`}
                </div>

                <div class="card-limit-panel">
                    <span class="card-balance-caption">Limite disponivel</span>
                    <strong class="card-limit-available ${t<0?"is-negative":""}">${s.formatMoney(t)}</strong>
                    <p class="card-limit-total">de ${s.formatMoney(a)} de limite total</p>
                </div>

                <div class="card-details">
                    <div class="card-detail-item ${p.className}">
                        <span class="card-detail-label">Uso do limite</span>
                        <strong class="card-detail-value">${T(r)}</strong>
                    </div>
                    <div class="card-detail-item">
                        <span class="card-detail-label">Fechamento</span>
                        <strong class="card-detail-value">${E}</strong>
                    </div>
                    <div class="card-detail-item">
                        <span class="card-detail-label">Vencimento</span>
                        <strong class="card-detail-value">${F}</strong>
                    </div>
                </div>

                <div class="card-progress">
                    <div class="card-progress-head">
                        <span>Uso do limite: ${T(r)}</span>
                        <span>${p.summary}</span>
                    </div>
                    <div class="limit-bar" aria-hidden="true">
                        <span class="limit-fill ${p.className}" style="width: ${c}%"></span>
                    </div>
                    <div class="card-progress-foot">
                        <span>Ja utilizado ${s.formatMoney(o)}</span>
                        <span>${k}</span>
                    </div>
                </div>
            </article>
        `},updateStats(){const e=l.cartoes.reduce((a,t)=>{const o=parseFloat(t.limite_total)||0,r=parseFloat(t.limite_disponivel_real??t.limite_disponivel)||0,n=parseFloat(t.limite_utilizado)||Math.max(0,o-r);return a.total+=1,a.limiteTotal+=o,a.limiteDisponivel+=r,a.limiteUtilizado+=n,a},{total:0,limiteTotal:0,limiteDisponivel:0,limiteUtilizado:0});document.getElementById("totalCartoes").textContent=String(e.total),document.getElementById("statLimiteTotal").textContent=s.formatMoney(e.limiteTotal),document.getElementById("limiteDisponivel").textContent=s.formatMoney(e.limiteDisponivel),document.getElementById("limiteUtilizado").textContent=s.formatMoney(e.limiteUtilizado),d.animateStats()},animateStats(){document.querySelectorAll(".stat-card").forEach((e,a)=>{e.style.animation="none",setTimeout(()=>{e.style.animation="fadeIn 0.5s ease forwards"},a*100)})},renderFilterSummary(){const e=document.getElementById("cartoesFilterSummary");if(!e)return;const a=l.cartoes.length,t=l.filteredCartoes.length,o=l.cartoes.filter(i=>i.temFaturaPendente).length,r=l.cartoes.filter(i=>R(i.percentual_uso)>=80).length,n=l.lastLoadedAt?new Date(l.lastLoadedAt).toLocaleTimeString("pt-BR",{hour:"2-digit",minute:"2-digit"}):null,c=A()?`Mostrando ${t} de ${a} cartoes com os filtros atuais.`:a?"Painel consolidado com limite, faturas e cartoes que pedem atencao.":"Cadastre seu primeiro cartao para acompanhar limite e vencimentos aqui.",u=[`<span class="cartoes-summary-pill neutral">${t} visiveis</span>`];l.currentFilter!=="all"&&u.push(`<span class="cartoes-summary-pill accent">Bandeira: ${L(oe[l.currentFilter]||l.currentFilter)}</span>`),l.searchTerm&&u.push(`<span class="cartoes-summary-pill info">Busca: ${L(l.searchTerm)}</span>`),A()||(u.push(`<span class="cartoes-summary-pill ${o?"warning":"success"}">${o} com fatura pendente</span>`),u.push(`<span class="cartoes-summary-pill ${r?"danger":"success"}">${r} com uso alto</span>`)),n&&u.push(`<span class="cartoes-summary-pill subtle">Atualizado as ${L(n)}</span>`),e.innerHTML=`
            <div class="cartoes-summary-row">
                <div class="cartoes-summary-copy">
                    <i data-lucide="${A()?"filter":"sparkles"}"></i>
                    <span>${c}</span>
                </div>
                <div class="cartoes-summary-pills">
                    ${u.join("")}
                </div>
            </div>
        `,I()},updateView(){const e=document.getElementById("cartoesGrid");e&&(e.classList.toggle("list-view",l.currentView==="list"),document.querySelectorAll(".view-btn").forEach(a=>{a.classList.toggle("active",a.dataset.view===l.currentView)}),localStorage.setItem("cartoes_view_mode",l.currentView),d.renderFilterSummary())},setModalSubmitState(e,a=!1){const t=document.getElementById("btnSalvarCartao"),o=document.getElementById("cartaoSubmitLabel");if(!t||!o)return;t.disabled=e,t.setAttribute("aria-busy",e?"true":"false"),o.textContent=e?a?"Salvando alteracoes...":"Salvando cartao...":a?"Salvar alteracoes":"Salvar cartao";const r=t.querySelector("[data-lucide], svg");r?.getAttribute&&(r.setAttribute("data-lucide",e?"loader-2":"save"),r.classList.toggle("icon-spin",e)),I()},async openModal(e="create",a=null){const t=document.getElementById("modalCartaoOverlay"),o=document.getElementById("modalCartao"),r=document.getElementById("formCartao"),n=document.getElementById("modalCartaoTitulo"),c=document.getElementById("modalCartaoSubtitle"),u=o?.querySelector(".modal-header");if(!t||!o||!r||!n||!c)return;if(typeof e!="string"){const C=l.cartoes.find(h=>h.id===Number(e));C?(a=C,e="edit"):e="create"}r.reset(),document.getElementById("cartaoId").value="",document.getElementById("limiteTotal").value="0,00",document.getElementById("contaVinculada").value="",document.getElementById("cartaoCanalInapp").checked=!0,document.getElementById("cartaoCanalEmail").checked=!1,d.syncReminderChannels();const i=await f.API.loadContasSelect(),p=e==="edit"&&!!a;p&&a?(n.textContent="Editar cartao de credito",c.textContent="Revise os dados e ajuste limite, vencimento ou conta vinculada.",document.getElementById("cartaoId").value=a.id,document.getElementById("nomeCartao").value=a.nome_cartao||"",document.getElementById("contaVinculada").value=a.conta_id||"",document.getElementById("bandeira").value=a.bandeira||"",document.getElementById("ultimosDigitos").value=a.ultimos_digitos||"",document.getElementById("limiteTotal").value=d.formatMoneyValue(a.limite_total||0),document.getElementById("diaFechamento").value=a.dia_fechamento||"",document.getElementById("diaVencimento").value=a.dia_vencimento||"",document.getElementById("cartaoLembreteAviso").value=a.lembrar_fatura_antes_segundos||"",document.getElementById("cartaoCanalInapp").checked=a.fatura_canal_inapp!==!1&&a.fatura_canal_inapp!==0,document.getElementById("cartaoCanalEmail").checked=!!a.fatura_canal_email,u&&(u.style.background=z(a))):(n.textContent="Novo cartao de credito",c.textContent=i?"Cadastre o cartao e vincule a conta usada para pagar a fatura.":"Antes de cadastrar um cartao, você precisa ter ao menos uma conta.",u&&(u.style.background="")),d.syncReminderChannels(),d.setModalSubmitState(!1,p),t.classList.add("active"),d.setScrollLock(!0),setTimeout(()=>{document.getElementById(i?"nomeCartao":"contaVinculada")?.focus()},80)},closeModal(){const e=document.getElementById("modalCartaoOverlay");if(!e)return;e.classList.remove("active"),d.setScrollLock(!1);const a=document.querySelector("#modalCartao .modal-header");a&&(a.style.background=""),l.isSaving=!1,d.setModalSubmitState(!1,!1),setTimeout(()=>{document.getElementById("formCartao")?.reset(),document.getElementById("cartaoId").value="",document.getElementById("limiteTotal").value="0,00",d.syncReminderChannels()},180)},setupCardActions(){document.querySelectorAll(".credit-card").forEach(e=>{e.addEventListener("click",a=>{if(a.target.closest("[data-card-interactive], .card-context-menu"))return;const t=parseInt(e.dataset.id,10);Number.isFinite(t)&&d.showCardDetails(t)}),e.addEventListener("keydown",a=>{if(a.key!=="Enter"&&a.key!==" ")return;a.preventDefault();const t=parseInt(e.dataset.id,10);Number.isFinite(t)&&d.showCardDetails(t)})})},closeCardMenu(){document.querySelector(".card-context-menu")?.remove(),typeof d._cardMenuCleanup=="function"&&(d._cardMenuCleanup(),d._cardMenuCleanup=null)},showCardMenu(e,a){a&&(a.stopPropagation(),a.preventDefault());const t=document.querySelector(".card-context-menu");if(t&&t.dataset.cartaoId===String(e)){d.closeCardMenu();return}d.closeCardMenu();const o=document.createElement("div");o.className="card-context-menu",o.dataset.cartaoId=String(e);const r=l.cartoes.find(v=>v.id===e);if(x(r)){N();return}const n=r?.temFaturaPendente?"Pagar fatura":"Ver fatura",c=r?.temFaturaPendente?"wallet":"file-text",u=`${s.getBaseUrl()}importacoes?import_target=cartao&source_type=ofx&cartao_id=${e}`;o.style.setProperty("--card-accent",U(r)),o.innerHTML=`
            <button type="button" class="card-context-item" data-card-menu-action="invoice">
                <i data-lucide="${c}"></i>
                <span>${n}</span>
            </button>
            <button type="button" class="card-context-item" data-card-menu-action="import-ofx">
                <i data-lucide="upload"></i>
                <span>Importar OFX</span>
            </button>
            <button type="button" class="card-context-item" data-card-menu-action="edit">
                <i data-lucide="pencil"></i>
                <span>Editar</span>
            </button>
            <button type="button" class="card-context-item danger" data-card-menu-action="archive">
                <i data-lucide="archive"></i>
                <span>Arquivar</span>
            </button>
        `,document.body.appendChild(o),I();const i=a?.target?.closest(".card-overflow-btn"),p=()=>{if(!i)return;const v=i.getBoundingClientRect(),g=o.offsetWidth||188,E=o.offsetHeight||156,F=Math.min(window.scrollX+window.innerWidth-g-12,Math.max(window.scrollX+12,v.right+window.scrollX-g)),k=Math.min(window.scrollY+window.innerHeight-E-12,v.bottom+window.scrollY+8);o.style.left=`${F}px`,o.style.top=`${k}px`};requestAnimationFrame(p),o.querySelectorAll("[data-card-menu-action]").forEach(v=>{v.addEventListener("click",g=>{switch(g.stopPropagation(),v.dataset.cardMenuAction){case"invoice":window.cartoesManager?.verFatura?.(e);break;case"import-ofx":window.location.href=u;break;case"edit":window.cartoesManager?.editCartao?.(e);break;case"archive":window.cartoesManager?.arquivarCartao?.(e);break}d.closeCardMenu()})});const C=v=>{!o.contains(v.target)&&!v.target.closest(".card-overflow-btn")&&d.closeCardMenu()},h=v=>{v.key==="Escape"&&d.closeCardMenu()},b=()=>p();document.addEventListener("click",C),document.addEventListener("keydown",h),window.addEventListener("resize",b),window.addEventListener("scroll",b,!0),d._cardMenuCleanup=()=>{document.removeEventListener("click",C),document.removeEventListener("keydown",h),window.removeEventListener("resize",b),window.removeEventListener("scroll",b,!0)}},async showCardDetails(e){const a=l.cartoes.find(t=>t.id===e);if(a){if(x(a)){N();return}if(window.LK_CardDetail?.open){window.LK_CardDetail.open(e,a.nome_cartao||a.nome||"Cartao",z(a),re());return}f.Fatura?.verFatura?.(e)}},async exportarRelatorio(){if(!l.filteredCartoes?.length){typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"info",title:"Nenhum cartao para exportar",text:"Adicione cartoes ou altere os filtros.",showConfirmButton:!1,timer:3e3,timerProgressBar:!0});return}try{const{jsPDF:e}=window.jspdf,a=new e,t=new Date,o=t.toLocaleDateString("pt-BR",{month:"long",year:"numeric"}),r=l.filteredCartoes.reduce((g,E)=>g+parseFloat(E.limite_total||0),0),n=l.filteredCartoes.reduce((g,E)=>g+parseFloat((E.limite_disponivel_real??E.limite_disponivel)||0),0),c=r-n,u=r>0?(c/r*100).toFixed(1):0,i=[230,126,34],p=[26,31,46],C=[248,249,250];a.setFillColor(...i),a.rect(0,0,210,35,"F"),a.setTextColor(255,255,255),a.setFontSize(22),a.setFont(void 0,"bold"),a.text("RELATORIO DE CARTOES DE CREDITO",105,15,{align:"center"}),a.setFontSize(10),a.setFont(void 0,"normal"),a.text(`Periodo: ${o}`,105,22,{align:"center"}),a.text(`Gerado em: ${t.toLocaleDateString("pt-BR")} as ${t.toLocaleTimeString("pt-BR")}`,105,28,{align:"center"});let h=45;a.setTextColor(...p),a.setFontSize(14),a.setFont(void 0,"bold"),a.text("RESUMO FINANCEIRO",14,h),h+=8,a.autoTable({startY:h,head:[["Indicador","Valor"]],body:[["Total de Cartoes",l.filteredCartoes.length.toString()],["Limite Total Combinado",s.formatMoney(r)],["Limite Utilizado",s.formatMoney(c)],["Limite Disponivel",s.formatMoney(n)],["Percentual de Utilizacao",`${u}%`]],theme:"grid",headStyles:{fillColor:i,textColor:[255,255,255],fontStyle:"bold",halign:"left"},columnStyles:{0:{cellWidth:100,fontStyle:"bold"},1:{cellWidth:86,halign:"right"}},styles:{fontSize:10,cellPadding:5},alternateRowStyles:{fillColor:C}}),h=a.lastAutoTable.finalY+15,a.setFontSize(14),a.setFont(void 0,"bold"),a.text("DETALHAMENTO POR CARTAO",14,h),h+=5;const b=l.filteredCartoes.map(g=>{const E=g.limite_disponivel_real??g.limite_disponivel??0,F=g.limite_total>0?((g.limite_total-E)/g.limite_total*100).toFixed(1):0;return[g.nome_cartao,s.formatBandeira(g.bandeira),`**** ${g.ultimos_digitos}`,s.formatMoney(g.limite_total),s.formatMoney(E),`${F}%`,g.ativo?"Ativo":"Inativo"]});a.autoTable({startY:h,head:[["Cartao","Bandeira","Final","Limite Total","Disponivel","Uso","Status"]],body:b,theme:"grid",headStyles:{fillColor:i,textColor:[255,255,255],fontStyle:"bold",halign:"center"},columnStyles:{0:{cellWidth:40},1:{cellWidth:25,halign:"center"},2:{cellWidth:25,halign:"center"},3:{cellWidth:28,halign:"right"},4:{cellWidth:28,halign:"right"},5:{cellWidth:18,halign:"center"},6:{cellWidth:22,halign:"center"}},styles:{fontSize:9,cellPadding:4},alternateRowStyles:{fillColor:C}});const v=a.internal.getNumberOfPages();for(let g=1;g<=v;g++)a.setPage(g),a.setFontSize(8),a.setTextColor(128,128,128),a.text(`Pagina ${g} de ${v} | Lukrato - Sistema de Gestao Financeira`,105,287,{align:"center"});a.save(`relatorio_cartoes_${t.toISOString().split("T")[0]}.pdf`),s.showToast("success","Relatorio exportado com sucesso")}catch(e){console.error("Erro ao exportar:",e),s.showToast("error","Erro ao exportar relatorio")}}};f.UI=d;const m={verFatura(e,a=null,t=null){const o=new Date;a=a||o.getMonth()+1,t=t||o.getFullYear(),window.location.href=`${y.BASE_URL}faturas?cartao_id=${e}&mes=${a}&ano=${t}`},mostrarModalFatura(e,a=null,t=null,o=null){const r=document.querySelector(".modal-fatura-overlay");r&&r.remove();const n=m.criarModalFatura(e,a,t,o);window.LK?.modalSystem?window.LK.modalSystem.prepareOverlay(n,{scope:"page"}):document.body.appendChild(n),I(),setTimeout(()=>{n.classList.add("show")},10),n.addEventListener("click",c=>{c.target===n&&m.fecharModalFatura(n)}),n.querySelector(".btn-fechar-fatura")?.addEventListener("click",()=>{m.fecharModalFatura(n)}),requestAnimationFrame(()=>{m.setupParcelaSelection(n,e)}),n.querySelector(".btn-pagar-fatura")?.addEventListener("click",()=>{m.pagarParcelasSelecionadas(e)})},setupParcelaSelection(e,a){const t=e.querySelector("#selectAllParcelas"),o=e.querySelectorAll(".parcela-checkbox"),r=e.querySelector("#totalSelecionado");if(e.dataset.parcelasConfigured==="true")return;e.dataset.parcelasConfigured="true";const n=()=>{let c=0;o.forEach(u=>{u.checked&&(c+=parseFloat(u.dataset.valor))}),r&&(r.textContent=s.formatMoney(c))};t&&t.addEventListener("change",c=>{o.forEach(u=>{u.checked=c.target.checked}),n()}),o.forEach(c=>{c.addEventListener("change",()=>{if(n(),t){const u=Array.from(o).every(i=>i.checked);t.checked=u}})}),n()},async pagarParcelasSelecionadas(e){const a=document.querySelectorAll(".parcela-checkbox:checked");if(a.forEach((r,n)=>{}),a.length===0){await Swal.fire({icon:"warning",title:"Atenção",text:"Selecione pelo menos uma parcela para pagar."});return}let t=0;a.forEach(r=>{const n=parseFloat(r.dataset.valor);t+=n}),await s.showConfirmDialog("Confirmar Pagamento",`Deseja pagar ${a.length} parcela(s) no valor total de ${s.formatMoney(t)}?`)&&await f.API.pagarParcelasIndividuais(a,e)},criarModalFatura(e,a=null,t=null,o=null){const r=s.resolverCorCartao(e,o),n=document.createElement("div");return n.className="modal-fatura-overlay",n.innerHTML=`<div class="modal-fatura-container" style="--card-accent: ${r};">${m.criarConteudoModal(e,a,t,o)}</div>`,n},criarConteudoModal(e,a=null,t=null,o=null){const r=o||e.cartao_id||e.cartao?.id;if(t&&t.pago)return m.criarConteudoModalFaturaPaga(e,t,a,r);const n=(e.itens||[]).filter(i=>!i.pago).length,c=(e.itens||[]).filter(i=>i.pago).length,u=e.cartao?.bandeira?s.getBrandIcon(e.cartao.bandeira):null;return`
                <div class="modal-fatura-header">
                    <div class="header-top-row">
                        <div class="header-card-identity">
                            ${u?`<img src="${u}" alt="${e.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">`:""}
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
                    ${n===0&&c===0?`
                        <div class="fatura-empty">
                            <div class="empty-icon-wrap">
                                <i data-lucide="inbox"></i>
                            </div>
                            <h3>Nenhum lançamento</h3>
                            <p>Não há compras registradas neste mês.</p>
                        </div>
                    `:n===0&&c>0?`
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
                                    <span class="secao-titulo-count">${c}</span>
                                </div>
                                <div class="lancamentos-lista">
                                    ${(e.itens||[]).filter(i=>i.pago).map(i=>m.renderItemPago(i)).join("")}
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
                                                ${m.renderBadgeRecorrente(i)}
                                            </span>
                                            ${i.data_compra?`<span class="lanc-data-compra"><i data-lucide="shopping-cart"></i> ${s.formatDate(i.data_compra)}</span>`:""}
                                        </div>
                                        <span class="lanc-valor">${s.formatMoney(i.valor)}</span>
                                    </div>
                                `).join("")}
                            </div>
                        </div>

                        ${c>0?`
                            <div class="fatura-parcelas-pagas">
                                <div class="secao-titulo-bar">
                                    <span class="secao-titulo-text"><i data-lucide="circle-check"></i> Pagos</span>
                                    <span class="secao-titulo-count">${c}</span>
                                </div>
                                <div class="lancamentos-lista">
                                    ${(e.itens||[]).filter(i=>i.pago).map(i=>m.renderItemPago(i)).join("")}
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
                        ${m.renderBadgeRecorrente(e)}
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

Esta ação criará um lançamento de despesa na conta vinculada e liberará o limite do cartão.`,"Sim, Pagar"))return;const t=document.querySelector(".btn-pagar-fatura"),o=t?t.innerHTML:"";try{t&&(t.disabled=!0,t.innerHTML='<i data-lucide="loader-2" class="icon-spin"></i> Processando...',I(),t.style.opacity="0.6",t.style.cursor="not-allowed");const r=await Y(`${y.API_URL}/cartoes/${e.cartao.id}/fatura/pagar`,{mes:e.mes,ano:e.ano}),n=w(r,null);n?.gamification?.achievements&&Array.isArray(n.gamification.achievements)&&(typeof window.notifyMultipleAchievements=="function"?window.notifyMultipleAchievements(n.gamification.achievements):console.error("❌ notifyMultipleAchievements não está disponível")),s.showToast("success",`Fatura paga com sucesso! ${n?.itens_pagos??""} parcela(s) quitada(s).`);const c=document.querySelector(".modal-fatura-overlay");c&&m.fecharModalFatura(c),f.API.loadCartoes()}catch(r){console.error("❌ Erro ao pagar fatura:",r),t&&(t.disabled=!1,t.innerHTML=o,t.style.opacity="1",t.style.cursor="pointer"),s.showToast("error",M(r,"Erro ao pagar fatura"))}},criarConteudoModalFaturaPaga(e,a,t,o){const r=o||e.cartao_id||e.cartao?.id,n=(e.itens||[]).filter(i=>i.pago).length,c=e.cartao?.bandeira?s.getBrandIcon(e.cartao.bandeira):null,u=a?.data_pagamento||(e.itens||[]).find(i=>i.pago&&i.data_pagamento)?.data_pagamento||null;return`
            <div class="modal-fatura-header modal-fatura-header--paga">
                <div class="header-top-row">
                    <div class="header-card-identity">
                        ${c?`<img src="${c}" alt="${e.cartao.bandeira}" class="header-brand-logo" onerror="this.style.display='none'">`:""}
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
                            ${u?`Pago em ${s.formatDate(u)} &bull; `:""}
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
                            ${(e.itens||[]).filter(i=>i.pago).map(i=>m.renderItemPago(i)).join("")}
                        </div>
                    </div>
                </div>
            </div>
        `},async navegarMes(e,a,t,o){let r=a+o,n=t;r>12?(r=1,n++):r<1&&(r=12,n--);try{const[c,u,i]=await Promise.all([B(`${y.API_URL}/cartoes/${e}/fatura`,{mes:r,ano:n}).catch(()=>null),B(`${y.API_URL}/cartoes/${e}/parcelamentos-resumo`,{mes:r,ano:n}).catch(()=>null),B(`${y.API_URL}/cartoes/${e}/fatura/status`,{mes:r,ano:n}).catch(()=>null)]);if(!c)throw new Error("Erro ao carregar fatura");const p=c.data||c;let C=null,h=null;u&&(C=u.data||u),i&&(h=i.data||i);const b=document.querySelector(".modal-fatura-container");if(b){const v=s.resolverCorCartao(p,e);b.style.setProperty("--card-accent",v);const g=m.criarConteudoModal(p,C,h,e);b.innerHTML=g,I(),b.querySelector(".btn-fechar-fatura")?.addEventListener("click",()=>{const F=document.querySelector(".modal-fatura-overlay");m.fecharModalFatura(F)}),b.querySelector(".btn-pagar-fatura")?.addEventListener("click",()=>{m.pagarParcelasSelecionadas(p)});const E=document.querySelector(".modal-fatura-overlay");requestAnimationFrame(()=>{m.setupParcelaSelection(E,p)})}}catch(c){console.error("❌ Erro ao navegar entre meses:",c),s.showToast("error",M(c,"Erro ao carregar fatura"))}},async toggleHistoricoFatura(e){try{const a=document.querySelector(".modal-fatura-container");if(!a)return;if(a.querySelector(".historico-faturas")){const o=new Date,r=o.getMonth()+1,n=o.getFullYear(),[c,u,i]=await Promise.all([f.API.carregarFatura(e,r,n),f.API.carregarParcelamentosResumo(e,r,n).catch(()=>null),B(`${y.API_URL}/cartoes/${e}/fatura/status`,{mes:r,ano:n}).then(C=>w(C,null)).catch(()=>null)]),p=m.criarConteudoModal(c,u,i,e);a.innerHTML=p,I(),m.adicionarEventListenersModal(c)}else{const o=await f.API.carregarHistoricoFaturas(e),r=m.criarConteudoHistorico(o,e);a.innerHTML=r,I(),m.adicionarEventListenersModal(null)}}catch(a){console.error("❌ Erro ao alternar histórico:",a),s.showToast("error","Erro ao carregar histórico")}},criarConteudoHistorico(e,a){return`
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
        `},adicionarEventListenersModal(e){const a=document.querySelector(".modal-fatura-container");a&&(a.querySelector(".btn-fechar-fatura")?.addEventListener("click",()=>{const t=document.querySelector(".modal-fatura-overlay");m.fecharModalFatura(t)}),e&&a.querySelector(".btn-pagar-fatura")?.addEventListener("click",()=>{m.pagarFatura(e)}))}};f.Fatura=m;const se={toggleCartoesHero:"cartoesHero",toggleCartoesKpis:"cartoesKpis",toggleCartoesToolbar:"cartoesToolbar"},H={toggleCartoesHero:!0,toggleCartoesKpis:!0,toggleCartoesToolbar:!0},ie={...H,toggleCartoesKpis:!1,toggleCartoesToolbar:!1};async function ce(){return ee("cartoes")}async function le(e){await Q("cartoes",e)}const D=Z({storageKey:"lk_cartoes_prefs",sectionMap:se,completeDefaults:H,essentialDefaults:ie,loadPreferences:ce,savePreferences:le,modal:{overlayId:"cartoesCustomizeModalOverlay",openButtonId:"btnCustomizeCartoes",closeButtonId:"btnCloseCustomizeCartoes",saveButtonId:"btnSaveCustomizeCartoes",presetEssentialButtonId:"btnPresetEssencialCartoes",presetCompleteButtonId:"btnPresetCompletoCartoes"}});function O(){D.init()}f.Customize={init:O,open:D.open,close:D.close};const de=async()=>{O(),d.setupEventListeners(),d.restoreViewPreference(),await f.API.loadCartoes()},ue=e=>l.cartoes.find(t=>t.id===e)?.is_demo?(s.showToast("info","Esse cartao e apenas um exemplo. Crie um cartao real para abrir a fatura."),!0):!1;window.cartoesManager={openModal:(e="create",a=null)=>d.openModal(e,a),closeModal:()=>d.closeModal(),moreCartao:(e,a)=>d.showCardMenu(e,a),editCartao:e=>f.API.editCartao(e),arquivarCartao:e=>f.API.arquivarCartao(e),deleteCartao:e=>f.API.deleteCartao(e),exportarRelatorio:()=>d.exportarRelatorio(),mostrarModalFatura:(e,a,t)=>m.mostrarModalFatura(e,a,t),verFatura:e=>{ue(e)||m.verFatura(e)},fecharModalFatura:()=>m.fecharModalFatura(),navegarMes:(e,a,t,o)=>m.navegarMes(e,a,t,o),pagarFatura:(e,a,t)=>m.pagarFatura(e,a,t),pagarParcelasSelecionadas:(e,a)=>m.pagarParcelasSelecionadas(e,a),toggleHistoricoFatura:e=>m.toggleHistoricoFatura(e),dismissAlerta:e=>f.API.dismissAlerta(e),loadCartoes:()=>f.API.loadCartoes(),desfazerPagamento:(e,a,t)=>f.API.desfazerPagamento(e,a,t),desfazerPagamentoParcela:e=>f.API.desfazerPagamentoParcela(e)};window.__CARTOES_MANAGER_INITIALIZED__||(window.__CARTOES_MANAGER_INITIALIZED__=!0,document.addEventListener("DOMContentLoaded",()=>de()));
