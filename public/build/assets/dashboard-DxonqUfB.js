import{m as se,l as T,k as me,i as _e,e as he,c as ae,d as De,p as Re}from"./api-DpYnTMaG.js";import{a as Pe,i as He,g as Se,o as Ee,r as Fe}from"./primary-actions-D39VL42P.js";import{o as Le,g as ne,e as pe,d as Oe,a as ze}from"./runtime-config-CXTcOn9X.js";import{t as $e,u as Ve}from"./finance-CgaDv1sH.js";import{j as qe,k as Ue}from"./lancamentos-BFIM3VKH.js";import{e as k}from"./utils-BWRVfML-.js";import{c as je,p as Ke,f as Ge}from"./ui-preferences-Bh_GTAc4.js";const O={BASE_URL:se(),TRANSACTIONS_LIMIT:5,CHART_MONTHS:6,ANIMATION_DELAY:300},_={saldoValue:document.getElementById("saldoValue"),receitasValue:document.getElementById("receitasValue"),despesasValue:document.getElementById("despesasValue"),saldoMesValue:document.getElementById("saldoMesValue"),categoryChart:document.getElementById("categoryChart"),chartLoading:document.getElementById("chartLoading"),transactionsList:document.getElementById("transactionsList"),emptyState:document.getElementById("emptyState"),metasBody:document.getElementById("sectionMetasBody"),cartoesBody:document.getElementById("sectionCartoesBody"),contasBody:document.getElementById("sectionContasBody"),orcamentosBody:document.getElementById("sectionOrcamentosBody"),faturasBody:document.getElementById("sectionFaturasBody"),chartContainer:document.getElementById("categoryChart"),tableBody:document.getElementById("transactionsTableBody"),table:document.getElementById("transactionsTable"),cardsContainer:document.getElementById("transactionsCards"),monthLabel:document.getElementById("currentMonthText"),streakDays:document.getElementById("streakDays"),badgesGrid:document.getElementById("badgesGrid"),userLevel:document.getElementById("userLevel"),totalLancamentos:document.getElementById("totalLancamentos"),totalCategorias:document.getElementById("totalCategorias"),mesesAtivos:document.getElementById("mesesAtivos"),pontosTotal:document.getElementById("pontosTotal")},I={chartInstance:null,currentMonth:null,isLoading:!1},v={money:o=>{try{return Number(o||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}catch{return"R$ 0,00"}},dateBR:o=>{if(!o)return"-";try{const t=String(o).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);return t?`${t[3]}/${t[2]}/${t[1]}`:"-"}catch{return"-"}},formatMonth:o=>{try{const[e,t]=String(o).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})}catch{return"-"}},formatMonthShort:o=>{try{const[e,t]=String(o).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"short"})}catch{return"-"}},getCurrentMonth:()=>window.LukratoHeader?.getMonth?.()||new Date().toISOString().slice(0,7),getPreviousMonths:(o,e)=>{const t=[],[a,r]=o.split("-").map(Number);for(let n=e-1;n>=0;n--){const i=new Date(a,r-1-n,1),s=i.getFullYear(),l=String(i.getMonth()+1).padStart(2,"0");t.push(`${s}-${l}`)}return t},getCssVar:(o,e="")=>{try{return(getComputedStyle(document.documentElement).getPropertyValue(o)||"").trim()||e}catch{return e}},isLightTheme:()=>{try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getContaLabel:o=>{if(typeof o.conta=="string"&&o.conta.trim())return o.conta.trim();const e=o.conta_instituicao??o.conta_nome??o.conta?.instituicao??o.conta?.nome??null,t=o.conta_destino_instituicao??o.conta_destino_nome??o.conta_destino?.instituicao??o.conta_destino?.nome??null;return o.eh_transferencia&&(e||t)?`${e||"-"}${t||"-"}`:o.conta_label&&String(o.conta_label).trim()?String(o.conta_label).trim():e||"-"},getTipoClass:o=>{const e=String(o||"").toLowerCase();return e==="receita"?"receita":e.includes("despesa")?"despesa":e.includes("transferencia")?"transferencia":""},removeLoadingClass:()=>{setTimeout(()=>{document.querySelectorAll(".kpi-value.loading").forEach(o=>{o.classList.remove("loading")})},O.ANIMATION_DELAY)}},ye=()=>{const o=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||v.isLightTheme?.();return{isLightTheme:o,axisColor:o?v.getCssVar("--color-primary","#e67e22")||"#e67e22":"rgba(255, 255, 255, 0.6)",yTickColor:o?"#000":"#fff",xTickColor:o?v.getCssVar("--color-text-muted","#6c757d")||"#6c757d":"rgba(255, 255, 255, 0.6)",gridColor:o?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",tooltipBg:o?"rgba(255, 255, 255, 0.92)":"rgba(0, 0, 0, 0.85)",tooltipColor:o?"#0f172a":"#f8fafc",labelColor:o?"#0f172a":"#f8fafc"}};function We(){return"api/v1/dashboard/overview"}function Qe(){return"api/v1/dashboard/evolucao"}const Ye=3e4;function Xe(o,e){return`dashboard:overview:${o}:${e}`}function W(o=v.getCurrentMonth(),{limit:e=O.TRANSACTIONS_LIMIT,force:t=!1}={}){return Pe(We(),{month:o,limit:e},{cacheKey:Xe(o,e),ttlMs:Ye,force:t})}function R(o=null){const e=o?`dashboard:overview:${o}:`:"dashboard:overview:";He(e)}class Je{constructor(e="greetingContainer"){this.container=document.getElementById(e),this.userName=this.getUserName(),this._listeningDataChanged=!1,Le(()=>{this.userName=this.getUserName(),this.updateGreetingTitle()})}getUserName(){return String(ne().username||"Usuario").trim().split(/\s+/)[0]||"Usuario"}render(){if(!this.container)return;this.userName=this.getUserName();const e=this.getGreeting(),a=new Date().toLocaleDateString("pt-BR",{weekday:"long",day:"numeric",month:"long"});this.container.innerHTML=`
      <div class="dashboard-greeting dashboard-greeting--compact" data-aos="fade-right" data-aos-duration="500">
        <p class="greeting-date">${a}</p>
        <p class="greeting-title">${e.title}</p>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    `,this.loadInsight(),pe({},{silent:!0})}updateGreetingTitle(){const e=this.container?.querySelector(".greeting-title");e&&(e.textContent=this.getGreeting().title)}getGreeting(){const e=new Date().getHours();return e>=5&&e<12?{title:`Bom dia, ${this.userName}.`}:e>=12&&e<18?{title:`Boa tarde, ${this.userName}.`}:e>=18&&e<24?{title:`Boa noite, ${this.userName}.`}:{title:`Boa madrugada, ${this.userName}.`}}async loadInsight({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t;a?.greeting_insight?this.displayInsight(a.greeting_insight):this.displayFallbackInsight()}catch(t){T("Error loading greeting insight",t,"Falha ao carregar insight"),this.displayFallbackInsight()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{R(),this.loadInsight({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.loadInsight({force:!0})}))}displayInsight(e){const t=document.getElementById("greetingInsight");if(!t)return;const{message:a,icon:r,color:n}=e;t.innerHTML=`
      <div class="insight-content">
        <div class="insight-icon" style="color: ${n||"var(--color-primary)"};">
          <i data-lucide="${r||"sparkles"}" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">${a}</p>
      </div>
    `,typeof window.lucide<"u"&&window.lucide.createIcons()}displayFallbackInsight(){const e=document.getElementById("greetingInsight");e&&(e.innerHTML=`
      <div class="insight-content">
        <div class="insight-icon">
          <i data-lucide="sparkles" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">Seu resumo financeiro do mes aparece logo abaixo.</p>
      </div>
    `,typeof window.lucide<"u"&&window.lucide.createIcons())}}window.DashboardGreeting=Je;class Ze{constructor(e="healthScoreContainer"){this.container=document.getElementById(e),this.healthScore=0,this.maxScore=100,this.animationDuration=1200}render(){if(!this.container)return;const e=45;this.circumference=2*Math.PI*e;const t=this.circumference;this.container.innerHTML=`
      <div class="health-score-widget surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400">
        <div class="hs-header">
          <h2 class="hs-title">Saude financeira</h2>
          <div class="hs-badge" id="healthIndicator">
            <span class="hs-badge-dot"></span>
            <span class="hs-badge-text">...</span>
          </div>
        </div>

        <div class="hs-gauge-area">
          <svg class="hs-gauge" viewBox="0 0 100 100">
            <defs>
              <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#10b981"/>
                <stop offset="100%" stop-color="#3b82f6"/>
              </linearGradient>
            </defs>
            <circle cx="50" cy="50" r="${e}" class="hs-gauge-track"/>
            <circle cx="50" cy="50" r="${e}" class="hs-gauge-fill"
              id="gaugeCircle"
              stroke-dasharray="${t}"
              stroke-dashoffset="${t}"
            />
            <text x="50" y="47" class="hs-gauge-value" id="gaugeValue">0</text>
            <text x="50" y="60" class="hs-gauge-label">de 100</text>
          </svg>
        </div>

        <p class="hs-message" id="healthMessage">Carregando...</p>

        <div class="hs-breakdown">
          <div class="hs-metric">
            <span class="hs-metric-label">Registros</span>
            <span class="hs-metric-value" id="hsLancamentos">--</span>
          </div>
          <div class="hs-metric">
            <span class="hs-metric-label">Limites</span>
            <span class="hs-metric-value" id="hsOrcamento">--</span>
          </div>
          <div class="hs-metric">
            <span class="hs-metric-label">Metas</span>
            <span class="hs-metric-value" id="hsMetas">--</span>
          </div>
        </div>
      </div>
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t;a?.health_score&&this.updateScore(a.health_score)}catch(t){T("Error loading health score",t,"Falha ao carregar health score"),this.showError()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{R(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.load({force:!0})}))}updateScore(e){const{score:t=0}=e;this.animateGauge(t),this.updateBreakdown(e),this.updateStatusIndicator(t)}animateGauge(e){const t=document.getElementById("gaugeCircle"),a=document.getElementById("gaugeValue");if(!t||!a)return;const r=this.circumference||2*Math.PI*45;let n=0;const i=e/(this.animationDuration/16),s=()=>{n+=i,n>=e&&(n=e);const l=r-r*n/this.maxScore;t.setAttribute("stroke-dashoffset",l),a.textContent=Math.round(n),n<e&&requestAnimationFrame(s)};s()}updateBreakdown(e){const t=document.getElementById("hsLancamentos"),a=document.getElementById("hsOrcamento"),r=document.getElementById("hsMetas");if(t){const n=e.lancamentos??0;t.textContent=`${n}`,n>=10?t.className="hs-metric-value color-success":n>=5?t.className="hs-metric-value color-warning":t.className="hs-metric-value color-muted"}if(a){const n=e.orcamentos??0,i=e.orcamentos_ok??0;n===0?(a.textContent="--",a.className="hs-metric-value color-muted"):(a.textContent=`${i}/${n}`,i===n?a.className="hs-metric-value color-success":i>=n/2?a.className="hs-metric-value color-warning":a.className="hs-metric-value color-danger")}if(r){const n=e.metas_ativas??0,i=e.metas_concluidas??0;n===0?(r.textContent="--",r.className="hs-metric-value color-muted"):i>0?(r.textContent=`${n}+${i}`,r.className="hs-metric-value color-success"):(r.textContent=`${n}`,r.className="hs-metric-value color-warning")}}updateStatusIndicator(e){const t=document.getElementById("healthIndicator"),a=document.getElementById("healthMessage");if(!t)return;let r="critical",n="CRÍTICA",i="Ajustes rápidos podem evitar aperto financeiro.";e>=70?(r="excellent",n="BOA",i="Você está no controle. Continue assim!"):e>=50?(r="good",n="ESTÁVEL",i="Controle bom, mas há espaço para melhorar."):e>=30&&(r="warning",n="ATENÇÃO",i="Alguns sinais pedem cuidado neste mês."),t.className=`hs-badge hs-badge--${r}`,t.innerHTML=`
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${n}</span>
    `,a&&(a.textContent=i)}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}showError(){const e=document.getElementById("healthIndicator"),t=document.getElementById("healthMessage");e&&(e.className="hs-badge hs-badge--error",e.innerHTML=`
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `),t&&(t.textContent="Não foi possível carregar.")}}window.HealthScoreWidget=Ze;class et{constructor(e="healthScoreInsights"){this.container=document.getElementById(e),this.baseURL=se(),this.init()}init(){this.container&&(this._initialized||(this._initialized=!0,this.renderSkeleton(),this.loadInsights(),this._intervalId=setInterval(()=>this.loadInsights({force:!0}),3e5),document.addEventListener("lukrato:data-changed",()=>{R(),this.loadInsights({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.loadInsights({force:!0})})))}renderSkeleton(){this.container.innerHTML=`
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `}async loadInsights({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t;a?.health_score_insights?this.renderInsights(a.health_score_insights):this.renderEmpty()}catch(t){T("Error loading health score insights",t,"Falha ao carregar insights"),this.renderEmpty()}}renderInsights(e){const t=Array.isArray(e)?e:e?.insights||[],a=Array.isArray(e)?"":e?.total_possible_improvement||"";if(t.length===0){this.renderEmpty();return}const r=t.map((n,i)=>{const s=this.normalizeInsight(n);return`
      <a href="${this.baseURL}${s.action.url}" class="hsi-card hsi-card--${s.priority}" style="animation-delay: ${i*80}ms;">
        <div class="hsi-card-icon hsi-icon--${s.priority}">
          <i data-lucide="${this.getIconForType(s.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${s.title}</span>
          <span class="hsi-card-desc">${s.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${s.impact}</span>
          <i data-lucide="chevron-right" style="width:14px;height:14px;" class="hsi-arrow"></i>
        </div>
      </a>
    `}).join("");this.container.innerHTML=`
      <div class="hsi-list">${r}</div>
      ${a?`
        <div class="hsi-summary">
          <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
          <span>Potencial: <strong>${a}</strong></span>
        </div>
      `:""}
    `,typeof window.lucide<"u"&&window.lucide.createIcons()}normalizeInsight(e){const a={negative_balance:{title:"Seu saldo ficou negativo",impact:"Aja agora",action:{url:"lancamentos?tipo=despesa"}},low_activity:{title:"Registre mais movimentações",impact:"Mais controle",action:{url:"lancamentos"}},low_categories:{title:"Use mais categorias",impact:"Mais clareza",action:{url:"categorias"}},no_goals:{title:"Defina uma meta financeira",impact:"Mais direcao",action:{url:"financas#metas"}}}[e.type]||{title:"Insight do mes",impact:"Ver detalhe",action:{url:"dashboard"}};return{priority:e.priority||"medium",type:e.type||"generic",title:e.title||a.title,message:e.message||"",impact:e.impact||a.impact,action:e.action||a.action}}renderEmpty(){this.container.innerHTML=""}getIconForType(e){return{savings_rate:"piggy-bank",consistency:"calendar-check",diversification:"layers",negative_balance:"alert-triangle",low_balance:"wallet",no_income:"alert-circle",no_goals:"target"}[e]||"lightbulb"}}window.HealthScoreInsights=et;class tt{constructor(e="aiTipContainer"){this.container=document.getElementById(e),this.baseURL=se()}init(){this.container&&(this._initialized||(this._initialized=!0,this.render(),this.load(),document.addEventListener("lukrato:data-changed",()=>{R(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.load({force:!0})})))}render(){this.container.innerHTML=`
      <div class="ai-tip-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400" data-aos-delay="100">
        <div class="ai-tip-header">
          <i data-lucide="sparkles" class="ai-tip-header-icon"></i>
          <h2 class="ai-tip-title">Prioridades do mês</h2>
          <span class="ai-tip-badge" id="aiTipBadge" style="display:none;"></span>
        </div>
        <div class="ai-tip-list" id="aiTipList">
          ${'<div class="ai-tip-skeleton"></div>'.repeat(4)}
        </div>
      </div>
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t,r=this.buildTips(a);this.renderTips(r)}catch(t){T("Error loading AI tips",t,"Falha ao carregar dicas"),this.renderEmpty()}}buildTips(e){const t=[],a=e?.health_score||{},r=e?.metrics||{},n=e?.provisao?.provisao||{},i=e?.provisao?.vencidos||{},s=e?.provisao?.parcelas||{},l=e?.chart||[],u=Array.isArray(e?.health_score_insights)?e.health_score_insights:e?.health_score_insights?.insights||[],c={critical:0,high:1,medium:2,low:3};if(u.sort((f,E)=>(c[f.priority]??9)-(c[E.priority]??9)).forEach(f=>{const E=this.normalizeInsight(f);t.push({type:E.type,priority:E.priority,icon:E.icon,title:f.title||E.title,desc:f.message||E.message,url:E.url,metric:f.metric||null,metricLabel:f.metric_label||null})}),i.count>0){const f=(i.total||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"overdue",priority:"critical",icon:"clock",title:`${i.count} conta(s) em atraso`,desc:"Regularize para evitar juros e manter o score saudável.",url:"lancamentos?status=vencido",metric:f,metricLabel:"em atraso"})}const d=e?.provisao?.proximos||[];if(d.length>0){const f=d[0],E=f.data_pagamento?new Date(f.data_pagamento+"T00:00:00"):null,M=new Date;if(M.setHours(0,0,0,0),E){const x=Math.ceil((E-M)/864e5);if(x>=0&&x<=3){const H=(f.valor||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"upcoming",priority:"high",icon:"calendar",title:x===0?"Vence hoje!":`Vence em ${x} dia(s)`,desc:f.titulo||"Conta próxima do vencimento",url:"lancamentos",metric:H,metricLabel:x===0?"hoje":`${x}d`})}}}if(e?.greeting_insight){const f=e.greeting_insight;t.push({type:"greeting",priority:"positive",icon:f.icon||"trending-up",title:f.message||"Evolução do mês",desc:"",url:null,metric:null,metricLabel:null})}const p=a.savingsRate??0;(r.receitas??0)>0&&p>=20&&t.push({type:"savings",priority:"positive",icon:"piggy-bank",title:"Ótima taxa de economia!",desc:"Você está guardando acima dos 20% recomendados.",url:null,metric:p+"%",metricLabel:"guardado"});const g=a.orcamentos??0,h=a.orcamentos_ok??0;if(g>0){const f=g-h;f>0?t.push({type:"budget",priority:"high",icon:"alert-circle",title:`${f} orçamento(s) estourado(s)`,desc:"Revise seus gastos para voltar ao controle.",url:"financas",metric:`${h}/${g}`,metricLabel:"no limite"}):t.push({type:"budget",priority:"positive",icon:"check-circle",title:"Orçamentos sob controle!",desc:`Todas as ${g} categoria(s) dentro do limite.`,url:"financas",metric:`${g}/${g}`,metricLabel:"ok"})}const m=a.metas_ativas??0,w=a.metas_concluidas??0;if(w>0?t.push({type:"goals",priority:"positive",icon:"trophy",title:`${w} meta(s) alcançada(s)!`,desc:m>0?`Continue! ${m} ainda em progresso.`:"Parabéns pelo progresso!",url:"financas#metas",metric:String(w),metricLabel:"concluída(s)"}):m>0&&t.push({type:"goals",priority:"low",icon:"target",title:`${m} meta(s) em progresso`,desc:"Cada passo conta. Mantenha o foco!",url:"financas#metas",metric:String(m),metricLabel:"ativa(s)"}),s.ativas>0){const f=(s.total_mensal||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"installments",priority:"info",icon:"layers",title:`${s.ativas} parcelamento(s) ativo(s)`,desc:`${f}/mês comprometidos com parcelas.`,url:"lancamentos",metric:f,metricLabel:"/mês"})}const C=n.saldo_projetado??0,N=n.saldo_atual??0;if(N>0&&C<0?t.push({type:"projection",priority:"critical",icon:"trending-down",title:"Atenção: saldo projetado negativo",desc:"Até o fim do mês, seu saldo pode ficar negativo. Reduza gastos.",url:null,metric:C.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}):C>N&&N>0&&t.push({type:"projection",priority:"positive",icon:"trending-up",title:"Projeção positiva!",desc:"Você deve fechar o mês com saldo maior.",url:null,metric:C.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}),l.length>=3){const f=l.slice(-3),E=f.every(x=>x.resultado>0),M=f.every(x=>x.resultado<0);E?t.push({type:"trend",priority:"positive",icon:"flame",title:"Sequência de 3 meses positivos!",desc:"Ótima consistência. Mantenha o ritmo!",url:"relatorios",metric:"3",metricLabel:"meses"}):M&&t.push({type:"trend",priority:"high",icon:"alert-triangle",title:"3 meses no vermelho",desc:"É hora de repensar seus gastos.",url:"relatorios",metric:"3",metricLabel:"meses"})}const A=new Set,$=t.filter(f=>A.has(f.type)?!1:(A.add(f.type),!0)),P={critical:0,high:1,medium:2,low:3,positive:4,info:5};return $.sort((f,E)=>(P[f.priority]??9)-(P[E.priority]??9)),$.slice(0,5)}normalizeInsight(e){const a={negative_balance:{title:"Saldo no vermelho",icon:"alert-triangle",url:"lancamentos?tipo=despesa"},overspending:{title:"Gastos acima da receita",icon:"trending-down",url:"lancamentos?tipo=despesa"},low_savings:{title:"Economia muito baixa",icon:"piggy-bank",url:"relatorios"},moderate_savings:{title:"Aumente sua economia",icon:"piggy-bank",url:"relatorios"},low_activity:{title:"Registre suas movimentações",icon:"edit-3",url:"lancamentos"},low_categories:{title:"Organize por categorias",icon:"layers",url:"categorias"},no_goals:{title:"Crie sua primeira meta",icon:"target",url:"financas#metas"},no_budgets:{title:"Defina limites de gastos",icon:"shield",url:"financas"}}[e.type]||{title:"Dica do mês",icon:"lightbulb",url:"dashboard"};return{type:e.type||"generic",priority:e.priority||"medium",title:e.title||a.title,message:e.message||"",icon:a.icon,url:a.url}}renderTips(e){const t=document.getElementById("aiTipList");if(!t)return;if(e.length===0){this.renderEmpty();return}const a=document.getElementById("aiTipBadge"),r=e.some(i=>i.priority==="critical"||i.priority==="high");if(a)if(r){const i=e.filter(s=>s.priority==="critical"||s.priority==="high").length;a.textContent=i===1?"1 em foco":`${i} em foco`,a.style.display="",a.style.background="color-mix(in srgb, var(--color-text-muted) 9%, transparent)",a.style.color="var(--color-text-muted)",a.style.borderColor="color-mix(in srgb, var(--color-text-muted) 16%, transparent)"}else a.style.display="none";const n=e.map((i,s)=>{const l=this.getIconClass(i.priority),u=i.url?"a":"div",c=i.url?` href="${this.baseURL}${i.url}"`:"",d=`ai-tip-accent--${i.priority||"info"}`,p=i.metric?`<div class="ai-tip-metric">
            <span class="ai-tip-metric-value">${i.metric}</span>
            ${i.metricLabel?`<span class="ai-tip-metric-label">${i.metricLabel}</span>`:""}
          </div>`:"";return`
        <${u}${c} class="ai-tip-item surface-card" data-priority="${i.priority}" style="animation-delay: ${s*70}ms;">
          <div class="ai-tip-accent ${d}"></div>
          <div class="ai-tip-content">
            <div class="ai-tip-item-icon ${l}">
              <i data-lucide="${i.icon}" style="width:16px;height:16px;"></i>
            </div>
            <div class="ai-tip-item-body">
              <span class="ai-tip-item-title">${i.title}</span>
              ${i.desc?`<span class="ai-tip-item-desc">${i.desc}</span>`:""}
            </div>
            ${i.url?'<i data-lucide="chevron-right" style="width:14px;height:14px;" class="ai-tip-item-arrow"></i>':""}
          </div>
          ${p}
        </${u}>
      `}).join("");t.innerHTML=n,this.updateIcons()}renderEmpty(){const e=document.getElementById("aiTipList");if(!e)return;e.innerHTML=`
      <div class="ai-tip-empty">
        <i data-lucide="check-circle" class="ai-tip-empty-icon"></i>
        <p>Tudo certo por aqui! Suas finanças estão no caminho certo.</p>
      </div>
    `;const t=document.getElementById("aiTipBadge");t&&(t.style.display="none"),this.updateIcons()}getIconClass(e){return{critical:"ai-tip-item-icon--critical",high:"ai-tip-item-icon--high",medium:"ai-tip-item-icon--medium",low:"ai-tip-item-icon--low",positive:"ai-tip-item-icon--positive"}[e]||"ai-tip-item-icon--info"}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.AiTipCard=tt;class at{constructor(e="financeOverviewContainer"){this.container=document.getElementById(e),this.baseURL=se()}render(){this.container&&(this.container.innerHTML=`
      <section class="finance-overview-section" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-section-heading">
          <div>
            <span class="dashboard-section-eyebrow">Metas</span>
            <h2 class="dashboard-section-title">Seu proximo objetivo</h2>
            <p class="dashboard-section-copy" id="foGoalsHeadline">Faltam R$ 0,00 para alcancar sua meta.</p>
          </div>
          <a href="${this.baseURL}financas#metas" class="dashboard-section-link">Criar metas</a>
        </div>

        <div class="fo-grid">
          <div class="fo-card fo-card--goal" id="foMetas">
            <div class="fo-skeleton"></div>
          </div>
          <div class="fo-card fo-card--budget" id="foOrcamento">
            <div class="fo-skeleton"></div>
          </div>
        </div>
      </section>
    `)}async load(){try{const{mes:e,ano:t}=this.getSelectedPeriod(),a=await me($e(),{mes:e,ano:t});a.success&&a.data?(this.renderAlerts(a.data),this.renderMetas(a.data.metas),this.renderOrcamento(a.data.orcamento)):(this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty())}catch(e){console.error("Error loading finance overview:",e),this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty()}this._listening||(this._listening=!0,document.addEventListener("lukrato:data-changed",()=>this.load()),document.addEventListener("lukrato:month-changed",()=>this.load()))}renderAlerts(e=null){const t=document.getElementById("dashboardAlertsBudget");if(!t)return;const a=Array.isArray(e?.orcamento?.orcamentos)?e.orcamento.orcamentos.slice():[],r=a.filter(s=>s.status==="estourado").sort((s,l)=>Number(l.excedido||0)-Number(s.excedido||0)),n=a.filter(s=>s.status==="alerta").sort((s,l)=>Number(l.percentual||0)-Number(s.percentual||0)),i=[];if(r.slice(0,2).forEach(s=>{i.push({variant:"danger",title:`Você já passou do limite em ${s.categoria_nome}`,message:`Excedido em ${this.money(s.excedido||0)}.`})}),i.length<2&&n.slice(0,2-i.length).forEach(s=>{i.push({variant:"warning",title:`${s.categoria_nome} já consumiu ${Math.round(s.percentual||0)}% do limite`,message:`Restam ${this.money(s.disponivel||0)} nessa categoria.`})}),i.length===0){t.innerHTML="",this.toggleAlertsSection();return}t.innerHTML=i.map(s=>`
      <a href="${this.baseURL}financas#orcamentos" class="dashboard-alert dashboard-alert--${s.variant}">
        <div class="dashboard-alert-icon">
          <i data-lucide="${s.variant==="danger"?"triangle-alert":"circle-alert"}" style="width:18px;height:18px;"></i>
        </div>
        <div class="dashboard-alert-content">
          <strong>${s.title}</strong>
          <span>${s.message}</span>
        </div>
        <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
      </a>
    `).join(""),this.toggleAlertsSection(),this.refreshIcons()}renderOrcamento(e){const t=document.getElementById("foOrcamento");if(!t)return;if(!e||e.total_categorias===0){this.renderOrcamentoEmpty();return}const a=Math.round(e.percentual_geral||0),r=this.getBarColor(a),i=(e.orcamentos||[]).slice().sort((l,u)=>Number(u.percentual||0)-Number(l.percentual||0)).slice(0,3).map(l=>{const u=Math.min(Number(l.percentual||0),100),c=this.getBarColor(l.percentual);return`
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">${l.categoria_nome}</span>
            <span class="fo-orc-item-pct" style="color:${c};">${Math.round(l.percentual||0)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${u}%; background:${c};"></div>
          </div>
        </div>
      `}).join("");let s="No controle";(e.estourados||0)>0?s=`${e.estourados} acima do limite`:(e.em_alerta||0)>0&&(s=`${e.em_alerta} em atencao`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </a>
        <span class="fo-badge" style="color:${r}; background:${r}18;">${s}</span>
      </div>

      <div class="fo-orc-summary">
        <span>${this.money(e.total_gasto||0)} usados de ${this.money(e.total_limite||0)}</span>
        <span class="fo-summary-status">Saude: ${e.saude_financeira?.label||"Boa"}</span>
      </div>

      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width:${Math.min(a,100)}%; background:${r};"></div>
      </div>

      ${i?`<div class="fo-orc-list">${i}</div>`:""}

      <a href="${this.baseURL}financas#orcamentos" class="fo-link">Ver limites <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `,this.refreshIcons()}renderOrcamentoEmpty(){const e=document.getElementById("foOrcamento");e&&(e.innerHTML=`
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </span>
      </div>
      <div class="fo-empty">
        <p>você ainda nao definiu limites para acompanhar categorias.</p>
        <a href="${this.baseURL}financas#orcamentos" class="fo-cta">Definir limite</a>
      </div>
    `,this.refreshIcons())}renderMetas(e){const t=document.getElementById("foMetas");if(!t)return;if(!e||e.total_metas===0){this.renderMetasEmpty();return}const a=e.proxima_concluir,r=Math.round(e.progresso_geral||0);if(!a){this.updateGoalsHeadline("você tem metas ativas, mas nenhuma esta proxima de concluir."),t.innerHTML=`
        <div class="fo-card-header">
          <a href="${this.baseURL}financas#metas" class="fo-card-title">
            <i data-lucide="target" style="width:16px;height:16px;"></i>
            Metas
          </a>
          <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
        </div>
        <div class="fo-metas-summary">
          <div class="fo-metas-stat">
            <span class="fo-metas-stat-value">${r}%</span>
            <span class="fo-metas-stat-label">progresso geral</span>
          </div>
        </div>
        <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
      `,this.refreshIcons();return}const n=a.cor||"var(--color-primary)",i=this.normalizeIconName(a.icone),s=Math.round(a.progresso||0),l=Math.max(Number(a.valor_alvo||0)-Number(a.valor_atual||0),0);this.updateGoalsHeadline(`Faltam ${this.money(l)} para alcancar sua meta.`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#metas" class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </a>
        <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
      </div>

      <div class="fo-meta-destaque">
        <div class="fo-meta-icon" style="color:${n}; background:${n}18;">
          <i data-lucide="${i}" style="width:16px;height:16px;"></i>
        </div>
        <div class="fo-meta-info">
          <span class="fo-meta-titulo">${a.titulo}</span>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${Math.min(s,100)}%; background:${n};"></div>
          </div>
          <span class="fo-meta-detail">${this.money(a.valor_atual||0)} de ${this.money(a.valor_alvo||0)}</span>
        </div>
        <span class="fo-meta-pct" style="color:${n};">${s}%</span>
      </div>

      <div class="fo-metas-summary">
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${this.money(l)}</span>
          <span class="fo-metas-stat-label">faltam para concluir</span>
        </div>
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${r}%</span>
          <span class="fo-metas-stat-label">progresso geral</span>
        </div>
      </div>

      <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `,this.refreshIcons()}renderMetasEmpty(){const e=document.getElementById("foMetas");e&&(this.updateGoalsHeadline("Defina uma meta para transformar sua sobra em um objetivo claro."),e.innerHTML=`
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </span>
      </div>
      <div class="fo-empty">
        <p>você ainda nao definiu uma meta ativa.</p>
        <a href="${this.baseURL}financas#metas" class="fo-cta">Criar meta</a>
      </div>
    `,this.refreshIcons())}updateGoalsHeadline(e){const t=document.getElementById("foGoalsHeadline");t&&(t.textContent=e)}toggleAlertsSection(){const e=document.getElementById("dashboardAlertsSection"),t=document.getElementById("dashboardAlertsOverview"),a=document.getElementById("dashboardAlertsBudget");if(!e)return;const r=t&&t.innerHTML.trim()!=="",n=a&&a.innerHTML.trim()!=="";e.style.display=r||n?"block":"none"}getSelectedPeriod(){const e=v.getCurrentMonth?v.getCurrentMonth():new Date().toISOString().slice(0,7),t=String(e).match(/^(\d{4})-(\d{2})$/);if(t)return{ano:Number(t[1]),mes:Number(t[2])};const a=new Date;return{mes:a.getMonth()+1,ano:a.getFullYear()}}getBarColor(e){return e>=100?"#ef4444":e>=80?"#f59e0b":"#10b981"}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-bullseye":"target","fa-target":"target","fa-wallet":"wallet","fa-university":"landmark","fa-plane":"plane","fa-car":"car","fa-home":"house","fa-heart":"heart","fa-briefcase":"briefcase-business","fa-piggy-bank":"piggy-bank","fa-shield":"shield","fa-graduation-cap":"graduation-cap","fa-store":"store","fa-baby":"baby","fa-hand-holding-usd":"hand-coins"}[t]||t.replace(/^fa-/,""))||"target"}money(e){return Number(e||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}refreshIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.FinanceOverview=at;class st{constructor(e="evolucaoChartsContainer"){this.container=document.getElementById(e),this._chartMensal=null,this._chartAnual=null,this._activeTab="mensal",this._currentMonth=null}init(){!this.container||this._initialized||(this._initialized=!0,this._render(),this._loadAndDraw(),document.addEventListener("lukrato:month-changed",e=>{this._currentMonth=e?.detail?.month??null,this._loadAndDraw()}),document.addEventListener("lukrato:data-changed",()=>{this._loadAndDraw()}))}_render(){this.container.innerHTML=`
      <div class="evo-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400">
        <div class="evo-header">
          <div class="evo-title-group">
            <i data-lucide="trending-up" class="evo-title-icon"></i>
                        <div class="evo-title-stack">
                            <h2 class="evo-title">Fluxo do período</h2>
                            <p class="evo-subtitle">Entradas, saídas e resultado em contexto.</p>
                        </div>
          </div>
          <div class="evo-tabs" role="tablist">
            <button class="evo-tab evo-tab--active" data-tab="mensal" role="tab" aria-selected="true">Mensal</button>
            <button class="evo-tab" data-tab="anual" role="tab" aria-selected="false">Anual</button>
          </div>
        </div>

        <div class="evo-stats" id="evoStats">
          <div class="evo-stat">
            <span class="evo-stat__label">Entradas</span>
            <span class="evo-stat__value evo-stat__value--income" id="evoStatReceitas">–</span>
          </div>
          <div class="evo-stat">
            <span class="evo-stat__label">Saídas</span>
            <span class="evo-stat__value evo-stat__value--expense" id="evoStatDespesas">–</span>
          </div>
          <div class="evo-stat">
            <span class="evo-stat__label">Resultado</span>
            <span class="evo-stat__value" id="evoStatResultado">–</span>
          </div>
        </div>

        <div class="evo-chart-wrap">
          <div id="evoChartMensal" class="evo-chart"></div>
          <div id="evoChartAnual"  class="evo-chart" style="display:none;"></div>
        </div>
      </div>
    `,this.container.querySelectorAll(".evo-tab").forEach(e=>{e.addEventListener("click",()=>this._switchTab(e.dataset.tab))}),typeof window.lucide<"u"&&window.lucide.createIcons({attrs:{class:["lucide"]}})}async _loadAndDraw(){const e=this._currentMonth||this._detectMonth();try{const t=await me(Qe(),{month:e}),a=t?.data??t;if(!a?.mensal)return;this._data=a,this._drawMensal(a.mensal),this._drawAnual(a.anual),this._updateStats(a)}catch{}}_detectMonth(){const e=document.getElementById("monthSelector")||document.querySelector("[data-month]");return e?.value||e?.dataset?.month||new Date().toISOString().slice(0,7)}_theme(){const e=document.documentElement.getAttribute("data-theme")!=="light",t=getComputedStyle(document.documentElement);return{isDark:e,mode:e?"dark":"light",textMuted:t.getPropertyValue("--color-text-muted").trim()||(e?"#94a3b8":"#666"),gridColor:e?"rgba(255,255,255,0.05)":"rgba(0,0,0,0.06)",primary:t.getPropertyValue("--color-primary").trim()||"#E67E22",success:t.getPropertyValue("--color-success").trim()||"#2ecc71",danger:t.getPropertyValue("--color-danger").trim()||"#e74c3c",surface:e?"#0f172a":"#ffffff"}}_fmt(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e??0)}_chartHeight(){return window.matchMedia("(max-width: 768px)").matches?176:188}_drawMensal(e){const t=document.getElementById("evoChartMensal");if(!t||!Array.isArray(e))return;this._chartMensal&&(this._chartMensal.destroy(),this._chartMensal=null);const a=this._theme(),r=e.map(s=>s.label),n=e.map(s=>+s.receitas),i=e.map(s=>+s.despesas);this._chartMensal=new ApexCharts(t,{chart:{type:"bar",height:this._chartHeight(),toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,sparkline:{enabled:!1},animations:{enabled:!0,speed:600}},series:[{name:"Entradas",data:n},{name:"Saídas",data:i}],xaxis:{categories:r,tickAmount:7,labels:{rotate:0,style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:s=>this._fmt(s)}},colors:[a.success,a.danger],plotOptions:{bar:{borderRadius:4,columnWidth:"70%",dataLabels:{position:"top"}}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:s=>this._fmt(s)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartMensal.render()}_drawAnual(e){const t=document.getElementById("evoChartAnual");if(!t||!Array.isArray(e))return;this._chartAnual&&(this._chartAnual.destroy(),this._chartAnual=null);const a=this._theme(),r=e.map(l=>l.label),n=e.map(l=>+l.receitas),i=e.map(l=>+l.despesas),s=e.map(l=>+l.saldo);this._chartAnual=new ApexCharts(t,{chart:{type:"line",height:this._chartHeight(),toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,animations:{enabled:!0,speed:600}},series:[{name:"Entradas",type:"column",data:n},{name:"Saídas",type:"column",data:i},{name:"Saldo",type:"area",data:s}],xaxis:{categories:r,labels:{style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:l=>this._fmt(l)}},colors:[a.success,a.danger,a.primary],plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},stroke:{curve:"smooth",width:[0,0,2.5]},fill:{type:["solid","solid","gradient"],gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:.02,stops:[0,100]}},markers:{size:[0,0,4],hover:{size:6}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:l=>this._fmt(l)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartAnual.render()}_updateStats(e){const t=this._activeTab==="anual";let a=0,r=0;t&&e.anual?.length?e.anual.forEach(u=>{a+=+u.receitas,r+=+u.despesas}):e.mensal?.length&&e.mensal.forEach(u=>{a+=+u.receitas,r+=+u.despesas});const n=a-r,i=document.getElementById("evoStatReceitas"),s=document.getElementById("evoStatDespesas"),l=document.getElementById("evoStatResultado");i&&(i.textContent=this._fmt(a)),s&&(s.textContent=this._fmt(r)),l&&(l.textContent=this._fmt(n),l.className="evo-stat__value "+(n>=0?"evo-stat__value--income":"evo-stat__value--expense"))}_switchTab(e){if(this._activeTab===e)return;this._activeTab=e,this.container.querySelectorAll(".evo-tab").forEach(r=>{const n=r.dataset.tab===e;r.classList.toggle("evo-tab--active",n),r.setAttribute("aria-selected",String(n))});const t=document.getElementById("evoChartMensal"),a=document.getElementById("evoChartAnual");t&&(t.style.display=e==="mensal"?"":"none"),a&&(a.style.display=e==="anual"?"":"none"),this._data&&this._updateStats(this._data),setTimeout(()=>{e==="mensal"&&this._chartMensal&&this._chartMensal.windowResizeHandler?.(),e==="anual"&&this._chartAnual&&this._chartAnual.windowResizeHandler?.()},10)}}window.EvolucaoCharts=st;class Ie{constructor(){this.initialized=!1,this.init()}init(){this.setupEventListeners(),this.initialized=!0}setupEventListeners(){document.addEventListener("lukrato:transaction-added",()=>{this.playAddedAnimation()}),document.addEventListener("lukrato:level-up",e=>{this.playLevelUpAnimation(e.detail?.level)}),document.addEventListener("lukrato:streak-milestone",e=>{this.playStreakAnimation(e.detail?.days)}),document.addEventListener("lukrato:goal-completed",e=>{this.playGoalAnimation(e.detail?.goalName)}),document.addEventListener("lukrato:achievement-unlocked",e=>{this.playAchievementAnimation(e.detail?.name,e.detail?.icon)})}playAddedAnimation(){window.fab&&window.fab.celebrate(),window.LK?.toast&&window.LK.toast.success("Lancamento adicionado com sucesso."),this.fireConfetti("small",.9,.9)}playLevelUpAnimation(e){this.showCelebrationToast({title:`Nivel ${e}`,subtitle:"você subiu de nivel.",icon:"star",duration:3e3}),this.fireConfetti("large",.5,.3),this.screenFlash("#f59e0b",.3,2),window.fab?.container&&(window.fab.container.style.animation="spin 0.8s ease-out",setTimeout(()=>{window.fab.container.style.animation=""},800))}playStreakAnimation(e){const a={7:{title:"Semana perfeita",subtitle:"você chegou a 7 dias seguidos."},14:{title:"Duas semanas",subtitle:"você chegou a 14 dias seguidos."},30:{title:"Mes epico",subtitle:"você chegou a 30 dias seguidos."},100:{title:"Marco historico",subtitle:"você chegou a 100 dias seguidos."}}[e]||{title:`${e} dias seguidos`,subtitle:"Sua sequencia continua forte."};this.showCelebrationModal(a.title,a.subtitle),this.fireConfetti("extreme",.5,.2)}playGoalAnimation(e){this.showCelebrationToast({title:"Meta atingida",subtitle:`você completou: ${e}`,icon:"target",duration:3500}),this.fireConfetti("large",.5,.4),this.screenFlash("#10b981",.4,1.5)}playAchievementAnimation(e,t){const a=this.normalizeIconName(t),r=document.createElement("div");r.className="achievement-popup",r.innerHTML=`
      <div class="achievement-card">
        <div class="achievement-icon">
          <i data-lucide="${a}"></i>
        </div>
        <div class="achievement-title">Conquista desbloqueada</div>
        <div class="achievement-name">${e}</div>
      </div>
    `,document.body.appendChild(r),typeof window.lucide<"u"&&window.lucide.createIcons(),setTimeout(()=>{r.classList.add("show")},10),setTimeout(()=>{r.classList.remove("show"),setTimeout(()=>r.remove(),300)},3500),this.fireConfetti("medium",.5,.6)}showCelebrationToast(e){const{title:t="Parabens",subtitle:a="você fez progresso.",icon:r="party-popper",duration:n=3e3}=e;window.LK?.toast&&window.LK.toast.success(`${t}
${a}`)}showCelebrationModal(e,t){typeof Swal>"u"||Swal.fire({title:e,text:t,icon:"success",confirmButtonText:"Continuar",confirmButtonColor:"var(--color-primary)",allowOutsideClick:!1,didOpen:()=>{this.fireConfetti("extreme",.5,.2)}})}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-trophy":"trophy","fa-award":"award","fa-medal":"medal","fa-star":"star","fa-target":"target"}[t]||t.replace(/^fa-/,""))||"trophy"}screenFlash(e="#10b981",t=.3,a=1){const r=document.createElement("div");r.style.cssText=`
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: ${e};
      opacity: 0;
      z-index: 99999;
      pointer-events: none;
      transition: none;
    `,document.body.appendChild(r),setTimeout(()=>{r.style.transition=`opacity ${a/2}ms ease-out`,r.style.opacity=t},10),setTimeout(()=>{r.style.transition=`opacity ${a/2}ms ease-in`,r.style.opacity="0"},a/2),setTimeout(()=>r.remove(),a)}fireConfetti(e="medium",t=.5,a=.5){if(typeof confetti!="function")return;const r={small:{particleCount:30,spread:40},medium:{particleCount:60,spread:60},large:{particleCount:100,spread:90},extreme:{particleCount:150,spread:120}},n=r[e]||r.medium;confetti({...n,origin:{x:t,y:a},gravity:.8,decay:.95,zIndex:99999})}}window.CelebrationSystem=Ie;document.addEventListener("DOMContentLoaded",()=>{window.celebrationSystem||(window.celebrationSystem=new Ie)});function nt(){return new Promise(o=>{let e=0;const t=setInterval(()=>{window.HealthScoreWidget&&window.DashboardGreeting&&window.HealthScoreInsights&&window.FinanceOverview&&window.EvolucaoCharts&&(clearInterval(t),o()),e++>50&&(clearInterval(t),o())},100)})}function ve(o,e){const t=document.getElementById(o);return t||e()}async function ot(){await nt(),document.readyState==="loading"?document.addEventListener("DOMContentLoaded",be):be()}function be(){const o=document.querySelector(".modern-dashboard");if(o){if(typeof window.DashboardGreeting<"u"&&(ve("greetingContainer",()=>{const t=document.createElement("div");return t.id="greetingContainer",o.insertBefore(t,o.firstChild),t}),new window.DashboardGreeting().render()),typeof window.HealthScoreWidget<"u"){if(document.getElementById("healthScoreContainer")){const t=new window.HealthScoreWidget;t.render(),t.load()}typeof window.HealthScoreInsights<"u"&&document.getElementById("healthScoreInsights")&&(window.healthScoreInsights=new window.HealthScoreInsights)}if(typeof window.AiTipCard<"u"&&document.getElementById("aiTipContainer")&&new window.AiTipCard().init(),typeof window.EvolucaoCharts<"u"&&document.getElementById("evolucaoChartsContainer")&&new window.EvolucaoCharts().init(),typeof window.FinanceOverview<"u"){ve("financeOverviewContainer",()=>{const t=document.createElement("div");t.id="financeOverviewContainer";const a=o.querySelector(".provisao-section");return a?a.insertAdjacentElement("afterend",t):o.appendChild(t),t});const e=new window.FinanceOverview;e.render(),e.load()}typeof window.lucide<"u"&&window.lucide.createIcons()}}ot();function we(o){return`lk_user_${ne().userId??"anon"}_${o}`}const K={DISPLAY_NAME_DISMISSED:()=>we("display_name_prompt_dismissed_v1"),FIRST_ACTION_TOAST:()=>we("dashboard_first_action_toast_v1")};class it{constructor(){this.state={accountCount:0,primaryAction:"create_transaction",transactionCount:null,isDemo:!1,awaitingFirstActionFeedback:!1},this.elements={firstRunStack:document.getElementById("dashboardFirstRunStack"),displayNameCard:document.getElementById("dashboardDisplayNamePrompt"),previewNotice:document.getElementById("dashboardPreviewNotice"),previewLearnMore:document.getElementById("dashboardPreviewLearnMore"),displayNameForm:document.getElementById("dashboardDisplayNameForm"),displayNameInput:document.getElementById("dashboardDisplayNameInput"),displayNameSubmit:document.getElementById("dashboardDisplayNameSubmit"),displayNameDismiss:document.getElementById("dashboardDisplayNameDismiss"),displayNameFeedback:document.getElementById("dashboardDisplayNameFeedback"),alertsSection:document.getElementById("sectionAlertas"),quickStart:document.getElementById("dashboardQuickStart"),quickStartEyebrow:document.getElementById("dashboardQuickStartEyebrow"),quickStartTitle:document.getElementById("dashboardQuickStartTitle"),quickStartSummary:document.getElementById("dashboardQuickStartSummary"),primaryActionCta:document.getElementById("dashboardFirstTransactionCta"),openTourPrompt:document.getElementById("dashboardOpenTourPrompt"),emptyStateTitle:document.querySelector("#emptyState p"),emptyStateDescription:document.querySelector("#emptyState .dash-empty__subtext"),emptyStateCta:document.getElementById("dashboardEmptyStateCta"),fabButton:document.getElementById("fabButton")}}init(){this.bindEvents(),this.syncDisplayNamePrompt(),this.syncStackVisibility(),Le(()=>{this.syncDisplayNamePrompt()}),pe({},{silent:!0}).then(()=>{this.syncDisplayNamePrompt()})}bindEvents(){this.elements.primaryActionCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.emptyStateCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.openTourPrompt?.addEventListener("click",()=>this.startTour()),this.elements.previewLearnMore?.addEventListener("click",()=>{this.openPreviewHelp()}),this.elements.displayNameDismiss?.addEventListener("click",()=>this.dismissDisplayNamePrompt()),this.elements.displayNameForm?.addEventListener("submit",e=>this.handleDisplayNameSubmit(e)),document.addEventListener("lukrato:dashboard-overview-rendered",e=>{this.handleOverviewUpdate(e.detail||{})}),document.addEventListener("lukrato:data-changed",e=>{e.detail?.resource==="transactions"&&e.detail?.action==="create"&&(this.state.awaitingFirstActionFeedback=!0)})}handleOverviewUpdate(e){const t=Number(this.state.transactionCount??0),a=Number(e.transactionCount||0),r=this.state.transactionCount===null,n=Se(e,{accountCount:Number(e.accountCount??0),actionType:e.primaryAction,ctaLabel:e.ctaLabel,ctaUrl:e.ctaUrl});this.state.accountCount=Math.max(0,Number(e.accountCount??n.action.accountCount??0)||0),this.state.primaryAction=n.action.actionType,this.state.transactionCount=a,this.state.isDemo=e.isDemo===!0;const i=this.shouldShowQuickStart();this.toggleQuickStart(i),this.syncPrimaryActionCopy(n),this.syncDisplayNamePrompt(),this.togglePrimaryActionFocus(i),!r&&t===0&&a>0?this.handleFirstActionCompleted():this.state.awaitingFirstActionFeedback&&a>0&&this.handleFirstActionCompleted()}toggleQuickStart(e){this.elements.quickStart&&(this.elements.quickStart.hidden=!e,e&&this.suppressHelpCenterOffer(),this.syncStackVisibility())}shouldShowQuickStart(){return this.state.isDemo===!0&&Number(this.state.transactionCount??0)===0}syncPrimaryActionCopy(e){if(!e)return;const t=this.buildQuickStartContent(e);this.elements.quickStartEyebrow&&(this.elements.quickStartEyebrow.textContent=t.eyebrow),this.elements.quickStartTitle&&(this.elements.quickStartTitle.textContent=t.title),this.elements.quickStartSummary&&(this.elements.quickStartSummary.textContent=t.summary),this.elements.primaryActionCta&&(this.elements.primaryActionCta.innerHTML=`<i data-lucide="plus"></i> ${this.getPrimaryCtaLabel(e)}`),this.elements.emptyStateTitle&&(this.elements.emptyStateTitle.textContent=e.emptyStateTitle),this.elements.emptyStateDescription&&(this.elements.emptyStateDescription.textContent=e.emptyStateDescription),this.elements.emptyStateCta&&(this.elements.emptyStateCta.innerHTML=`<i data-lucide="plus"></i> ${e.emptyStateButton}`),this.elements.openTourPrompt&&(this.elements.openTourPrompt.hidden=!this.hasTourAction(e)),this.elements.previewLearnMore&&(this.elements.previewLearnMore.hidden=!this.hasPreviewHelp()),typeof window.lucide<"u"&&window.lucide.createIcons()}getPrimaryCtaLabel(e){return e?.action?.actionType==="create_account"?"Criar primeira conta":e?.action?.actionType==="create_transaction"?"Registrar primeira transação":String(e?.quickStartButton||"Continuar").trim()}buildQuickStartContent(e){return e?.action?.actionType==="create_transaction"?{eyebrow:"Próxima ação",title:"Registre a primeira transação",summary:"Com a conta pronta, registre a primeira movimentação para transformar o painel inicial em acompanhamento real do período."}:{eyebrow:"Configuração inicial",title:"Cadastre sua primeira conta",summary:"Comece pela base do seu fluxo financeiro. Assim que a conta for criada, o painel passa a refletir a sua operação."}}hasTourAction(e=null){return!(!!!(window.LKHelpCenter?.startCurrentPageTutorial||window.LKHelpCenter?.showCurrentPageTips)||e&&e.shouldOfferTour===!1&&!this.state.isDemo)}hasPreviewHelp(){return!0}syncDisplayNamePrompt(){if(!this.elements.displayNameCard)return;const e=this.state.isDemo,t=!!ne().needsDisplayNamePrompt&&localStorage.getItem(K.DISPLAY_NAME_DISMISSED())!=="1";this.elements.previewNotice&&(this.elements.previewNotice.hidden=!e),this.elements.previewLearnMore&&(this.elements.previewLearnMore.hidden=!e),this.elements.displayNameForm&&(this.elements.displayNameForm.hidden=!t),this.elements.displayNameCard.hidden=!t,this.elements.displayNameCard.classList.toggle("is-name-only",t),this.syncStackVisibility()}syncStackVisibility(){if(!this.elements.firstRunStack)return;const e=this.elements.quickStart&&!this.elements.quickStart.hidden,t=this.elements.displayNameCard&&!this.elements.displayNameCard.hidden;this.elements.firstRunStack.hidden=!(e||t),document.body.classList.toggle("dashboard-demo-preview-active",this.state.isDemo===!0),document.body.classList.toggle("dashboard-first-use-active",!!e),document.body.classList.toggle("dashboard-onboarding-active",!!e),this.elements.alertsSection&&this.elements.alertsSection.classList.toggle("dashboard-alerts--suppressed",!!e)}dismissDisplayNamePrompt(){localStorage.setItem(K.DISPLAY_NAME_DISMISSED(),"1"),this.syncDisplayNamePrompt()}async handleDisplayNameSubmit(e){if(e.preventDefault(),!this.elements.displayNameInput||!this.elements.displayNameSubmit)return;const t=this.elements.displayNameInput.value.trim();if(t.length<2){this.showDisplayNameFeedback("Use pelo menos 2 caracteres.",!0);return}this.elements.displayNameSubmit.disabled=!0,this.elements.displayNameSubmit.textContent="Salvando...";try{const a=await _e(Oe(),{display_name:t});if(a?.success===!1)throw a;const r=a?.data||{},n=String(r.display_name||t).trim(),i=String(r.first_name||n).trim();ze({username:n,needsDisplayNamePrompt:!1},{source:"display-name"}),localStorage.removeItem(K.DISPLAY_NAME_DISMISSED()),this.updateGlobalIdentity(n,i),this.showDisplayNameFeedback("Perfeito. Agora o Lukrato já fala com você do jeito certo."),window.setTimeout(()=>this.syncDisplayNamePrompt(),900),window.LK?.toast&&window.LK.toast.success("Nome de exibição salvo.")}catch(a){T("Erro ao salvar nome de exibição",a,"Falha ao salvar nome de exibição"),this.showDisplayNameFeedback(he(a,"Não foi possível salvar agora."),!0)}finally{this.elements.displayNameSubmit.disabled=!1,this.elements.displayNameSubmit.textContent="Salvar"}}showDisplayNameFeedback(e,t=!1){this.elements.displayNameFeedback&&(this.elements.displayNameFeedback.hidden=!1,this.elements.displayNameFeedback.textContent=e,this.elements.displayNameFeedback.classList.toggle("is-error",t))}updateGlobalIdentity(e,t){const a=t||e||"U",r=a.charAt(0).toUpperCase();document.querySelectorAll(".greeting-name strong").forEach(s=>{s.textContent=a}),document.querySelectorAll(".avatar-initials-sm, .avatar-initials-xs").forEach(s=>{s.textContent=r});const n=document.getElementById("lkSupportToggle");n&&(n.dataset.supportName=e);const i=document.getElementById("sfName");i&&(i.textContent=e),this.elements.displayNameInput&&(this.elements.displayNameInput.value=e)}async openPreviewHelp(){if(window.Swal?.fire){const e=this.elements.primaryActionCta?.textContent?.trim()||"Continuar",t=this.hasTourAction(),a=await window.Swal.fire({title:"O que é esta prévia?",html:`
          <div class="dash-preview-modal__content">
            <p class="dash-preview-modal__intro">
              Estes números servem só para mostrar como o Lukrato organiza suas finanças antes do primeiro uso real.
            </p>
            <ul class="dash-preview-modal__list">
              <li>Os valores exibidos aqui são apenas de exemplo.</li>
              <li>Nada dessa prévia entra no seu histórico real.</li>
              <li>Assim que você criar sua primeira conta e começar a usar, a demonstração some.</li>
            </ul>
            <p class="dash-preview-modal__footnote">
              O próximo passo é só um: começar seu painel com dados seus.
            </p>
          </div>
        `,showConfirmButton:!0,confirmButtonText:e,showDenyButton:t,denyButtonText:"Ver tour",showCancelButton:!0,cancelButtonText:"Fechar",reverseButtons:!1,focusConfirm:!0,customClass:{popup:"lk-swal-popup dash-preview-modal",confirmButton:"dash-preview-modal__confirm",denyButton:"dash-preview-modal__deny",cancelButton:"dash-preview-modal__cancel"}});if(a.isConfirmed){this.openPrimaryAction();return}a.isDenied&&this.startTour();return}window.alert("Estes números são apenas de exemplo. Assim que você começar a usar, a prévia some.")}startTour(){if(window.LKHelpCenter?.startCurrentPageTutorial){this.suppressHelpCenterOffer(),window.LKHelpCenter.startCurrentPageTutorial({source:"dashboard-first-run"});return}if(window.LKHelpCenter?.showCurrentPageTips){window.LKHelpCenter.showCurrentPageTips();return}window.LK?.toast?.info("Tutorial indisponível no momento.")}suppressHelpCenterOffer(){const e=window.LKHelpCenter;if(!e?.getPageTutorialTarget)return;const t=e.getPageTutorialTarget();t&&(e.markOfferShownThisSession?.(t),e.hideOffer?.())}togglePrimaryActionFocus(e){[this.elements.fabButton,this.elements.primaryActionCta,document.getElementById("dashboardEmptyStateCta"),document.getElementById("dashboardChartEmptyCta")].forEach(a=>{a&&a.classList.toggle("dash-primary-cta-highlight",e)})}focusPrimaryAction(){this.togglePrimaryActionFocus(!0),this.shouldShowQuickStart()&&this.elements.quickStart?.scrollIntoView({behavior:"smooth",block:"center"})}handleFirstActionCompleted(){this.state.awaitingFirstActionFeedback=!1,localStorage.getItem(K.FIRST_ACTION_TOAST())!=="1"&&(window.LK?.toast&&window.LK.toast.success("Boa! Você já começou a controlar suas finanças."),localStorage.setItem(K.FIRST_ACTION_TOAST(),"1")),this.togglePrimaryActionFocus(!1)}openPrimaryAction(){Ee({primary_action:this.state.primaryAction,real_account_count:this.state.accountCount})}}document.addEventListener("DOMContentLoaded",()=>{document.querySelector(".modern-dashboard")&&(window.dashboardFirstRunExperience||(window.dashboardFirstRunExperience=new it,window.dashboardFirstRunExperience.init()))});function rt({API:o,CONFIG:e,Utils:t,escapeHtml:a,logClientError:r}){const n={getContainer:(i,s)=>{const l=document.getElementById(s);if(l)return l;const u=document.getElementById(i);if(!u)return null;const c=u.querySelector(".dash-optional-body");if(c)return c.id||(c.id=s),c;const d=document.createElement("div");d.className="dash-optional-body",d.id=s;const p=u.querySelector(".dash-section-header"),y=Array.from(u.children).filter(g=>g.classList?.contains("dash-placeholder"));return p?.nextSibling?u.insertBefore(d,p.nextSibling):u.appendChild(d),y.forEach(g=>d.appendChild(g)),d},renderLoading:i=>{i&&(i.innerHTML=`
                <div class="dash-widget dash-widget--loading" aria-hidden="true">
                    <div class="dash-widget-skeleton dash-widget-skeleton--title"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--value"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--text"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--bar"></div>
                </div>
            `)},renderEmpty:(i,s,l,u)=>{i&&(i.innerHTML=`
                <div class="dash-widget-empty">
                    <p>${s}</p>
                    ${l&&u?`<a href="${l}" class="dash-widget-link">${u}</a>`:""}
                </div>
            `)},getUsageColor:i=>i>=85?"#ef4444":i>=60?"#f59e0b":"#10b981",getAccountBalance:i=>{const l=[i?.saldoAtual,i?.saldo_atual,i?.saldo,i?.saldoInicial,i?.saldo_inicial].find(u=>Number.isFinite(Number(u)));return Number(l||0)},renderMetas:async i=>{const s=n.getContainer("sectionMetas","sectionMetasBody");if(s){n.renderLoading(s);try{const u=(await o.getFinanceSummary(i))?.metas??null;if(!u||Number(u.total_metas||0)===0){n.renderEmpty(s,"Você ainda não tem metas ativas neste momento.",`${e.BASE_URL}financas#metas`,"Criar meta");return}const c=u.proxima_concluir||null,d=Math.round(Number(u.progresso_geral||0));if(!c){s.innerHTML=`
                        <div class="dash-widget">
                            <span class="dash-widget-label">Metas ativas</span>
                            <strong class="dash-widget-value">${Number(u.total_metas||0)}</strong>
                            <p class="dash-widget-caption">Você tem metas em andamento, mas nenhuma está próxima de conclusão.</p>
                            <div class="dash-widget-meta">
                                <span>Progresso geral</span>
                                <strong>${d}%</strong>
                            </div>
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(d,100)}%; background:var(--color-primary);"></span>
                            </div>
                            <a href="${e.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                        </div>
                    `;return}const p=a(String(c.titulo||"Sua meta principal")),y=Number(c.valor_atual||0),g=Number(c.valor_alvo||0),h=Math.max(g-y,0),m=Math.round(Number(c.progresso||0)),w=c.cor||"var(--color-primary)";s.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Próxima meta</span>
                        <strong class="dash-widget-value">${p}</strong>
                        <p class="dash-widget-caption">Faltam ${t.money(h)} para concluir.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(m,100)}%; background:${w};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>${t.money(y)} de ${t.money(g)}</span>
                            <strong style="color:${w};">${m}%</strong>
                        </div>
                        <a href="${e.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                    </div>
                `}catch(l){r("Erro ao carregar widget de metas",l,"Falha ao carregar metas"),n.renderEmpty(s,"Não foi possível carregar suas metas agora.",`${e.BASE_URL}financas#metas`,"Tentar nas finanças")}}},renderCartoes:async()=>{const i=n.getContainer("sectionCartoes","sectionCartoesBody");if(i){n.renderLoading(i);try{const s=await o.getCardsSummary(),l=Number(s?.total_cartoes||0);if(!s||l===0){n.renderEmpty(i,"Você ainda não tem cartões ativos no dashboard.",`${e.BASE_URL}cartoes`,"Cadastrar cartão");return}const u=Number(s.limite_disponivel||0),c=Number(s.limite_total||0),d=Math.round(Number(s.percentual_uso||0)),p=n.getUsageColor(d);i.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Limite disponível</span>
                        <strong class="dash-widget-value">${t.money(u)}</strong>
                        <p class="dash-widget-caption">${l} cartão(ões) ativo(s) com ${d}% de uso consolidado.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(d,100)}%; background:${p};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>Limite total ${t.money(c)}</span>
                            <strong style="color:${p};">${d}% usado</strong>
                        </div>
                        <a href="${e.BASE_URL}cartoes" class="dash-widget-link">Criar cartões</a>
                    </div>
                `}catch(s){r("Erro ao carregar widget de cartões",s,"Falha ao carregar cartões"),n.renderEmpty(i,"Não foi possível carregar seus cartões agora.",`${e.BASE_URL}cartoes`,"Criar cartões")}}},renderContas:async i=>{const s=n.getContainer("sectionContas","sectionContasBody");if(s){n.renderLoading(s);try{const l=await o.getAccountsBalances(i),u=Array.isArray(l)?l:[];if(u.length===0){n.renderEmpty(s,"Você ainda não tem contas ativas conectadas.",`${e.BASE_URL}contas`,"Adicionar conta");return}const c=u.map(h=>({...h,__saldo:n.getAccountBalance(h)})).sort((h,m)=>m.__saldo-h.__saldo),d=c.reduce((h,m)=>h+m.__saldo,0),p=c[0]||null,y=a(String(p?.nome||p?.nome_conta||p?.instituicao||p?.banco_nome||"Conta principal")),g=p?t.money(p.__saldo):t.money(0);s.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Saldo consolidado</span>
                        <strong class="dash-widget-value">${t.money(d)}</strong>
                        <p class="dash-widget-caption">${c.length} conta(s) ativa(s) no painel.</p>
                        <div class="dash-widget-list">
                            ${c.slice(0,3).map(h=>`
                                    <div class="dash-widget-list-item">
                                        <span>${a(String(h.nome||h.nome_conta||h.instituicao||h.banco_nome||"Conta"))}</span>
                                        <strong>${t.money(h.__saldo)}</strong>
                                    </div>
                                `).join("")}
                        </div>
                        <div class="dash-widget-meta">
                            <span>Maior saldo em ${y}</span>
                            <strong>${g}</strong>
                        </div>
                        <a href="${e.BASE_URL}contas" class="dash-widget-link">Criar contas +</a>
                    </div>
                `}catch(l){r("Erro ao carregar widget de contas",l,"Falha ao carregar contas"),n.renderEmpty(s,"Não foi possível carregar suas contas agora.",`${e.BASE_URL}contas`,"Criar contas +")}}},renderOrcamentos:async i=>{const s=n.getContainer("sectionOrcamentos","sectionOrcamentosBody");if(s){n.renderLoading(s);try{const u=(await o.getFinanceSummary(i))?.orcamento??null;if(!u||Number(u.total_categorias||0)===0){n.renderEmpty(s,"Você ainda não definiu limites para categorias.",`${e.BASE_URL}financas#orcamentos`,"Definir limite");return}const c=Math.round(Number(u.percentual_geral||0)),d=n.getUsageColor(c),y=(u.orcamentos||[]).slice().sort((g,h)=>Number(h.percentual||0)-Number(g.percentual||0)).slice(0,3).map(g=>{const h=n.getUsageColor(g.percentual);return`
                        <div class="dash-widget-list-item">
                            <span>${a(g.categoria_nome||"Categoria")}</span>
                            <strong style="color:${h};">${Math.round(g.percentual||0)}%</strong>
                        </div>
                    `}).join("");s.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Uso geral dos limites</span>
                        <strong class="dash-widget-value" style="color:${d};">${c}%</strong>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(c,100)}%; background:${d};"></span>
                        </div>
                        <p class="dash-widget-caption">${t.money(u.total_gasto||0)} de ${t.money(u.total_limite||0)}</p>
                        ${y?`<div class="dash-widget-list">${y}</div>`:""}
                        <a href="${e.BASE_URL}financas#orcamentos" class="dash-widget-link">Ver orçamentos</a>
                    </div>
                `}catch(l){r("Erro ao carregar widget de orçamentos",l,"Falha ao carregar orçamentos"),n.renderEmpty(s,"Não foi possível carregar seus orçamentos.",`${e.BASE_URL}financas#orcamentos`,"Abrir orçamentos")}}},renderFaturas:async()=>{const i=n.getContainer("sectionFaturas","sectionFaturasBody");if(i){n.renderLoading(i);try{const s=await o.getCardsSummary(),l=Number(s?.total_cartoes||0);if(!s||l===0){n.renderEmpty(i,"Você não tem cartões com faturas abertas.",`${e.BASE_URL}faturas`,"Criar faturas +");return}const u=Number(s.fatura_aberta??s.limite_utilizado??0),c=Number(s.limite_total||0),d=c>0?Math.round(u/c*100):Number(s.percentual_uso||0),p=n.getUsageColor(d);i.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Fatura atual</span>
                        <strong class="dash-widget-value">${t.money(u)}</strong>
                        ${c>0?`
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(d,100)}%; background:${p};"></span>
                            </div>
                            <p class="dash-widget-caption">${d}% do limite utilizado</p>
                        `:`
                            <p class="dash-widget-caption">${l} cartão(ões) ativo(s)</p>
                        `}
                        <a href="${e.BASE_URL}faturas" class="dash-widget-link">Abrir faturas</a>
                    </div>
                `}catch(s){r("Erro ao carregar widget de faturas",s,"Falha ao carregar faturas"),n.renderEmpty(i,"Não foi possível carregar suas faturas.",`${e.BASE_URL}faturas`,"Ver faturas")}}},render:async i=>{await Promise.allSettled([n.renderMetas(i),n.renderCartoes(),n.renderContas(i),n.renderOrcamentos(i),n.renderFaturas()])}};return n}function ct({API:o,Utils:e,escapeHtml:t,logClientError:a}){const r=(l,{includeYear:u=!0}={})=>{try{const[c,d]=String(l||"").split("-").map(Number);return new Date(c,d-1,1).toLocaleDateString("pt-BR",{month:"long",...u?{year:"numeric"}:{}})}catch{return u?"este mês":"próximo mês"}},n=l=>{try{const[u,c]=String(l||"").split("-").map(Number),d=new Date(u,c,1);return`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,"0")}`}catch{return e.getCurrentMonth()}},i=l=>{const u=Number(l||0);return`${u>=0?"+":"-"}${e.money(Math.abs(u))}`},s={isProUser:null,checkProStatus:async()=>{try{const l=await o.getOverview(e.getCurrentMonth());s.isProUser=l?.plan?.is_pro===!0}catch{s.isProUser=!1}return s.isProUser},render:async l=>{const u=document.getElementById("sectionPrevisao");if(!u)return;await s.checkProStatus();const c=document.getElementById("provisaoProOverlay"),d=s.isProUser;u.classList.remove("is-locked"),c&&(c.style.display="none");try{const p=await o.getOverview(l);s.renderData(p.provisao||null,d)}catch(p){a("Erro ao carregar provisão",p,"Falha ao carregar previsão")}},renderData:(l,u=!0)=>{if(!l)return;const c=l.provisao||{},d=e.money,p=l.month||e.getCurrentMonth(),y=r(p),g=r(p,{includeYear:!1}),h=r(n(p),{includeYear:!1}),m=document.getElementById("provisaoTitle"),w=document.getElementById("provisaoHeadline");m&&(m.textContent=`Fechamento previsto de ${y}`),w&&(w.textContent=`Saldo atual ${d(c.saldo_atual||0)}. Com o que ainda entra e sai, ${g} fecha em ${d(c.saldo_projetado||0)}.`);const C=document.getElementById("provisaoProximosTitle"),N=document.getElementById("provisaoVerTodos");C&&(C.innerHTML=u?'<i data-lucide="clock"></i> Próximos compromissos':'<i data-lucide="credit-card"></i> Próximas Faturas'),N&&(N.href=u?ae("lancamentos"):ae("faturas"));const A=document.getElementById("provisaoPagar"),$=document.getElementById("provisaoReceber"),P=document.getElementById("provisaoProjetado"),f=document.getElementById("provisaoPrevistoMes"),E=document.getElementById("provisaoPagarCount"),M=document.getElementById("provisaoReceberCount"),x=document.getElementById("provisaoProjetadoLabel"),H=document.getElementById("provisaoPrevistoCard"),z=document.getElementById("provisaoPrevistoMesLabel"),V=Number((c.a_receber||0)-(c.a_pagar||0)),Q=$?.closest(".provisao-card");if(A&&(A.textContent=d(c.a_pagar||0)),u?($&&($.textContent=d(c.a_receber||0)),Q&&(Q.style.opacity="1")):($&&($.textContent="R$ --"),Q&&(Q.style.opacity="0.5")),P&&(P.textContent=d(c.saldo_projetado||0),P.style.color=(c.saldo_projetado||0)>=0?"":"var(--color-danger)"),E){const S=c.count_pagar||0,b=c.count_faturas||0;if(u){let B=`${S} pendente${S!==1?"s":""}`;b>0&&(B+=` • ${b} fatura${b!==1?"s":""}`),E.textContent=B}else E.textContent=`${b} fatura${b!==1?"s":""}`}u?M&&(M.textContent=`${c.count_receber||0} pendente${(c.count_receber||0)!==1?"s":""}`):M&&(M.textContent="Pro"),x&&(x.textContent=`abre ${h} com ${d(c.saldo_projetado||0)}`),H&&(H.classList.remove("is-positive","is-negative","is-neutral"),u?V>0?H.classList.add("is-positive"):V<0?H.classList.add("is-negative"):H.classList.add("is-neutral"):H.classList.add("is-neutral")),f&&(u?(f.textContent=i(V),f.style.color=""):(f.textContent="R$ --",f.style.color="")),z&&(u?V>0?z.textContent=`${g} vira no azul`:V<0?z.textContent=`${g} vira no vermelho`:z.textContent="entradas e saídas empatadas":z.textContent="Pro");const Y=l.vencidos||{},oe=document.getElementById("provisaoAlertDespesas");if(oe){const S=Y.despesas||{};if(u&&(S.count||0)>0){oe.style.display="flex";const b=document.getElementById("provisaoAlertDespesasCount"),B=document.getElementById("provisaoAlertDespesasTotal");b&&(b.textContent=S.count),B&&(B.textContent=d(S.total||0))}else oe.style.display="none"}const ie=document.getElementById("provisaoAlertReceitas");if(ie){const S=Y.receitas||{};if(u&&(S.count||0)>0){ie.style.display="flex";const b=document.getElementById("provisaoAlertReceitasCount"),B=document.getElementById("provisaoAlertReceitasTotal");b&&(b.textContent=S.count),B&&(B.textContent=d(S.total||0))}else ie.style.display="none"}const re=document.getElementById("provisaoAlertFaturas");if(re){const S=Y.count_faturas||0;if(S>0){re.style.display="flex";const b=document.getElementById("provisaoAlertFaturasCount"),B=document.getElementById("provisaoAlertFaturasTotal");b&&(b.textContent=S),B&&(B.textContent=d(Y.total_faturas||0))}else re.style.display="none"}const q=document.getElementById("provisaoProximosList"),X=document.getElementById("provisaoEmpty");let J=l.proximos||[];if(u||(J=J.filter(S=>S.is_fatura===!0)),q)if(J.length===0){if(q.innerHTML="",X){const S=X.querySelector("span");S&&(S.textContent=u?"Nenhum vencimento pendente":"Nenhuma fatura pendente"),q.appendChild(X),X.style.display="flex"}}else{q.innerHTML="";const S=new Date().toISOString().slice(0,10);J.forEach(b=>{const B=(b.tipo||"").toLowerCase(),Z=b.is_fatura===!0,ge=(b.data_pagamento||"").split(/[T\s]/)[0],Ae=ge===S,Me=s.formatDateShort(ge);let F="";Ae&&(F+='<span class="provisao-item-badge vence-hoje">Hoje</span>'),Z?(F+='<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>',b.cartao_ultimos_digitos&&(F+=`<span>****${b.cartao_ultimos_digitos}</span>`)):(b.eh_parcelado&&b.numero_parcelas>1&&(F+=`<span class="provisao-item-badge parcela">${b.parcela_atual}/${b.numero_parcelas}</span>`),b.recorrente&&(F+='<span class="provisao-item-badge recorrente">Recorrente</span>'),b.categoria&&(F+=`<span>${t(b.categoria)}</span>`));const fe=Z?"fatura":B,j=document.createElement("div");j.className="provisao-item"+(Z?" is-fatura":""),j.innerHTML=`
                                <div class="provisao-item-dot ${fe}"></div>
                                <div class="provisao-item-info">
                                    <div class="provisao-item-titulo">${t(b.titulo||"Sem título")}</div>
                                    <div class="provisao-item-meta">${F}</div>
                                </div>
                                <span class="provisao-item-valor ${fe}">${d(b.valor||0)}</span>
                                <span class="provisao-item-data">${Me}</span>
                            `,Z&&b.cartao_id&&(j.style.cursor="pointer",j.addEventListener("click",()=>{const ke=(b.data_pagamento||"").split(/[T\s]/)[0],[Te,Ne]=ke.split("-");window.location.href=ae("faturas",{cartao_id:b.cartao_id,mes:parseInt(Ne,10),ano:Te})})),q.appendChild(j)})}const ce=document.getElementById("provisaoParcelas"),U=l.parcelas||{};if(ce)if(u&&(U.ativas||0)>0){ce.style.display="flex";const S=document.getElementById("provisaoParcelasText"),b=document.getElementById("provisaoParcelasValor");S&&(S.textContent=`${U.ativas} parcelamento${U.ativas!==1?"s":""} ativo${U.ativas!==1?"s":""}`),b&&(b.textContent=`${d(U.total_mensal||0)}/mês`)}else ce.style.display="none"},formatDateShort:l=>{if(!l)return"-";try{const u=l.match(/^(\d{4})-(\d{2})-(\d{2})$/);return u?`${u[3]}/${u[2]}`:"-"}catch{return"-"}}};return s}function lt({getDashboardOverview:o,getApiPayload:e,apiGet:t,apiDelete:a,apiPost:r,getErrorMessage:n}){function i(c){window.LKDemoPreviewBanner?.hide()}const s={getOverview:async(c,d={})=>{const p=await o(c,d),y=e(p,{});return i(y?.meta),y},fetch:async c=>{const d=await t(c);if(d?.success===!1)throw new Error(n({data:d},"Erro na API"));return d?.data??d},getMetrics:async c=>(await s.getOverview(c)).metrics||{},getAccountsBalances:async c=>{const d=await s.getOverview(c);return Array.isArray(d.accounts_balances)?d.accounts_balances:[]},getTransactions:async(c,d)=>{const p=await s.getOverview(c,{limit:d});return Array.isArray(p.recent_transactions)?p.recent_transactions:[]},getChartData:async c=>{const d=await s.getOverview(c);return Array.isArray(d.chart)?d.chart:[]},getFinanceSummary:async c=>{const d=String(c||"").match(/^(\d{4})-(\d{2})$/);if(!d)return{};const p=await t($e(),{ano:Number(d[1]),mes:Number(d[2])});return e(p,{})},getCardsSummary:async()=>{const c=await t(Ve());return e(c,{})},deleteTransaction:async c=>{const d=[{request:()=>a(qe(c))},{request:()=>r(Ue(),{id:c})}];for(const p of d)try{return await p.request()}catch(y){if(y?.status!==404)throw new Error(n(y,"Erro ao excluir"))}throw new Error("Endpoint de exclusão não encontrado.")}},l={ensureSwal:async()=>{window.Swal},toast:(c,d)=>{if(window.LK?.toast)return LK.toast[c]?.(d)||LK.toast.info(d);window.Swal?.fire({toast:!0,position:"top-end",timer:2500,timerProgressBar:!0,showConfirmButton:!1,icon:c,title:d})},loading:(c="Processando...")=>{if(window.LK?.loading)return LK.loading(c);window.Swal?.fire({title:c,didOpen:()=>window.Swal.showLoading(),allowOutsideClick:!1,showConfirmButton:!1})},close:()=>{if(window.LK?.hideLoading)return LK.hideLoading();window.Swal?.close()},confirm:async(c,d)=>window.LK?.confirm?LK.confirm({title:c,text:d,confirmText:"Sim, confirmar",danger:!0}):(await window.Swal?.fire({title:c,text:d,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, confirmar",cancelButtonText:"Cancelar",confirmButtonColor:"var(--color-danger)",cancelButtonColor:"var(--color-text-muted)"}))?.isConfirmed,error:(c,d)=>{if(window.LK?.toast)return LK.toast.error(d||c);window.Swal?.fire({icon:"error",title:c,text:d,confirmButtonColor:"var(--color-primary)"})}},u={badges:[{id:"first",icon:"target",name:"Inicio",condition:c=>c.totalTransactions>=1},{id:"week",icon:"bar-chart-3",name:"7 Dias",condition:c=>c.streak>=7},{id:"month",icon:"gem",name:"30 Dias",condition:c=>c.streak>=30},{id:"saver",icon:"coins",name:"Economia",condition:c=>c.savingsRate>=10},{id:"diverse",icon:"palette",name:"Diverso",condition:c=>c.uniqueCategories>=5},{id:"master",icon:"crown",name:"Mestre",condition:c=>c.totalTransactions>=100}],calculateStreak:c=>{if(!Array.isArray(c)||c.length===0)return 0;const d=c.map(m=>m.data_lancamento||m.data).filter(Boolean).map(m=>{const w=String(m).match(/^(\d{4})-(\d{2})-(\d{2})/);return w?`${w[1]}-${w[2]}-${w[3]}`:null}).filter(Boolean).sort().reverse();if(d.length===0)return 0;const p=[...new Set(d)],y=new Date;y.setHours(0,0,0,0);let g=0,h=new Date(y);for(const m of p){const[w,C,N]=m.split("-").map(Number),A=new Date(w,C-1,N);A.setHours(0,0,0,0);const $=Math.round((h-A)/(1e3*60*60*24));if($===0||$===1)g++,h=new Date(A),h.setDate(h.getDate()-1);else if($>1)break}return g},calculateLevel:c=>c<100?1:c<300?2:c<600?3:c<1e3?4:c<1500?5:c<2500?6:c<5e3?7:c<1e4?8:c<2e4?9:10,calculatePoints:c=>{let d=0;return d+=c.totalTransactions*10,d+=c.streak*50,d+=c.activeMonths*100,d+=c.uniqueCategories*20,d+=Math.floor(c.savingsRate)*30,d},calculateData:(c,d)=>{const p=c.length,y=u.calculateStreak(c),g=new Set(c.map(f=>f.categoria_id||f.categoria).filter(Boolean)).size,m=new Set(c.map(f=>{const E=f.data_lancamento||f.data;if(!E)return null;const M=String(E).match(/^(\d{4}-\d{2})/);return M?M[1]:null}).filter(Boolean)).size,w=Number(d?.receitas||0),C=Number(d?.despesas||0),N=w>0?(w-C)/w*100:0,A={totalTransactions:p,streak:y,uniqueCategories:g,activeMonths:m,savingsRate:Math.max(0,N)},$=u.calculatePoints(A),P=u.calculateLevel($);return{...A,points:$,level:P}}};return{API:s,Notifications:l,Gamification:u}}function dt({STATE:o,DOM:e,Utils:t,API:a,Notifications:r,Renderers:n,Provisao:i,OptionalWidgets:s,invalidateDashboardOverview:l,getErrorMessage:u,logClientError:c}){const d={delete:async(g,h)=>{try{if(await r.ensureSwal(),!await r.confirm("Excluir lançamento?","Esta ação não pode ser desfeita."))return;r.loading("Excluindo..."),await a.deleteTransaction(Number(g)),r.close(),r.toast("success","Lançamento excluído com sucesso!"),h&&(h.style.opacity="0",h.style.transform="translateX(-20px)",setTimeout(()=>{h.remove(),e.tableBody.children.length===0&&(e.emptyState&&(e.emptyState.style.display="block"),e.table&&(e.table.style.display="none"))},300)),document.dispatchEvent(new CustomEvent("lukrato:data-changed",{detail:{resource:"transactions",action:"delete",id:Number(g)}}))}catch(m){console.error("Erro ao excluir lançamento:",m),await r.ensureSwal(),r.error("Erro",u(m,"Falha ao excluir lançamento"))}}},p={refresh:async({force:g=!1}={})=>{if(o.isLoading)return;o.isLoading=!0;const h=t.getCurrentMonth();o.currentMonth=h,g&&l(h);try{n.updateMonthLabel(h),await Promise.allSettled([n.renderKPIs(h),n.renderTable(h),n.renderTransactionsList(h),n.renderChart(h),i.render(h),s.render(h)])}catch(m){c("Erro ao atualizar dashboard",m,"Falha ao atualizar dashboard")}finally{o.isLoading=!1}},init:async()=>{await p.refresh({force:!1})}};return{TransactionManager:d,DashboardManager:p,EventListeners:{init:()=>{if(o.eventListenersInitialized)return;o.eventListenersInitialized=!0,e.tableBody?.addEventListener("click",async h=>{const m=h.target.closest(".btn-del");if(!m)return;const w=h.target.closest("tr"),C=m.getAttribute("data-id");C&&(m.disabled=!0,await d.delete(C,w),m.disabled=!1)}),e.cardsContainer?.addEventListener("click",async h=>{const m=h.target.closest(".btn-del");if(!m)return;const w=h.target.closest(".transaction-card"),C=m.getAttribute("data-id");C&&(m.disabled=!0,await d.delete(C,w),m.disabled=!1)}),e.transactionsList?.addEventListener("click",async h=>{const m=h.target.closest(".btn-del");if(!m)return;const w=h.target.closest(".dash-tx-item"),C=m.getAttribute("data-id");C&&(m.disabled=!0,await d.delete(C,w),m.disabled=!1)}),document.addEventListener("lukrato:data-changed",()=>{l(o.currentMonth||t.getCurrentMonth()),p.refresh({force:!1})}),document.addEventListener("lukrato:month-changed",()=>{p.refresh({force:!1})}),document.addEventListener("lukrato:theme-changed",()=>{n.renderChart(o.currentMonth||t.getCurrentMonth())});const g=document.getElementById("chartToggle");g&&g.addEventListener("click",h=>{const m=h.target.closest("[data-mode]");if(!m)return;const w=m.getAttribute("data-mode");g.querySelectorAll(".dash-chart-toggle__btn").forEach(C=>C.classList.remove("is-active")),m.classList.add("is-active"),n.renderChart(o.currentMonth||t.getCurrentMonth(),w)})}}}}const{API:D,Notifications:ut}=lt({getDashboardOverview:W,getApiPayload:De,apiGet:me,apiDelete:Re,apiPost:_e,getErrorMessage:he}),L={updateMonthLabel:o=>{_.monthLabel&&(_.monthLabel.textContent=v.formatMonth(o))},toggleAlertsSection:()=>{const o=document.getElementById("dashboardAlertsSection");o&&(o.style.display="none")},setSignedState:(o,e,t)=>{const a=document.getElementById(o),r=document.getElementById(e);!a||!r||(a.classList.remove("is-positive","is-negative","income","expense"),r.classList.remove("is-positive","is-negative"),t>0?(a.classList.add("is-positive"),r.classList.add("is-positive")):t<0&&(a.classList.add("is-negative"),r.classList.add("is-negative")))},formatSignedMoney:o=>{const e=Number(o||0);return`${e>=0?"+":"-"}${v.money(Math.abs(e))}`},renderStatusChip:(o,e,t)=>{o&&(o.innerHTML=`
            <i data-lucide="${e}" class="dashboard-status-chip-icon" style="width:16px;height:16px;"></i>
            <span>${t}</span>
        `,typeof window.lucide<"u"&&window.lucide.createIcons())},renderHeroNarrative:({saldo:o,receitas:e,despesas:t,resultado:a})=>{const r=document.getElementById("dashboardHeroStatus"),n=document.getElementById("dashboardHeroMessage"),i=Number(e||0),s=Number(t||0),l=Number.isFinite(Number(a))?Number(a):i-s;if(!(!r||!n)){if(r.className="dashboard-status-chip",n.className="dashboard-hero-message",s>i){r.classList.add("dashboard-status-chip--negative"),n.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(r,"triangle-alert",`Mês no vermelho (${L.formatSignedMoney(l)})`),n.textContent=`Atenção: você gastou mais do que ganhou (${L.formatSignedMoney(l)}).`;return}if(l>0){r.classList.add("dashboard-status-chip--positive"),n.classList.add("dashboard-hero-message--positive"),L.renderStatusChip(r,o>=0?"piggy-bank":"trending-up",o>=0?`Mês positivo (${L.formatSignedMoney(l)})`:`Recuperando o mês (${L.formatSignedMoney(l)})`),n.textContent=`Você está positivo este mês (${L.formatSignedMoney(l)}).`;return}if(l===0){r.classList.add("dashboard-status-chip--neutral"),L.renderStatusChip(r,"scale","Mês zerado (R$ 0,00)"),n.textContent=`Entrou ${v.money(i)} e saiu ${v.money(s)}. Seu saldo do mês está em R$ 0,00.`;return}r.classList.add("dashboard-status-chip--negative"),n.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(r,"wallet",`Resultado do mês ${L.formatSignedMoney(l)}`),n.textContent=`Seu resultado mensal está em ${L.formatSignedMoney(l)}. Vale rever os gastos mais pesados agora.`}},renderHeroSparkline:async o=>{const e=document.getElementById("heroSparkline");if(!(!e||typeof ApexCharts>"u"))try{const t=await D.getOverview(o),a=Array.isArray(t.chart)?t.chart:[];if(a.length<2){e.innerHTML="";return}const r=a.map(l=>Number(l.resultado||0)),{isLightTheme:n}=ye(),s=(r[r.length-1]||0)>=0?"#10b981":"#ef4444";I._heroSparkInstance&&(I._heroSparkInstance.destroy(),I._heroSparkInstance=null),I._heroSparkInstance=new ApexCharts(e,{chart:{type:"area",height:48,sparkline:{enabled:!0},background:"transparent"},series:[{data:r}],stroke:{width:2,curve:"smooth",colors:[s]},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:0,stops:[0,100],colorStops:[{offset:0,color:s,opacity:.25},{offset:100,color:s,opacity:0}]}},tooltip:{enabled:!0,fixed:{enabled:!1},x:{show:!1},y:{formatter:l=>v.money(l),title:{formatter:()=>""}},theme:n?"light":"dark"},colors:[s]}),I._heroSparkInstance.render()}catch{}},renderHeroContext:({receitas:o,despesas:e})=>{const t=document.getElementById("heroContext");if(!t)return;const a=Number(o||0),r=Number(e||0);if(a<=0){t.style.display="none";return}const n=(a-r)/a*100;let i,s,l;n>=20?(i="piggy-bank",s=`Você está economizando ${Math.round(n)}% da renda — excelente!`,l="dash-hero__context--positive"):n>=1?(i="target",s=`Economia de ${Math.round(n)}% da renda — meta ideal é 20%.`,l="dash-hero__context--neutral"):(i="alert-triangle",s="Sem margem de economia este mês. Revise seus gastos.",l="dash-hero__context--negative"),t.className=`dash-hero__context ${l}`,t.innerHTML=`<i data-lucide="${i}" style="width:14px;height:14px;"></i> ${s}`,t.style.display="",typeof window.lucide<"u"&&window.lucide.createIcons()},renderOverviewAlerts:({receitas:o,despesas:e})=>{const t=document.getElementById("dashboardAlertsOverview");if(!t)return;const a=document.getElementById("dashboardAlertsSection");a&&(a.style.display="none");const r=Number(o||0),n=Number(e||0),i=r-n;n>r?(t.innerHTML=`
                <a href="${O.BASE_URL}lancamentos?tipo=despesa" class="dashboard-alert dashboard-alert--danger">
                    <div class="dashboard-alert-icon">
                        <i data-lucide="triangle-alert" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="dashboard-alert-content">
                        <strong>Atenção: você gastou mais do que ganhou</strong>
                        <span>Entrou ${v.money(r)} e saiu ${v.money(n)}. Diferença do mês: ${L.formatSignedMoney(i)}.</span>
                    </div>
                    <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
                </a>
            `,typeof window.lucide<"u"&&window.lucide.createIcons()):t.innerHTML="",L.toggleAlertsSection()},renderChartInsight:(o,e)=>{const t=document.getElementById("chartInsight");if(!t)return;if(!Array.isArray(e)||e.length===0||e.every(i=>Number(i)===0)){t.textContent="Seu historico aparece aqui conforme você usa o Lukrato mais vezes.";return}let a=0;e.forEach((i,s)=>{Number(i)<Number(e[a])&&(a=s)});const r=o[a],n=Number(e[a]||0);if(n<0){t.textContent=`Seu pior mes foi ${v.formatMonth(r)} (${v.money(n)}).`;return}t.textContent=`Seu pior mes foi ${v.formatMonth(r)} e mesmo assim fechou em ${v.money(n)}.`},renderKPIs:async o=>{try{const e=await D.getOverview(o),t=e?.metrics||{},a=Array.isArray(e?.accounts_balances)?e.accounts_balances:[],r=e?.meta||{},n={receitasValue:t.receitas||0,despesasValue:t.despesas||0,saldoMesValue:t.resultado||0};Object.entries(n).forEach(([y,g])=>{const h=document.getElementById(y);h&&(h.textContent=v.money(g))});const i=Number(t.saldoAcumulado??t.saldo??0),s=(Array.isArray(a)?a:[]).reduce((y,g)=>{const h=typeof g.saldoAtual=="number"?g.saldoAtual:g.saldoInicial||0;return y+(isFinite(h)?Number(h):0)},0),l=Array.isArray(a)&&a.length>0?s:i;_.saldoValue&&(_.saldoValue.textContent=v.money(l)),L.setSignedState("saldoValue","saldoCard",l),L.setSignedState("saldoMesValue","saldoMesCard",Number(t.resultado||0)),L.renderHeroNarrative({saldo:l,receitas:Number(t.receitas||0),despesas:Number(t.despesas||0),resultado:Number(t.resultado||0)}),L.renderHeroSparkline(o),L.renderHeroContext({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)}),L.renderOverviewAlerts({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)});const u=Number(r?.real_transaction_count??t.count??0),c=Number(r?.real_category_count??t.categories??0),d=Number(r?.real_account_count??a.length??0),p=Fe(r,{accountCount:d});document.dispatchEvent(new CustomEvent("lukrato:dashboard-overview-rendered",{detail:{month:o,accountCount:d,transactionCount:u,categoryCount:c,hasData:u>0,primaryAction:p.actionType,ctaLabel:p.ctaLabel,ctaUrl:p.ctaUrl,isDemo:!!r?.is_demo}})),v.removeLoadingClass()}catch(e){T("Erro ao renderizar KPIs",e,"Falha ao carregar indicadores"),["saldoValue","receitasValue","despesasValue","saldoMesValue"].forEach(t=>{const a=document.getElementById(t);a&&(a.textContent="R$ 0,00",a.classList.remove("loading"))})}},renderTable:async o=>{try{const e=await D.getTransactions(o,O.TRANSACTIONS_LIMIT);_.tableBody&&(_.tableBody.innerHTML=""),_.cardsContainer&&(_.cardsContainer.innerHTML=""),Array.isArray(e)&&e.length>0&&e.forEach(a=>{const r=String(a.tipo||"").toLowerCase(),n=v.getTipoClass(r),i=String(a.tipo||"").replace(/_/g," "),s=a.categoria_nome??(typeof a.categoria=="string"?a.categoria:a.categoria?.nome)??null,l=s?k(s):'<span class="categoria-empty">Sem categoria</span>',u=k(v.getContaLabel(a)),c=k(a.descricao||"--"),d=k(i),p=Number(a.valor)||0,y=v.dateBR(a.data),g=document.createElement("tr");if(g.setAttribute("data-id",a.id),g.innerHTML=`
              <td data-label="Data">${y}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${n}">${d}</span>
              </td>
              <td data-label="Categoria">${l}</td>
              <td data-label="Conta">${u}</td>
              <td data-label="Descrição">${c}</td>
              <td data-label="Valor" class="valor-cell ${n}">${v.money(p)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            `,_.tableBody&&_.tableBody.appendChild(g),_.cardsContainer){const h=document.createElement("div");h.className="transaction-card",h.setAttribute("data-id",a.id),h.innerHTML=`
                <div class="transaction-card-header">
                  <span class="transaction-date">${y}</span>
                  <span class="transaction-value ${n}">${v.money(p)}</span>
                </div>
                <div class="transaction-card-body">
                  <div class="transaction-info-row">
                    <span class="transaction-label">Tipo</span>
                    <span class="transaction-badge tipo-${n}">${d}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${l}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Conta</span>
                    <span class="transaction-text">${u}</span>
                  </div>
                  ${c!=="--"?`
                  <div class="transaction-info-row">
                    <span class="transaction-label">Descrição</span>
                    <span class="transaction-description">${c}</span>
                  </div>
                  `:""}
                </div>
                <div class="transaction-card-actions">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              `,_.cardsContainer.appendChild(h)}})}catch(e){T("Erro ao renderizar transações",e,"Falha ao carregar transações")}},renderTransactionsList:async o=>{if(_.transactionsList)try{const e=await D.getTransactions(o,O.TRANSACTIONS_LIMIT),t=Array.isArray(e)&&e.length>0;if(_.transactionsList.innerHTML="",_.emptyState&&(_.emptyState.style.display=t?"none":"flex"),!t)return;const a=new Date().toISOString().slice(0,10),r=new Date(Date.now()-864e5).toISOString().slice(0,10),n=new Map;e.forEach(i=>{const s=String(i.data||"").split(/[T\s]/)[0];n.has(s)||n.set(s,[]),n.get(s).push(i)});for(const[i,s]of n){let l;i===a?l="Hoje":i===r?l="Ontem":l=v.dateBR(i);const u=document.createElement("div");u.className="dash-tx-date-group",u.textContent=l,_.transactionsList.appendChild(u),s.forEach(c=>{const p=String(c.tipo||"").toLowerCase()==="receita",y=k(c.descricao||"--"),g=c.categoria_nome??(typeof c.categoria=="string"?c.categoria:c.categoria?.nome)??"Sem categoria",h=Number(c.valor)||0,m=!!c.pago,w=c.categoria_icone||(p?"arrow-down-left":"arrow-up-right"),C=document.createElement("div");C.className="dash-tx-item surface-card",C.setAttribute("data-id",c.id),C.innerHTML=`
                        <div class="dash-tx__left">
                            <div class="dash-tx__icon dash-tx__icon--${p?"income":"expense"}">
                                <i data-lucide="${k(w)}"></i>
                            </div>
                            <div class="dash-tx__info">
                                <span class="dash-tx__desc">${y}</span>
                                <span class="dash-tx__category">${k(g)}</span>
                            </div>
                        </div>
                        <div class="dash-tx__right">
                            <span class="dash-tx__amount dash-tx__amount--${p?"income":"expense"}">${p?"+":"-"}${v.money(Math.abs(h))}</span>
                            <span class="dash-tx__badge dash-tx__badge--${m?"paid":"pending"}">${m?"Pago":"Pendente"}</span>
                        </div>
                    `,_.transactionsList.appendChild(C)})}typeof window.lucide<"u"&&window.lucide.createIcons()}catch(e){T("Erro ao renderizar lista de transações",e,"Falha ao carregar transações"),_.emptyState&&(_.emptyState.style.display="flex")}},renderChart:async(o,e)=>{if(!(!_.categoryChart||typeof ApexCharts>"u")){e||(e=I._chartMode||"donut"),I._chartMode=e,_.chartLoading&&(_.chartLoading.style.display="flex");try{const t=await D.getOverview(o),a=Array.isArray(t.despesas_por_categoria)?t.despesas_por_categoria:[],{isLightTheme:r}=ye(),n=r?"light":"dark";if(I.chartInstance&&(I.chartInstance.destroy(),I.chartInstance=null),a.length===0){const s=Se(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)});_.categoryChart.innerHTML=`
                    <div class="dash-chart-empty">
                        <i data-lucide="pie-chart"></i>
                        <strong>${k(s.chartEmptyTitle)}</strong>
                        <p>${k(s.chartEmptyDescription)}</p>
                        <button class="dash-btn dash-btn--ghost" type="button" id="dashboardChartEmptyCta">
                            <i data-lucide="plus"></i> ${k(s.chartEmptyButton)}
                        </button>
                    </div>
                `,document.getElementById("dashboardChartEmptyCta")?.addEventListener("click",()=>{Ee(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)})}),typeof window.lucide<"u"&&window.lucide.createIcons();return}const i=["#E67E22","#2ecc71","#e74c3c","#3498db","#9b59b6","#1abc9c","#f39c12","#e91e63","#00bcd4","#8bc34a"];if(e==="compare"){const l=v.getPreviousMonths(o,2)[0];let u=[];try{const m=await D.getOverview(l);u=Array.isArray(m.despesas_por_categoria)?m.despesas_por_categoria:[]}catch{}const d=[...new Set([...a.map(m=>m.categoria),...u.map(m=>m.categoria)])],p=Object.fromEntries(a.map(m=>[m.categoria,Math.abs(Number(m.valor)||0)])),y=Object.fromEntries(u.map(m=>[m.categoria,Math.abs(Number(m.valor)||0)])),g=d.map(m=>p[m]||0),h=d.map(m=>y[m]||0);I.chartInstance=new ApexCharts(_.categoryChart,{chart:{type:"bar",height:300,background:"transparent",fontFamily:"Inter, Arial, sans-serif",toolbar:{show:!1}},series:[{name:v.formatMonthShort(o),data:g},{name:v.formatMonthShort(l),data:h}],colors:["#E67E22","rgba(230,126,34,0.35)"],xaxis:{categories:d,labels:{style:{colors:r?"#555":"#aaa",fontSize:"11px"},rotate:-35,trim:!0,maxHeight:80}},yaxis:{labels:{formatter:m=>v.money(m),style:{colors:r?"#555":"#aaa"}}},plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},dataLabels:{enabled:!1},legend:{position:"top",fontSize:"12px",labels:{colors:r?"#555":"#ccc"}},tooltip:{theme:n,y:{formatter:m=>v.money(m)}},grid:{borderColor:r?"#e5e5e5":"rgba(255,255,255,0.06)",strokeDashArray:3},theme:{mode:n}})}else{const s=a.map(u=>u.categoria),l=a.map(u=>Math.abs(Number(u.valor)||0));I.chartInstance=new ApexCharts(_.categoryChart,{chart:{type:"donut",height:280,background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:l,labels:s,colors:i.slice(0,s.length),stroke:{width:2,colors:[r?"#fff":"#1e1e1e"]},plotOptions:{pie:{donut:{size:"60%",labels:{show:!0,value:{formatter:u=>v.money(Number(u))},total:{show:!0,label:"Total",formatter:u=>v.money(u.globals.seriesTotals.reduce((c,d)=>c+d,0))}}}}},legend:{position:"bottom",fontSize:"13px",labels:{colors:r?"#555":"#ccc"}},tooltip:{theme:n,y:{formatter:u=>v.money(u)}},dataLabels:{enabled:!1},theme:{mode:n}})}I.chartInstance.render()}catch(t){T("Erro ao renderizar gráfico",t,"Falha ao carregar gráfico")}finally{_.chartLoading&&setTimeout(()=>{_.chartLoading.style.display="none"},300)}}}},mt=rt({API:D,CONFIG:O,Utils:v,escapeHtml:k,logClientError:T}),ht=ct({API:D,Utils:v,escapeHtml:k,logClientError:T}),{DashboardManager:le,EventListeners:pt}=dt({STATE:I,DOM:_,Utils:v,API:D,Notifications:ut,Renderers:L,Provisao:ht,OptionalWidgets:mt,invalidateDashboardOverview:R,getErrorMessage:he,logClientError:T});let ee=null,Ce=!1,de=null,xe={};function gt(){const o=ne(),e=o?.pageCapabilities?.pageKey==="dashboard"&&o?.pageCapabilities?.customizer&&typeof o.pageCapabilities.customizer=="object"?o.pageCapabilities.customizer:null,t=e?.descriptor&&typeof e.descriptor=="object"?e.descriptor:null,a=t?.sectionMap&&typeof t.sectionMap=="object"?t.sectionMap:{},r=e?.completePreferences&&typeof e.completePreferences=="object"?e.completePreferences:{},n=e?.essentialPreferences&&typeof e.essentialPreferences=="object"?e.essentialPreferences:{},i=Array.isArray(t?.gridToggleKeys)?t.gridToggleKeys:[],s=t?.ids&&typeof t.ids=="object"?{overlayId:t.ids.overlay,openButtonId:t.trigger?.id||"btnCustomizeDashboard",closeButtonId:t.ids.close,saveButtonId:t.ids.save,presetEssentialButtonId:t.ids.presetEssential,presetCompleteButtonId:t.ids.presetComplete}:void 0;return xe=r,{capabilities:e,sectionMap:a,completeDefaults:r,essentialDefaults:n,gridToggleKeys:i,modalConfig:s}}function ft(){const o=gt();return ee||Object.keys(o.sectionMap).length===0?{customizer:ee,resolved:o}:(ee=je({storageKey:"lk_dashboard_prefs",sectionMap:o.sectionMap,completeDefaults:o.completeDefaults,essentialDefaults:o.essentialDefaults,gridContainerId:"optionalGrid",gridToggleKeys:o.gridToggleKeys,capabilities:o.capabilities,loadPreferences:yt,savePreferences:vt,onApply:Be,onLockedOpen:bt,modal:o.modalConfig}),{customizer:ee,resolved:o})}async function yt(){return Ge("dashboard")}async function vt(o){await Ke("dashboard",o)}function bt(){window.location.href=ae("billing")}function ue(o){return!!o&&getComputedStyle(o).display!=="none"}function G(o,{hideWhenEmpty:e=!0}={}){if(!o)return 0;const t=Array.from(o.children).filter(ue).length;return o.dataset.visibleCount=String(t),e&&(o.style.display=t>0?"":"none"),t}function te(o,e){o&&(o.dataset.visibleCount=String(e),o.style.display=e>0?"":"none")}function Be(o=xe){const e=document.querySelector(".dashboard-stage--overview"),t=document.querySelector(".dashboard-overview-top"),a=document.querySelector(".dashboard-overview-bottom"),r=document.getElementById("sectionAlertas"),n=document.getElementById("rowHealthAi"),i=document.getElementById("healthScoreInsights"),s=document.querySelector(".dashboard-stage--decision"),l=document.querySelector(".dash-duo-row--decision"),u=document.querySelector(".dash-duo-row--insights"),c=document.querySelector(".dashboard-stage--history"),d=document.getElementById("sectionEvolucao"),p=document.querySelector(".dashboard-stage--secondary"),y=document.getElementById("optionalGrid"),g=G(t,{hideWhenEmpty:!1});G(n),i&&(i.style.display=o.toggleHealthScore?"":"none");const h=[r,n,i].filter(ue).length;a&&(a.dataset.visibleCount=String(h),a.style.display=h>0?"":"none");const m=G(l),w=G(u,{hideWhenEmpty:!1}),C=G(y);y&&(y.dataset.layout=C>0&&C<5?"fluid":"default"),te(e,(g>0?1:0)+(h>0?1:0)),te(s,(m>0?1:0)+(w>0?1:0)),te(c,ue(d)?1:0),te(p,C>0?1:0)}function wt(){const o=()=>{const{customizer:e,resolved:t}=ft();return e?(Ce||(e.init(),Ce=!0),!0):(Be(t.essentialDefaults),!1)};o()||de||(de=pe({},{silent:!0}).finally(()=>{de=null,o()}))}window.__LK_DASHBOARD_LOADER__||(window.__LK_DASHBOARD_LOADER__=!0,window.refreshDashboard=le.refresh,window.LK=window.LK||{},window.LK.refreshDashboard=le.refresh,(()=>{const e=()=>{pt.init(),le.init(),wt()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",e):e()})());
