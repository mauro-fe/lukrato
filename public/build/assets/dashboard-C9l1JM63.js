import{l as A,d as G,c as K,e as W,j as J,i as ve}from"./api-CpqPnVR7.js";import{a as be,i as we,g as ce,o as le,r as _e}from"./primary-actions-CqqZgHlT.js";import{e as $}from"./utils-Bj4jxwhy.js";const y={BASE_URL:(()=>{let e=document.querySelector('meta[name="base-url"]')?.content||"";if(!e){const t=location.pathname.match(/^(.*\/public\/)/);e=t?location.origin+t[1]:location.origin+"/"}if(e&&!/\/public\/?$/.test(e)){const t=location.pathname.match(/^(.*\/public\/)/);t&&(e=location.origin+t[1])}return e.replace(/\/?$/,"/")})(),TRANSACTIONS_LIMIT:5,CHART_MONTHS:6,ANIMATION_DELAY:300};y.API_URL=`${y.BASE_URL}api/`;const g={saldoValue:document.getElementById("saldoValue"),receitasValue:document.getElementById("receitasValue"),despesasValue:document.getElementById("despesasValue"),saldoMesValue:document.getElementById("saldoMesValue"),categoryChart:document.getElementById("categoryChart"),chartLoading:document.getElementById("chartLoading"),transactionsList:document.getElementById("transactionsList"),emptyState:document.getElementById("emptyState"),metasBody:document.getElementById("sectionMetasBody"),cartoesBody:document.getElementById("sectionCartoesBody"),contasBody:document.getElementById("sectionContasBody"),orcamentosBody:document.getElementById("sectionOrcamentosBody"),faturasBody:document.getElementById("sectionFaturasBody"),chartContainer:document.getElementById("categoryChart"),tableBody:document.getElementById("transactionsTableBody"),table:document.getElementById("transactionsTable"),cardsContainer:document.getElementById("transactionsCards"),monthLabel:document.getElementById("currentMonthText"),streakDays:document.getElementById("streakDays"),badgesGrid:document.getElementById("badgesGrid"),userLevel:document.getElementById("userLevel"),totalLancamentos:document.getElementById("totalLancamentos"),totalCategorias:document.getElementById("totalCategorias"),mesesAtivos:document.getElementById("mesesAtivos"),pontosTotal:document.getElementById("pontosTotal")},L={chartInstance:null,currentMonth:null,isLoading:!1},d={money:s=>{try{return Number(s||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}catch{return"R$ 0,00"}},dateBR:s=>{if(!s)return"-";try{const t=String(s).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);return t?`${t[3]}/${t[2]}/${t[1]}`:"-"}catch{return"-"}},formatMonth:s=>{try{const[e,t]=String(s).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})}catch{return"-"}},formatMonthShort:s=>{try{const[e,t]=String(s).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"short"})}catch{return"-"}},getCurrentMonth:()=>window.LukratoHeader?.getMonth?.()||new Date().toISOString().slice(0,7),getPreviousMonths:(s,e)=>{const t=[],[a,o]=s.split("-").map(Number);for(let n=e-1;n>=0;n--){const i=new Date(a,o-1-n,1),r=i.getFullYear(),c=String(i.getMonth()+1).padStart(2,"0");t.push(`${r}-${c}`)}return t},getCssVar:(s,e="")=>{try{return(getComputedStyle(document.documentElement).getPropertyValue(s)||"").trim()||e}catch{return e}},isLightTheme:()=>{try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getContaLabel:s=>{if(typeof s.conta=="string"&&s.conta.trim())return s.conta.trim();const e=s.conta_instituicao??s.conta_nome??s.conta?.instituicao??s.conta?.nome??null,t=s.conta_destino_instituicao??s.conta_destino_nome??s.conta_destino?.instituicao??s.conta_destino?.nome??null;return s.eh_transferencia&&(e||t)?`${e||"-"}${t||"-"}`:s.conta_label&&String(s.conta_label).trim()?String(s.conta_label).trim():e||"-"},getTipoClass:s=>{const e=String(s||"").toLowerCase();return e==="receita"?"receita":e.includes("despesa")?"despesa":e.includes("transferencia")?"transferencia":""},removeLoadingClass:()=>{setTimeout(()=>{document.querySelectorAll(".kpi-value.loading").forEach(s=>{s.classList.remove("loading")})},y.ANIMATION_DELAY)}},ne=()=>{const s=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||d.isLightTheme?.();return{isLightTheme:s,axisColor:s?d.getCssVar("--color-primary","#e67e22")||"#e67e22":"rgba(255, 255, 255, 0.6)",yTickColor:s?"#000":"#fff",xTickColor:s?d.getCssVar("--color-text-muted","#6c757d")||"#6c757d":"rgba(255, 255, 255, 0.6)",gridColor:s?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",tooltipBg:s?"rgba(255, 255, 255, 0.92)":"rgba(0, 0, 0, 0.85)",tooltipColor:s?"#0f172a":"#f8fafc",labelColor:s?"#0f172a":"#f8fafc"}},Ee=3e4;function Ce(s,e){return`dashboard:overview:${s}:${e}`}function j(s=d.getCurrentMonth(),{limit:e=y.TRANSACTIONS_LIMIT,force:t=!1}={}){return be(`${y.API_URL}dashboard/overview`,{month:s,limit:e},{cacheKey:Ce(s,e),ttlMs:Ee,force:t})}function B(s=null){const e=s?`dashboard:overview:${s}:`:"dashboard:overview:";we(e)}class Se{constructor(e="greetingContainer"){this.container=document.getElementById(e);const t=window.__LK_CONFIG?.username||"Usuario";this.userName=t.split(" ")[0],this._listeningDataChanged=!1}render(){if(!this.container)return;const e=this.getGreeting(),a=new Date().toLocaleDateString("pt-BR",{weekday:"long",day:"numeric",month:"long"});this.container.innerHTML=`
      <div class="dashboard-greeting dashboard-greeting--compact" data-aos="fade-right" data-aos-duration="500">
        <p class="greeting-date">${a}</p>
        <p class="greeting-title">${e.title}</p>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    `,this.loadInsight()}getGreeting(){const e=new Date().getHours();return e>=5&&e<12?{title:`Bom dia, ${this.userName}.`}:e>=12&&e<18?{title:`Boa tarde, ${this.userName}.`}:e>=18&&e<24?{title:`Boa noite, ${this.userName}.`}:{title:`Boa madrugada, ${this.userName}.`}}async loadInsight({force:e=!1}={}){try{const t=await j(void 0,{force:e}),a=t?.data??t;a?.greeting_insight?this.displayInsight(a.greeting_insight):this.displayFallbackInsight()}catch(t){A("Error loading greeting insight",t,"Falha ao carregar insight"),this.displayFallbackInsight()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{B(),this.loadInsight({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{B(),this.loadInsight({force:!0})}))}displayInsight(e){const t=document.getElementById("greetingInsight");if(!t)return;const{message:a,icon:o,color:n}=e;t.innerHTML=`
      <div class="insight-content">
        <div class="insight-icon" style="color: ${n||"var(--color-primary)"};">
          <i data-lucide="${o||"sparkles"}" style="width:16px;height:16px;"></i>
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
    `,typeof window.lucide<"u"&&window.lucide.createIcons())}}window.DashboardGreeting=Se;class Le{constructor(e="healthScoreContainer"){this.container=document.getElementById(e),this.healthScore=0,this.maxScore=100,this.animationDuration=1200}render(){if(!this.container)return;const e=45;this.circumference=2*Math.PI*e;const t=this.circumference;this.container.innerHTML=`
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
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await j(void 0,{force:e}),a=t?.data??t;a?.health_score&&this.updateScore(a.health_score)}catch(t){A("Error loading health score",t,"Falha ao carregar health score"),this.showError()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{B(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{B(),this.load({force:!0})}))}updateScore(e){const{score:t=0}=e;this.animateGauge(t),this.updateBreakdown(e),this.updateStatusIndicator(t)}animateGauge(e){const t=document.getElementById("gaugeCircle"),a=document.getElementById("gaugeValue");if(!t||!a)return;const o=this.circumference||2*Math.PI*45;let n=0;const i=e/(this.animationDuration/16),r=()=>{n+=i,n>=e&&(n=e);const c=o-o*n/this.maxScore;t.setAttribute("stroke-dashoffset",c),a.textContent=Math.round(n),n<e&&requestAnimationFrame(r)};r()}updateBreakdown(e){const t=document.getElementById("hsLancamentos"),a=document.getElementById("hsOrcamento"),o=document.getElementById("hsMetas");if(t){const n=e.lancamentos??0;t.textContent=`${n}`,n>=10?t.className="hs-metric-value color-success":n>=5?t.className="hs-metric-value color-warning":t.className="hs-metric-value color-muted"}if(a){const n=e.orcamentos??0,i=e.orcamentos_ok??0;n===0?(a.textContent="--",a.className="hs-metric-value color-muted"):(a.textContent=`${i}/${n}`,i===n?a.className="hs-metric-value color-success":i>=n/2?a.className="hs-metric-value color-warning":a.className="hs-metric-value color-danger")}if(o){const n=e.metas_ativas??0,i=e.metas_concluidas??0;n===0?(o.textContent="--",o.className="hs-metric-value color-muted"):i>0?(o.textContent=`${n}+${i}`,o.className="hs-metric-value color-success"):(o.textContent=`${n}`,o.className="hs-metric-value color-warning")}}updateStatusIndicator(e){const t=document.getElementById("healthIndicator"),a=document.getElementById("healthMessage");if(!t)return;let o="critical",n="CRITICA",i="Ajustes rapidos podem evitar aperto financeiro.";e>=70?(o="excellent",n="BOA",i="Você está no controle. Continue assim!"):e>=50?(o="good",n="ESTAVEL",i="Controle bom, mas há espaço para melhorar."):e>=30&&(o="warning",n="ATENCAO",i="Alguns sinais pedem cuidado neste mês."),t.className=`hs-badge hs-badge--${o}`,t.innerHTML=`
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${n}</span>
    `,a&&(a.textContent=i)}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}showError(){const e=document.getElementById("healthIndicator"),t=document.getElementById("healthMessage");e&&(e.className="hs-badge hs-badge--error",e.innerHTML=`
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `),t&&(t.textContent="Nao foi possivel carregar.")}}window.HealthScoreWidget=Le;class Ie{constructor(e="healthScoreInsights"){this.container=document.getElementById(e),this.baseURL=window.BASE_URL||"/",this.init()}init(){this.container&&(this._initialized||(this._initialized=!0,this.renderSkeleton(),this.loadInsights(),this._intervalId=setInterval(()=>this.loadInsights({force:!0}),3e5),document.addEventListener("lukrato:data-changed",()=>{B(),this.loadInsights({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{B(),this.loadInsights({force:!0})})))}renderSkeleton(){this.container.innerHTML=`
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `}async loadInsights({force:e=!1}={}){try{const t=await j(void 0,{force:e}),a=t?.data??t;a?.health_score_insights?this.renderInsights(a.health_score_insights):this.renderEmpty()}catch(t){A("Error loading health score insights",t,"Falha ao carregar insights"),this.renderEmpty()}}renderInsights(e){const t=Array.isArray(e)?e:e?.insights||[],a=Array.isArray(e)?"":e?.total_possible_improvement||"";if(t.length===0){this.renderEmpty();return}const o=t.map((n,i)=>{const r=this.normalizeInsight(n);return`
      <a href="${this.baseURL}${r.action.url}" class="hsi-card hsi-card--${r.priority}" style="animation-delay: ${i*80}ms;">
        <div class="hsi-card-icon hsi-icon--${r.priority}">
          <i data-lucide="${this.getIconForType(r.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${r.title}</span>
          <span class="hsi-card-desc">${r.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${r.impact}</span>
          <i data-lucide="chevron-right" style="width:14px;height:14px;" class="hsi-arrow"></i>
        </div>
      </a>
    `}).join("");this.container.innerHTML=`
      <div class="hsi-list">${o}</div>
      ${a?`
        <div class="hsi-summary">
          <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
          <span>Potencial: <strong>${a}</strong></span>
        </div>
      `:""}
    `,typeof window.lucide<"u"&&window.lucide.createIcons()}normalizeInsight(e){const a={negative_balance:{title:"Seu saldo ficou negativo",impact:"Aja agora",action:{url:"lancamentos?tipo=despesa"}},low_activity:{title:"Registre mais movimentações",impact:"Mais controle",action:{url:"lancamentos"}},low_categories:{title:"Use mais categorias",impact:"Mais clareza",action:{url:"categorias"}},no_goals:{title:"Defina uma meta financeira",impact:"Mais direcao",action:{url:"financas#metas"}}}[e.type]||{title:"Insight do mes",impact:"Ver detalhe",action:{url:"dashboard"}};return{priority:e.priority||"medium",type:e.type||"generic",title:e.title||a.title,message:e.message||"",impact:e.impact||a.impact,action:e.action||a.action}}renderEmpty(){this.container.innerHTML=""}getIconForType(e){return{savings_rate:"piggy-bank",consistency:"calendar-check",diversification:"layers",negative_balance:"alert-triangle",low_balance:"wallet",no_income:"alert-circle",no_goals:"target"}[e]||"lightbulb"}}window.HealthScoreInsights=Ie;class $e{constructor(e="aiTipContainer"){this.container=document.getElementById(e),this.baseURL=window.BASE_URL||"/"}init(){this.container&&(this._initialized||(this._initialized=!0,this.render(),this.load(),document.addEventListener("lukrato:data-changed",()=>{B(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{B(),this.load({force:!0})})))}render(){this.container.innerHTML=`
      <div class="ai-tip-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400" data-aos-delay="100">
        <div class="ai-tip-header">
          <i data-lucide="sparkles" class="ai-tip-header-icon"></i>
          <h2 class="ai-tip-title">Dicas do Lukrato</h2>
          <span class="ai-tip-badge" id="aiTipBadge" style="display:none;"></span>
        </div>
        <div class="ai-tip-list" id="aiTipList">
          ${'<div class="ai-tip-skeleton"></div>'.repeat(4)}
        </div>
      </div>
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await j(void 0,{force:e}),a=t?.data??t,o=this.buildTips(a);this.renderTips(o)}catch(t){A("Error loading AI tips",t,"Falha ao carregar dicas"),this.renderEmpty()}}buildTips(e){const t=[],a=e?.health_score||{},o=e?.metrics||{},n=e?.provisao?.provisao||{},i=e?.provisao?.vencidos||{},r=e?.provisao?.parcelas||{},c=e?.chart||[],l=Array.isArray(e?.health_score_insights)?e.health_score_insights:e?.health_score_insights?.insights||[],m={critical:0,high:1,medium:2,low:3};if(l.sort((p,h)=>(m[p.priority]??9)-(m[h.priority]??9)).forEach(p=>{const h=this.normalizeInsight(p);t.push({type:h.type,priority:h.priority,icon:h.icon,title:p.title||h.title,desc:p.message||h.message,url:h.url,metric:p.metric||null,metricLabel:p.metric_label||null})}),i.count>0){const p=(i.total||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"overdue",priority:"critical",icon:"clock",title:`${i.count} conta(s) em atraso`,desc:"Regularize para evitar juros e manter o score saudável.",url:"lancamentos?status=vencido",metric:p,metricLabel:"em atraso"})}const _=e?.provisao?.proximos||[];if(_.length>0){const p=_[0],h=p.data_pagamento?new Date(p.data_pagamento+"T00:00:00"):null,u=new Date;if(u.setHours(0,0,0,0),h){const E=Math.ceil((h-u)/864e5);if(E>=0&&E<=3){const U=(p.valor||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"upcoming",priority:"high",icon:"calendar",title:E===0?"Vence hoje!":`Vence em ${E} dia(s)`,desc:p.titulo||"Conta próxima do vencimento",url:"lancamentos",metric:U,metricLabel:E===0?"hoje":`${E}d`})}}}if(e?.greeting_insight){const p=e.greeting_insight;t.push({type:"greeting",priority:"positive",icon:p.icon||"trending-up",title:p.message||"Evolução do mês",desc:"",url:null,metric:null,metricLabel:null})}const C=a.savingsRate??0;(o.receitas??0)>0&&C>=20&&t.push({type:"savings",priority:"positive",icon:"piggy-bank",title:"Ótima taxa de economia!",desc:"Você está guardando acima dos 20% recomendados.",url:null,metric:C+"%",metricLabel:"guardado"});const w=a.orcamentos??0,S=a.orcamentos_ok??0;if(w>0){const p=w-S;p>0?t.push({type:"budget",priority:"high",icon:"alert-circle",title:`${p} orçamento(s) estourado(s)`,desc:"Revise seus gastos para voltar ao controle.",url:"financas",metric:`${S}/${w}`,metricLabel:"no limite"}):t.push({type:"budget",priority:"positive",icon:"check-circle",title:"Orçamentos sob controle!",desc:`Todas as ${w} categoria(s) dentro do limite.`,url:"financas",metric:`${w}/${w}`,metricLabel:"ok"})}const f=a.metas_ativas??0,M=a.metas_concluidas??0;if(M>0?t.push({type:"goals",priority:"positive",icon:"trophy",title:`${M} meta(s) alcançada(s)!`,desc:f>0?`Continue! ${f} ainda em progresso.`:"Parabéns pelo progresso!",url:"financas#metas",metric:String(M),metricLabel:"concluída(s)"}):f>0&&t.push({type:"goals",priority:"low",icon:"target",title:`${f} meta(s) em progresso`,desc:"Cada passo conta. Mantenha o foco!",url:"financas#metas",metric:String(f),metricLabel:"ativa(s)"}),r.ativas>0){const p=(r.total_mensal||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"installments",priority:"info",icon:"layers",title:`${r.ativas} parcelamento(s) ativo(s)`,desc:`${p}/mês comprometidos com parcelas.`,url:"lancamentos",metric:p,metricLabel:"/mês"})}const T=n.saldo_projetado??0,k=n.saldo_atual??0;if(k>0&&T<0?t.push({type:"projection",priority:"critical",icon:"trending-down",title:"Atenção: saldo projetado negativo",desc:"Até o fim do mês, seu saldo pode ficar negativo. Reduza gastos.",url:null,metric:T.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}):T>k&&k>0&&t.push({type:"projection",priority:"positive",icon:"trending-up",title:"Projeção positiva!",desc:"Você deve fechar o mês com saldo maior.",url:null,metric:T.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}),c.length>=3){const p=c.slice(-3),h=p.every(E=>E.resultado>0),u=p.every(E=>E.resultado<0);h?t.push({type:"trend",priority:"positive",icon:"flame",title:"Sequência de 3 meses positivos!",desc:"Ótima consistência. Mantenha o ritmo!",url:"relatorios",metric:"3",metricLabel:"meses"}):u&&t.push({type:"trend",priority:"high",icon:"alert-triangle",title:"3 meses no vermelho",desc:"É hora de repensar seus gastos.",url:"relatorios",metric:"3",metricLabel:"meses"})}const R=new Set,O=t.filter(p=>R.has(p.type)?!1:(R.add(p.type),!0)),H={critical:0,high:1,medium:2,low:3,positive:4,info:5};return O.sort((p,h)=>(H[p.priority]??9)-(H[h.priority]??9)),O.slice(0,5)}normalizeInsight(e){const a={negative_balance:{title:"Saldo no vermelho",icon:"alert-triangle",url:"lancamentos?tipo=despesa"},overspending:{title:"Gastos acima da receita",icon:"trending-down",url:"lancamentos?tipo=despesa"},low_savings:{title:"Economia muito baixa",icon:"piggy-bank",url:"relatorios"},moderate_savings:{title:"Aumente sua economia",icon:"piggy-bank",url:"relatorios"},low_activity:{title:"Registre suas movimentações",icon:"edit-3",url:"lancamentos"},low_categories:{title:"Organize por categorias",icon:"layers",url:"categorias"},no_goals:{title:"Crie sua primeira meta",icon:"target",url:"financas#metas"},no_budgets:{title:"Defina limites de gastos",icon:"shield",url:"financas"}}[e.type]||{title:"Dica do mês",icon:"lightbulb",url:"dashboard"};return{type:e.type||"generic",priority:e.priority||"medium",title:e.title||a.title,message:e.message||"",icon:a.icon,url:a.url}}renderTips(e){const t=document.getElementById("aiTipList");if(!t)return;if(e.length===0){this.renderEmpty();return}const a=document.getElementById("aiTipBadge"),o=e.some(i=>i.priority==="critical"||i.priority==="high");if(a)if(o)a.textContent=`${e.filter(i=>i.priority==="critical"||i.priority==="high").length} atenção`,a.style.display="",a.style.background="rgba(239, 68, 68, 0.12)",a.style.color="#ef4444";else{const i=e.filter(r=>r.priority==="positive").length;i>0?(a.textContent=`${i} positivo(s)`,a.style.display="",a.style.background="rgba(16, 185, 129, 0.12)",a.style.color="#10b981"):a.style.display="none"}const n=e.map((i,r)=>{const c=this.getIconClass(i.priority),l=i.url?"a":"div",m=i.url?` href="${this.baseURL}${i.url}"`:"",_=`ai-tip-accent--${i.priority||"info"}`,C=i.metric?`<div class="ai-tip-metric">
            <span class="ai-tip-metric-value">${i.metric}</span>
            ${i.metricLabel?`<span class="ai-tip-metric-label">${i.metricLabel}</span>`:""}
          </div>`:"";return`
        <${l}${m} class="ai-tip-item surface-card" data-priority="${i.priority}" style="animation-delay: ${r*70}ms;">
          <div class="ai-tip-accent ${_}"></div>
          <div class="ai-tip-content">
            <div class="ai-tip-item-icon ${c}">
              <i data-lucide="${i.icon}" style="width:16px;height:16px;"></i>
            </div>
            <div class="ai-tip-item-body">
              <span class="ai-tip-item-title">${i.title}</span>
              ${i.desc?`<span class="ai-tip-item-desc">${i.desc}</span>`:""}
            </div>
            ${i.url?'<i data-lucide="chevron-right" style="width:14px;height:14px;" class="ai-tip-item-arrow"></i>':""}
          </div>
          ${C}
        </${l}>
      `}).join("");t.innerHTML=n,this.updateIcons()}renderEmpty(){const e=document.getElementById("aiTipList");if(!e)return;e.innerHTML=`
      <div class="ai-tip-empty">
        <i data-lucide="check-circle" class="ai-tip-empty-icon"></i>
        <p>Tudo certo por aqui! Suas finanças estão no caminho certo.</p>
      </div>
    `;const t=document.getElementById("aiTipBadge");t&&(t.textContent="Tudo ok",t.style.display="",t.style.background="rgba(16, 185, 129, 0.12)",t.style.color="#10b981"),this.updateIcons()}getIconClass(e){return{critical:"ai-tip-item-icon--critical",high:"ai-tip-item-icon--high",medium:"ai-tip-item-icon--medium",low:"ai-tip-item-icon--low",positive:"ai-tip-item-icon--positive"}[e]||"ai-tip-item-icon--info"}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.AiTipCard=$e;class Ae{constructor(e="financeOverviewContainer"){this.container=document.getElementById(e),this.baseURL=window.BASE_URL||"/"}render(){this.container&&(this.container.innerHTML=`
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
    `)}async load(){try{const{mes:e,ano:t}=this.getSelectedPeriod(),a=await G(`${this.baseURL}api/financas/resumo`,{mes:e,ano:t});a.success&&a.data?(this.renderAlerts(a.data),this.renderMetas(a.data.metas),this.renderOrcamento(a.data.orcamento)):(this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty())}catch(e){console.error("Error loading finance overview:",e),this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty()}this._listening||(this._listening=!0,document.addEventListener("lukrato:data-changed",()=>this.load()),document.addEventListener("lukrato:month-changed",()=>this.load()))}renderAlerts(e=null){const t=document.getElementById("dashboardAlertsBudget");if(!t)return;const a=Array.isArray(e?.orcamento?.orcamentos)?e.orcamento.orcamentos.slice():[],o=a.filter(r=>r.status==="estourado").sort((r,c)=>Number(c.excedido||0)-Number(r.excedido||0)),n=a.filter(r=>r.status==="alerta").sort((r,c)=>Number(c.percentual||0)-Number(r.percentual||0)),i=[];if(o.slice(0,2).forEach(r=>{i.push({variant:"danger",title:`Você já passou do limite em ${r.categoria_nome}`,message:`Excedido em ${this.money(r.excedido||0)}.`})}),i.length<2&&n.slice(0,2-i.length).forEach(r=>{i.push({variant:"warning",title:`${r.categoria_nome} ja consumiu ${Math.round(r.percentual||0)}% do limite`,message:`Restam ${this.money(r.disponivel||0)} nessa categoria.`})}),i.length===0){t.innerHTML="",this.toggleAlertsSection();return}t.innerHTML=i.map(r=>`
      <a href="${this.baseURL}financas#orcamentos" class="dashboard-alert dashboard-alert--${r.variant}">
        <div class="dashboard-alert-icon">
          <i data-lucide="${r.variant==="danger"?"triangle-alert":"circle-alert"}" style="width:18px;height:18px;"></i>
        </div>
        <div class="dashboard-alert-content">
          <strong>${r.title}</strong>
          <span>${r.message}</span>
        </div>
        <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
      </a>
    `).join(""),this.toggleAlertsSection(),this.refreshIcons()}renderOrcamento(e){const t=document.getElementById("foOrcamento");if(!t)return;if(!e||e.total_categorias===0){this.renderOrcamentoEmpty();return}const a=Math.round(e.percentual_geral||0),o=this.getBarColor(a),i=(e.orcamentos||[]).slice().sort((c,l)=>Number(l.percentual||0)-Number(c.percentual||0)).slice(0,3).map(c=>{const l=Math.min(Number(c.percentual||0),100),m=this.getBarColor(c.percentual);return`
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">${c.categoria_nome}</span>
            <span class="fo-orc-item-pct" style="color:${m};">${Math.round(c.percentual||0)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${l}%; background:${m};"></div>
          </div>
        </div>
      `}).join("");let r="No controle";(e.estourados||0)>0?r=`${e.estourados} acima do limite`:(e.em_alerta||0)>0&&(r=`${e.em_alerta} em atencao`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </a>
        <span class="fo-badge" style="color:${o}; background:${o}18;">${r}</span>
      </div>

      <div class="fo-orc-summary">
        <span>${this.money(e.total_gasto||0)} usados de ${this.money(e.total_limite||0)}</span>
        <span class="fo-summary-status">Saude: ${e.saude_financeira?.label||"Boa"}</span>
      </div>

      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width:${Math.min(a,100)}%; background:${o};"></div>
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
    `,this.refreshIcons())}renderMetas(e){const t=document.getElementById("foMetas");if(!t)return;if(!e||e.total_metas===0){this.renderMetasEmpty();return}const a=e.proxima_concluir,o=Math.round(e.progresso_geral||0);if(!a){this.updateGoalsHeadline("você tem metas ativas, mas nenhuma esta proxima de concluir."),t.innerHTML=`
        <div class="fo-card-header">
          <a href="${this.baseURL}financas#metas" class="fo-card-title">
            <i data-lucide="target" style="width:16px;height:16px;"></i>
            Metas
          </a>
          <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
        </div>
        <div class="fo-metas-summary">
          <div class="fo-metas-stat">
            <span class="fo-metas-stat-value">${o}%</span>
            <span class="fo-metas-stat-label">progresso geral</span>
          </div>
        </div>
        <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
      `,this.refreshIcons();return}const n=a.cor||"var(--color-primary)",i=this.normalizeIconName(a.icone),r=Math.round(a.progresso||0),c=Math.max(Number(a.valor_alvo||0)-Number(a.valor_atual||0),0);this.updateGoalsHeadline(`Faltam ${this.money(c)} para alcancar sua meta.`),t.innerHTML=`
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
            <div class="fo-bar-fill" style="width:${Math.min(r,100)}%; background:${n};"></div>
          </div>
          <span class="fo-meta-detail">${this.money(a.valor_atual||0)} de ${this.money(a.valor_alvo||0)}</span>
        </div>
        <span class="fo-meta-pct" style="color:${n};">${r}%</span>
      </div>

      <div class="fo-metas-summary">
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${this.money(c)}</span>
          <span class="fo-metas-stat-label">faltam para concluir</span>
        </div>
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${o}%</span>
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
    `,this.refreshIcons())}updateGoalsHeadline(e){const t=document.getElementById("foGoalsHeadline");t&&(t.textContent=e)}toggleAlertsSection(){const e=document.getElementById("dashboardAlertsSection"),t=document.getElementById("dashboardAlertsOverview"),a=document.getElementById("dashboardAlertsBudget");if(!e)return;const o=t&&t.innerHTML.trim()!=="",n=a&&a.innerHTML.trim()!=="";e.style.display=o||n?"block":"none"}getSelectedPeriod(){const e=d.getCurrentMonth?d.getCurrentMonth():new Date().toISOString().slice(0,7),t=String(e).match(/^(\d{4})-(\d{2})$/);if(t)return{ano:Number(t[1]),mes:Number(t[2])};const a=new Date;return{mes:a.getMonth()+1,ano:a.getFullYear()}}getBarColor(e){return e>=100?"#ef4444":e>=80?"#f59e0b":"#10b981"}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-bullseye":"target","fa-target":"target","fa-wallet":"wallet","fa-university":"landmark","fa-plane":"plane","fa-car":"car","fa-home":"house","fa-heart":"heart","fa-briefcase":"briefcase-business","fa-piggy-bank":"piggy-bank","fa-shield":"shield","fa-graduation-cap":"graduation-cap","fa-store":"store","fa-baby":"baby","fa-hand-holding-usd":"hand-coins"}[t]||t.replace(/^fa-/,""))||"target"}money(e){return Number(e||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}refreshIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.FinanceOverview=Ae;class xe{constructor(e="evolucaoChartsContainer"){this.container=document.getElementById(e),this.baseURL=window.BASE_URL||window.__LK_CONFIG?.baseUrl||"/",this._chartMensal=null,this._chartAnual=null,this._activeTab="mensal",this._currentMonth=null}init(){!this.container||this._initialized||(this._initialized=!0,this._render(),this._loadAndDraw(),document.addEventListener("lukrato:month-changed",e=>{this._currentMonth=e?.detail?.month??null,this._loadAndDraw()}),document.addEventListener("lukrato:data-changed",()=>{this._loadAndDraw()}))}_render(){this.container.innerHTML=`
      <div class="evo-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400">
        <div class="evo-header">
          <div class="evo-title-group">
            <i data-lucide="trending-up" class="evo-title-icon"></i>
            <h2 class="evo-title">Evolução financeira</h2>
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
    `,this.container.querySelectorAll(".evo-tab").forEach(e=>{e.addEventListener("click",()=>this._switchTab(e.dataset.tab))}),typeof window.lucide<"u"&&window.lucide.createIcons({attrs:{class:["lucide"]}})}async _loadAndDraw(){const e=this._currentMonth||this._detectMonth(),t=`${this.baseURL}api/dashboard/evolucao?month=${encodeURIComponent(e)}`;try{const o=await(await fetch(t,{credentials:"same-origin"})).json(),n=o?.data??o;if(!n?.mensal)return;this._data=n,this._drawMensal(n.mensal),this._drawAnual(n.anual),this._updateStats(n)}catch{}}_detectMonth(){const e=document.getElementById("monthSelector")||document.querySelector("[data-month]");return e?.value||e?.dataset?.month||new Date().toISOString().slice(0,7)}_theme(){const e=document.documentElement.getAttribute("data-theme")!=="light",t=getComputedStyle(document.documentElement);return{isDark:e,mode:e?"dark":"light",textMuted:t.getPropertyValue("--color-text-muted").trim()||(e?"#94a3b8":"#666"),gridColor:e?"rgba(255,255,255,0.05)":"rgba(0,0,0,0.06)",primary:t.getPropertyValue("--color-primary").trim()||"#E67E22",success:t.getPropertyValue("--color-success").trim()||"#2ecc71",danger:t.getPropertyValue("--color-danger").trim()||"#e74c3c",surface:e?"#0f172a":"#ffffff"}}_fmt(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e??0)}_drawMensal(e){const t=document.getElementById("evoChartMensal");if(!t||!Array.isArray(e))return;this._chartMensal&&(this._chartMensal.destroy(),this._chartMensal=null);const a=this._theme(),o=e.map(r=>r.label),n=e.map(r=>+r.receitas),i=e.map(r=>+r.despesas);this._chartMensal=new ApexCharts(t,{chart:{type:"bar",height:220,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,sparkline:{enabled:!1},animations:{enabled:!0,speed:600}},series:[{name:"Entradas",data:n},{name:"Saídas",data:i}],xaxis:{categories:o,tickAmount:7,labels:{rotate:0,style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:r=>this._fmt(r)}},colors:[a.success,a.danger],plotOptions:{bar:{borderRadius:4,columnWidth:"70%",dataLabels:{position:"top"}}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:r=>this._fmt(r)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartMensal.render()}_drawAnual(e){const t=document.getElementById("evoChartAnual");if(!t||!Array.isArray(e))return;this._chartAnual&&(this._chartAnual.destroy(),this._chartAnual=null);const a=this._theme(),o=e.map(c=>c.label),n=e.map(c=>+c.receitas),i=e.map(c=>+c.despesas),r=e.map(c=>+c.saldo);this._chartAnual=new ApexCharts(t,{chart:{type:"line",height:220,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,animations:{enabled:!0,speed:600}},series:[{name:"Entradas",type:"column",data:n},{name:"Saídas",type:"column",data:i},{name:"Saldo",type:"area",data:r}],xaxis:{categories:o,labels:{style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:c=>this._fmt(c)}},colors:[a.success,a.danger,a.primary],plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},stroke:{curve:"smooth",width:[0,0,2.5]},fill:{type:["solid","solid","gradient"],gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:.02,stops:[0,100]}},markers:{size:[0,0,4],hover:{size:6}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:c=>this._fmt(c)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartAnual.render()}_updateStats(e){const t=this._activeTab==="anual";let a=0,o=0;t&&e.anual?.length?e.anual.forEach(l=>{a+=+l.receitas,o+=+l.despesas}):e.mensal?.length&&e.mensal.forEach(l=>{a+=+l.receitas,o+=+l.despesas});const n=a-o,i=document.getElementById("evoStatReceitas"),r=document.getElementById("evoStatDespesas"),c=document.getElementById("evoStatResultado");i&&(i.textContent=this._fmt(a)),r&&(r.textContent=this._fmt(o)),c&&(c.textContent=this._fmt(n),c.className="evo-stat__value "+(n>=0?"evo-stat__value--income":"evo-stat__value--expense"))}_switchTab(e){if(this._activeTab===e)return;this._activeTab=e,this.container.querySelectorAll(".evo-tab").forEach(o=>{const n=o.dataset.tab===e;o.classList.toggle("evo-tab--active",n),o.setAttribute("aria-selected",String(n))});const t=document.getElementById("evoChartMensal"),a=document.getElementById("evoChartAnual");t&&(t.style.display=e==="mensal"?"":"none"),a&&(a.style.display=e==="anual"?"":"none"),this._data&&this._updateStats(this._data),setTimeout(()=>{e==="mensal"&&this._chartMensal&&this._chartMensal.windowResizeHandler?.(),e==="anual"&&this._chartAnual&&this._chartAnual.windowResizeHandler?.()},10)}}window.EvolucaoCharts=xe;class de{constructor(){this.initialized=!1,this.init()}init(){this.setupEventListeners(),this.initialized=!0}setupEventListeners(){document.addEventListener("lukrato:transaction-added",()=>{this.playAddedAnimation()}),document.addEventListener("lukrato:level-up",e=>{this.playLevelUpAnimation(e.detail?.level)}),document.addEventListener("lukrato:streak-milestone",e=>{this.playStreakAnimation(e.detail?.days)}),document.addEventListener("lukrato:goal-completed",e=>{this.playGoalAnimation(e.detail?.goalName)}),document.addEventListener("lukrato:achievement-unlocked",e=>{this.playAchievementAnimation(e.detail?.name,e.detail?.icon)})}playAddedAnimation(){window.fab&&window.fab.celebrate(),window.LK?.toast&&window.LK.toast.success("Lancamento adicionado com sucesso."),this.fireConfetti("small",.9,.9)}playLevelUpAnimation(e){this.showCelebrationToast({title:`Nivel ${e}`,subtitle:"você subiu de nivel.",icon:"star",duration:3e3}),this.fireConfetti("large",.5,.3),this.screenFlash("#f59e0b",.3,2),window.fab?.container&&(window.fab.container.style.animation="spin 0.8s ease-out",setTimeout(()=>{window.fab.container.style.animation=""},800))}playStreakAnimation(e){const a={7:{title:"Semana perfeita",subtitle:"você chegou a 7 dias seguidos."},14:{title:"Duas semanas",subtitle:"você chegou a 14 dias seguidos."},30:{title:"Mes epico",subtitle:"você chegou a 30 dias seguidos."},100:{title:"Marco historico",subtitle:"você chegou a 100 dias seguidos."}}[e]||{title:`${e} dias seguidos`,subtitle:"Sua sequencia continua forte."};this.showCelebrationModal(a.title,a.subtitle),this.fireConfetti("extreme",.5,.2)}playGoalAnimation(e){this.showCelebrationToast({title:"Meta atingida",subtitle:`você completou: ${e}`,icon:"target",duration:3500}),this.fireConfetti("large",.5,.4),this.screenFlash("#10b981",.4,1.5)}playAchievementAnimation(e,t){const a=this.normalizeIconName(t),o=document.createElement("div");o.className="achievement-popup",o.innerHTML=`
      <div class="achievement-card">
        <div class="achievement-icon">
          <i data-lucide="${a}"></i>
        </div>
        <div class="achievement-title">Conquista desbloqueada</div>
        <div class="achievement-name">${e}</div>
      </div>
    `,document.body.appendChild(o),typeof window.lucide<"u"&&window.lucide.createIcons(),setTimeout(()=>{o.classList.add("show")},10),setTimeout(()=>{o.classList.remove("show"),setTimeout(()=>o.remove(),300)},3500),this.fireConfetti("medium",.5,.6)}showCelebrationToast(e){const{title:t="Parabens",subtitle:a="você fez progresso.",icon:o="party-popper",duration:n=3e3}=e;window.LK?.toast&&window.LK.toast.success(`${t}
${a}`)}showCelebrationModal(e,t){typeof Swal>"u"||Swal.fire({title:e,text:t,icon:"success",confirmButtonText:"Continuar",confirmButtonColor:"var(--color-primary)",allowOutsideClick:!1,didOpen:()=>{this.fireConfetti("extreme",.5,.2)}})}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-trophy":"trophy","fa-award":"award","fa-medal":"medal","fa-star":"star","fa-target":"target"}[t]||t.replace(/^fa-/,""))||"trophy"}screenFlash(e="#10b981",t=.3,a=1){const o=document.createElement("div");o.style.cssText=`
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
    `,document.body.appendChild(o),setTimeout(()=>{o.style.transition=`opacity ${a/2}ms ease-out`,o.style.opacity=t},10),setTimeout(()=>{o.style.transition=`opacity ${a/2}ms ease-in`,o.style.opacity="0"},a/2),setTimeout(()=>o.remove(),a)}fireConfetti(e="medium",t=.5,a=.5){if(typeof confetti!="function")return;const o={small:{particleCount:30,spread:40},medium:{particleCount:60,spread:60},large:{particleCount:100,spread:90},extreme:{particleCount:150,spread:120}},n=o[e]||o.medium;confetti({...n,origin:{x:t,y:a},gravity:.8,decay:.95,zIndex:99999})}}window.CelebrationSystem=de;document.addEventListener("DOMContentLoaded",()=>{window.celebrationSystem||(window.celebrationSystem=new de)});function Te(){return new Promise(s=>{let e=0;const t=setInterval(()=>{window.HealthScoreWidget&&window.DashboardGreeting&&window.HealthScoreInsights&&window.FinanceOverview&&window.EvolucaoCharts&&(clearInterval(t),s()),e++>50&&(clearInterval(t),s())},100)})}function ie(s,e){const t=document.getElementById(s);return t||e()}async function Be(){await Te(),document.readyState==="loading"?document.addEventListener("DOMContentLoaded",re):re()}function re(){const s=document.querySelector(".modern-dashboard");if(s){if(typeof window.DashboardGreeting<"u"&&(ie("greetingContainer",()=>{const t=document.createElement("div");return t.id="greetingContainer",s.insertBefore(t,s.firstChild),t}),new window.DashboardGreeting().render()),typeof window.HealthScoreWidget<"u"){if(document.getElementById("healthScoreContainer")){const t=new window.HealthScoreWidget;t.render(),t.load()}typeof window.HealthScoreInsights<"u"&&document.getElementById("healthScoreInsights")&&(window.healthScoreInsights=new window.HealthScoreInsights)}if(typeof window.AiTipCard<"u"&&document.getElementById("aiTipContainer")&&new window.AiTipCard().init(),typeof window.EvolucaoCharts<"u"&&document.getElementById("evolucaoChartsContainer")&&new window.EvolucaoCharts().init(),typeof window.FinanceOverview<"u"){ie("financeOverviewContainer",()=>{const t=document.createElement("div");t.id="financeOverviewContainer";const a=s.querySelector(".provisao-section");return a?a.insertAdjacentElement("afterend",t):s.appendChild(t),t});const e=new window.FinanceOverview;e.render(),e.load()}typeof window.lucide<"u"&&window.lucide.createIcons()}}Be();const Me=`lk_user_${window.__LK_CONFIG?.userId??"anon"}_`;function X(s){return Me+s}const N={DISPLAY_NAME_DISMISSED:X("display_name_prompt_dismissed_v1"),TOUR_PROMPT_DISMISSED:X("dashboard_tour_prompt_dismissed_v1"),FIRST_ACTION_TOAST:X("dashboard_first_action_toast_v1")};class ke{constructor(){this.state={accountCount:0,primaryAction:"create_transaction",transactionCount:null,promptScheduled:!1,tourPromptVisible:!1,awaitingFirstActionFeedback:!1},this.elements={displayNameCard:document.getElementById("dashboardDisplayNamePrompt"),displayNameForm:document.getElementById("dashboardDisplayNameForm"),displayNameInput:document.getElementById("dashboardDisplayNameInput"),displayNameSubmit:document.getElementById("dashboardDisplayNameSubmit"),displayNameDismiss:document.getElementById("dashboardDisplayNameDismiss"),displayNameFeedback:document.getElementById("dashboardDisplayNameFeedback"),quickStart:document.getElementById("dashboardQuickStart"),quickStartTitle:document.querySelector("#dashboardQuickStart .dash-quick-start__header h2"),quickStartDescription:document.querySelector("#dashboardQuickStart .dash-quick-start__header p"),quickStartNotes:Array.from(document.querySelectorAll("#dashboardQuickStart .dash-quick-start__notes span")),firstTransactionCta:document.getElementById("dashboardFirstTransactionCta"),openTourPrompt:document.getElementById("dashboardOpenTourPrompt"),emptyStateTitle:document.querySelector("#emptyState p"),emptyStateDescription:document.querySelector("#emptyState .dash-empty__subtext"),emptyStateCta:document.getElementById("dashboardEmptyStateCta"),fabButton:document.getElementById("fabButton")}}init(){window.LKHelpCenter?.isManagingAutoOffers?.()||this.createTourPrompt(),this.bindEvents(),this.syncDisplayNamePrompt()}bindEvents(){this.elements.firstTransactionCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.emptyStateCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.openTourPrompt?.addEventListener("click",()=>this.startTour()),this.elements.displayNameDismiss?.addEventListener("click",()=>this.dismissDisplayNamePrompt()),this.elements.displayNameForm?.addEventListener("submit",e=>this.handleDisplayNameSubmit(e)),this.tourPrompt?.querySelector('[data-tour-action="start"]')?.addEventListener("click",()=>this.startTour()),this.tourPrompt?.querySelector('[data-tour-action="dismiss"]')?.addEventListener("click",()=>{localStorage.setItem(N.TOUR_PROMPT_DISMISSED,"1"),this.hideTourPrompt(),this.focusPrimaryAction()}),document.addEventListener("lukrato:dashboard-overview-rendered",e=>{this.handleOverviewUpdate(e.detail||{})}),document.addEventListener("lukrato:data-changed",e=>{e.detail?.resource==="transactions"&&e.detail?.action==="create"&&(this.state.awaitingFirstActionFeedback=!0)})}createTourPrompt(){const e=document.createElement("div");e.className="dash-tour-offer",e.id="dashboardTourOffer",e.innerHTML=`
      <div class="dash-tour-offer__inner surface-card">
        <div class="dash-tour-offer__icon">
          <i data-lucide="sparkles"></i>
        </div>
        <div class="dash-tour-offer__copy">
          <span class="dash-tour-offer__eyebrow">Tour opcional</span>
          <strong>Quer um tour rápido de 30 segundos?</strong>
          <p>Eu te mostro só o essencial para começar sem travar sua navegação.</p>
        </div>
        <div class="dash-tour-offer__actions">
          <button type="button" class="dash-btn dash-btn--primary" data-tour-action="start">Sim</button>
          <button type="button" class="dash-btn dash-btn--ghost" data-tour-action="dismiss">Agora não</button>
        </div>
      </div>
    `,document.body.appendChild(e),this.tourPrompt=e,typeof window.lucide<"u"&&window.lucide.createIcons()}handleOverviewUpdate(e){const t=Number(this.state.transactionCount??0),a=Number(e.transactionCount||0),o=this.state.transactionCount===null,n=ce(e,{accountCount:Number(e.accountCount??0),actionType:e.primaryAction,ctaLabel:e.ctaLabel,ctaUrl:e.ctaUrl});this.state.accountCount=Number(n.action.accountCount||0),this.state.primaryAction=n.action.actionType,this.state.transactionCount=a,this.toggleQuickStart(a===0&&!e.isDemo),this.togglePrimaryActionFocus(a===0),this.syncPrimaryActionCopy(n),this.syncDisplayNamePrompt(),!this.state.promptScheduled&&this.shouldOfferTour()&&(this.state.promptScheduled=!0,window.setTimeout(()=>{this.shouldOfferTour()&&this.showTourPrompt()},1600)),!o&&t===0&&a>0?this.handleFirstActionCompleted():this.state.awaitingFirstActionFeedback&&a>0&&this.handleFirstActionCompleted()}shouldOfferTour(){return!window.LKHelpCenter?.isManagingAutoOffers?.()&&localStorage.getItem(N.TOUR_PROMPT_DISMISSED)!=="1"&&window.__LK_CONFIG?.tourCompleted!==!0&&this.state.primaryAction==="create_transaction"&&Number(this.state.transactionCount??0)===0}showTourPrompt(){!this.tourPrompt||this.state.tourPromptVisible||(this.state.tourPromptVisible=!0,this.tourPrompt.classList.add("is-visible"))}hideTourPrompt(){this.tourPrompt&&(this.state.tourPromptVisible=!1,this.tourPrompt.classList.remove("is-visible"))}toggleQuickStart(e){this.elements.quickStart&&(this.elements.quickStart.style.display=e?"":"none")}syncPrimaryActionCopy(e){e&&(this.elements.quickStartTitle&&(this.elements.quickStartTitle.textContent=e.quickStartTitle),this.elements.quickStartDescription&&(this.elements.quickStartDescription.textContent=e.quickStartDescription),this.elements.firstTransactionCta&&(this.elements.firstTransactionCta.innerHTML=`<i data-lucide="plus"></i> ${e.quickStartButton}`),this.elements.quickStartNotes.forEach((t,a)=>{if(!t)return;const o=e.quickStartNotes[a]||"",n=t.querySelector("i, svg")?.outerHTML||"";t.innerHTML=`${n} ${o}`}),this.elements.emptyStateTitle&&(this.elements.emptyStateTitle.textContent=e.emptyStateTitle),this.elements.emptyStateDescription&&(this.elements.emptyStateDescription.textContent=e.emptyStateDescription),this.elements.emptyStateCta&&(this.elements.emptyStateCta.innerHTML=`<i data-lucide="plus"></i> ${e.emptyStateButton}`),this.elements.openTourPrompt&&(this.elements.openTourPrompt.style.display=e.shouldOfferTour?"":"none"),e.shouldOfferTour||this.hideTourPrompt(),typeof window.lucide<"u"&&window.lucide.createIcons())}syncDisplayNamePrompt(){if(!this.elements.displayNameCard)return;const e=!!window.__LK_CONFIG?.needsDisplayNamePrompt&&localStorage.getItem(N.DISPLAY_NAME_DISMISSED)!=="1";this.elements.displayNameCard.style.display=e?"":"none"}dismissDisplayNamePrompt(){localStorage.setItem(N.DISPLAY_NAME_DISMISSED,"1"),this.syncDisplayNamePrompt()}async handleDisplayNameSubmit(e){if(e.preventDefault(),!this.elements.displayNameInput||!this.elements.displayNameSubmit)return;const t=this.elements.displayNameInput.value.trim();if(t.length<2){this.showDisplayNameFeedback("Use pelo menos 2 caracteres.",!0);return}this.elements.displayNameSubmit.disabled=!0,this.elements.displayNameSubmit.textContent="Salvando...";try{const a=await K(`${y.BASE_URL}api/user/display-name`,{display_name:t});if(a?.success===!1)throw a;const o=a?.data||{},n=String(o.display_name||t).trim(),i=String(o.first_name||n).trim();window.__LK_CONFIG.username=n,window.__LK_CONFIG.needsDisplayNamePrompt=!1,localStorage.removeItem(N.DISPLAY_NAME_DISMISSED),this.updateGlobalIdentity(n,i),this.showDisplayNameFeedback("Perfeito. Agora o Lukrato já fala com você do jeito certo."),window.setTimeout(()=>this.syncDisplayNamePrompt(),900),window.LK?.toast&&window.LK.toast.success("Nome de exibição salvo.")}catch(a){A("Erro ao salvar nome de exibição",a,"Falha ao salvar nome de exibição"),this.showDisplayNameFeedback(W(a,"Não foi possível salvar agora."),!0)}finally{this.elements.displayNameSubmit.disabled=!1,this.elements.displayNameSubmit.textContent="Salvar nome"}}showDisplayNameFeedback(e,t=!1){this.elements.displayNameFeedback&&(this.elements.displayNameFeedback.hidden=!1,this.elements.displayNameFeedback.textContent=e,this.elements.displayNameFeedback.classList.toggle("is-error",t))}updateGlobalIdentity(e,t){const a=t||e||"U",o=a.charAt(0).toUpperCase();document.querySelectorAll(".greeting-name strong").forEach(r=>{r.textContent=a}),document.querySelectorAll(".avatar-initials-sm, .avatar-initials-xs").forEach(r=>{r.textContent=o});const n=document.getElementById("lkSupportToggle");n&&(n.dataset.supportName=e);const i=document.getElementById("sfName");i&&(i.textContent=e),this.elements.displayNameInput&&(this.elements.displayNameInput.value=e)}startTour(){if(!window.LKHelpCenter?.startCurrentPageTutorial){window.LK?.toast?.info("Tutorial indisponível no momento.");return}localStorage.setItem(N.TOUR_PROMPT_DISMISSED,"1"),this.hideTourPrompt(),window.LKHelpCenter.startCurrentPageTutorial({source:"dashboard-first-run"})}togglePrimaryActionFocus(e){[this.elements.fabButton,this.elements.firstTransactionCta,document.getElementById("dashboardEmptyStateCta"),document.getElementById("dashboardChartEmptyCta")].forEach(a=>{a&&a.classList.toggle("dash-primary-cta-highlight",e)})}focusPrimaryAction(){this.togglePrimaryActionFocus(!0),this.state.transactionCount===0&&this.elements.quickStart?.scrollIntoView({behavior:"smooth",block:"center"})}handleFirstActionCompleted(){this.state.awaitingFirstActionFeedback=!1,localStorage.getItem(N.FIRST_ACTION_TOAST)!=="1"&&(window.LK?.toast&&window.LK.toast.success("Boa! Você já começou a controlar suas finanças."),localStorage.setItem(N.FIRST_ACTION_TOAST,"1")),this.hideTourPrompt(),this.togglePrimaryActionFocus(!1)}openPrimaryAction(){le({primary_action:this.state.primaryAction,real_account_count:this.state.accountCount})}}document.addEventListener("DOMContentLoaded",()=>{document.querySelector(".modern-dashboard")&&(window.dashboardFirstRunExperience||(window.dashboardFirstRunExperience=new ke,window.dashboardFirstRunExperience.init()))});function Ne(s){if(s?.is_demo){window.LKDemoPreviewBanner?.show(s);return}window.LKDemoPreviewBanner?.hide()}const I={getOverview:async(s,e={})=>{const t=await j(s,e),a=J(t,{});return Ne(a?.meta),a},fetch:async s=>{if(window.LK?.api){const t=await LK.api.get(s);if(!t.ok)throw new Error(t.message||"Erro na API");return t.data}const e=await G(s);if(e?.success===!1)throw new Error(W({data:e},"Erro na API"));return e?.data??e},getMetrics:async s=>(await I.getOverview(s)).metrics||{},getAccountsBalances:async s=>{const e=await I.getOverview(s);return Array.isArray(e.accounts_balances)?e.accounts_balances:[]},getTransactions:async(s,e)=>{const t=await I.getOverview(s,{limit:e});return Array.isArray(t.recent_transactions)?t.recent_transactions:[]},getChartData:async s=>{const e=await I.getOverview(s);return Array.isArray(e.chart)?e.chart:[]},getFinanceSummary:async s=>{const e=String(s||"").match(/^(\d{4})-(\d{2})$/);if(!e)return{};const t=await G(`${y.API_URL}financas/resumo`,{ano:Number(e[1]),mes:Number(e[2])});return J(t,{})},getCardsSummary:async()=>{const s=await G(`${y.API_URL}cartoes/resumo`);return J(s,{})},deleteTransaction:async s=>{if(window.LK?.api){const t=await LK.api.delete(`${y.API_URL}lancamentos/${s}`);if(t.ok)return t.data;throw new Error(t.message||"Erro ao excluir")}const e=[{request:()=>ve(`${y.API_URL}lancamentos/${s}`)},{request:()=>K(`${y.API_URL}lancamentos/${s}/delete`,{})},{request:()=>K(`${y.API_URL}lancamentos/delete`,{id:s})}];for(const t of e)try{return await t.request()}catch(a){if(a?.status!==404)throw new Error(W(a,"Erro ao excluir"))}throw new Error("Endpoint de exclusÃ£o nÃ£o encontrado.")}},F={ensureSwal:async()=>{window.Swal},toast:(s,e)=>{if(window.LK?.toast)return LK.toast[s]?.(e)||LK.toast.info(e);window.Swal?.fire({toast:!0,position:"top-end",timer:2500,timerProgressBar:!0,showConfirmButton:!1,icon:s,title:e})},loading:(s="Processando...")=>{if(window.LK?.loading)return LK.loading(s);window.Swal?.fire({title:s,didOpen:()=>window.Swal.showLoading(),allowOutsideClick:!1,showConfirmButton:!1})},close:()=>{if(window.LK?.hideLoading)return LK.hideLoading();window.Swal?.close()},confirm:async(s,e)=>window.LK?.confirm?LK.confirm({title:s,text:e,confirmText:"Sim, confirmar",danger:!0}):(await window.Swal?.fire({title:s,text:e,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, confirmar",cancelButtonText:"Cancelar",confirmButtonColor:"var(--color-danger)",cancelButtonColor:"var(--color-text-muted)"}))?.isConfirmed,error:(s,e)=>{if(window.LK?.toast)return LK.toast.error(e||s);window.Swal?.fire({icon:"error",title:s,text:e,confirmButtonColor:"var(--color-primary)"})}},b={updateMonthLabel:s=>{g.monthLabel&&(g.monthLabel.textContent=d.formatMonth(s))},toggleAlertsSection:()=>{const s=document.getElementById("dashboardAlertsSection");s&&(s.style.display="none")},setSignedState:(s,e,t)=>{const a=document.getElementById(s),o=document.getElementById(e);!a||!o||(a.classList.remove("is-positive","is-negative","income","expense"),o.classList.remove("is-positive","is-negative"),t>0?(a.classList.add("is-positive"),o.classList.add("is-positive")):t<0&&(a.classList.add("is-negative"),o.classList.add("is-negative")))},formatSignedMoney:s=>{const e=Number(s||0);return`${e>=0?"+":"-"}${d.money(Math.abs(e))}`},renderStatusChip:(s,e,t)=>{s&&(s.innerHTML=`
            <i data-lucide="${e}" class="dashboard-status-chip-icon" style="width:16px;height:16px;"></i>
            <span>${t}</span>
        `,typeof window.lucide<"u"&&window.lucide.createIcons())},renderHeroNarrative:({saldo:s,receitas:e,despesas:t,resultado:a})=>{const o=document.getElementById("dashboardHeroStatus"),n=document.getElementById("dashboardHeroMessage"),i=Number(e||0),r=Number(t||0),c=Number.isFinite(Number(a))?Number(a):i-r;if(!(!o||!n)){if(o.className="dashboard-status-chip",n.className="dashboard-hero-message",r>i){o.classList.add("dashboard-status-chip--negative"),n.classList.add("dashboard-hero-message--negative"),b.renderStatusChip(o,"triangle-alert",`Mês no vermelho (${b.formatSignedMoney(c)})`),n.textContent=`Atenção: você gastou mais do que ganhou (${b.formatSignedMoney(c)}).`;return}if(c>0){o.classList.add("dashboard-status-chip--positive"),n.classList.add("dashboard-hero-message--positive"),b.renderStatusChip(o,s>=0?"piggy-bank":"trending-up",s>=0?`Mês positivo (${b.formatSignedMoney(c)})`:`Recuperando o mês (${b.formatSignedMoney(c)})`),n.textContent=`Você está positivo este mês (${b.formatSignedMoney(c)}).`;return}if(c===0){o.classList.add("dashboard-status-chip--neutral"),b.renderStatusChip(o,"scale","Mês zerado (R$ 0,00)"),n.textContent=`Entrou ${d.money(i)} e saiu ${d.money(r)}. Seu saldo do mês está em R$ 0,00.`;return}o.classList.add("dashboard-status-chip--negative"),n.classList.add("dashboard-hero-message--negative"),b.renderStatusChip(o,"wallet",`Resultado do mês ${b.formatSignedMoney(c)}`),n.textContent=`Seu resultado mensal está em ${b.formatSignedMoney(c)}. Vale rever os gastos mais pesados agora.`}},renderHeroSparkline:async s=>{const e=document.getElementById("heroSparkline");if(!(!e||typeof ApexCharts>"u"))try{const t=await I.getOverview(s),a=Array.isArray(t.chart)?t.chart:[];if(a.length<2){e.innerHTML="";return}const o=a.map(c=>Number(c.resultado||0)),{isLightTheme:n}=ne(),r=(o[o.length-1]||0)>=0?"#10b981":"#ef4444";L._heroSparkInstance&&(L._heroSparkInstance.destroy(),L._heroSparkInstance=null),L._heroSparkInstance=new ApexCharts(e,{chart:{type:"area",height:48,sparkline:{enabled:!0},background:"transparent"},series:[{data:o}],stroke:{width:2,curve:"smooth",colors:[r]},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:0,stops:[0,100],colorStops:[{offset:0,color:r,opacity:.25},{offset:100,color:r,opacity:0}]}},tooltip:{enabled:!0,fixed:{enabled:!1},x:{show:!1},y:{formatter:c=>d.money(c),title:{formatter:()=>""}},theme:n?"light":"dark"},colors:[r]}),L._heroSparkInstance.render()}catch{}},renderHeroContext:({receitas:s,despesas:e})=>{const t=document.getElementById("heroContext");if(!t)return;const a=Number(s||0),o=Number(e||0);if(a<=0){t.style.display="none";return}const n=(a-o)/a*100;let i,r,c;n>=20?(i="piggy-bank",r=`Você está economizando ${Math.round(n)}% da renda — excelente!`,c="dash-hero__context--positive"):n>=1?(i="target",r=`Economia de ${Math.round(n)}% da renda — meta ideal é 20%.`,c="dash-hero__context--neutral"):(i="alert-triangle",r="Sem margem de economia este mês. Revise seus gastos.",c="dash-hero__context--negative"),t.className=`dash-hero__context ${c}`,t.innerHTML=`<i data-lucide="${i}" style="width:14px;height:14px;"></i> ${r}`,t.style.display="",typeof window.lucide<"u"&&window.lucide.createIcons()},renderOverviewAlerts:({receitas:s,despesas:e})=>{const t=document.getElementById("dashboardAlertsOverview");if(!t)return;const a=document.getElementById("dashboardAlertsSection");a&&(a.style.display="none");const o=Number(s||0),n=Number(e||0),i=o-n;n>o?(t.innerHTML=`
                <a href="${y.BASE_URL}lancamentos?tipo=despesa" class="dashboard-alert dashboard-alert--danger">
                    <div class="dashboard-alert-icon">
                        <i data-lucide="triangle-alert" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="dashboard-alert-content">
                        <strong>Atenção: você gastou mais do que ganhou</strong>
                        <span>Entrou ${d.money(o)} e saiu ${d.money(n)}. Diferença do mês: ${b.formatSignedMoney(i)}.</span>
                    </div>
                    <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
                </a>
            `,typeof window.lucide<"u"&&window.lucide.createIcons()):t.innerHTML="",b.toggleAlertsSection()},renderChartInsight:(s,e)=>{const t=document.getElementById("chartInsight");if(!t)return;if(!Array.isArray(e)||e.length===0||e.every(i=>Number(i)===0)){t.textContent="Seu historico aparece aqui conforme você usa o Lukrato mais vezes.";return}let a=0;e.forEach((i,r)=>{Number(i)<Number(e[a])&&(a=r)});const o=s[a],n=Number(e[a]||0);if(n<0){t.textContent=`Seu pior mes foi ${d.formatMonth(o)} (${d.money(n)}).`;return}t.textContent=`Seu pior mes foi ${d.formatMonth(o)} e mesmo assim fechou em ${d.money(n)}.`},renderKPIs:async s=>{try{const e=await I.getOverview(s),t=e?.metrics||{},a=Array.isArray(e?.accounts_balances)?e.accounts_balances:[],o=e?.meta||{},n={receitasValue:t.receitas||0,despesasValue:t.despesas||0,saldoMesValue:t.resultado||0};Object.entries(n).forEach(([x,w])=>{const S=document.getElementById(x);S&&(S.textContent=d.money(w))});const i=Number(t.saldoAcumulado??t.saldo??0),r=(Array.isArray(a)?a:[]).reduce((x,w)=>{const S=typeof w.saldoAtual=="number"?w.saldoAtual:w.saldoInicial||0;return x+(isFinite(S)?Number(S):0)},0),c=Array.isArray(a)&&a.length>0?r:i;g.saldoValue&&(g.saldoValue.textContent=d.money(c)),b.setSignedState("saldoValue","saldoCard",c),b.setSignedState("saldoMesValue","saldoMesCard",Number(t.resultado||0)),b.renderHeroNarrative({saldo:c,receitas:Number(t.receitas||0),despesas:Number(t.despesas||0),resultado:Number(t.resultado||0)}),b.renderHeroSparkline(s),b.renderHeroContext({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)}),b.renderOverviewAlerts({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)});const l=Number(o?.real_transaction_count??t.count??0),m=Number(o?.real_category_count??t.categories??0),_=Number(o?.real_account_count??a.length??0),C=_e(o,{accountCount:_});document.dispatchEvent(new CustomEvent("lukrato:dashboard-overview-rendered",{detail:{month:s,accountCount:_,transactionCount:l,categoryCount:m,hasData:l>0,primaryAction:C.actionType,ctaLabel:C.ctaLabel,ctaUrl:C.ctaUrl,isDemo:!!o?.is_demo}})),d.removeLoadingClass()}catch(e){A("Erro ao renderizar KPIs",e,"Falha ao carregar indicadores"),["saldoValue","receitasValue","despesasValue","saldoMesValue"].forEach(t=>{const a=document.getElementById(t);a&&(a.textContent="R$ 0,00",a.classList.remove("loading"))})}},renderTable:async s=>{try{const e=await I.getTransactions(s,y.TRANSACTIONS_LIMIT);g.tableBody&&(g.tableBody.innerHTML=""),g.cardsContainer&&(g.cardsContainer.innerHTML=""),Array.isArray(e)&&e.length>0&&e.forEach(a=>{const o=String(a.tipo||"").toLowerCase(),n=d.getTipoClass(o),i=String(a.tipo||"").replace(/_/g," "),r=a.categoria_nome??(typeof a.categoria=="string"?a.categoria:a.categoria?.nome)??null,c=r?$(r):'<span class="categoria-empty">Sem categoria</span>',l=$(d.getContaLabel(a)),m=$(a.descricao||"--"),_=$(i),C=Number(a.valor)||0,x=d.dateBR(a.data),w=document.createElement("tr");if(w.setAttribute("data-id",a.id),w.innerHTML=`
              <td data-label="Data">${x}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${n}">${_}</span>
              </td>
              <td data-label="Categoria">${c}</td>
              <td data-label="Conta">${l}</td>
              <td data-label="DescriÃ§Ã£o">${m}</td>
              <td data-label="Valor" class="valor-cell ${n}">${d.money(C)}</td>
              <td data-label="AÃ§Ãµes" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            `,g.tableBody&&g.tableBody.appendChild(w),g.cardsContainer){const S=document.createElement("div");S.className="transaction-card",S.setAttribute("data-id",a.id),S.innerHTML=`
                <div class="transaction-card-header">
                  <span class="transaction-date">${x}</span>
                  <span class="transaction-value ${n}">${d.money(C)}</span>
                </div>
                <div class="transaction-card-body">
                  <div class="transaction-info-row">
                    <span class="transaction-label">Tipo</span>
                    <span class="transaction-badge tipo-${n}">${_}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${c}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Conta</span>
                    <span class="transaction-text">${l}</span>
                  </div>
                  ${m!=="--"?`
                  <div class="transaction-info-row">
                    <span class="transaction-label">DescriÃ§Ã£o</span>
                    <span class="transaction-description">${m}</span>
                  </div>
                  `:""}
                </div>
                <div class="transaction-card-actions">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              `,g.cardsContainer.appendChild(S)}})}catch(e){A("Erro ao renderizar transações",e,"Falha ao carregar transações")}},renderTransactionsList:async s=>{if(g.transactionsList)try{const e=await I.getTransactions(s,y.TRANSACTIONS_LIMIT),t=Array.isArray(e)&&e.length>0;if(g.transactionsList.innerHTML="",g.emptyState&&(g.emptyState.style.display=t?"none":"flex"),!t)return;const a=new Date().toISOString().slice(0,10),o=new Date(Date.now()-864e5).toISOString().slice(0,10),n=new Map;e.forEach(i=>{const r=String(i.data||"").split(/[T\s]/)[0];n.has(r)||n.set(r,[]),n.get(r).push(i)});for(const[i,r]of n){let c;i===a?c="Hoje":i===o?c="Ontem":c=d.dateBR(i);const l=document.createElement("div");l.className="dash-tx-date-group",l.textContent=c,g.transactionsList.appendChild(l),r.forEach(m=>{const C=String(m.tipo||"").toLowerCase()==="receita",x=$(m.descricao||"--"),w=m.categoria_nome??(typeof m.categoria=="string"?m.categoria:m.categoria?.nome)??"Sem categoria",S=Number(m.valor)||0,f=!!m.pago,M=m.categoria_icone||(C?"arrow-down-left":"arrow-up-right"),T=document.createElement("div");T.className="dash-tx-item surface-card",T.setAttribute("data-id",m.id),T.innerHTML=`
                        <div class="dash-tx__left">
                            <div class="dash-tx__icon dash-tx__icon--${C?"income":"expense"}">
                                <i data-lucide="${$(M)}"></i>
                            </div>
                            <div class="dash-tx__info">
                                <span class="dash-tx__desc">${x}</span>
                                <span class="dash-tx__category">${$(w)}</span>
                            </div>
                        </div>
                        <div class="dash-tx__right">
                            <span class="dash-tx__amount dash-tx__amount--${C?"income":"expense"}">${C?"+":"-"}${d.money(Math.abs(S))}</span>
                            <span class="dash-tx__badge dash-tx__badge--${f?"paid":"pending"}">${f?"Pago":"Pendente"}</span>
                        </div>
                    `,g.transactionsList.appendChild(T)})}typeof window.lucide<"u"&&window.lucide.createIcons()}catch(e){A("Erro ao renderizar lista de transações",e,"Falha ao carregar transações"),g.emptyState&&(g.emptyState.style.display="flex")}},renderChart:async(s,e)=>{if(!(!g.categoryChart||typeof ApexCharts>"u")){e||(e=L._chartMode||"donut"),L._chartMode=e,g.chartLoading&&(g.chartLoading.style.display="flex");try{const t=await I.getOverview(s),a=Array.isArray(t.despesas_por_categoria)?t.despesas_por_categoria:[],{isLightTheme:o}=ne(),n=o?"light":"dark";if(L.chartInstance&&(L.chartInstance.destroy(),L.chartInstance=null),a.length===0){const r=ce(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)});g.categoryChart.innerHTML=`
                    <div class="dash-chart-empty">
                        <i data-lucide="pie-chart"></i>
                        <strong>${$(r.chartEmptyTitle)}</strong>
                        <p>${$(r.chartEmptyDescription)}</p>
                        <button class="dash-btn dash-btn--ghost" type="button" id="dashboardChartEmptyCta">
                            <i data-lucide="plus"></i> ${$(r.chartEmptyButton)}
                        </button>
                    </div>
                `,document.getElementById("dashboardChartEmptyCta")?.addEventListener("click",()=>{le(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)})}),typeof window.lucide<"u"&&window.lucide.createIcons();return}const i=["#E67E22","#2ecc71","#e74c3c","#3498db","#9b59b6","#1abc9c","#f39c12","#e91e63","#00bcd4","#8bc34a"];if(e==="compare"){const c=d.getPreviousMonths(s,2)[0];let l=[];try{const f=await I.getOverview(c);l=Array.isArray(f.despesas_por_categoria)?f.despesas_por_categoria:[]}catch{}const _=[...new Set([...a.map(f=>f.categoria),...l.map(f=>f.categoria)])],C=Object.fromEntries(a.map(f=>[f.categoria,Math.abs(Number(f.valor)||0)])),x=Object.fromEntries(l.map(f=>[f.categoria,Math.abs(Number(f.valor)||0)])),w=_.map(f=>C[f]||0),S=_.map(f=>x[f]||0);L.chartInstance=new ApexCharts(g.categoryChart,{chart:{type:"bar",height:300,background:"transparent",fontFamily:"Inter, Arial, sans-serif",toolbar:{show:!1}},series:[{name:d.formatMonthShort(s),data:w},{name:d.formatMonthShort(c),data:S}],colors:["#E67E22","rgba(230,126,34,0.35)"],xaxis:{categories:_,labels:{style:{colors:o?"#555":"#aaa",fontSize:"11px"},rotate:-35,trim:!0,maxHeight:80}},yaxis:{labels:{formatter:f=>d.money(f),style:{colors:o?"#555":"#aaa"}}},plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},dataLabels:{enabled:!1},legend:{position:"top",fontSize:"12px",labels:{colors:o?"#555":"#ccc"}},tooltip:{theme:n,y:{formatter:f=>d.money(f)}},grid:{borderColor:o?"#e5e5e5":"rgba(255,255,255,0.06)",strokeDashArray:3},theme:{mode:n}})}else{const r=a.map(l=>l.categoria),c=a.map(l=>Math.abs(Number(l.valor)||0));L.chartInstance=new ApexCharts(g.categoryChart,{chart:{type:"donut",height:280,background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:c,labels:r,colors:i.slice(0,r.length),stroke:{width:2,colors:[o?"#fff":"#1e1e1e"]},plotOptions:{pie:{donut:{size:"60%",labels:{show:!0,value:{formatter:l=>d.money(Number(l))},total:{show:!0,label:"Total",formatter:l=>d.money(l.globals.seriesTotals.reduce((m,_)=>m+_,0))}}}}},legend:{position:"bottom",fontSize:"13px",labels:{colors:o?"#555":"#ccc"}},tooltip:{theme:n,y:{formatter:l=>d.money(l)}},dataLabels:{enabled:!1},theme:{mode:n}})}L.chartInstance.render()}catch(t){A("Erro ao renderizar gráfico",t,"Falha ao carregar gráfico")}finally{g.chartLoading&&setTimeout(()=>{g.chartLoading.style.display="none"},300)}}}},v={getContainer:(s,e)=>{const t=document.getElementById(e);if(t)return t;const a=document.getElementById(s);if(!a)return null;const o=a.querySelector(".dash-optional-body");if(o)return o.id||(o.id=e),o;const n=document.createElement("div");n.className="dash-optional-body",n.id=e;const i=a.querySelector(".dash-section-header"),r=Array.from(a.children).filter(c=>c.classList?.contains("dash-placeholder"));return i?.nextSibling?a.insertBefore(n,i.nextSibling):a.appendChild(n),r.forEach(c=>n.appendChild(c)),n},renderLoading:s=>{s&&(s.innerHTML=`
            <div class="dash-widget dash-widget--loading" aria-hidden="true">
                <div class="dash-widget-skeleton dash-widget-skeleton--title"></div>
                <div class="dash-widget-skeleton dash-widget-skeleton--value"></div>
                <div class="dash-widget-skeleton dash-widget-skeleton--text"></div>
                <div class="dash-widget-skeleton dash-widget-skeleton--bar"></div>
            </div>
        `)},renderEmpty:(s,e,t,a)=>{s&&(s.innerHTML=`
            <div class="dash-widget-empty">
                <p>${e}</p>
                ${t&&a?`<a href="${t}" class="dash-widget-link">${a}</a>`:""}
            </div>
        `)},getUsageColor:s=>s>=85?"#ef4444":s>=60?"#f59e0b":"#10b981",getAccountBalance:s=>{const t=[s?.saldoAtual,s?.saldo_atual,s?.saldo,s?.saldoInicial,s?.saldo_inicial].find(a=>Number.isFinite(Number(a)));return Number(t||0)},renderMetas:async s=>{const e=v.getContainer("sectionMetas","sectionMetasBody");if(e){v.renderLoading(e);try{const a=(await I.getFinanceSummary(s))?.metas??null;if(!a||Number(a.total_metas||0)===0){v.renderEmpty(e,"Você ainda não tem metas ativas neste momento.",`${y.BASE_URL}financas#metas`,"Criar meta");return}const o=a.proxima_concluir||null,n=Math.round(Number(a.progresso_geral||0));if(!o){e.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Metas ativas</span>
                        <strong class="dash-widget-value">${Number(a.total_metas||0)}</strong>
                        <p class="dash-widget-caption">Você tem metas em andamento, mas nenhuma está próxima de conclusão.</p>
                        <div class="dash-widget-meta">
                            <span>Progresso geral</span>
                            <strong>${n}%</strong>
                        </div>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(n,100)}%; background:var(--color-primary);"></span>
                        </div>
                        <a href="${y.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                    </div>
                `;return}const i=$(String(o.titulo||"Sua meta principal")),r=Number(o.valor_atual||0),c=Number(o.valor_alvo||0),l=Math.max(c-r,0),m=Math.round(Number(o.progresso||0)),_=o.cor||"var(--color-primary)";e.innerHTML=`
                <div class="dash-widget">
                    <span class="dash-widget-label">Próxima meta</span>
                    <strong class="dash-widget-value">${i}</strong>
                    <p class="dash-widget-caption">Faltam ${d.money(l)} para concluir.</p>
                    <div class="dash-widget-progress">
                        <span style="width:${Math.min(m,100)}%; background:${_};"></span>
                    </div>
                    <div class="dash-widget-meta">
                        <span>${d.money(r)} de ${d.money(c)}</span>
                        <strong style="color:${_};">${m}%</strong>
                    </div>
                    <a href="${y.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                </div>
            `}catch(t){A("Erro ao carregar widget de metas",t,"Falha ao carregar metas"),v.renderEmpty(e,"Não foi possível carregar suas metas agora.",`${y.BASE_URL}financas#metas`,"Tentar nas finanças")}}},renderCartoes:async()=>{const s=v.getContainer("sectionCartoes","sectionCartoesBody");if(s){v.renderLoading(s);try{const e=await I.getCardsSummary(),t=Number(e?.total_cartoes||0);if(!e||t===0){v.renderEmpty(s,"Você ainda não tem cartões ativos no dashboard.",`${y.BASE_URL}cartoes`,"Cadastrar cartão");return}const a=Number(e.limite_disponivel||0),o=Number(e.limite_total||0),n=Math.round(Number(e.percentual_uso||0)),i=v.getUsageColor(n);s.innerHTML=`
                <div class="dash-widget">
                    <span class="dash-widget-label">Limite disponível</span>
                    <strong class="dash-widget-value">${d.money(a)}</strong>
                    <p class="dash-widget-caption">${t} cartão(ões) ativo(s) com ${n}% de uso consolidado.</p>
                    <div class="dash-widget-progress">
                        <span style="width:${Math.min(n,100)}%; background:${i};"></span>
                    </div>
                    <div class="dash-widget-meta">
                        <span>Limite total ${d.money(o)}</span>
                        <strong style="color:${i};">${n}% usado</strong>
                    </div>
                    <a href="${y.BASE_URL}cartoes" class="dash-widget-link">Criar cartões</a>
                </div>
            `}catch(e){A("Erro ao carregar widget de cartões",e,"Falha ao carregar cartões"),v.renderEmpty(s,"Não foi possível carregar seus cartões agora.",`${y.BASE_URL}cartoes`,"Criar cartões")}}},renderContas:async s=>{const e=v.getContainer("sectionContas","sectionContasBody");if(e){v.renderLoading(e);try{const t=await I.getAccountsBalances(s),a=Array.isArray(t)?t:[];if(a.length===0){v.renderEmpty(e,"Você ainda não tem contas ativas conectadas.",`${y.BASE_URL}contas`,"Adicionar conta");return}const o=a.map(l=>({...l,__saldo:v.getAccountBalance(l)})).sort((l,m)=>m.__saldo-l.__saldo),n=o.reduce((l,m)=>l+m.__saldo,0),i=o[0]||null,r=$(String(i?.nome||i?.nome_conta||i?.instituicao||i?.banco_nome||"Conta principal")),c=i?d.money(i.__saldo):d.money(0);e.innerHTML=`
                <div class="dash-widget">
                    <span class="dash-widget-label">Saldo consolidado</span>
                    <strong class="dash-widget-value">${d.money(n)}</strong>
                    <p class="dash-widget-caption">${o.length} conta(s) ativa(s) no painel.</p>
                    <div class="dash-widget-list">
                        ${o.slice(0,3).map(l=>`
                                <div class="dash-widget-list-item">
                                    <span>${$(String(l.nome||l.nome_conta||l.instituicao||l.banco_nome||"Conta"))}</span>
                                    <strong>${d.money(l.__saldo)}</strong>
                                </div>
                            `).join("")}
                    </div>
                    <div class="dash-widget-meta">
                        <span>Maior saldo em ${r}</span>
                        <strong>${c}</strong>
                    </div>
                    <a href="${y.BASE_URL}contas" class="dash-widget-link">Abrir contas</a>
                </div>
            `}catch(t){A("Erro ao carregar widget de contas",t,"Falha ao carregar contas"),v.renderEmpty(e,"Não foi possível carregar suas contas agora.",`${y.BASE_URL}contas`,"Abrir contas")}}},renderOrcamentos:async s=>{const e=v.getContainer("sectionOrcamentos","sectionOrcamentosBody");if(e){v.renderLoading(e);try{const a=(await I.getFinanceSummary(s))?.orcamento??null;if(!a||Number(a.total_categorias||0)===0){v.renderEmpty(e,"Você ainda não definiu limites para categorias.",`${y.BASE_URL}financas#orcamentos`,"Definir limite");return}const o=Math.round(Number(a.percentual_geral||0)),n=v.getUsageColor(o),r=(a.orcamentos||[]).slice().sort((c,l)=>Number(l.percentual||0)-Number(c.percentual||0)).slice(0,3).map(c=>{const l=v.getUsageColor(c.percentual);return`
                    <div class="dash-widget-list-item">
                        <span>${$(c.categoria_nome||"Categoria")}</span>
                        <strong style="color:${l};">${Math.round(c.percentual||0)}%</strong>
                    </div>
                `}).join("");e.innerHTML=`
                <div class="dash-widget">
                    <span class="dash-widget-label">Uso geral dos limites</span>
                    <strong class="dash-widget-value" style="color:${n};">${o}%</strong>
                    <div class="dash-widget-progress">
                        <span style="width:${Math.min(o,100)}%; background:${n};"></span>
                    </div>
                    <p class="dash-widget-caption">${d.money(a.total_gasto||0)} de ${d.money(a.total_limite||0)}</p>
                    ${r?`<div class="dash-widget-list">${r}</div>`:""}
                    <a href="${y.BASE_URL}financas#orcamentos" class="dash-widget-link">Ver orçamentos</a>
                </div>
            `}catch(t){A("Erro ao carregar widget de orçamentos",t,"Falha ao carregar orçamentos"),v.renderEmpty(e,"Não foi possível carregar seus orçamentos.",`${y.BASE_URL}financas#orcamentos`,"Abrir orçamentos")}}},renderFaturas:async()=>{const s=v.getContainer("sectionFaturas","sectionFaturasBody");if(s){v.renderLoading(s);try{const e=await I.getCardsSummary(),t=Number(e?.total_cartoes||0);if(!e||t===0){v.renderEmpty(s,"Você não tem cartões com faturas abertas.",`${y.BASE_URL}faturas`,"Ver faturas");return}const a=Number(e.fatura_aberta??e.limite_utilizado??0),o=Number(e.limite_total||0),n=o>0?Math.round(a/o*100):Number(e.percentual_uso||0),i=v.getUsageColor(n);s.innerHTML=`
                <div class="dash-widget">
                    <span class="dash-widget-label">Fatura atual</span>
                    <strong class="dash-widget-value">${d.money(a)}</strong>
                    ${o>0?`
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(n,100)}%; background:${i};"></span>
                        </div>
                        <p class="dash-widget-caption">${n}% do limite utilizado</p>
                    `:`
                        <p class="dash-widget-caption">${t} cartão(ões) ativo(s)</p>
                    `}
                    <a href="${y.BASE_URL}faturas" class="dash-widget-link">Abrir faturas</a>
                </div>
            `}catch(e){A("Erro ao carregar widget de faturas",e,"Falha ao carregar faturas"),v.renderEmpty(s,"Não foi possível carregar suas faturas.",`${y.BASE_URL}faturas`,"Ver faturas")}}},render:async s=>{await Promise.allSettled([v.renderMetas(s),v.renderCartoes(),v.renderContas(s),v.renderOrcamentos(s),v.renderFaturas()])}},Z={delete:async(s,e)=>{try{if(await F.ensureSwal(),!await F.confirm("Excluir lançamento?","Esta ação não pode ser desfeita."))return;F.loading("Excluindo..."),await I.deleteTransaction(Number(s)),F.close(),F.toast("success","LanÃ§amento excluÃ­do com sucesso!"),e&&(e.style.opacity="0",e.style.transform="translateX(-20px)",setTimeout(()=>{e.remove(),g.tableBody.children.length===0&&(g.emptyState&&(g.emptyState.style.display="block"),g.table&&(g.table.style.display="none"))},300)),document.dispatchEvent(new CustomEvent("lukrato:data-changed",{detail:{resource:"transactions",action:"delete",id:Number(s)}}))}catch(t){console.error("Erro ao excluir lanÃ§amento:",t),await F.ensureSwal(),F.error("Erro",W(t,"Falha ao excluir lanÃ§amento"))}}},D={isProUser:null,checkProStatus:async()=>{try{const s=await I.getOverview(d.getCurrentMonth());D.isProUser=s?.plan?.is_pro===!0}catch{D.isProUser=!1}return D.isProUser},render:async s=>{const e=document.getElementById("sectionPrevisao");if(!e)return;await D.checkProStatus();const t=document.getElementById("provisaoProOverlay"),a=D.isProUser;e.classList.remove("is-locked"),t&&(t.style.display="none");try{const o=await I.getOverview(s);D.renderData(o.provisao||null,a)}catch(o){A("Erro ao carregar provisÃ£o",o,"Falha ao carregar previsÃ£o")}},renderData:(s,e=!0)=>{if(!s)return;const t=s.provisao||{},a=d.money,o=document.getElementById("provisaoTitle"),n=document.getElementById("provisaoHeadline");o&&(o.textContent=`Se continuar assim, você termina o mês com ${a(t.saldo_projetado||0)}`),n&&(n.textContent=(t.saldo_projetado||0)>=0?"A previsão abaixo considera seu saldo atual, o que ainda vai entrar e o que ainda vai sair.":"A previsao indica aperto no fim do mes se o ritmo atual continuar.");const i=document.getElementById("provisaoProximosTitle"),r=document.getElementById("provisaoVerTodos");i&&(i.innerHTML=e?'<i data-lucide="clock"></i> Próximos Vencimentos':'<i data-lucide="credit-card"></i> Próximas Faturas'),r&&(r.href=e?`${window.BASE_URL||"/"}lancamentos`:`${window.BASE_URL||"/"}faturas`);const c=document.getElementById("provisaoPagar"),l=document.getElementById("provisaoReceber"),m=document.getElementById("provisaoProjetado"),_=document.getElementById("provisaoPagarCount"),C=document.getElementById("provisaoReceberCount"),x=document.getElementById("provisaoProjetadoLabel"),w=l?.closest(".provisao-card");if(c&&(c.textContent=a(t.a_pagar||0)),e?(l&&(l.textContent=a(t.a_receber||0)),w&&(w.style.opacity="1")):(l&&(l.textContent="R$ --"),w&&(w.style.opacity="0.5")),m&&(m.textContent=a(t.saldo_projetado||0),m.style.color=(t.saldo_projetado||0)>=0?"":"var(--color-danger)"),_){const h=t.count_pagar||0,u=t.count_faturas||0;if(e){let E=`${h} pendente${h!==1?"s":""}`;u>0&&(E+=` â€¢ ${u} fatura${u!==1?"s":""}`),_.textContent=E}else _.textContent=`${u} fatura${u!==1?"s":""}`}e?C&&(C.textContent=`${t.count_receber||0} pendente${(t.count_receber||0)!==1?"s":""}`):C&&(C.textContent="Pro"),x&&(x.textContent=`saldo atual: ${a(t.saldo_atual||0)}`);const S=s.vencidos||{},f=document.getElementById("provisaoAlertDespesas");if(f){const h=S.despesas||{};if(e&&(h.count||0)>0){f.style.display="flex";const u=document.getElementById("provisaoAlertDespesasCount"),E=document.getElementById("provisaoAlertDespesasTotal");u&&(u.textContent=h.count),E&&(E.textContent=a(h.total||0))}else f.style.display="none"}const M=document.getElementById("provisaoAlertReceitas");if(M){const h=S.receitas||{};if(e&&(h.count||0)>0){M.style.display="flex";const u=document.getElementById("provisaoAlertReceitasCount"),E=document.getElementById("provisaoAlertReceitasTotal");u&&(u.textContent=h.count),E&&(E.textContent=a(h.total||0))}else M.style.display="none"}const T=document.getElementById("provisaoAlertFaturas");if(T){const h=S.count_faturas||0;if(h>0){T.style.display="flex";const u=document.getElementById("provisaoAlertFaturasCount"),E=document.getElementById("provisaoAlertFaturasTotal");u&&(u.textContent=h),E&&(E.textContent=a(S.total_faturas||0))}else T.style.display="none"}const k=document.getElementById("provisaoProximosList"),R=document.getElementById("provisaoEmpty");let O=s.proximos||[];if(e||(O=O.filter(h=>h.is_fatura===!0)),k)if(O.length===0){if(k.innerHTML="",R){const h=R.querySelector("span");h&&(h.textContent=e?"Nenhum vencimento pendente":"Nenhuma fatura pendente"),k.appendChild(R),R.style.display="flex"}}else{k.innerHTML="";const h=new Date().toISOString().slice(0,10);O.forEach(u=>{const E=(u.tipo||"").toLowerCase(),U=u.is_fatura===!0,se=(u.data_pagamento||"").split(/[T\s]/)[0],he=se===h,pe=D.formatDateShort(se);let P="";he&&(P+='<span class="provisao-item-badge vence-hoje">Hoje</span>'),U?(P+='<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>',u.cartao_ultimos_digitos&&(P+=`<span>****${u.cartao_ultimos_digitos}</span>`)):(u.eh_parcelado&&u.numero_parcelas>1&&(P+=`<span class="provisao-item-badge parcela">${u.parcela_atual}/${u.numero_parcelas}</span>`),u.recorrente&&(P+='<span class="provisao-item-badge recorrente">Recorrente</span>'),u.categoria&&(P+=`<span>${$(u.categoria)}</span>`));const oe=U?"fatura":E,q=document.createElement("div");q.className="provisao-item"+(U?" is-fatura":""),q.innerHTML=`
                            <div class="provisao-item-dot ${oe}"></div>
                            <div class="provisao-item-info">
                                <div class="provisao-item-titulo">${$(u.titulo||"Sem tÃ­tulo")}</div>
                                <div class="provisao-item-meta">${P}</div>
                            </div>
                            <span class="provisao-item-valor ${oe}">${a(u.valor||0)}</span>
                            <span class="provisao-item-data">${pe}</span>
                        `,U&&u.cartao_id&&(q.style.cursor="pointer",q.addEventListener("click",()=>{const ge=(u.data_pagamento||"").split(/[T\s]/)[0],[fe,ye]=ge.split("-");window.location.href=`${window.BASE_URL||"/"}faturas?cartao_id=${u.cartao_id}&mes=${parseInt(ye)}&ano=${fe}`})),k.appendChild(q)})}const H=document.getElementById("provisaoParcelas"),p=s.parcelas||{};if(H)if(e&&(p.ativas||0)>0){H.style.display="flex";const h=document.getElementById("provisaoParcelasText"),u=document.getElementById("provisaoParcelasValor");h&&(h.textContent=`${p.ativas} parcelamento${p.ativas!==1?"s":""} ativo${p.ativas!==1?"s":""}`),u&&(u.textContent=`${a(p.total_mensal||0)}/mÃªs`)}else H.style.display="none"},formatDateShort:s=>{if(!s)return"-";try{const e=s.match(/^(\d{4})-(\d{2})-(\d{2})$/);return e?`${e[3]}/${e[2]}`:"-"}catch{return"-"}}},V={refresh:async({force:s=!1}={})=>{if(L.isLoading)return;L.isLoading=!0;const e=d.getCurrentMonth();L.currentMonth=e,s&&B(e);try{b.updateMonthLabel(e),await Promise.allSettled([b.renderKPIs(e),b.renderTable(e),b.renderTransactionsList(e),b.renderChart(e),D.render(e),v.render(e)])}catch(t){A("Erro ao atualizar dashboard",t,"Falha ao atualizar dashboard")}finally{L.isLoading=!1}},init:async()=>{await V.refresh({force:!1})}},De={init:()=>{if(L.eventListenersInitialized)return;L.eventListenersInitialized=!0,g.tableBody?.addEventListener("click",async e=>{const t=e.target.closest(".btn-del");if(!t)return;const a=e.target.closest("tr"),o=t.getAttribute("data-id");o&&(t.disabled=!0,await Z.delete(o,a),t.disabled=!1)}),g.cardsContainer?.addEventListener("click",async e=>{const t=e.target.closest(".btn-del");if(!t)return;const a=e.target.closest(".transaction-card"),o=t.getAttribute("data-id");o&&(t.disabled=!0,await Z.delete(o,a),t.disabled=!1)}),g.transactionsList?.addEventListener("click",async e=>{const t=e.target.closest(".btn-del");if(!t)return;const a=e.target.closest(".dash-tx-item"),o=t.getAttribute("data-id");o&&(t.disabled=!0,await Z.delete(o,a),t.disabled=!1)}),document.addEventListener("lukrato:data-changed",()=>{B(L.currentMonth||d.getCurrentMonth()),V.refresh({force:!1})}),document.addEventListener("lukrato:month-changed",()=>{V.refresh({force:!1})}),document.addEventListener("lukrato:theme-changed",()=>{b.renderChart(L.currentMonth||d.getCurrentMonth())});const s=document.getElementById("chartToggle");s&&s.addEventListener("click",e=>{const t=e.target.closest("[data-mode]");if(!t)return;const a=t.getAttribute("data-mode");s.querySelectorAll(".dash-chart-toggle__btn").forEach(o=>o.classList.remove("is-active")),t.classList.add("is-active"),b.renderChart(L.currentMonth||d.getCurrentMonth(),a)})}},ue="lk_dashboard_prefs";let ee=0;const ae={toggleHealthScore:"sectionHealthScore",toggleAiTip:"sectionAiTip",toggleEvolucao:"sectionEvolucao",toggleAlertas:"sectionAlertas",toggleGrafico:"chart-section",togglePrevisao:"sectionPrevisao",toggleMetas:"sectionMetas",toggleCartoes:"sectionCartoes",toggleContas:"sectionContas",toggleOrcamentos:"sectionOrcamentos",toggleFaturas:"sectionFaturas",toggleGamificacao:"sectionGamificacao"},z={toggleHealthScore:!0,toggleAiTip:!0,toggleEvolucao:!0,toggleAlertas:!0,toggleGrafico:!0,togglePrevisao:!0,toggleMetas:!1,toggleCartoes:!1,toggleContas:!1,toggleOrcamentos:!1,toggleFaturas:!1,toggleGamificacao:!1};function Re(){try{const s=localStorage.getItem(ue);return s?{...z,...JSON.parse(s)}:null}catch{return null}}function Y(s){try{localStorage.setItem(ue,JSON.stringify(s))}catch{}}async function Oe(){try{const s=await G(`${y.API_URL}perfil/dashboard-preferences`),t=(s?.data??s)?.preferences;if(t&&typeof t=="object"&&Object.keys(t).length>0){const a={...z,...t};return Y(a),a}return Y(z),{...z}}catch{}return null}async function Pe(s){Y(s);try{await K(`${y.API_URL}perfil/dashboard-preferences`,s)}catch{}}function me(){return Re()??{...z}}function te(s){Object.entries(ae).forEach(([t,a])=>{const o=document.getElementById(a);o&&(o.style.display=s[t]?"":"none")});const e=document.getElementById("optionalGrid");if(e){const a=["toggleMetas","toggleCartoes","toggleContas","toggleOrcamentos","toggleFaturas"].some(o=>s[o]);e.style.display=a?"":"none"}}function Fe(s){Object.keys(ae).forEach(e=>{const t=document.getElementById(e);t&&(t.checked=!!s[e])})}function He(){const s=document.getElementById("customizeModalOverlay");if(!s)return;Fe(me()),s.style.display="flex";const e=t=>{t.key==="Escape"&&(Q(),document.removeEventListener("keydown",e))};document.addEventListener("keydown",e)}function Q(){const s=document.getElementById("customizeModalOverlay");s&&(s.style.display="none")}function Ue(){const s={};Object.keys(ae).forEach(e=>{const t=document.getElementById(e);s[e]=t?t.checked:z[e]}),ee+=1,Y(s),te(s),Q(),Pe(s),typeof window.lucide<"u"&&window.lucide.createIcons()}function Ve(){te(me());const s=ee;Oe().then(n=>{n&&ee===s&&te(n)});const e=document.getElementById("btnCustomizeDashboard");e&&e.addEventListener("click",He);const t=document.getElementById("btnCloseCustomize");t&&t.addEventListener("click",Q);const a=document.getElementById("btnSaveCustomize");a&&a.addEventListener("click",Ue);const o=document.getElementById("customizeModalOverlay");o&&o.addEventListener("click",n=>{n.target===o&&Q()})}window.__LK_DASHBOARD_LOADER__||(window.__LK_DASHBOARD_LOADER__=!0,window.refreshDashboard=V.refresh,window.LK=window.LK||{},window.LK.refreshDashboard=V.refresh,(()=>{const e=()=>{De.init(),V.init(),Ve()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",e):e()})());
