import{m as te,l as k,k as le,i as be,e as de,c as ie,d as Me,p as Te}from"./api-DpYnTMaG.js";import{a as ke,i as Ne,g as we,o as Ce,r as De}from"./primary-actions-D39VL42P.js";import{o as Se,g as ue,e as _e,d as Re,a as Pe}from"./runtime-config-CXTcOn9X.js";import{t as Ee,u as He}from"./finance-CgaDv1sH.js";import{j as Fe,k as Oe}from"./lancamentos-BFIM3VKH.js";import{e as T}from"./utils-Bj4jxwhy.js";import{c as Ve,p as qe,f as ze}from"./ui-preferences-CUuIZRMg.js";const O={BASE_URL:te(),TRANSACTIONS_LIMIT:5,CHART_MONTHS:6,ANIMATION_DELAY:300},S={saldoValue:document.getElementById("saldoValue"),receitasValue:document.getElementById("receitasValue"),despesasValue:document.getElementById("despesasValue"),saldoMesValue:document.getElementById("saldoMesValue"),categoryChart:document.getElementById("categoryChart"),chartLoading:document.getElementById("chartLoading"),transactionsList:document.getElementById("transactionsList"),emptyState:document.getElementById("emptyState"),metasBody:document.getElementById("sectionMetasBody"),cartoesBody:document.getElementById("sectionCartoesBody"),contasBody:document.getElementById("sectionContasBody"),orcamentosBody:document.getElementById("sectionOrcamentosBody"),faturasBody:document.getElementById("sectionFaturasBody"),chartContainer:document.getElementById("categoryChart"),tableBody:document.getElementById("transactionsTableBody"),table:document.getElementById("transactionsTable"),cardsContainer:document.getElementById("transactionsCards"),monthLabel:document.getElementById("currentMonthText"),streakDays:document.getElementById("streakDays"),badgesGrid:document.getElementById("badgesGrid"),userLevel:document.getElementById("userLevel"),totalLancamentos:document.getElementById("totalLancamentos"),totalCategorias:document.getElementById("totalCategorias"),mesesAtivos:document.getElementById("mesesAtivos"),pontosTotal:document.getElementById("pontosTotal")},I={chartInstance:null,currentMonth:null,isLoading:!1},b={money:r=>{try{return Number(r||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}catch{return"R$ 0,00"}},dateBR:r=>{if(!r)return"-";try{const t=String(r).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);return t?`${t[3]}/${t[2]}/${t[1]}`:"-"}catch{return"-"}},formatMonth:r=>{try{const[e,t]=String(r).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})}catch{return"-"}},formatMonthShort:r=>{try{const[e,t]=String(r).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"short"})}catch{return"-"}},getCurrentMonth:()=>window.LukratoHeader?.getMonth?.()||new Date().toISOString().slice(0,7),getPreviousMonths:(r,e)=>{const t=[],[a,i]=r.split("-").map(Number);for(let s=e-1;s>=0;s--){const n=new Date(a,i-1-s,1),o=n.getFullYear(),l=String(n.getMonth()+1).padStart(2,"0");t.push(`${o}-${l}`)}return t},getCssVar:(r,e="")=>{try{return(getComputedStyle(document.documentElement).getPropertyValue(r)||"").trim()||e}catch{return e}},isLightTheme:()=>{try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getContaLabel:r=>{if(typeof r.conta=="string"&&r.conta.trim())return r.conta.trim();const e=r.conta_instituicao??r.conta_nome??r.conta?.instituicao??r.conta?.nome??null,t=r.conta_destino_instituicao??r.conta_destino_nome??r.conta_destino?.instituicao??r.conta_destino?.nome??null;return r.eh_transferencia&&(e||t)?`${e||"-"}${t||"-"}`:r.conta_label&&String(r.conta_label).trim()?String(r.conta_label).trim():e||"-"},getTipoClass:r=>{const e=String(r||"").toLowerCase();return e==="receita"?"receita":e.includes("despesa")?"despesa":e.includes("transferencia")?"transferencia":""},removeLoadingClass:()=>{setTimeout(()=>{document.querySelectorAll(".kpi-value.loading").forEach(r=>{r.classList.remove("loading")})},O.ANIMATION_DELAY)}},ge=()=>{const r=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||b.isLightTheme?.();return{isLightTheme:r,axisColor:r?b.getCssVar("--color-primary","#e67e22")||"#e67e22":"rgba(255, 255, 255, 0.6)",yTickColor:r?"#000":"#fff",xTickColor:r?b.getCssVar("--color-text-muted","#6c757d")||"#6c757d":"rgba(255, 255, 255, 0.6)",gridColor:r?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",tooltipBg:r?"rgba(255, 255, 255, 0.92)":"rgba(0, 0, 0, 0.85)",tooltipColor:r?"#0f172a":"#f8fafc",labelColor:r?"#0f172a":"#f8fafc"}};function Ue(){return"api/v1/dashboard/overview"}function je(){return"api/v1/dashboard/evolucao"}const Ke=3e4;function Ge(r,e){return`dashboard:overview:${r}:${e}`}function W(r=b.getCurrentMonth(),{limit:e=O.TRANSACTIONS_LIMIT,force:t=!1}={}){return ke(Ue(),{month:r,limit:e},{cacheKey:Ge(r,e),ttlMs:Ke,force:t})}function P(r=null){const e=r?`dashboard:overview:${r}:`:"dashboard:overview:";Ne(e)}class We{constructor(e="greetingContainer"){this.container=document.getElementById(e),this.userName=this.getUserName(),this._listeningDataChanged=!1,Se(()=>{this.userName=this.getUserName(),this.updateGreetingTitle()})}getUserName(){return String(ue().username||"Usuario").trim().split(/\s+/)[0]||"Usuario"}render(){if(!this.container)return;this.userName=this.getUserName();const e=this.getGreeting(),a=new Date().toLocaleDateString("pt-BR",{weekday:"long",day:"numeric",month:"long"});this.container.innerHTML=`
      <div class="dashboard-greeting dashboard-greeting--compact" data-aos="fade-right" data-aos-duration="500">
        <p class="greeting-date">${a}</p>
        <p class="greeting-title">${e.title}</p>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    `,this.loadInsight(),_e({},{silent:!0})}updateGreetingTitle(){const e=this.container?.querySelector(".greeting-title");e&&(e.textContent=this.getGreeting().title)}getGreeting(){const e=new Date().getHours();return e>=5&&e<12?{title:`Bom dia, ${this.userName}.`}:e>=12&&e<18?{title:`Boa tarde, ${this.userName}.`}:e>=18&&e<24?{title:`Boa noite, ${this.userName}.`}:{title:`Boa madrugada, ${this.userName}.`}}async loadInsight({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t;a?.greeting_insight?this.displayInsight(a.greeting_insight):this.displayFallbackInsight()}catch(t){k("Error loading greeting insight",t,"Falha ao carregar insight"),this.displayFallbackInsight()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{P(),this.loadInsight({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.loadInsight({force:!0})}))}displayInsight(e){const t=document.getElementById("greetingInsight");if(!t)return;const{message:a,icon:i,color:s}=e;t.innerHTML=`
      <div class="insight-content">
        <div class="insight-icon" style="color: ${s||"var(--color-primary)"};">
          <i data-lucide="${i||"sparkles"}" style="width:16px;height:16px;"></i>
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
    `,typeof window.lucide<"u"&&window.lucide.createIcons())}}window.DashboardGreeting=We;class Qe{constructor(e="healthScoreContainer"){this.container=document.getElementById(e),this.healthScore=0,this.maxScore=100,this.animationDuration=1200}render(){if(!this.container)return;const e=45;this.circumference=2*Math.PI*e;const t=this.circumference;this.container.innerHTML=`
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
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t;a?.health_score&&this.updateScore(a.health_score)}catch(t){k("Error loading health score",t,"Falha ao carregar health score"),this.showError()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{P(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.load({force:!0})}))}updateScore(e){const{score:t=0}=e;this.animateGauge(t),this.updateBreakdown(e),this.updateStatusIndicator(t)}animateGauge(e){const t=document.getElementById("gaugeCircle"),a=document.getElementById("gaugeValue");if(!t||!a)return;const i=this.circumference||2*Math.PI*45;let s=0;const n=e/(this.animationDuration/16),o=()=>{s+=n,s>=e&&(s=e);const l=i-i*s/this.maxScore;t.setAttribute("stroke-dashoffset",l),a.textContent=Math.round(s),s<e&&requestAnimationFrame(o)};o()}updateBreakdown(e){const t=document.getElementById("hsLancamentos"),a=document.getElementById("hsOrcamento"),i=document.getElementById("hsMetas");if(t){const s=e.lancamentos??0;t.textContent=`${s}`,s>=10?t.className="hs-metric-value color-success":s>=5?t.className="hs-metric-value color-warning":t.className="hs-metric-value color-muted"}if(a){const s=e.orcamentos??0,n=e.orcamentos_ok??0;s===0?(a.textContent="--",a.className="hs-metric-value color-muted"):(a.textContent=`${n}/${s}`,n===s?a.className="hs-metric-value color-success":n>=s/2?a.className="hs-metric-value color-warning":a.className="hs-metric-value color-danger")}if(i){const s=e.metas_ativas??0,n=e.metas_concluidas??0;s===0?(i.textContent="--",i.className="hs-metric-value color-muted"):n>0?(i.textContent=`${s}+${n}`,i.className="hs-metric-value color-success"):(i.textContent=`${s}`,i.className="hs-metric-value color-warning")}}updateStatusIndicator(e){const t=document.getElementById("healthIndicator"),a=document.getElementById("healthMessage");if(!t)return;let i="critical",s="CRÍTICA",n="Ajustes rápidos podem evitar aperto financeiro.";e>=70?(i="excellent",s="BOA",n="Você está no controle. Continue assim!"):e>=50?(i="good",s="ESTÁVEL",n="Controle bom, mas há espaço para melhorar."):e>=30&&(i="warning",s="ATENÇÃO",n="Alguns sinais pedem cuidado neste mês."),t.className=`hs-badge hs-badge--${i}`,t.innerHTML=`
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${s}</span>
    `,a&&(a.textContent=n)}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}showError(){const e=document.getElementById("healthIndicator"),t=document.getElementById("healthMessage");e&&(e.className="hs-badge hs-badge--error",e.innerHTML=`
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `),t&&(t.textContent="Não foi possível carregar.")}}window.HealthScoreWidget=Qe;class Ye{constructor(e="healthScoreInsights"){this.container=document.getElementById(e),this.baseURL=te(),this.init()}init(){this.container&&(this._initialized||(this._initialized=!0,this.renderSkeleton(),this.loadInsights(),this._intervalId=setInterval(()=>this.loadInsights({force:!0}),3e5),document.addEventListener("lukrato:data-changed",()=>{P(),this.loadInsights({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.loadInsights({force:!0})})))}renderSkeleton(){this.container.innerHTML=`
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `}async loadInsights({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t;a?.health_score_insights?this.renderInsights(a.health_score_insights):this.renderEmpty()}catch(t){k("Error loading health score insights",t,"Falha ao carregar insights"),this.renderEmpty()}}renderInsights(e){const t=Array.isArray(e)?e:e?.insights||[],a=Array.isArray(e)?"":e?.total_possible_improvement||"";if(t.length===0){this.renderEmpty();return}const i=t.map((s,n)=>{const o=this.normalizeInsight(s);return`
      <a href="${this.baseURL}${o.action.url}" class="hsi-card hsi-card--${o.priority}" style="animation-delay: ${n*80}ms;">
        <div class="hsi-card-icon hsi-icon--${o.priority}">
          <i data-lucide="${this.getIconForType(o.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${o.title}</span>
          <span class="hsi-card-desc">${o.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${o.impact}</span>
          <i data-lucide="chevron-right" style="width:14px;height:14px;" class="hsi-arrow"></i>
        </div>
      </a>
    `}).join("");this.container.innerHTML=`
      <div class="hsi-list">${i}</div>
      ${a?`
        <div class="hsi-summary">
          <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
          <span>Potencial: <strong>${a}</strong></span>
        </div>
      `:""}
    `,typeof window.lucide<"u"&&window.lucide.createIcons()}normalizeInsight(e){const a={negative_balance:{title:"Seu saldo ficou negativo",impact:"Aja agora",action:{url:"lancamentos?tipo=despesa"}},low_activity:{title:"Registre mais movimentações",impact:"Mais controle",action:{url:"lancamentos"}},low_categories:{title:"Use mais categorias",impact:"Mais clareza",action:{url:"categorias"}},no_goals:{title:"Defina uma meta financeira",impact:"Mais direcao",action:{url:"financas#metas"}}}[e.type]||{title:"Insight do mes",impact:"Ver detalhe",action:{url:"dashboard"}};return{priority:e.priority||"medium",type:e.type||"generic",title:e.title||a.title,message:e.message||"",impact:e.impact||a.impact,action:e.action||a.action}}renderEmpty(){this.container.innerHTML=""}getIconForType(e){return{savings_rate:"piggy-bank",consistency:"calendar-check",diversification:"layers",negative_balance:"alert-triangle",low_balance:"wallet",no_income:"alert-circle",no_goals:"target"}[e]||"lightbulb"}}window.HealthScoreInsights=Ye;class Xe{constructor(e="aiTipContainer"){this.container=document.getElementById(e),this.baseURL=te()}init(){this.container&&(this._initialized||(this._initialized=!0,this.render(),this.load(),document.addEventListener("lukrato:data-changed",()=>{P(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.load({force:!0})})))}render(){this.container.innerHTML=`
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
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await W(void 0,{force:e}),a=t?.data??t,i=this.buildTips(a);this.renderTips(i)}catch(t){k("Error loading AI tips",t,"Falha ao carregar dicas"),this.renderEmpty()}}buildTips(e){const t=[],a=e?.health_score||{},i=e?.metrics||{},s=e?.provisao?.provisao||{},n=e?.provisao?.vencidos||{},o=e?.provisao?.parcelas||{},l=e?.chart||[],d=Array.isArray(e?.health_score_insights)?e.health_score_insights:e?.health_score_insights?.insights||[],m={critical:0,high:1,medium:2,low:3};if(d.sort((y,_)=>(m[y.priority]??9)-(m[_.priority]??9)).forEach(y=>{const _=this.normalizeInsight(y);t.push({type:_.type,priority:_.priority,icon:_.icon,title:y.title||_.title,desc:y.message||_.message,url:_.url,metric:y.metric||null,metricLabel:y.metric_label||null})}),n.count>0){const y=(n.total||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"overdue",priority:"critical",icon:"clock",title:`${n.count} conta(s) em atraso`,desc:"Regularize para evitar juros e manter o score saudável.",url:"lancamentos?status=vencido",metric:y,metricLabel:"em atraso"})}const c=e?.provisao?.proximos||[];if(c.length>0){const y=c[0],_=y.data_pagamento?new Date(y.data_pagamento+"T00:00:00"):null,M=new Date;if(M.setHours(0,0,0,0),_){const $=Math.ceil((_-M)/864e5);if($>=0&&$<=3){const H=(y.valor||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"upcoming",priority:"high",icon:"calendar",title:$===0?"Vence hoje!":`Vence em ${$} dia(s)`,desc:y.titulo||"Conta próxima do vencimento",url:"lancamentos",metric:H,metricLabel:$===0?"hoje":`${$}d`})}}}if(e?.greeting_insight){const y=e.greeting_insight;t.push({type:"greeting",priority:"positive",icon:y.icon||"trending-up",title:y.message||"Evolução do mês",desc:"",url:null,metric:null,metricLabel:null})}const u=a.savingsRate??0;(i.receitas??0)>0&&u>=20&&t.push({type:"savings",priority:"positive",icon:"piggy-bank",title:"Ótima taxa de economia!",desc:"Você está guardando acima dos 20% recomendados.",url:null,metric:u+"%",metricLabel:"guardado"});const g=a.orcamentos??0,h=a.orcamentos_ok??0;if(g>0){const y=g-h;y>0?t.push({type:"budget",priority:"high",icon:"alert-circle",title:`${y} orçamento(s) estourado(s)`,desc:"Revise seus gastos para voltar ao controle.",url:"financas",metric:`${h}/${g}`,metricLabel:"no limite"}):t.push({type:"budget",priority:"positive",icon:"check-circle",title:"Orçamentos sob controle!",desc:`Todas as ${g} categoria(s) dentro do limite.`,url:"financas",metric:`${g}/${g}`,metricLabel:"ok"})}const p=a.metas_ativas??0,C=a.metas_concluidas??0;if(C>0?t.push({type:"goals",priority:"positive",icon:"trophy",title:`${C} meta(s) alcançada(s)!`,desc:p>0?`Continue! ${p} ainda em progresso.`:"Parabéns pelo progresso!",url:"financas#metas",metric:String(C),metricLabel:"concluída(s)"}):p>0&&t.push({type:"goals",priority:"low",icon:"target",title:`${p} meta(s) em progresso`,desc:"Cada passo conta. Mantenha o foco!",url:"financas#metas",metric:String(p),metricLabel:"ativa(s)"}),o.ativas>0){const y=(o.total_mensal||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"installments",priority:"info",icon:"layers",title:`${o.ativas} parcelamento(s) ativo(s)`,desc:`${y}/mês comprometidos com parcelas.`,url:"lancamentos",metric:y,metricLabel:"/mês"})}const v=s.saldo_projetado??0,N=s.saldo_atual??0;if(N>0&&v<0?t.push({type:"projection",priority:"critical",icon:"trending-down",title:"Atenção: saldo projetado negativo",desc:"Até o fim do mês, seu saldo pode ficar negativo. Reduza gastos.",url:null,metric:v.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}):v>N&&N>0&&t.push({type:"projection",priority:"positive",icon:"trending-up",title:"Projeção positiva!",desc:"Você deve fechar o mês com saldo maior.",url:null,metric:v.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}),l.length>=3){const y=l.slice(-3),_=y.every($=>$.resultado>0),M=y.every($=>$.resultado<0);_?t.push({type:"trend",priority:"positive",icon:"flame",title:"Sequência de 3 meses positivos!",desc:"Ótima consistência. Mantenha o ritmo!",url:"relatorios",metric:"3",metricLabel:"meses"}):M&&t.push({type:"trend",priority:"high",icon:"alert-triangle",title:"3 meses no vermelho",desc:"É hora de repensar seus gastos.",url:"relatorios",metric:"3",metricLabel:"meses"})}const D=new Set,x=t.filter(y=>D.has(y.type)?!1:(D.add(y.type),!0)),A={critical:0,high:1,medium:2,low:3,positive:4,info:5};return x.sort((y,_)=>(A[y.priority]??9)-(A[_.priority]??9)),x.slice(0,5)}normalizeInsight(e){const a={negative_balance:{title:"Saldo no vermelho",icon:"alert-triangle",url:"lancamentos?tipo=despesa"},overspending:{title:"Gastos acima da receita",icon:"trending-down",url:"lancamentos?tipo=despesa"},low_savings:{title:"Economia muito baixa",icon:"piggy-bank",url:"relatorios"},moderate_savings:{title:"Aumente sua economia",icon:"piggy-bank",url:"relatorios"},low_activity:{title:"Registre suas movimentações",icon:"edit-3",url:"lancamentos"},low_categories:{title:"Organize por categorias",icon:"layers",url:"categorias"},no_goals:{title:"Crie sua primeira meta",icon:"target",url:"financas#metas"},no_budgets:{title:"Defina limites de gastos",icon:"shield",url:"financas"}}[e.type]||{title:"Dica do mês",icon:"lightbulb",url:"dashboard"};return{type:e.type||"generic",priority:e.priority||"medium",title:e.title||a.title,message:e.message||"",icon:a.icon,url:a.url}}renderTips(e){const t=document.getElementById("aiTipList");if(!t)return;if(e.length===0){this.renderEmpty();return}const a=document.getElementById("aiTipBadge"),i=e.some(n=>n.priority==="critical"||n.priority==="high");if(a)if(i){const n=e.filter(o=>o.priority==="critical"||o.priority==="high").length;a.textContent=n===1?"1 em foco":`${n} em foco`,a.style.display="",a.style.background="color-mix(in srgb, var(--color-text-muted) 9%, transparent)",a.style.color="var(--color-text-muted)",a.style.borderColor="color-mix(in srgb, var(--color-text-muted) 16%, transparent)"}else a.style.display="none";const s=e.map((n,o)=>{const l=this.getIconClass(n.priority),d=n.url?"a":"div",m=n.url?` href="${this.baseURL}${n.url}"`:"",c=`ai-tip-accent--${n.priority||"info"}`,u=n.metric?`<div class="ai-tip-metric">
            <span class="ai-tip-metric-value">${n.metric}</span>
            ${n.metricLabel?`<span class="ai-tip-metric-label">${n.metricLabel}</span>`:""}
          </div>`:"";return`
        <${d}${m} class="ai-tip-item surface-card" data-priority="${n.priority}" style="animation-delay: ${o*70}ms;">
          <div class="ai-tip-accent ${c}"></div>
          <div class="ai-tip-content">
            <div class="ai-tip-item-icon ${l}">
              <i data-lucide="${n.icon}" style="width:16px;height:16px;"></i>
            </div>
            <div class="ai-tip-item-body">
              <span class="ai-tip-item-title">${n.title}</span>
              ${n.desc?`<span class="ai-tip-item-desc">${n.desc}</span>`:""}
            </div>
            ${n.url?'<i data-lucide="chevron-right" style="width:14px;height:14px;" class="ai-tip-item-arrow"></i>':""}
          </div>
          ${u}
        </${d}>
      `}).join("");t.innerHTML=s,this.updateIcons()}renderEmpty(){const e=document.getElementById("aiTipList");if(!e)return;e.innerHTML=`
      <div class="ai-tip-empty">
        <i data-lucide="check-circle" class="ai-tip-empty-icon"></i>
        <p>Tudo certo por aqui! Suas finanças estão no caminho certo.</p>
      </div>
    `;const t=document.getElementById("aiTipBadge");t&&(t.style.display="none"),this.updateIcons()}getIconClass(e){return{critical:"ai-tip-item-icon--critical",high:"ai-tip-item-icon--high",medium:"ai-tip-item-icon--medium",low:"ai-tip-item-icon--low",positive:"ai-tip-item-icon--positive"}[e]||"ai-tip-item-icon--info"}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.AiTipCard=Xe;class Je{constructor(e="financeOverviewContainer"){this.container=document.getElementById(e),this.baseURL=te()}render(){this.container&&(this.container.innerHTML=`
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
    `)}async load(){try{const{mes:e,ano:t}=this.getSelectedPeriod(),a=await le(Ee(),{mes:e,ano:t});a.success&&a.data?(this.renderAlerts(a.data),this.renderMetas(a.data.metas),this.renderOrcamento(a.data.orcamento)):(this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty())}catch(e){console.error("Error loading finance overview:",e),this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty()}this._listening||(this._listening=!0,document.addEventListener("lukrato:data-changed",()=>this.load()),document.addEventListener("lukrato:month-changed",()=>this.load()))}renderAlerts(e=null){const t=document.getElementById("dashboardAlertsBudget");if(!t)return;const a=Array.isArray(e?.orcamento?.orcamentos)?e.orcamento.orcamentos.slice():[],i=a.filter(o=>o.status==="estourado").sort((o,l)=>Number(l.excedido||0)-Number(o.excedido||0)),s=a.filter(o=>o.status==="alerta").sort((o,l)=>Number(l.percentual||0)-Number(o.percentual||0)),n=[];if(i.slice(0,2).forEach(o=>{n.push({variant:"danger",title:`Você já passou do limite em ${o.categoria_nome}`,message:`Excedido em ${this.money(o.excedido||0)}.`})}),n.length<2&&s.slice(0,2-n.length).forEach(o=>{n.push({variant:"warning",title:`${o.categoria_nome} já consumiu ${Math.round(o.percentual||0)}% do limite`,message:`Restam ${this.money(o.disponivel||0)} nessa categoria.`})}),n.length===0){t.innerHTML="",this.toggleAlertsSection();return}t.innerHTML=n.map(o=>`
      <a href="${this.baseURL}financas#orcamentos" class="dashboard-alert dashboard-alert--${o.variant}">
        <div class="dashboard-alert-icon">
          <i data-lucide="${o.variant==="danger"?"triangle-alert":"circle-alert"}" style="width:18px;height:18px;"></i>
        </div>
        <div class="dashboard-alert-content">
          <strong>${o.title}</strong>
          <span>${o.message}</span>
        </div>
        <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
      </a>
    `).join(""),this.toggleAlertsSection(),this.refreshIcons()}renderOrcamento(e){const t=document.getElementById("foOrcamento");if(!t)return;if(!e||e.total_categorias===0){this.renderOrcamentoEmpty();return}const a=Math.round(e.percentual_geral||0),i=this.getBarColor(a),n=(e.orcamentos||[]).slice().sort((l,d)=>Number(d.percentual||0)-Number(l.percentual||0)).slice(0,3).map(l=>{const d=Math.min(Number(l.percentual||0),100),m=this.getBarColor(l.percentual);return`
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">${l.categoria_nome}</span>
            <span class="fo-orc-item-pct" style="color:${m};">${Math.round(l.percentual||0)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${d}%; background:${m};"></div>
          </div>
        </div>
      `}).join("");let o="No controle";(e.estourados||0)>0?o=`${e.estourados} acima do limite`:(e.em_alerta||0)>0&&(o=`${e.em_alerta} em atencao`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </a>
        <span class="fo-badge" style="color:${i}; background:${i}18;">${o}</span>
      </div>

      <div class="fo-orc-summary">
        <span>${this.money(e.total_gasto||0)} usados de ${this.money(e.total_limite||0)}</span>
        <span class="fo-summary-status">Saude: ${e.saude_financeira?.label||"Boa"}</span>
      </div>

      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width:${Math.min(a,100)}%; background:${i};"></div>
      </div>

      ${n?`<div class="fo-orc-list">${n}</div>`:""}

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
    `,this.refreshIcons())}renderMetas(e){const t=document.getElementById("foMetas");if(!t)return;if(!e||e.total_metas===0){this.renderMetasEmpty();return}const a=e.proxima_concluir,i=Math.round(e.progresso_geral||0);if(!a){this.updateGoalsHeadline("você tem metas ativas, mas nenhuma esta proxima de concluir."),t.innerHTML=`
        <div class="fo-card-header">
          <a href="${this.baseURL}financas#metas" class="fo-card-title">
            <i data-lucide="target" style="width:16px;height:16px;"></i>
            Metas
          </a>
          <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
        </div>
        <div class="fo-metas-summary">
          <div class="fo-metas-stat">
            <span class="fo-metas-stat-value">${i}%</span>
            <span class="fo-metas-stat-label">progresso geral</span>
          </div>
        </div>
        <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
      `,this.refreshIcons();return}const s=a.cor||"var(--color-primary)",n=this.normalizeIconName(a.icone),o=Math.round(a.progresso||0),l=Math.max(Number(a.valor_alvo||0)-Number(a.valor_atual||0),0);this.updateGoalsHeadline(`Faltam ${this.money(l)} para alcancar sua meta.`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#metas" class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </a>
        <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
      </div>

      <div class="fo-meta-destaque">
        <div class="fo-meta-icon" style="color:${s}; background:${s}18;">
          <i data-lucide="${n}" style="width:16px;height:16px;"></i>
        </div>
        <div class="fo-meta-info">
          <span class="fo-meta-titulo">${a.titulo}</span>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${Math.min(o,100)}%; background:${s};"></div>
          </div>
          <span class="fo-meta-detail">${this.money(a.valor_atual||0)} de ${this.money(a.valor_alvo||0)}</span>
        </div>
        <span class="fo-meta-pct" style="color:${s};">${o}%</span>
      </div>

      <div class="fo-metas-summary">
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${this.money(l)}</span>
          <span class="fo-metas-stat-label">faltam para concluir</span>
        </div>
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${i}%</span>
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
    `,this.refreshIcons())}updateGoalsHeadline(e){const t=document.getElementById("foGoalsHeadline");t&&(t.textContent=e)}toggleAlertsSection(){const e=document.getElementById("dashboardAlertsSection"),t=document.getElementById("dashboardAlertsOverview"),a=document.getElementById("dashboardAlertsBudget");if(!e)return;const i=t&&t.innerHTML.trim()!=="",s=a&&a.innerHTML.trim()!=="";e.style.display=i||s?"block":"none"}getSelectedPeriod(){const e=b.getCurrentMonth?b.getCurrentMonth():new Date().toISOString().slice(0,7),t=String(e).match(/^(\d{4})-(\d{2})$/);if(t)return{ano:Number(t[1]),mes:Number(t[2])};const a=new Date;return{mes:a.getMonth()+1,ano:a.getFullYear()}}getBarColor(e){return e>=100?"#ef4444":e>=80?"#f59e0b":"#10b981"}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-bullseye":"target","fa-target":"target","fa-wallet":"wallet","fa-university":"landmark","fa-plane":"plane","fa-car":"car","fa-home":"house","fa-heart":"heart","fa-briefcase":"briefcase-business","fa-piggy-bank":"piggy-bank","fa-shield":"shield","fa-graduation-cap":"graduation-cap","fa-store":"store","fa-baby":"baby","fa-hand-holding-usd":"hand-coins"}[t]||t.replace(/^fa-/,""))||"target"}money(e){return Number(e||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}refreshIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.FinanceOverview=Je;class Ze{constructor(e="evolucaoChartsContainer"){this.container=document.getElementById(e),this._chartMensal=null,this._chartAnual=null,this._activeTab="mensal",this._currentMonth=null}init(){!this.container||this._initialized||(this._initialized=!0,this._render(),this._loadAndDraw(),document.addEventListener("lukrato:month-changed",e=>{this._currentMonth=e?.detail?.month??null,this._loadAndDraw()}),document.addEventListener("lukrato:data-changed",()=>{this._loadAndDraw()}))}_render(){this.container.innerHTML=`
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
    `,this.container.querySelectorAll(".evo-tab").forEach(e=>{e.addEventListener("click",()=>this._switchTab(e.dataset.tab))}),typeof window.lucide<"u"&&window.lucide.createIcons({attrs:{class:["lucide"]}})}async _loadAndDraw(){const e=this._currentMonth||this._detectMonth();try{const t=await le(je(),{month:e}),a=t?.data??t;if(!a?.mensal)return;this._data=a,this._drawMensal(a.mensal),this._drawAnual(a.anual),this._updateStats(a)}catch{}}_detectMonth(){const e=document.getElementById("monthSelector")||document.querySelector("[data-month]");return e?.value||e?.dataset?.month||new Date().toISOString().slice(0,7)}_theme(){const e=document.documentElement.getAttribute("data-theme")!=="light",t=getComputedStyle(document.documentElement);return{isDark:e,mode:e?"dark":"light",textMuted:t.getPropertyValue("--color-text-muted").trim()||(e?"#94a3b8":"#666"),gridColor:e?"rgba(255,255,255,0.05)":"rgba(0,0,0,0.06)",primary:t.getPropertyValue("--color-primary").trim()||"#E67E22",success:t.getPropertyValue("--color-success").trim()||"#2ecc71",danger:t.getPropertyValue("--color-danger").trim()||"#e74c3c",surface:e?"#0f172a":"#ffffff"}}_fmt(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e??0)}_chartHeight(){return window.matchMedia("(max-width: 768px)").matches?176:188}_drawMensal(e){const t=document.getElementById("evoChartMensal");if(!t||!Array.isArray(e))return;this._chartMensal&&(this._chartMensal.destroy(),this._chartMensal=null);const a=this._theme(),i=e.map(o=>o.label),s=e.map(o=>+o.receitas),n=e.map(o=>+o.despesas);this._chartMensal=new ApexCharts(t,{chart:{type:"bar",height:this._chartHeight(),toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,sparkline:{enabled:!1},animations:{enabled:!0,speed:600}},series:[{name:"Entradas",data:s},{name:"Saídas",data:n}],xaxis:{categories:i,tickAmount:7,labels:{rotate:0,style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:o=>this._fmt(o)}},colors:[a.success,a.danger],plotOptions:{bar:{borderRadius:4,columnWidth:"70%",dataLabels:{position:"top"}}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:o=>this._fmt(o)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartMensal.render()}_drawAnual(e){const t=document.getElementById("evoChartAnual");if(!t||!Array.isArray(e))return;this._chartAnual&&(this._chartAnual.destroy(),this._chartAnual=null);const a=this._theme(),i=e.map(l=>l.label),s=e.map(l=>+l.receitas),n=e.map(l=>+l.despesas),o=e.map(l=>+l.saldo);this._chartAnual=new ApexCharts(t,{chart:{type:"line",height:this._chartHeight(),toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,animations:{enabled:!0,speed:600}},series:[{name:"Entradas",type:"column",data:s},{name:"Saídas",type:"column",data:n},{name:"Saldo",type:"area",data:o}],xaxis:{categories:i,labels:{style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:l=>this._fmt(l)}},colors:[a.success,a.danger,a.primary],plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},stroke:{curve:"smooth",width:[0,0,2.5]},fill:{type:["solid","solid","gradient"],gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:.02,stops:[0,100]}},markers:{size:[0,0,4],hover:{size:6}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:l=>this._fmt(l)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartAnual.render()}_updateStats(e){const t=this._activeTab==="anual";let a=0,i=0;t&&e.anual?.length?e.anual.forEach(d=>{a+=+d.receitas,i+=+d.despesas}):e.mensal?.length&&e.mensal.forEach(d=>{a+=+d.receitas,i+=+d.despesas});const s=a-i,n=document.getElementById("evoStatReceitas"),o=document.getElementById("evoStatDespesas"),l=document.getElementById("evoStatResultado");n&&(n.textContent=this._fmt(a)),o&&(o.textContent=this._fmt(i)),l&&(l.textContent=this._fmt(s),l.className="evo-stat__value "+(s>=0?"evo-stat__value--income":"evo-stat__value--expense"))}_switchTab(e){if(this._activeTab===e)return;this._activeTab=e,this.container.querySelectorAll(".evo-tab").forEach(i=>{const s=i.dataset.tab===e;i.classList.toggle("evo-tab--active",s),i.setAttribute("aria-selected",String(s))});const t=document.getElementById("evoChartMensal"),a=document.getElementById("evoChartAnual");t&&(t.style.display=e==="mensal"?"":"none"),a&&(a.style.display=e==="anual"?"":"none"),this._data&&this._updateStats(this._data),setTimeout(()=>{e==="mensal"&&this._chartMensal&&this._chartMensal.windowResizeHandler?.(),e==="anual"&&this._chartAnual&&this._chartAnual.windowResizeHandler?.()},10)}}window.EvolucaoCharts=Ze;class Le{constructor(){this.initialized=!1,this.init()}init(){this.setupEventListeners(),this.initialized=!0}setupEventListeners(){document.addEventListener("lukrato:transaction-added",()=>{this.playAddedAnimation()}),document.addEventListener("lukrato:level-up",e=>{this.playLevelUpAnimation(e.detail?.level)}),document.addEventListener("lukrato:streak-milestone",e=>{this.playStreakAnimation(e.detail?.days)}),document.addEventListener("lukrato:goal-completed",e=>{this.playGoalAnimation(e.detail?.goalName)}),document.addEventListener("lukrato:achievement-unlocked",e=>{this.playAchievementAnimation(e.detail?.name,e.detail?.icon)})}playAddedAnimation(){window.fab&&window.fab.celebrate(),window.LK?.toast&&window.LK.toast.success("Lancamento adicionado com sucesso."),this.fireConfetti("small",.9,.9)}playLevelUpAnimation(e){this.showCelebrationToast({title:`Nivel ${e}`,subtitle:"você subiu de nivel.",icon:"star",duration:3e3}),this.fireConfetti("large",.5,.3),this.screenFlash("#f59e0b",.3,2),window.fab?.container&&(window.fab.container.style.animation="spin 0.8s ease-out",setTimeout(()=>{window.fab.container.style.animation=""},800))}playStreakAnimation(e){const a={7:{title:"Semana perfeita",subtitle:"você chegou a 7 dias seguidos."},14:{title:"Duas semanas",subtitle:"você chegou a 14 dias seguidos."},30:{title:"Mes epico",subtitle:"você chegou a 30 dias seguidos."},100:{title:"Marco historico",subtitle:"você chegou a 100 dias seguidos."}}[e]||{title:`${e} dias seguidos`,subtitle:"Sua sequencia continua forte."};this.showCelebrationModal(a.title,a.subtitle),this.fireConfetti("extreme",.5,.2)}playGoalAnimation(e){this.showCelebrationToast({title:"Meta atingida",subtitle:`você completou: ${e}`,icon:"target",duration:3500}),this.fireConfetti("large",.5,.4),this.screenFlash("#10b981",.4,1.5)}playAchievementAnimation(e,t){const a=this.normalizeIconName(t),i=document.createElement("div");i.className="achievement-popup",i.innerHTML=`
      <div class="achievement-card">
        <div class="achievement-icon">
          <i data-lucide="${a}"></i>
        </div>
        <div class="achievement-title">Conquista desbloqueada</div>
        <div class="achievement-name">${e}</div>
      </div>
    `,document.body.appendChild(i),typeof window.lucide<"u"&&window.lucide.createIcons(),setTimeout(()=>{i.classList.add("show")},10),setTimeout(()=>{i.classList.remove("show"),setTimeout(()=>i.remove(),300)},3500),this.fireConfetti("medium",.5,.6)}showCelebrationToast(e){const{title:t="Parabens",subtitle:a="você fez progresso.",icon:i="party-popper",duration:s=3e3}=e;window.LK?.toast&&window.LK.toast.success(`${t}
${a}`)}showCelebrationModal(e,t){typeof Swal>"u"||Swal.fire({title:e,text:t,icon:"success",confirmButtonText:"Continuar",confirmButtonColor:"var(--color-primary)",allowOutsideClick:!1,didOpen:()=>{this.fireConfetti("extreme",.5,.2)}})}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-trophy":"trophy","fa-award":"award","fa-medal":"medal","fa-star":"star","fa-target":"target"}[t]||t.replace(/^fa-/,""))||"trophy"}screenFlash(e="#10b981",t=.3,a=1){const i=document.createElement("div");i.style.cssText=`
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
    `,document.body.appendChild(i),setTimeout(()=>{i.style.transition=`opacity ${a/2}ms ease-out`,i.style.opacity=t},10),setTimeout(()=>{i.style.transition=`opacity ${a/2}ms ease-in`,i.style.opacity="0"},a/2),setTimeout(()=>i.remove(),a)}fireConfetti(e="medium",t=.5,a=.5){if(typeof confetti!="function")return;const i={small:{particleCount:30,spread:40},medium:{particleCount:60,spread:60},large:{particleCount:100,spread:90},extreme:{particleCount:150,spread:120}},s=i[e]||i.medium;confetti({...s,origin:{x:t,y:a},gravity:.8,decay:.95,zIndex:99999})}}window.CelebrationSystem=Le;document.addEventListener("DOMContentLoaded",()=>{window.celebrationSystem||(window.celebrationSystem=new Le)});function et(){return new Promise(r=>{let e=0;const t=setInterval(()=>{window.HealthScoreWidget&&window.DashboardGreeting&&window.HealthScoreInsights&&window.FinanceOverview&&window.EvolucaoCharts&&(clearInterval(t),r()),e++>50&&(clearInterval(t),r())},100)})}function fe(r,e){const t=document.getElementById(r);return t||e()}async function tt(){await et(),document.readyState==="loading"?document.addEventListener("DOMContentLoaded",ye):ye()}function ye(){const r=document.querySelector(".modern-dashboard");if(r){if(typeof window.DashboardGreeting<"u"&&(fe("greetingContainer",()=>{const t=document.createElement("div");return t.id="greetingContainer",r.insertBefore(t,r.firstChild),t}),new window.DashboardGreeting().render()),typeof window.HealthScoreWidget<"u"){if(document.getElementById("healthScoreContainer")){const t=new window.HealthScoreWidget;t.render(),t.load()}typeof window.HealthScoreInsights<"u"&&document.getElementById("healthScoreInsights")&&(window.healthScoreInsights=new window.HealthScoreInsights)}if(typeof window.AiTipCard<"u"&&document.getElementById("aiTipContainer")&&new window.AiTipCard().init(),typeof window.EvolucaoCharts<"u"&&document.getElementById("evolucaoChartsContainer")&&new window.EvolucaoCharts().init(),typeof window.FinanceOverview<"u"){fe("financeOverviewContainer",()=>{const t=document.createElement("div");t.id="financeOverviewContainer";const a=r.querySelector(".provisao-section");return a?a.insertAdjacentElement("afterend",t):r.appendChild(t),t});const e=new window.FinanceOverview;e.render(),e.load()}typeof window.lucide<"u"&&window.lucide.createIcons()}}tt();function ve(r){return`lk_user_${ue().userId??"anon"}_${r}`}const K={DISPLAY_NAME_DISMISSED:()=>ve("display_name_prompt_dismissed_v1"),FIRST_ACTION_TOAST:()=>ve("dashboard_first_action_toast_v1")};class at{constructor(){this.state={accountCount:0,primaryAction:"create_transaction",transactionCount:null,isDemo:!1,awaitingFirstActionFeedback:!1},this.elements={firstRunStack:document.getElementById("dashboardFirstRunStack"),displayNameCard:document.getElementById("dashboardDisplayNamePrompt"),previewNotice:document.getElementById("dashboardPreviewNotice"),previewLearnMore:document.getElementById("dashboardPreviewLearnMore"),displayNameForm:document.getElementById("dashboardDisplayNameForm"),displayNameInput:document.getElementById("dashboardDisplayNameInput"),displayNameSubmit:document.getElementById("dashboardDisplayNameSubmit"),displayNameDismiss:document.getElementById("dashboardDisplayNameDismiss"),displayNameFeedback:document.getElementById("dashboardDisplayNameFeedback"),alertsSection:document.getElementById("sectionAlertas"),quickStart:document.getElementById("dashboardQuickStart"),quickStartEyebrow:document.getElementById("dashboardQuickStartEyebrow"),quickStartTitle:document.getElementById("dashboardQuickStartTitle"),quickStartSummary:document.getElementById("dashboardQuickStartSummary"),primaryActionCta:document.getElementById("dashboardFirstTransactionCta"),openTourPrompt:document.getElementById("dashboardOpenTourPrompt"),emptyStateTitle:document.querySelector("#emptyState p"),emptyStateDescription:document.querySelector("#emptyState .dash-empty__subtext"),emptyStateCta:document.getElementById("dashboardEmptyStateCta"),fabButton:document.getElementById("fabButton")}}init(){this.bindEvents(),this.syncDisplayNamePrompt(),this.syncStackVisibility(),Se(()=>{this.syncDisplayNamePrompt()}),_e({},{silent:!0}).then(()=>{this.syncDisplayNamePrompt()})}bindEvents(){this.elements.primaryActionCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.emptyStateCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.openTourPrompt?.addEventListener("click",()=>this.startTour()),this.elements.previewLearnMore?.addEventListener("click",()=>{this.openPreviewHelp()}),this.elements.displayNameDismiss?.addEventListener("click",()=>this.dismissDisplayNamePrompt()),this.elements.displayNameForm?.addEventListener("submit",e=>this.handleDisplayNameSubmit(e)),document.addEventListener("lukrato:dashboard-overview-rendered",e=>{this.handleOverviewUpdate(e.detail||{})}),document.addEventListener("lukrato:data-changed",e=>{e.detail?.resource==="transactions"&&e.detail?.action==="create"&&(this.state.awaitingFirstActionFeedback=!0)})}handleOverviewUpdate(e){const t=Number(this.state.transactionCount??0),a=Number(e.transactionCount||0),i=this.state.transactionCount===null,s=we(e,{accountCount:Number(e.accountCount??0),actionType:e.primaryAction,ctaLabel:e.ctaLabel,ctaUrl:e.ctaUrl});this.state.accountCount=Math.max(0,Number(e.accountCount??s.action.accountCount??0)||0),this.state.primaryAction=s.action.actionType,this.state.transactionCount=a,this.state.isDemo=e.isDemo===!0;const n=this.shouldShowQuickStart();this.toggleQuickStart(n),this.syncPrimaryActionCopy(s),this.syncDisplayNamePrompt(),this.togglePrimaryActionFocus(n),!i&&t===0&&a>0?this.handleFirstActionCompleted():this.state.awaitingFirstActionFeedback&&a>0&&this.handleFirstActionCompleted()}toggleQuickStart(e){this.elements.quickStart&&(this.elements.quickStart.hidden=!e,e&&this.suppressHelpCenterOffer(),this.syncStackVisibility())}shouldShowQuickStart(){return this.state.isDemo===!0&&Number(this.state.transactionCount??0)===0}syncPrimaryActionCopy(e){if(!e)return;const t=this.buildQuickStartContent(e);this.elements.quickStartEyebrow&&(this.elements.quickStartEyebrow.textContent=t.eyebrow),this.elements.quickStartTitle&&(this.elements.quickStartTitle.textContent=t.title),this.elements.quickStartSummary&&(this.elements.quickStartSummary.textContent=t.summary),this.elements.primaryActionCta&&(this.elements.primaryActionCta.innerHTML=`<i data-lucide="plus"></i> ${this.getPrimaryCtaLabel(e)}`),this.elements.emptyStateTitle&&(this.elements.emptyStateTitle.textContent=e.emptyStateTitle),this.elements.emptyStateDescription&&(this.elements.emptyStateDescription.textContent=e.emptyStateDescription),this.elements.emptyStateCta&&(this.elements.emptyStateCta.innerHTML=`<i data-lucide="plus"></i> ${e.emptyStateButton}`),this.elements.openTourPrompt&&(this.elements.openTourPrompt.hidden=!this.hasTourAction(e)),this.elements.previewLearnMore&&(this.elements.previewLearnMore.hidden=!this.hasPreviewHelp()),typeof window.lucide<"u"&&window.lucide.createIcons()}getPrimaryCtaLabel(e){return e?.action?.actionType==="create_account"?"Criar primeira conta":e?.action?.actionType==="create_transaction"?"Registrar primeira transação":String(e?.quickStartButton||"Continuar").trim()}buildQuickStartContent(e){return e?.action?.actionType==="create_transaction"?{eyebrow:"Próxima ação",title:"Registre a primeira transação",summary:"Com a conta pronta, registre a primeira movimentação para transformar o painel inicial em acompanhamento real do período."}:{eyebrow:"Configuração inicial",title:"Cadastre sua primeira conta",summary:"Comece pela base do seu fluxo financeiro. Assim que a conta for criada, o painel passa a refletir a sua operação."}}hasTourAction(e=null){return!(!!!(window.LKHelpCenter?.startCurrentPageTutorial||window.LKHelpCenter?.showCurrentPageTips)||e&&e.shouldOfferTour===!1&&!this.state.isDemo)}hasPreviewHelp(){return!0}syncDisplayNamePrompt(){if(!this.elements.displayNameCard)return;const e=this.state.isDemo,t=!!ue().needsDisplayNamePrompt&&localStorage.getItem(K.DISPLAY_NAME_DISMISSED())!=="1";this.elements.previewNotice&&(this.elements.previewNotice.hidden=!e),this.elements.previewLearnMore&&(this.elements.previewLearnMore.hidden=!e),this.elements.displayNameForm&&(this.elements.displayNameForm.hidden=!t),this.elements.displayNameCard.hidden=!t,this.elements.displayNameCard.classList.toggle("is-name-only",t),this.syncStackVisibility()}syncStackVisibility(){if(!this.elements.firstRunStack)return;const e=this.elements.quickStart&&!this.elements.quickStart.hidden,t=this.elements.displayNameCard&&!this.elements.displayNameCard.hidden;this.elements.firstRunStack.hidden=!(e||t),document.body.classList.toggle("dashboard-demo-preview-active",this.state.isDemo===!0),document.body.classList.toggle("dashboard-first-use-active",!!e),document.body.classList.toggle("dashboard-onboarding-active",!!e),this.elements.alertsSection&&this.elements.alertsSection.classList.toggle("dashboard-alerts--suppressed",!!e)}dismissDisplayNamePrompt(){localStorage.setItem(K.DISPLAY_NAME_DISMISSED(),"1"),this.syncDisplayNamePrompt()}async handleDisplayNameSubmit(e){if(e.preventDefault(),!this.elements.displayNameInput||!this.elements.displayNameSubmit)return;const t=this.elements.displayNameInput.value.trim();if(t.length<2){this.showDisplayNameFeedback("Use pelo menos 2 caracteres.",!0);return}this.elements.displayNameSubmit.disabled=!0,this.elements.displayNameSubmit.textContent="Salvando...";try{const a=await be(Re(),{display_name:t});if(a?.success===!1)throw a;const i=a?.data||{},s=String(i.display_name||t).trim(),n=String(i.first_name||s).trim();Pe({username:s,needsDisplayNamePrompt:!1},{source:"display-name"}),localStorage.removeItem(K.DISPLAY_NAME_DISMISSED()),this.updateGlobalIdentity(s,n),this.showDisplayNameFeedback("Perfeito. Agora o Lukrato já fala com você do jeito certo."),window.setTimeout(()=>this.syncDisplayNamePrompt(),900),window.LK?.toast&&window.LK.toast.success("Nome de exibição salvo.")}catch(a){k("Erro ao salvar nome de exibição",a,"Falha ao salvar nome de exibição"),this.showDisplayNameFeedback(de(a,"Não foi possível salvar agora."),!0)}finally{this.elements.displayNameSubmit.disabled=!1,this.elements.displayNameSubmit.textContent="Salvar"}}showDisplayNameFeedback(e,t=!1){this.elements.displayNameFeedback&&(this.elements.displayNameFeedback.hidden=!1,this.elements.displayNameFeedback.textContent=e,this.elements.displayNameFeedback.classList.toggle("is-error",t))}updateGlobalIdentity(e,t){const a=t||e||"U",i=a.charAt(0).toUpperCase();document.querySelectorAll(".greeting-name strong").forEach(o=>{o.textContent=a}),document.querySelectorAll(".avatar-initials-sm, .avatar-initials-xs").forEach(o=>{o.textContent=i});const s=document.getElementById("lkSupportToggle");s&&(s.dataset.supportName=e);const n=document.getElementById("sfName");n&&(n.textContent=e),this.elements.displayNameInput&&(this.elements.displayNameInput.value=e)}async openPreviewHelp(){if(window.Swal?.fire){const e=this.elements.primaryActionCta?.textContent?.trim()||"Continuar",t=this.hasTourAction(),a=await window.Swal.fire({title:"O que é esta prévia?",html:`
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
        `,showConfirmButton:!0,confirmButtonText:e,showDenyButton:t,denyButtonText:"Ver tour",showCancelButton:!0,cancelButtonText:"Fechar",reverseButtons:!1,focusConfirm:!0,customClass:{popup:"lk-swal-popup dash-preview-modal",confirmButton:"dash-preview-modal__confirm",denyButton:"dash-preview-modal__deny",cancelButton:"dash-preview-modal__cancel"}});if(a.isConfirmed){this.openPrimaryAction();return}a.isDenied&&this.startTour();return}window.alert("Estes números são apenas de exemplo. Assim que você começar a usar, a prévia some.")}startTour(){if(window.LKHelpCenter?.startCurrentPageTutorial){this.suppressHelpCenterOffer(),window.LKHelpCenter.startCurrentPageTutorial({source:"dashboard-first-run"});return}if(window.LKHelpCenter?.showCurrentPageTips){window.LKHelpCenter.showCurrentPageTips();return}window.LK?.toast?.info("Tutorial indisponível no momento.")}suppressHelpCenterOffer(){const e=window.LKHelpCenter;if(!e?.getPageTutorialTarget)return;const t=e.getPageTutorialTarget();t&&(e.markOfferShownThisSession?.(t),e.hideOffer?.())}togglePrimaryActionFocus(e){[this.elements.fabButton,this.elements.primaryActionCta,document.getElementById("dashboardEmptyStateCta"),document.getElementById("dashboardChartEmptyCta")].forEach(a=>{a&&a.classList.toggle("dash-primary-cta-highlight",e)})}focusPrimaryAction(){this.togglePrimaryActionFocus(!0),this.shouldShowQuickStart()&&this.elements.quickStart?.scrollIntoView({behavior:"smooth",block:"center"})}handleFirstActionCompleted(){this.state.awaitingFirstActionFeedback=!1,localStorage.getItem(K.FIRST_ACTION_TOAST())!=="1"&&(window.LK?.toast&&window.LK.toast.success("Boa! Você já começou a controlar suas finanças."),localStorage.setItem(K.FIRST_ACTION_TOAST(),"1")),this.togglePrimaryActionFocus(!1)}openPrimaryAction(){Ce({primary_action:this.state.primaryAction,real_account_count:this.state.accountCount})}}document.addEventListener("DOMContentLoaded",()=>{document.querySelector(".modern-dashboard")&&(window.dashboardFirstRunExperience||(window.dashboardFirstRunExperience=new at,window.dashboardFirstRunExperience.init()))});function st({API:r,CONFIG:e,Utils:t,escapeHtml:a,logClientError:i}){const s={getContainer:(n,o)=>{const l=document.getElementById(o);if(l)return l;const d=document.getElementById(n);if(!d)return null;const m=d.querySelector(".dash-optional-body");if(m)return m.id||(m.id=o),m;const c=document.createElement("div");c.className="dash-optional-body",c.id=o;const u=d.querySelector(".dash-section-header"),f=Array.from(d.children).filter(g=>g.classList?.contains("dash-placeholder"));return u?.nextSibling?d.insertBefore(c,u.nextSibling):d.appendChild(c),f.forEach(g=>c.appendChild(g)),c},renderLoading:n=>{n&&(n.innerHTML=`
                <div class="dash-widget dash-widget--loading" aria-hidden="true">
                    <div class="dash-widget-skeleton dash-widget-skeleton--title"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--value"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--text"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--bar"></div>
                </div>
            `)},renderEmpty:(n,o,l,d)=>{n&&(n.innerHTML=`
                <div class="dash-widget-empty">
                    <p>${o}</p>
                    ${l&&d?`<a href="${l}" class="dash-widget-link">${d}</a>`:""}
                </div>
            `)},getUsageColor:n=>n>=85?"#ef4444":n>=60?"#f59e0b":"#10b981",getAccountBalance:n=>{const l=[n?.saldoAtual,n?.saldo_atual,n?.saldo,n?.saldoInicial,n?.saldo_inicial].find(d=>Number.isFinite(Number(d)));return Number(l||0)},renderMetas:async n=>{const o=s.getContainer("sectionMetas","sectionMetasBody");if(o){s.renderLoading(o);try{const d=(await r.getFinanceSummary(n))?.metas??null;if(!d||Number(d.total_metas||0)===0){s.renderEmpty(o,"Você ainda não tem metas ativas neste momento.",`${e.BASE_URL}financas#metas`,"Criar meta");return}const m=d.proxima_concluir||null,c=Math.round(Number(d.progresso_geral||0));if(!m){o.innerHTML=`
                        <div class="dash-widget">
                            <span class="dash-widget-label">Metas ativas</span>
                            <strong class="dash-widget-value">${Number(d.total_metas||0)}</strong>
                            <p class="dash-widget-caption">Você tem metas em andamento, mas nenhuma está próxima de conclusão.</p>
                            <div class="dash-widget-meta">
                                <span>Progresso geral</span>
                                <strong>${c}%</strong>
                            </div>
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(c,100)}%; background:var(--color-primary);"></span>
                            </div>
                            <a href="${e.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                        </div>
                    `;return}const u=a(String(m.titulo||"Sua meta principal")),f=Number(m.valor_atual||0),g=Number(m.valor_alvo||0),h=Math.max(g-f,0),p=Math.round(Number(m.progresso||0)),C=m.cor||"var(--color-primary)";o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Próxima meta</span>
                        <strong class="dash-widget-value">${u}</strong>
                        <p class="dash-widget-caption">Faltam ${t.money(h)} para concluir.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(p,100)}%; background:${C};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>${t.money(f)} de ${t.money(g)}</span>
                            <strong style="color:${C};">${p}%</strong>
                        </div>
                        <a href="${e.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                    </div>
                `}catch(l){i("Erro ao carregar widget de metas",l,"Falha ao carregar metas"),s.renderEmpty(o,"Não foi possível carregar suas metas agora.",`${e.BASE_URL}financas#metas`,"Tentar nas finanças")}}},renderCartoes:async()=>{const n=s.getContainer("sectionCartoes","sectionCartoesBody");if(n){s.renderLoading(n);try{const o=await r.getCardsSummary(),l=Number(o?.total_cartoes||0);if(!o||l===0){s.renderEmpty(n,"Você ainda não tem cartões ativos no dashboard.",`${e.BASE_URL}cartoes`,"Cadastrar cartão");return}const d=Number(o.limite_disponivel||0),m=Number(o.limite_total||0),c=Math.round(Number(o.percentual_uso||0)),u=s.getUsageColor(c);n.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Limite disponível</span>
                        <strong class="dash-widget-value">${t.money(d)}</strong>
                        <p class="dash-widget-caption">${l} cartão(ões) ativo(s) com ${c}% de uso consolidado.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(c,100)}%; background:${u};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>Limite total ${t.money(m)}</span>
                            <strong style="color:${u};">${c}% usado</strong>
                        </div>
                        <a href="${e.BASE_URL}cartoes" class="dash-widget-link">Criar cartões</a>
                    </div>
                `}catch(o){i("Erro ao carregar widget de cartões",o,"Falha ao carregar cartões"),s.renderEmpty(n,"Não foi possível carregar seus cartões agora.",`${e.BASE_URL}cartoes`,"Criar cartões")}}},renderContas:async n=>{const o=s.getContainer("sectionContas","sectionContasBody");if(o){s.renderLoading(o);try{const l=await r.getAccountsBalances(n),d=Array.isArray(l)?l:[];if(d.length===0){s.renderEmpty(o,"Você ainda não tem contas ativas conectadas.",`${e.BASE_URL}contas`,"Adicionar conta");return}const m=d.map(h=>({...h,__saldo:s.getAccountBalance(h)})).sort((h,p)=>p.__saldo-h.__saldo),c=m.reduce((h,p)=>h+p.__saldo,0),u=m[0]||null,f=a(String(u?.nome||u?.nome_conta||u?.instituicao||u?.banco_nome||"Conta principal")),g=u?t.money(u.__saldo):t.money(0);o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Saldo consolidado</span>
                        <strong class="dash-widget-value">${t.money(c)}</strong>
                        <p class="dash-widget-caption">${m.length} conta(s) ativa(s) no painel.</p>
                        <div class="dash-widget-list">
                            ${m.slice(0,3).map(h=>`
                                    <div class="dash-widget-list-item">
                                        <span>${a(String(h.nome||h.nome_conta||h.instituicao||h.banco_nome||"Conta"))}</span>
                                        <strong>${t.money(h.__saldo)}</strong>
                                    </div>
                                `).join("")}
                        </div>
                        <div class="dash-widget-meta">
                            <span>Maior saldo em ${f}</span>
                            <strong>${g}</strong>
                        </div>
                        <a href="${e.BASE_URL}contas" class="dash-widget-link">Criar contas +</a>
                    </div>
                `}catch(l){i("Erro ao carregar widget de contas",l,"Falha ao carregar contas"),s.renderEmpty(o,"Não foi possível carregar suas contas agora.",`${e.BASE_URL}contas`,"Criar contas +")}}},renderOrcamentos:async n=>{const o=s.getContainer("sectionOrcamentos","sectionOrcamentosBody");if(o){s.renderLoading(o);try{const d=(await r.getFinanceSummary(n))?.orcamento??null;if(!d||Number(d.total_categorias||0)===0){s.renderEmpty(o,"Você ainda não definiu limites para categorias.",`${e.BASE_URL}financas#orcamentos`,"Definir limite");return}const m=Math.round(Number(d.percentual_geral||0)),c=s.getUsageColor(m),f=(d.orcamentos||[]).slice().sort((g,h)=>Number(h.percentual||0)-Number(g.percentual||0)).slice(0,3).map(g=>{const h=s.getUsageColor(g.percentual);return`
                        <div class="dash-widget-list-item">
                            <span>${a(g.categoria_nome||"Categoria")}</span>
                            <strong style="color:${h};">${Math.round(g.percentual||0)}%</strong>
                        </div>
                    `}).join("");o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Uso geral dos limites</span>
                        <strong class="dash-widget-value" style="color:${c};">${m}%</strong>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(m,100)}%; background:${c};"></span>
                        </div>
                        <p class="dash-widget-caption">${t.money(d.total_gasto||0)} de ${t.money(d.total_limite||0)}</p>
                        ${f?`<div class="dash-widget-list">${f}</div>`:""}
                        <a href="${e.BASE_URL}financas#orcamentos" class="dash-widget-link">Ver orçamentos</a>
                    </div>
                `}catch(l){i("Erro ao carregar widget de orçamentos",l,"Falha ao carregar orçamentos"),s.renderEmpty(o,"Não foi possível carregar seus orçamentos.",`${e.BASE_URL}financas#orcamentos`,"Abrir orçamentos")}}},renderFaturas:async()=>{const n=s.getContainer("sectionFaturas","sectionFaturasBody");if(n){s.renderLoading(n);try{const o=await r.getCardsSummary(),l=Number(o?.total_cartoes||0);if(!o||l===0){s.renderEmpty(n,"Você não tem cartões com faturas abertas.",`${e.BASE_URL}faturas`,"Criar faturas +");return}const d=Number(o.fatura_aberta??o.limite_utilizado??0),m=Number(o.limite_total||0),c=m>0?Math.round(d/m*100):Number(o.percentual_uso||0),u=s.getUsageColor(c);n.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Fatura atual</span>
                        <strong class="dash-widget-value">${t.money(d)}</strong>
                        ${m>0?`
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(c,100)}%; background:${u};"></span>
                            </div>
                            <p class="dash-widget-caption">${c}% do limite utilizado</p>
                        `:`
                            <p class="dash-widget-caption">${l} cartão(ões) ativo(s)</p>
                        `}
                        <a href="${e.BASE_URL}faturas" class="dash-widget-link">Abrir faturas</a>
                    </div>
                `}catch(o){i("Erro ao carregar widget de faturas",o,"Falha ao carregar faturas"),s.renderEmpty(n,"Não foi possível carregar suas faturas.",`${e.BASE_URL}faturas`,"Ver faturas")}}},render:async n=>{await Promise.allSettled([s.renderMetas(n),s.renderCartoes(),s.renderContas(n),s.renderOrcamentos(n),s.renderFaturas()])}};return s}function ot({API:r,Utils:e,escapeHtml:t,logClientError:a}){const i=(l,{includeYear:d=!0}={})=>{try{const[m,c]=String(l||"").split("-").map(Number);return new Date(m,c-1,1).toLocaleDateString("pt-BR",{month:"long",...d?{year:"numeric"}:{}})}catch{return d?"este mês":"próximo mês"}},s=l=>{try{const[d,m]=String(l||"").split("-").map(Number),c=new Date(d,m,1);return`${c.getFullYear()}-${String(c.getMonth()+1).padStart(2,"0")}`}catch{return e.getCurrentMonth()}},n=l=>{const d=Number(l||0);return`${d>=0?"+":"-"}${e.money(Math.abs(d))}`},o={isProUser:null,checkProStatus:async()=>{try{const l=await r.getOverview(e.getCurrentMonth());o.isProUser=l?.plan?.is_pro===!0}catch{o.isProUser=!1}return o.isProUser},render:async l=>{const d=document.getElementById("sectionPrevisao");if(!d)return;await o.checkProStatus();const m=document.getElementById("provisaoProOverlay"),c=o.isProUser;d.classList.remove("is-locked"),m&&(m.style.display="none");try{const u=await r.getOverview(l);o.renderData(u.provisao||null,c)}catch(u){a("Erro ao carregar provisão",u,"Falha ao carregar previsão")}},renderData:(l,d=!0)=>{if(!l)return;const m=l.provisao||{},c=e.money,u=l.month||e.getCurrentMonth(),f=i(u),g=i(u,{includeYear:!1}),h=i(s(u),{includeYear:!1}),p=document.getElementById("provisaoTitle"),C=document.getElementById("provisaoHeadline");p&&(p.textContent=`Fechamento previsto de ${f}`),C&&(C.textContent=`Saldo atual ${c(m.saldo_atual||0)}. Com o que ainda entra e sai, ${g} fecha em ${c(m.saldo_projetado||0)}.`);const v=document.getElementById("provisaoProximosTitle"),N=document.getElementById("provisaoVerTodos");v&&(v.innerHTML=d?'<i data-lucide="clock"></i> Próximos compromissos':'<i data-lucide="credit-card"></i> Próximas Faturas'),N&&(N.href=d?ie("lancamentos"):ie("faturas"));const D=document.getElementById("provisaoPagar"),x=document.getElementById("provisaoReceber"),A=document.getElementById("provisaoProjetado"),y=document.getElementById("provisaoPrevistoMes"),_=document.getElementById("provisaoPagarCount"),M=document.getElementById("provisaoReceberCount"),$=document.getElementById("provisaoProjetadoLabel"),H=document.getElementById("provisaoPrevistoCard"),V=document.getElementById("provisaoPrevistoMesLabel"),q=Number((m.a_receber||0)-(m.a_pagar||0)),Q=x?.closest(".provisao-card");if(D&&(D.textContent=c(m.a_pagar||0)),d?(x&&(x.textContent=c(m.a_receber||0)),Q&&(Q.style.opacity="1")):(x&&(x.textContent="R$ --"),Q&&(Q.style.opacity="0.5")),A&&(A.textContent=c(m.saldo_projetado||0),A.style.color=(m.saldo_projetado||0)>=0?"":"var(--color-danger)"),_){const E=m.count_pagar||0,w=m.count_faturas||0;if(d){let B=`${E} pendente${E!==1?"s":""}`;w>0&&(B+=` • ${w} fatura${w!==1?"s":""}`),_.textContent=B}else _.textContent=`${w} fatura${w!==1?"s":""}`}d?M&&(M.textContent=`${m.count_receber||0} pendente${(m.count_receber||0)!==1?"s":""}`):M&&(M.textContent="Pro"),$&&($.textContent=`abre ${h} com ${c(m.saldo_projetado||0)}`),H&&(H.classList.remove("is-positive","is-negative","is-neutral"),d?q>0?H.classList.add("is-positive"):q<0?H.classList.add("is-negative"):H.classList.add("is-neutral"):H.classList.add("is-neutral")),y&&(d?(y.textContent=n(q),y.style.color=""):(y.textContent="R$ --",y.style.color="")),V&&(d?q>0?V.textContent=`${g} vira no azul`:q<0?V.textContent=`${g} vira no vermelho`:V.textContent="entradas e saídas empatadas":V.textContent="Pro");const Y=l.vencidos||{},ae=document.getElementById("provisaoAlertDespesas");if(ae){const E=Y.despesas||{};if(d&&(E.count||0)>0){ae.style.display="flex";const w=document.getElementById("provisaoAlertDespesasCount"),B=document.getElementById("provisaoAlertDespesasTotal");w&&(w.textContent=E.count),B&&(B.textContent=c(E.total||0))}else ae.style.display="none"}const se=document.getElementById("provisaoAlertReceitas");if(se){const E=Y.receitas||{};if(d&&(E.count||0)>0){se.style.display="flex";const w=document.getElementById("provisaoAlertReceitasCount"),B=document.getElementById("provisaoAlertReceitasTotal");w&&(w.textContent=E.count),B&&(B.textContent=c(E.total||0))}else se.style.display="none"}const oe=document.getElementById("provisaoAlertFaturas");if(oe){const E=Y.count_faturas||0;if(E>0){oe.style.display="flex";const w=document.getElementById("provisaoAlertFaturasCount"),B=document.getElementById("provisaoAlertFaturasTotal");w&&(w.textContent=E),B&&(B.textContent=c(Y.total_faturas||0))}else oe.style.display="none"}const z=document.getElementById("provisaoProximosList"),X=document.getElementById("provisaoEmpty");let J=l.proximos||[];if(d||(J=J.filter(E=>E.is_fatura===!0)),z)if(J.length===0){if(z.innerHTML="",X){const E=X.querySelector("span");E&&(E.textContent=d?"Nenhum vencimento pendente":"Nenhuma fatura pendente"),z.appendChild(X),X.style.display="flex"}}else{z.innerHTML="";const E=new Date().toISOString().slice(0,10);J.forEach(w=>{const B=(w.tipo||"").toLowerCase(),Z=w.is_fatura===!0,he=(w.data_pagamento||"").split(/[T\s]/)[0],$e=he===E,xe=o.formatDateShort(he);let F="";$e&&(F+='<span class="provisao-item-badge vence-hoje">Hoje</span>'),Z?(F+='<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>',w.cartao_ultimos_digitos&&(F+=`<span>****${w.cartao_ultimos_digitos}</span>`)):(w.eh_parcelado&&w.numero_parcelas>1&&(F+=`<span class="provisao-item-badge parcela">${w.parcela_atual}/${w.numero_parcelas}</span>`),w.recorrente&&(F+='<span class="provisao-item-badge recorrente">Recorrente</span>'),w.categoria&&(F+=`<span>${t(w.categoria)}</span>`));const pe=Z?"fatura":B,j=document.createElement("div");j.className="provisao-item"+(Z?" is-fatura":""),j.innerHTML=`
                                <div class="provisao-item-dot ${pe}"></div>
                                <div class="provisao-item-info">
                                    <div class="provisao-item-titulo">${t(w.titulo||"Sem título")}</div>
                                    <div class="provisao-item-meta">${F}</div>
                                </div>
                                <span class="provisao-item-valor ${pe}">${c(w.valor||0)}</span>
                                <span class="provisao-item-data">${xe}</span>
                            `,Z&&w.cartao_id&&(j.style.cursor="pointer",j.addEventListener("click",()=>{const Ie=(w.data_pagamento||"").split(/[T\s]/)[0],[Ae,Be]=Ie.split("-");window.location.href=ie("faturas",{cartao_id:w.cartao_id,mes:parseInt(Be,10),ano:Ae})})),z.appendChild(j)})}const ne=document.getElementById("provisaoParcelas"),U=l.parcelas||{};if(ne)if(d&&(U.ativas||0)>0){ne.style.display="flex";const E=document.getElementById("provisaoParcelasText"),w=document.getElementById("provisaoParcelasValor");E&&(E.textContent=`${U.ativas} parcelamento${U.ativas!==1?"s":""} ativo${U.ativas!==1?"s":""}`),w&&(w.textContent=`${c(U.total_mensal||0)}/mês`)}else ne.style.display="none"},formatDateShort:l=>{if(!l)return"-";try{const d=l.match(/^(\d{4})-(\d{2})-(\d{2})$/);return d?`${d[3]}/${d[2]}`:"-"}catch{return"-"}}};return o}function nt({CONFIG:r,getDashboardOverview:e,getApiPayload:t,apiGet:a,apiDelete:i,apiPost:s,getErrorMessage:n}){function o(c){window.LKDemoPreviewBanner?.hide()}const l={getOverview:async(c,u={})=>{const f=await e(c,u),g=t(f,{});return o(g?.meta),g},fetch:async c=>{const u=await a(c);if(u?.success===!1)throw new Error(n({data:u},"Erro na API"));return u?.data??u},getMetrics:async c=>(await l.getOverview(c)).metrics||{},getAccountsBalances:async c=>{const u=await l.getOverview(c);return Array.isArray(u.accounts_balances)?u.accounts_balances:[]},getTransactions:async(c,u)=>{const f=await l.getOverview(c,{limit:u});return Array.isArray(f.recent_transactions)?f.recent_transactions:[]},getChartData:async c=>{const u=await l.getOverview(c);return Array.isArray(u.chart)?u.chart:[]},getFinanceSummary:async c=>{const u=String(c||"").match(/^(\d{4})-(\d{2})$/);if(!u)return{};const f=await a(Ee(),{ano:Number(u[1]),mes:Number(u[2])});return t(f,{})},getCardsSummary:async()=>{const c=await a(He());return t(c,{})},deleteTransaction:async c=>{const u=[{request:()=>i(Fe(c))},{request:()=>s(Oe(),{id:c})}];for(const f of u)try{return await f.request()}catch(g){if(g?.status!==404)throw new Error(n(g,"Erro ao excluir"))}throw new Error("Endpoint de exclusão não encontrado.")}},d={ensureSwal:async()=>{window.Swal},toast:(c,u)=>{if(window.LK?.toast)return LK.toast[c]?.(u)||LK.toast.info(u);window.Swal?.fire({toast:!0,position:"top-end",timer:2500,timerProgressBar:!0,showConfirmButton:!1,icon:c,title:u})},loading:(c="Processando...")=>{if(window.LK?.loading)return LK.loading(c);window.Swal?.fire({title:c,didOpen:()=>window.Swal.showLoading(),allowOutsideClick:!1,showConfirmButton:!1})},close:()=>{if(window.LK?.hideLoading)return LK.hideLoading();window.Swal?.close()},confirm:async(c,u)=>window.LK?.confirm?LK.confirm({title:c,text:u,confirmText:"Sim, confirmar",danger:!0}):(await window.Swal?.fire({title:c,text:u,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, confirmar",cancelButtonText:"Cancelar",confirmButtonColor:"var(--color-danger)",cancelButtonColor:"var(--color-text-muted)"}))?.isConfirmed,error:(c,u)=>{if(window.LK?.toast)return LK.toast.error(u||c);window.Swal?.fire({icon:"error",title:c,text:u,confirmButtonColor:"var(--color-primary)"})}},m={badges:[{id:"first",icon:"target",name:"Inicio",condition:c=>c.totalTransactions>=1},{id:"week",icon:"bar-chart-3",name:"7 Dias",condition:c=>c.streak>=7},{id:"month",icon:"gem",name:"30 Dias",condition:c=>c.streak>=30},{id:"saver",icon:"coins",name:"Economia",condition:c=>c.savingsRate>=10},{id:"diverse",icon:"palette",name:"Diverso",condition:c=>c.uniqueCategories>=5},{id:"master",icon:"crown",name:"Mestre",condition:c=>c.totalTransactions>=100}],calculateStreak:c=>{if(!Array.isArray(c)||c.length===0)return 0;const u=c.map(C=>C.data_lancamento||C.data).filter(Boolean).map(C=>{const v=String(C).match(/^(\d{4})-(\d{2})-(\d{2})/);return v?`${v[1]}-${v[2]}-${v[3]}`:null}).filter(Boolean).sort().reverse();if(u.length===0)return 0;const f=[...new Set(u)],g=new Date;g.setHours(0,0,0,0);let h=0,p=new Date(g);for(const C of f){const[v,N,D]=C.split("-").map(Number),x=new Date(v,N-1,D);x.setHours(0,0,0,0);const A=Math.round((p-x)/(1e3*60*60*24));if(A===0||A===1)h++,p=new Date(x),p.setDate(p.getDate()-1);else if(A>1)break}return h},calculateLevel:c=>c<100?1:c<300?2:c<600?3:c<1e3?4:c<1500?5:c<2500?6:c<5e3?7:c<1e4?8:c<2e4?9:10,calculatePoints:c=>{let u=0;return u+=c.totalTransactions*10,u+=c.streak*50,u+=c.activeMonths*100,u+=c.uniqueCategories*20,u+=Math.floor(c.savingsRate)*30,u},calculateData:(c,u)=>{const f=c.length,g=m.calculateStreak(c),h=new Set(c.map(_=>_.categoria_id||_.categoria).filter(Boolean)).size,C=new Set(c.map(_=>{const M=_.data_lancamento||_.data;if(!M)return null;const $=String(M).match(/^(\d{4}-\d{2})/);return $?$[1]:null}).filter(Boolean)).size,v=Number(u?.receitas||0),N=Number(u?.despesas||0),D=v>0?(v-N)/v*100:0,x={totalTransactions:f,streak:g,uniqueCategories:h,activeMonths:C,savingsRate:Math.max(0,D)},A=m.calculatePoints(x),y=m.calculateLevel(A);return{...x,points:A,level:y}}};return{API:l,Notifications:d,Gamification:m}}function it({STATE:r,DOM:e,Utils:t,API:a,Notifications:i,Renderers:s,Provisao:n,OptionalWidgets:o,invalidateDashboardOverview:l,getErrorMessage:d,logClientError:m}){const c={delete:async(g,h)=>{try{if(await i.ensureSwal(),!await i.confirm("Excluir lançamento?","Esta ação não pode ser desfeita."))return;i.loading("Excluindo..."),await a.deleteTransaction(Number(g)),i.close(),i.toast("success","Lançamento excluído com sucesso!"),h&&(h.style.opacity="0",h.style.transform="translateX(-20px)",setTimeout(()=>{h.remove(),e.tableBody.children.length===0&&(e.emptyState&&(e.emptyState.style.display="block"),e.table&&(e.table.style.display="none"))},300)),document.dispatchEvent(new CustomEvent("lukrato:data-changed",{detail:{resource:"transactions",action:"delete",id:Number(g)}}))}catch(p){console.error("Erro ao excluir lançamento:",p),await i.ensureSwal(),i.error("Erro",d(p,"Falha ao excluir lançamento"))}}},u={refresh:async({force:g=!1}={})=>{if(r.isLoading)return;r.isLoading=!0;const h=t.getCurrentMonth();r.currentMonth=h,g&&l(h);try{s.updateMonthLabel(h),await Promise.allSettled([s.renderKPIs(h),s.renderTable(h),s.renderTransactionsList(h),s.renderChart(h),n.render(h),o.render(h)])}catch(p){m("Erro ao atualizar dashboard",p,"Falha ao atualizar dashboard")}finally{r.isLoading=!1}},init:async()=>{await u.refresh({force:!1})}};return{TransactionManager:c,DashboardManager:u,EventListeners:{init:()=>{if(r.eventListenersInitialized)return;r.eventListenersInitialized=!0,e.tableBody?.addEventListener("click",async h=>{const p=h.target.closest(".btn-del");if(!p)return;const C=h.target.closest("tr"),v=p.getAttribute("data-id");v&&(p.disabled=!0,await c.delete(v,C),p.disabled=!1)}),e.cardsContainer?.addEventListener("click",async h=>{const p=h.target.closest(".btn-del");if(!p)return;const C=h.target.closest(".transaction-card"),v=p.getAttribute("data-id");v&&(p.disabled=!0,await c.delete(v,C),p.disabled=!1)}),e.transactionsList?.addEventListener("click",async h=>{const p=h.target.closest(".btn-del");if(!p)return;const C=h.target.closest(".dash-tx-item"),v=p.getAttribute("data-id");v&&(p.disabled=!0,await c.delete(v,C),p.disabled=!1)}),document.addEventListener("lukrato:data-changed",()=>{l(r.currentMonth||t.getCurrentMonth()),u.refresh({force:!1})}),document.addEventListener("lukrato:month-changed",()=>{u.refresh({force:!1})}),document.addEventListener("lukrato:theme-changed",()=>{s.renderChart(r.currentMonth||t.getCurrentMonth())});const g=document.getElementById("chartToggle");g&&g.addEventListener("click",h=>{const p=h.target.closest("[data-mode]");if(!p)return;const C=p.getAttribute("data-mode");g.querySelectorAll(".dash-chart-toggle__btn").forEach(v=>v.classList.remove("is-active")),p.classList.add("is-active"),s.renderChart(r.currentMonth||t.getCurrentMonth(),C)})}}}}const{API:R,Notifications:rt}=nt({CONFIG:O,getDashboardOverview:W,getApiPayload:Me,apiGet:le,apiDelete:Te,apiPost:be,getErrorMessage:de}),L={updateMonthLabel:r=>{S.monthLabel&&(S.monthLabel.textContent=b.formatMonth(r))},toggleAlertsSection:()=>{const r=document.getElementById("dashboardAlertsSection");r&&(r.style.display="none")},setSignedState:(r,e,t)=>{const a=document.getElementById(r),i=document.getElementById(e);!a||!i||(a.classList.remove("is-positive","is-negative","income","expense"),i.classList.remove("is-positive","is-negative"),t>0?(a.classList.add("is-positive"),i.classList.add("is-positive")):t<0&&(a.classList.add("is-negative"),i.classList.add("is-negative")))},formatSignedMoney:r=>{const e=Number(r||0);return`${e>=0?"+":"-"}${b.money(Math.abs(e))}`},renderStatusChip:(r,e,t)=>{r&&(r.innerHTML=`
            <i data-lucide="${e}" class="dashboard-status-chip-icon" style="width:16px;height:16px;"></i>
            <span>${t}</span>
        `,typeof window.lucide<"u"&&window.lucide.createIcons())},renderHeroNarrative:({saldo:r,receitas:e,despesas:t,resultado:a})=>{const i=document.getElementById("dashboardHeroStatus"),s=document.getElementById("dashboardHeroMessage"),n=Number(e||0),o=Number(t||0),l=Number.isFinite(Number(a))?Number(a):n-o;if(!(!i||!s)){if(i.className="dashboard-status-chip",s.className="dashboard-hero-message",o>n){i.classList.add("dashboard-status-chip--negative"),s.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(i,"triangle-alert",`Mês no vermelho (${L.formatSignedMoney(l)})`),s.textContent=`Atenção: você gastou mais do que ganhou (${L.formatSignedMoney(l)}).`;return}if(l>0){i.classList.add("dashboard-status-chip--positive"),s.classList.add("dashboard-hero-message--positive"),L.renderStatusChip(i,r>=0?"piggy-bank":"trending-up",r>=0?`Mês positivo (${L.formatSignedMoney(l)})`:`Recuperando o mês (${L.formatSignedMoney(l)})`),s.textContent=`Você está positivo este mês (${L.formatSignedMoney(l)}).`;return}if(l===0){i.classList.add("dashboard-status-chip--neutral"),L.renderStatusChip(i,"scale","Mês zerado (R$ 0,00)"),s.textContent=`Entrou ${b.money(n)} e saiu ${b.money(o)}. Seu saldo do mês está em R$ 0,00.`;return}i.classList.add("dashboard-status-chip--negative"),s.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(i,"wallet",`Resultado do mês ${L.formatSignedMoney(l)}`),s.textContent=`Seu resultado mensal está em ${L.formatSignedMoney(l)}. Vale rever os gastos mais pesados agora.`}},renderHeroSparkline:async r=>{const e=document.getElementById("heroSparkline");if(!(!e||typeof ApexCharts>"u"))try{const t=await R.getOverview(r),a=Array.isArray(t.chart)?t.chart:[];if(a.length<2){e.innerHTML="";return}const i=a.map(l=>Number(l.resultado||0)),{isLightTheme:s}=ge(),o=(i[i.length-1]||0)>=0?"#10b981":"#ef4444";I._heroSparkInstance&&(I._heroSparkInstance.destroy(),I._heroSparkInstance=null),I._heroSparkInstance=new ApexCharts(e,{chart:{type:"area",height:48,sparkline:{enabled:!0},background:"transparent"},series:[{data:i}],stroke:{width:2,curve:"smooth",colors:[o]},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:0,stops:[0,100],colorStops:[{offset:0,color:o,opacity:.25},{offset:100,color:o,opacity:0}]}},tooltip:{enabled:!0,fixed:{enabled:!1},x:{show:!1},y:{formatter:l=>b.money(l),title:{formatter:()=>""}},theme:s?"light":"dark"},colors:[o]}),I._heroSparkInstance.render()}catch{}},renderHeroContext:({receitas:r,despesas:e})=>{const t=document.getElementById("heroContext");if(!t)return;const a=Number(r||0),i=Number(e||0);if(a<=0){t.style.display="none";return}const s=(a-i)/a*100;let n,o,l;s>=20?(n="piggy-bank",o=`Você está economizando ${Math.round(s)}% da renda — excelente!`,l="dash-hero__context--positive"):s>=1?(n="target",o=`Economia de ${Math.round(s)}% da renda — meta ideal é 20%.`,l="dash-hero__context--neutral"):(n="alert-triangle",o="Sem margem de economia este mês. Revise seus gastos.",l="dash-hero__context--negative"),t.className=`dash-hero__context ${l}`,t.innerHTML=`<i data-lucide="${n}" style="width:14px;height:14px;"></i> ${o}`,t.style.display="",typeof window.lucide<"u"&&window.lucide.createIcons()},renderOverviewAlerts:({receitas:r,despesas:e})=>{const t=document.getElementById("dashboardAlertsOverview");if(!t)return;const a=document.getElementById("dashboardAlertsSection");a&&(a.style.display="none");const i=Number(r||0),s=Number(e||0),n=i-s;s>i?(t.innerHTML=`
                <a href="${O.BASE_URL}lancamentos?tipo=despesa" class="dashboard-alert dashboard-alert--danger">
                    <div class="dashboard-alert-icon">
                        <i data-lucide="triangle-alert" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="dashboard-alert-content">
                        <strong>Atenção: você gastou mais do que ganhou</strong>
                        <span>Entrou ${b.money(i)} e saiu ${b.money(s)}. Diferença do mês: ${L.formatSignedMoney(n)}.</span>
                    </div>
                    <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
                </a>
            `,typeof window.lucide<"u"&&window.lucide.createIcons()):t.innerHTML="",L.toggleAlertsSection()},renderChartInsight:(r,e)=>{const t=document.getElementById("chartInsight");if(!t)return;if(!Array.isArray(e)||e.length===0||e.every(n=>Number(n)===0)){t.textContent="Seu historico aparece aqui conforme você usa o Lukrato mais vezes.";return}let a=0;e.forEach((n,o)=>{Number(n)<Number(e[a])&&(a=o)});const i=r[a],s=Number(e[a]||0);if(s<0){t.textContent=`Seu pior mes foi ${b.formatMonth(i)} (${b.money(s)}).`;return}t.textContent=`Seu pior mes foi ${b.formatMonth(i)} e mesmo assim fechou em ${b.money(s)}.`},renderKPIs:async r=>{try{const e=await R.getOverview(r),t=e?.metrics||{},a=Array.isArray(e?.accounts_balances)?e.accounts_balances:[],i=e?.meta||{},s={receitasValue:t.receitas||0,despesasValue:t.despesas||0,saldoMesValue:t.resultado||0};Object.entries(s).forEach(([f,g])=>{const h=document.getElementById(f);h&&(h.textContent=b.money(g))});const n=Number(t.saldoAcumulado??t.saldo??0),o=(Array.isArray(a)?a:[]).reduce((f,g)=>{const h=typeof g.saldoAtual=="number"?g.saldoAtual:g.saldoInicial||0;return f+(isFinite(h)?Number(h):0)},0),l=Array.isArray(a)&&a.length>0?o:n;S.saldoValue&&(S.saldoValue.textContent=b.money(l)),L.setSignedState("saldoValue","saldoCard",l),L.setSignedState("saldoMesValue","saldoMesCard",Number(t.resultado||0)),L.renderHeroNarrative({saldo:l,receitas:Number(t.receitas||0),despesas:Number(t.despesas||0),resultado:Number(t.resultado||0)}),L.renderHeroSparkline(r),L.renderHeroContext({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)}),L.renderOverviewAlerts({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)});const d=Number(i?.real_transaction_count??t.count??0),m=Number(i?.real_category_count??t.categories??0),c=Number(i?.real_account_count??a.length??0),u=De(i,{accountCount:c});document.dispatchEvent(new CustomEvent("lukrato:dashboard-overview-rendered",{detail:{month:r,accountCount:c,transactionCount:d,categoryCount:m,hasData:d>0,primaryAction:u.actionType,ctaLabel:u.ctaLabel,ctaUrl:u.ctaUrl,isDemo:!!i?.is_demo}})),b.removeLoadingClass()}catch(e){k("Erro ao renderizar KPIs",e,"Falha ao carregar indicadores"),["saldoValue","receitasValue","despesasValue","saldoMesValue"].forEach(t=>{const a=document.getElementById(t);a&&(a.textContent="R$ 0,00",a.classList.remove("loading"))})}},renderTable:async r=>{try{const e=await R.getTransactions(r,O.TRANSACTIONS_LIMIT);S.tableBody&&(S.tableBody.innerHTML=""),S.cardsContainer&&(S.cardsContainer.innerHTML=""),Array.isArray(e)&&e.length>0&&e.forEach(a=>{const i=String(a.tipo||"").toLowerCase(),s=b.getTipoClass(i),n=String(a.tipo||"").replace(/_/g," "),o=a.categoria_nome??(typeof a.categoria=="string"?a.categoria:a.categoria?.nome)??null,l=o?T(o):'<span class="categoria-empty">Sem categoria</span>',d=T(b.getContaLabel(a)),m=T(a.descricao||"--"),c=T(n),u=Number(a.valor)||0,f=b.dateBR(a.data),g=document.createElement("tr");if(g.setAttribute("data-id",a.id),g.innerHTML=`
              <td data-label="Data">${f}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${s}">${c}</span>
              </td>
              <td data-label="Categoria">${l}</td>
              <td data-label="Conta">${d}</td>
              <td data-label="Descrição">${m}</td>
              <td data-label="Valor" class="valor-cell ${s}">${b.money(u)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            `,S.tableBody&&S.tableBody.appendChild(g),S.cardsContainer){const h=document.createElement("div");h.className="transaction-card",h.setAttribute("data-id",a.id),h.innerHTML=`
                <div class="transaction-card-header">
                  <span class="transaction-date">${f}</span>
                  <span class="transaction-value ${s}">${b.money(u)}</span>
                </div>
                <div class="transaction-card-body">
                  <div class="transaction-info-row">
                    <span class="transaction-label">Tipo</span>
                    <span class="transaction-badge tipo-${s}">${c}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${l}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Conta</span>
                    <span class="transaction-text">${d}</span>
                  </div>
                  ${m!=="--"?`
                  <div class="transaction-info-row">
                    <span class="transaction-label">Descrição</span>
                    <span class="transaction-description">${m}</span>
                  </div>
                  `:""}
                </div>
                <div class="transaction-card-actions">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              `,S.cardsContainer.appendChild(h)}})}catch(e){k("Erro ao renderizar transações",e,"Falha ao carregar transações")}},renderTransactionsList:async r=>{if(S.transactionsList)try{const e=await R.getTransactions(r,O.TRANSACTIONS_LIMIT),t=Array.isArray(e)&&e.length>0;if(S.transactionsList.innerHTML="",S.emptyState&&(S.emptyState.style.display=t?"none":"flex"),!t)return;const a=new Date().toISOString().slice(0,10),i=new Date(Date.now()-864e5).toISOString().slice(0,10),s=new Map;e.forEach(n=>{const o=String(n.data||"").split(/[T\s]/)[0];s.has(o)||s.set(o,[]),s.get(o).push(n)});for(const[n,o]of s){let l;n===a?l="Hoje":n===i?l="Ontem":l=b.dateBR(n);const d=document.createElement("div");d.className="dash-tx-date-group",d.textContent=l,S.transactionsList.appendChild(d),o.forEach(m=>{const u=String(m.tipo||"").toLowerCase()==="receita",f=T(m.descricao||"--"),g=m.categoria_nome??(typeof m.categoria=="string"?m.categoria:m.categoria?.nome)??"Sem categoria",h=Number(m.valor)||0,p=!!m.pago,C=m.categoria_icone||(u?"arrow-down-left":"arrow-up-right"),v=document.createElement("div");v.className="dash-tx-item surface-card",v.setAttribute("data-id",m.id),v.innerHTML=`
                        <div class="dash-tx__left">
                            <div class="dash-tx__icon dash-tx__icon--${u?"income":"expense"}">
                                <i data-lucide="${T(C)}"></i>
                            </div>
                            <div class="dash-tx__info">
                                <span class="dash-tx__desc">${f}</span>
                                <span class="dash-tx__category">${T(g)}</span>
                            </div>
                        </div>
                        <div class="dash-tx__right">
                            <span class="dash-tx__amount dash-tx__amount--${u?"income":"expense"}">${u?"+":"-"}${b.money(Math.abs(h))}</span>
                            <span class="dash-tx__badge dash-tx__badge--${p?"paid":"pending"}">${p?"Pago":"Pendente"}</span>
                        </div>
                    `,S.transactionsList.appendChild(v)})}typeof window.lucide<"u"&&window.lucide.createIcons()}catch(e){k("Erro ao renderizar lista de transações",e,"Falha ao carregar transações"),S.emptyState&&(S.emptyState.style.display="flex")}},renderChart:async(r,e)=>{if(!(!S.categoryChart||typeof ApexCharts>"u")){e||(e=I._chartMode||"donut"),I._chartMode=e,S.chartLoading&&(S.chartLoading.style.display="flex");try{const t=await R.getOverview(r),a=Array.isArray(t.despesas_por_categoria)?t.despesas_por_categoria:[],{isLightTheme:i}=ge(),s=i?"light":"dark";if(I.chartInstance&&(I.chartInstance.destroy(),I.chartInstance=null),a.length===0){const o=we(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)});S.categoryChart.innerHTML=`
                    <div class="dash-chart-empty">
                        <i data-lucide="pie-chart"></i>
                        <strong>${T(o.chartEmptyTitle)}</strong>
                        <p>${T(o.chartEmptyDescription)}</p>
                        <button class="dash-btn dash-btn--ghost" type="button" id="dashboardChartEmptyCta">
                            <i data-lucide="plus"></i> ${T(o.chartEmptyButton)}
                        </button>
                    </div>
                `,document.getElementById("dashboardChartEmptyCta")?.addEventListener("click",()=>{Ce(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)})}),typeof window.lucide<"u"&&window.lucide.createIcons();return}const n=["#E67E22","#2ecc71","#e74c3c","#3498db","#9b59b6","#1abc9c","#f39c12","#e91e63","#00bcd4","#8bc34a"];if(e==="compare"){const l=b.getPreviousMonths(r,2)[0];let d=[];try{const p=await R.getOverview(l);d=Array.isArray(p.despesas_por_categoria)?p.despesas_por_categoria:[]}catch{}const c=[...new Set([...a.map(p=>p.categoria),...d.map(p=>p.categoria)])],u=Object.fromEntries(a.map(p=>[p.categoria,Math.abs(Number(p.valor)||0)])),f=Object.fromEntries(d.map(p=>[p.categoria,Math.abs(Number(p.valor)||0)])),g=c.map(p=>u[p]||0),h=c.map(p=>f[p]||0);I.chartInstance=new ApexCharts(S.categoryChart,{chart:{type:"bar",height:300,background:"transparent",fontFamily:"Inter, Arial, sans-serif",toolbar:{show:!1}},series:[{name:b.formatMonthShort(r),data:g},{name:b.formatMonthShort(l),data:h}],colors:["#E67E22","rgba(230,126,34,0.35)"],xaxis:{categories:c,labels:{style:{colors:i?"#555":"#aaa",fontSize:"11px"},rotate:-35,trim:!0,maxHeight:80}},yaxis:{labels:{formatter:p=>b.money(p),style:{colors:i?"#555":"#aaa"}}},plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},dataLabels:{enabled:!1},legend:{position:"top",fontSize:"12px",labels:{colors:i?"#555":"#ccc"}},tooltip:{theme:s,y:{formatter:p=>b.money(p)}},grid:{borderColor:i?"#e5e5e5":"rgba(255,255,255,0.06)",strokeDashArray:3},theme:{mode:s}})}else{const o=a.map(d=>d.categoria),l=a.map(d=>Math.abs(Number(d.valor)||0));I.chartInstance=new ApexCharts(S.categoryChart,{chart:{type:"donut",height:280,background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:l,labels:o,colors:n.slice(0,o.length),stroke:{width:2,colors:[i?"#fff":"#1e1e1e"]},plotOptions:{pie:{donut:{size:"60%",labels:{show:!0,value:{formatter:d=>b.money(Number(d))},total:{show:!0,label:"Total",formatter:d=>b.money(d.globals.seriesTotals.reduce((m,c)=>m+c,0))}}}}},legend:{position:"bottom",fontSize:"13px",labels:{colors:i?"#555":"#ccc"}},tooltip:{theme:s,y:{formatter:d=>b.money(d)}},dataLabels:{enabled:!1},theme:{mode:s}})}I.chartInstance.render()}catch(t){k("Erro ao renderizar gráfico",t,"Falha ao carregar gráfico")}finally{S.chartLoading&&setTimeout(()=>{S.chartLoading.style.display="none"},300)}}}},ct=st({API:R,CONFIG:O,Utils:b,escapeHtml:T,logClientError:k}),lt=ot({API:R,Utils:b,escapeHtml:T,logClientError:k}),{DashboardManager:re,EventListeners:dt}=it({STATE:I,DOM:S,Utils:b,API:R,Notifications:rt,Renderers:L,Provisao:lt,OptionalWidgets:ct,invalidateDashboardOverview:P,getErrorMessage:de,logClientError:k}),ut={toggleHealthScore:"sectionHealthScore",toggleAiTip:"sectionAiTip",toggleEvolucao:"sectionEvolucao",toggleAlertas:"sectionAlertas",toggleGrafico:"chart-section",togglePrevisao:"sectionPrevisao",toggleMetas:"sectionMetas",toggleCartoes:"sectionCartoes",toggleContas:"sectionContas",toggleOrcamentos:"sectionOrcamentos",toggleFaturas:"sectionFaturas",toggleGamificacao:"sectionGamificacao"},me={toggleHealthScore:!0,toggleAiTip:!0,toggleEvolucao:!0,toggleAlertas:!0,toggleGrafico:!0,togglePrevisao:!0,toggleMetas:!1,toggleCartoes:!1,toggleContas:!1,toggleOrcamentos:!1,toggleFaturas:!1,toggleGamificacao:!1},mt={...me,toggleHealthScore:!1,toggleAiTip:!1,toggleEvolucao:!1,togglePrevisao:!1};async function ht(){return ze("dashboard")}async function pt(r){await qe("dashboard",r)}function ce(r){return!!r&&getComputedStyle(r).display!=="none"}function G(r,{hideWhenEmpty:e=!0}={}){if(!r)return 0;const t=Array.from(r.children).filter(ce).length;return r.dataset.visibleCount=String(t),e&&(r.style.display=t>0?"":"none"),t}function ee(r,e){r&&(r.dataset.visibleCount=String(e),r.style.display=e>0?"":"none")}function gt(r=me){const e=document.querySelector(".dashboard-stage--overview"),t=document.querySelector(".dashboard-overview-top"),a=document.querySelector(".dashboard-overview-bottom"),i=document.getElementById("sectionAlertas"),s=document.getElementById("rowHealthAi"),n=document.getElementById("healthScoreInsights"),o=document.querySelector(".dashboard-stage--decision"),l=document.querySelector(".dash-duo-row--decision"),d=document.querySelector(".dash-duo-row--insights"),m=document.querySelector(".dashboard-stage--history"),c=document.getElementById("sectionEvolucao"),u=document.querySelector(".dashboard-stage--secondary"),f=document.getElementById("optionalGrid"),g=G(t,{hideWhenEmpty:!1});G(s),n&&(n.style.display=r.toggleHealthScore?"":"none");const h=[i,s,n].filter(ce).length;a&&(a.dataset.visibleCount=String(h),a.style.display=h>0?"":"none");const p=G(l),C=G(d,{hideWhenEmpty:!1}),v=G(f);f&&(f.dataset.layout=v>0&&v<5?"fluid":"default"),ee(e,(g>0?1:0)+(h>0?1:0)),ee(o,(p>0?1:0)+(C>0?1:0)),ee(m,ce(c)?1:0),ee(u,v>0?1:0)}const ft=Ve({storageKey:"lk_dashboard_prefs",sectionMap:ut,completeDefaults:me,essentialDefaults:mt,gridContainerId:"optionalGrid",gridToggleKeys:["toggleMetas","toggleCartoes","toggleContas","toggleOrcamentos","toggleFaturas"],loadPreferences:ht,savePreferences:pt,onApply:gt});function yt(){ft.init()}window.__LK_DASHBOARD_LOADER__||(window.__LK_DASHBOARD_LOADER__=!0,window.refreshDashboard=re.refresh,window.LK=window.LK||{},window.LK.refreshDashboard=re.refresh,(()=>{const e=()=>{dt.init(),re.init(),yt()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",e):e()})());
