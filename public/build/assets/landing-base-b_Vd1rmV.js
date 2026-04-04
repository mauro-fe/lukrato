const A="lukrato-theme",q="lukrato:theme-changed";function $(){const n=document.documentElement,e=localStorage.getItem(A);if(e==="light"||e==="dark")return e;const r=n.getAttribute("data-theme");return r==="light"||r==="dark"?r:"light"}function T(n){document.querySelectorAll(".lk-theme-toggle").forEach(e=>{e.classList.toggle("dark",n==="dark")})}function C(n){document.documentElement.setAttribute("data-theme",n),localStorage.setItem(A,n),T(n);const r=document.querySelector('meta[name="theme-color"]');r&&r.setAttribute("content",n==="dark"?"#092741":"#e67e22"),document.dispatchEvent(new CustomEvent(q,{detail:{theme:n}})),window.lucide&&requestAnimationFrame(()=>lucide.createIcons())}function R(){const n=$();C(n==="dark"?"light":"dark")}function D(){const n=$();C(n),document.querySelectorAll(".lk-theme-toggle").forEach(e=>{e.addEventListener("click",R)}),document.addEventListener(q,e=>{T(e.detail.theme)})}function P(){function n(){const t=document.querySelector('meta[name="base-url"]');if(t?.content)try{return new URL(t.content).pathname.replace(/\/?$/,"/")}catch{return t.content.replace(/\/?$/,"/")}const s=location.pathname,o=s.indexOf("/public/");return o!==-1?s.substring(0,o+8):"/"}const e=n(),r=["funcionalidades","beneficios","gamificacao","planos","indicacao","contato"];function d(){const t=document.querySelector("header, .lk-site-header");return t?t.offsetHeight+10:0}function i(t){const s=document.getElementById(t);if(!s)return;const o=s.getBoundingClientRect().top+window.pageYOffset-d();window.scrollTo({top:o,behavior:"smooth"})}function c(t){if(!t.startsWith(e))return null;const o=t.slice(e.length).replace(/^\/+/,"").split("/")[0];return r.includes(o)?o:null}const a=c(location.pathname);a&&(history.replaceState(null,"",e+a),requestAnimationFrame(()=>i(a))),document.addEventListener("click",t=>{const s=t.target.closest("a[href]");if(!s)return;let o=s.getAttribute("href");if(!o)return;try{o=new URL(o,location.origin).pathname}catch{return}const l=c(o);l&&(t.preventDefault(),history.pushState(null,"",e+l),i(l))}),window.addEventListener("popstate",()=>{const t=c(location.pathname);t&&i(t)})}function M(){if(window.lkLandingBootstrapped)return;window.lkLandingBootstrapped=!0;const n=document.querySelector(".lk-site-burger"),e=document.querySelector(".lk-site-header"),r=document.body;if(!n||!e)return;const d=document.createElement("div");d.className="lk-site-menu-overlay",e.appendChild(d);function i(){e.classList.remove("is-open"),r.classList.remove("lk-nav-open"),n.setAttribute("aria-expanded","false")}function c(){const a=!e.classList.contains("is-open");e.classList.toggle("is-open",a),r.classList.toggle("lk-nav-open",a),n.setAttribute("aria-expanded",a?"true":"false")}n.setAttribute("aria-expanded","false"),n.addEventListener("click",c),d.addEventListener("click",i),window.addEventListener("resize",()=>{window.innerWidth>768&&e.classList.contains("is-open")&&i()}),e.addEventListener("click",a=>{a.target.closest(".lk-site-nav-link")&&e.classList.contains("is-open")&&i()}),document.addEventListener("keydown",a=>{a.key==="Escape"&&e.classList.contains("is-open")&&i()})}function _(){const n="func-gallery",e=document.getElementById(n);if(!e)return;const r=document.querySelectorAll(`[data-open="${n}"]`),d=e.querySelectorAll(`[data-close="${n}"]`),i=e.querySelector(".lk-gallery-track"),c=i?Array.from(i.querySelectorAll("img")):[],a=e.querySelector(".lk-gallery-prev"),t=e.querySelector(".lk-gallery-next"),s=e.querySelector("#lkGalleryTitle"),o=e.querySelector("#lkGalleryDesc"),l=e.querySelector("#lkGalleryCount"),f=e.querySelector(".lk-gallery");let m=0,h=0,p=!1;function y(){e.setAttribute("aria-hidden","false"),e.classList.add("is-open"),document.body.style.overflow="hidden",requestAnimationFrame(()=>k(!0))}function w(){e.setAttribute("aria-hidden","true"),e.classList.remove("is-open"),document.body.style.overflow=""}function b(){if(!c.length)return;const u=c[m],g=u.dataset.title||u.alt||"Tela",z=u.dataset.desc||"";s&&(s.textContent=g),o&&(o.textContent=z),l&&(l.textContent=`${m+1}/${c.length}`)}function k(u=!1){if(!i||c.length===0)return;const g=c[0].clientWidth;u&&(i.style.transition="none"),i.style.transform=`translateX(-${m*g}px)`,b(),u&&requestAnimationFrame(()=>{i.style.transition="transform 0.28s ease"})}function v(){c.length&&(m=(m+1)%c.length,k())}function x(){c.length&&(m=(m-1+c.length)%c.length,k())}r.forEach(u=>u.addEventListener("click",y)),d.forEach(u=>u.addEventListener("click",w)),a&&a.addEventListener("click",x),t&&t.addEventListener("click",v),c.forEach(u=>u.addEventListener("click",v)),document.addEventListener("keydown",u=>{e.classList.contains("is-open")&&(u.key==="Escape"&&w(),u.key==="ArrowRight"&&v(),u.key==="ArrowLeft"&&x())}),f&&(f.addEventListener("touchstart",u=>{e.classList.contains("is-open")&&(h=u.touches[0].clientX,p=!0)},{passive:!0}),f.addEventListener("touchmove",()=>{},{passive:!0}),f.addEventListener("touchend",u=>{if(!p)return;const g=u.changedTouches[0].clientX-h;Math.abs(g)>45&&(g<0?v():x()),p=!1})),window.addEventListener("resize",()=>k(!0)),b()}let E;function L(){return window.Swal?Promise.resolve(window.Swal):(E||(E=new Promise((n,e)=>{const r=document.createElement("script");r.src="https://cdn.jsdelivr.net/npm/sweetalert2@11",r.onload=()=>n(window.Swal),r.onerror=e,document.head.appendChild(r)})),E)}function F(){const n=document.querySelector("#contato.lk-contact");if(n){let s=function(o){a.forEach(l=>{const f=l.dataset.target===o;l.classList.toggle("is-active",f),l.setAttribute("aria-selected",f?"true":"false")}),t.forEach(l=>l.classList.toggle("is-active",l.dataset.panel===o))};var c=s;const a=n.querySelectorAll(".lk-toggle-btn"),t=n.querySelectorAll(".lk-contact-panel");a.forEach(o=>o.addEventListener("click",()=>s(o.dataset.target))),s("whatsapp")}const e=document.getElementById("contactForm"),r=document.getElementById("whatsapp");if(!e)return;let d=!1;const i=`${window.APP_BASE_URL}/api/contato/enviar`;if(r){const a=t=>t.length<=2?`(${t}`:t.length<=6?`(${t.slice(0,2)}) ${t.slice(2)}`:t.length<=10?`(${t.slice(0,2)}) ${t.slice(2,6)}-${t.slice(6)}`:`(${t.slice(0,2)}) ${t.slice(2,7)}-${t.slice(7,11)}`;r.addEventListener("input",t=>{const s=t.target.value.replace(/\D/g,"").slice(0,11);t.target.value=a(s)})}e.addEventListener("submit",async a=>{if(a.preventDefault(),d)return;d=!0;const t=e.querySelector('[type="submit"]'),s=t?t.textContent:null;t&&(t.disabled=!0,t.textContent="Enviando...");try{const o=await fetch(i,{method:"POST",body:new FormData(e)}),l=await o.text();let f=null;try{f=JSON.parse(l)}catch{}const m=f?.success===!0,h=f?.message??"Mensagem enviada com sucesso.",p=await L();if(o.ok&&m){await p.fire({icon:"success",title:"Mensagem enviada! ",text:h,confirmButtonText:"Ok",confirmButtonColor:"#e67e22"}),e.reset();return}const y=f?.message??f?.data?.message??`Erro ao enviar (status ${o.status}).`;await p.fire({icon:o.status===422?"warning":"error",title:o.status===422?"Verifique os campos":"Não foi possível enviar",text:y,confirmButtonColor:"#e67e22"})}catch(o){console.error(o);try{await(await L()).fire({icon:"error",title:"Erro de conexão",text:"Não foi possível enviar sua mensagem agora. Tente novamente.",confirmButtonColor:"#e67e22"})}catch{}}finally{d=!1,t&&(t.disabled=!1,t.textContent=s??"Enviar")}})}function O(){const n=document.getElementById("lkBackToTop");if(!n)return;const e=420;let r=!1;function d(){(window.scrollY||document.documentElement.scrollTop||0)>e?n.classList.add("is-visible"):n.classList.remove("is-visible"),r=!1}window.addEventListener("scroll",()=>{r||(window.requestAnimationFrame(d),r=!0)},{passive:!0}),n.addEventListener("click",()=>window.scrollTo({top:0,behavior:"smooth"})),d()}function N(){const n="lk_cookie_consent";if(localStorage.getItem(n))return;const e=(window.APP_BASE_URL||"")+"/";setTimeout(function(){const r=document.createElement("div");r.id="cookieConsent",r.innerHTML=`
            <style>
                @keyframes cookieSlideIn {
                    from { opacity: 0; transform: translateY(20px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                #cookieBannerInner {
                    position: fixed;
                    bottom: 16px;
                    left: 16px;
                    right: 16px;
                    max-width: 380px;
                    background: var(--color-bg);
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0,0,0,.15), 0 0 0 1px rgba(0,0,0,.05);
                    padding: 16px;
                    z-index: 9999;
                    font-family: var(--font-primary);
                    animation: cookieSlideIn .4s ease-out;
                }
                @media (min-width: 480px) {
                    #cookieBannerInner { right: auto; padding: 20px; }
                }
                #cookieBannerInner .cookie-content { display: flex; align-items: flex-start; gap: 12px; }
                #cookieBannerInner .cookie-icon {
                    width: 36px; height: 36px;
                    background: var(--color-primary);
                    border-radius: 10px;
                    display: flex; align-items: center; justify-content: center;
                    flex-shrink: 0; font-size: 18px;
                }
                @media (min-width: 480px) {
                    #cookieBannerInner .cookie-icon { width: 40px; height: 40px; font-size: 20px; }
                }
                #cookieBannerInner .cookie-text { flex: 1; min-width: 0; }
                #cookieBannerInner h4 { margin: 0 0 4px; font-size: 14px; font-weight: 600; color: var(--color-text); }
                @media (min-width: 480px) { #cookieBannerInner h4 { font-size: 15px; margin-bottom: 6px; } }
                #cookieBannerInner p { margin: 0 0 12px; font-size: 12px; color: var(--color-text); line-height: 1.5; }
                @media (min-width: 480px) { #cookieBannerInner p { font-size: 13px; margin-bottom: 14px; } }
                #cookieBannerInner a { color: var(--color-primary); text-decoration: underline; }
                #cookieBannerInner .cookie-buttons { display: flex; gap: 8px; }
                #cookieBannerInner .btn-accept {
                    flex: 1; padding: 10px 14px;
                    background: var(--color-primary);
                    color: white; border: none; border-radius: 8px;
                    font-size: 13px; font-weight: 600; cursor: pointer;
                    transition: all .2s ease;
                }
                #cookieBannerInner .btn-accept:hover { transform: scale(1.02); }
                #cookieBannerInner .btn-decline {
                    padding: 10px 14px; background: var(--glass-bg); color:var(--color-text);
                    border: 1px solid var(--color-primary); border-radius: 8px; font-size: 13px; font-weight: 500;
                    cursor: pointer; transition: all .2s ease;
                }
                #cookieBannerInner .btn-decline:hover { background: var(--color-surface); }
            </style>
            <div id="cookieBannerInner">
                <div class="cookie-content">
                    <div class="cookie-icon">
                        <i data-lucide="cookie" class="w-6 h-6"></i>
                    </div>
                    <div class="cookie-text">
                        <h4>Usamos cookies</h4>
                        <p>
                            Para melhorar sua experiência e analisar o uso do site.
                            <a href="${e}/privacidade">Saiba mais</a>
                        </p>
                        <div class="cookie-buttons">
                            <button id="cookieAccept" class="btn-accept">Aceitar</button>
                            <button id="cookieDecline" class="btn-decline">Recusar</button>
                        </div>
                    </div>
                </div>
            </div>
        `,document.body.appendChild(r),window.lucide&&lucide.createIcons(),document.getElementById("cookieAccept").addEventListener("click",function(){localStorage.setItem(n,"accepted"),d()}),document.getElementById("cookieDecline").addEventListener("click",function(){localStorage.setItem(n,"declined"),d()});function d(){const i=document.getElementById("cookieBannerInner");i&&(i.style.opacity="0",i.style.transform="translateY(20px)",i.style.transition="all .3s ease",setTimeout(()=>{const c=document.getElementById("cookieConsent");c&&c.remove()},300))}},1500)}function S(n){return n.toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}function B(n){return String(n||"").replace(/\D/g,"")}function U(n){n.classList.remove("lk-vazamento-resultado"),n.offsetWidth,n.classList.add("lk-vazamento-resultado")}function Y(){document.querySelectorAll("[data-leak-calculator]").forEach(e=>{if(e.dataset.lkLeakCalculatorReady==="true")return;const r=e.querySelector("[data-lk-vazamento-input]"),d=e.querySelector("[data-lk-vazamento-submit]"),i=e.querySelector("[data-lk-vazamento-result]"),c=e.querySelector("[data-lk-vazamento-amount]");if(!r||!d||!i||!c)return;e.dataset.lkLeakCalculatorReady="true";function a(){c.textContent="",i.hidden=!0}function t(){const o=B(r.value);if(!o){r.value="",a();return}const l=Number.parseInt(o,10)/100;r.value=`R$ ${S(l)}`,a()}function s(){const o=B(r.value);if(!o){a();return}const l=Number.parseInt(o,10)/100;if(!Number.isFinite(l)||l<=0){a();return}const f=Math.round(l*.15*100)/100;c.textContent=S(f),i.hidden=!1,U(i)}r.addEventListener("input",t),r.addEventListener("keydown",o=>{o.key==="Enter"&&(o.preventDefault(),s())}),d.addEventListener("click",s)})}D();function I(){P(),M(),_(),F(),O(),N(),Y()}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",I):I();
