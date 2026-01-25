(() => {
    const SELECTOR = "[data-lk-tooltip]";
    const OFFSET = 10;

    const tooltip = document.createElement("div");
    tooltip.className = "lk-tooltip";
    tooltip.setAttribute("role", "tooltip");
    tooltip.innerHTML = `
    <div class="lk-tooltip__title" style="display:none;"></div>
    <p class="lk-tooltip__text"></p>
  `;
    document.body.appendChild(tooltip);

    const titleEl = tooltip.querySelector(".lk-tooltip__title");
    const textEl = tooltip.querySelector(".lk-tooltip__text");

    let activeEl = null;
    let openByClick = false;

    function isTouchDevice() {
        return window.matchMedia("(pointer: coarse)").matches;
    }

    function setContent(el) {
        const raw = el.getAttribute("data-lk-tooltip") || "";
        const title = el.getAttribute("data-lk-tooltip-title") || "";

        // Variante automática: textos grandes viram "popover"
        const variant = raw.length > 110 ? "popover" : "tooltip";
        tooltip.setAttribute("data-variant", variant);

        if (title.trim()) {
            titleEl.textContent = title;
            titleEl.style.display = "";
        } else {
            titleEl.textContent = "";
            titleEl.style.display = "none";
        }

        textEl.textContent = raw;
    }

    function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
    }

    function positionTooltip(el) {
        const rect = el.getBoundingClientRect();

        // Primeiro abre "invisível" pra medir
        tooltip.style.left = "0px";
        tooltip.style.top = "0px";

        const tRect = tooltip.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        // tenta acima, se não couber vai abaixo
        let top = rect.top - tRect.height - OFFSET;
        let placement = "top";
        if (top < 8) {
            top = rect.bottom + OFFSET;
            placement = "bottom";
        }

        let left = rect.left + rect.width / 2 - tRect.width / 2;
        left = clamp(left, 8, vw - tRect.width - 8);

        // setinha
        const arrowSize = 10;
        const arrowLeft = clamp(
            rect.left + rect.width / 2 - left - arrowSize / 2,
            12,
            tRect.width - 22
        );

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${clamp(top, 8, vh - tRect.height - 8)}px`;

        // reset pseudo-element via CSS variables (simples e compatível)
        tooltip.style.setProperty("--lk-arrow-left", `${arrowLeft}px`);

        // posiciona a setinha
        tooltip.style.setProperty("--lk-after-top", placement === "top" ? "auto" : "-6px");
        tooltip.style.setProperty("--lk-after-bottom", placement === "top" ? "-6px" : "auto");

        // aplica direto no ::after usando atributos (fallback via inline style hack)
        tooltip.dataset.placement = placement;
    }

    // Ajuste do ::after conforme placement usando dataset
    const style = document.createElement("style");
    style.textContent = `
    .lk-tooltip::after{
      left: var(--lk-arrow-left, 50%);
      top: var(--lk-after-top, auto);
      bottom: var(--lk-after-bottom, -6px);
    }
    .lk-tooltip[data-placement="bottom"]::after{
      bottom: auto;
      top: -6px;
      transform: rotate(225deg);
    }
  `;
    document.head.appendChild(style);

    function openTooltip(el, byClick = false) {
        if (!el) return;

        activeEl = el;
        openByClick = byClick;

        setContent(el);
        tooltip.setAttribute("data-open", "true");

        // acessibilidade: associa tooltip ao botão
        const id = "lk-tooltip";
        tooltip.id = id;
        el.setAttribute("aria-describedby", id);

        // precisa do frame pra medir corretamente
        requestAnimationFrame(() => {
            tooltip.dataset.placement = "top";
            positionTooltip(el);

            // se o tooltip ficou embaixo, marca placement
            const rect = el.getBoundingClientRect();
            const tRect = tooltip.getBoundingClientRect();
            if (tRect.top > rect.bottom) tooltip.dataset.placement = "bottom";
        });
    }

    function closeTooltip() {
        if (activeEl) activeEl.removeAttribute("aria-describedby");
        activeEl = null;
        openByClick = false;
        tooltip.setAttribute("data-open", "false");
    }

    function onEnter(e) {
        const el = e.target.closest(SELECTOR);
        if (!el) return;
        if (isTouchDevice()) return; // no touch, não abre por hover
        if (openByClick) return;     // se abriu por click, não mexe
        openTooltip(el, false);
    }

    function onLeave(e) {
        if (isTouchDevice()) return;
        if (openByClick) return;
        const to = e.relatedTarget;
        // se o mouse foi pro tooltip, não fecha
        if (to && (to === tooltip || tooltip.contains(to))) return;
        closeTooltip();
    }

    function onClick(e) {
        const el = e.target.closest(SELECTOR);

        // click fora fecha (quando abriu por click)
        if (!el) {
            if (openByClick) closeTooltip();
            return;
        }

        // alterna
        if (activeEl === el && openByClick) {
            closeTooltip();
        } else {
            openTooltip(el, true);
        }
    }

    function onKeyDown(e) {
        if (e.key === "Escape") closeTooltip();
    }

    function onScrollOrResize() {
        if (!activeEl) return;
        positionTooltip(activeEl);
    }

    document.addEventListener("pointerenter", onEnter, true);
    document.addEventListener("pointerleave", onLeave, true);
    document.addEventListener("click", onClick);
    document.addEventListener("keydown", onKeyDown);
    window.addEventListener("scroll", onScrollOrResize, true);
    window.addEventListener("resize", onScrollOrResize);
})();
