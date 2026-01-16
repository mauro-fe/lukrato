
/*-------URLS------------------------*/

(function () {
  const basePath = "<?= rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/') ?>/";
  const slugs = ["funcionalidades", "beneficios", "planos", "contato"];

  function headerOffset() {
    const h = document.querySelector("header, .lk-site-header");
    return h ? h.offsetHeight + 10 : 0;
  }

  function scrollToSlug(slug) {
    const el = document.getElementById(slug);
    if (!el) return;

    const y = el.getBoundingClientRect().top + window.pageYOffset - headerOffset();
    window.scrollTo({ top: y, behavior: "smooth" });
  }

  function getSlugFromPath(pathname) {
    if (!pathname.startsWith(basePath)) return null;

    const rest = pathname.slice(basePath.length).replace(/^\/+/, "");
    const slug = rest.split("/")[0];
    return slugs.includes(slug) ? slug : null;
  }

  // 🔹 Ao carregar a página diretamente (/planos)
  document.addEventListener("DOMContentLoaded", () => {
    const slug = getSlugFromPath(location.pathname);
    if (!slug) return;

    history.replaceState(null, "", basePath + slug);
    requestAnimationFrame(() => scrollToSlug(slug));
  });

  // 🔹 Clique no menu (sem #)
  document.addEventListener("click", (e) => {
    const link = e.target.closest("a[href]");
    if (!link) return;

    let href = link.getAttribute("href");
    if (!href) return;

    // Normaliza URL absoluta ou relativa
    try {
      href = new URL(href, location.origin).pathname;
    } catch {
      return;
    }

    const slug = getSlugFromPath(href);
    if (!slug) return;

    e.preventDefault();
    history.pushState(null, "", basePath + slug);
    scrollToSlug(slug);
  });

  // 🔹 Back / forward
  window.addEventListener("popstate", () => {
    const slug = getSlugFromPath(location.pathname);
    if (slug) scrollToSlug(slug);
  });
})();


function initLandingScripts() {
  if (window.lkLandingBootstrapped) return;
  window.lkLandingBootstrapped = true;

  /*------- Menu Hamburguer -------*/
  (function setupMenu() {
    const burger = document.querySelector(".lk-site-burger");
    const header = document.querySelector(".lk-site-header");
    const body = document.body;

    if (!burger || !header) return;

    // Backdrop para fechar ao tocar fora
    const overlay = document.createElement("div");
    overlay.className = "lk-site-menu-overlay";
    header.appendChild(overlay);

    function closeMenu() {
      header.classList.remove("is-open");
      body.classList.remove("lk-nav-open");
      burger.setAttribute("aria-expanded", "false");
    }

    function toggleMenu() {
      const willOpen = !header.classList.contains("is-open");
      if (willOpen) {
        header.classList.add("is-open");
        body.classList.add("lk-nav-open");
      } else {
        header.classList.remove("is-open");
        body.classList.remove("lk-nav-open");
      }
      burger.setAttribute("aria-expanded", willOpen ? "true" : "false");
    }

    burger.setAttribute("aria-expanded", "false");
    burger.addEventListener("click", toggleMenu);
    overlay.addEventListener("click", closeMenu);

    // Fecha se voltar para desktop
    window.addEventListener("resize", () => {
      if (window.innerWidth > 768 && header.classList.contains("is-open")) {
        closeMenu();
      }
    });

    // Fecha ao clicar em link
    header.addEventListener("click", (e) => {
      const link = e.target.closest(".lk-site-nav-link");
      if (link && header.classList.contains("is-open")) {
        closeMenu();
      }
    });

    // Fecha com ESC
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && header.classList.contains("is-open")) {
        closeMenu();
      }
    });
  })();
}

/*------- Modal / Galeria -------*/
(function setupGallery() {
  const modalId = "func-gallery";
  const modal = document.getElementById(modalId);
  if (!modal) return;

  const openButtons = document.querySelectorAll(`[data-open="${modalId}"]`);
  const closeButtons = modal.querySelectorAll(`[data-close="${modalId}"]`);

  const track = modal.querySelector(".lk-gallery-track");
  const slides = track ? Array.from(track.querySelectorAll("img")) : [];

  const prevBtn = modal.querySelector(".lk-gallery-prev");
  const nextBtn = modal.querySelector(".lk-gallery-next");

  const titleEl = modal.querySelector("#lkGalleryTitle");
  const descEl = modal.querySelector("#lkGalleryDesc");
  const countEl = modal.querySelector("#lkGalleryCount");

  const gallery = modal.querySelector(".lk-gallery");

  let currentIndex = 0;
  let startX = 0;
  let isDragging = false;

  function openModal() {
    modal.setAttribute("aria-hidden", "false");
    modal.classList.add("is-open");
    document.body.style.overflow = "hidden";
    requestAnimationFrame(() => {
      updateGallery(true);
    });
  }

  function closeModal() {
    modal.setAttribute("aria-hidden", "true");
    modal.classList.remove("is-open");
    document.body.style.overflow = "";
  }

  function updateMeta() {
    if (!slides.length) return;

    const s = slides[currentIndex];
    const t = s.dataset.title || s.alt || "Tela";
    const d = s.dataset.desc || "";

    if (titleEl) titleEl.textContent = t;
    if (descEl) descEl.textContent = d;
    if (countEl) countEl.textContent = `${currentIndex + 1}/${slides.length}`;
  }

  function updateGallery(skipAnim = false) {
    if (!track || slides.length === 0) return;

    const width = slides[0].clientWidth;
    if (skipAnim) track.style.transition = "none";
    track.style.transform = `translateX(-${currentIndex * width}px)`;
    updateMeta();

    if (skipAnim) {
      requestAnimationFrame(() => {
        track.style.transition = "transform 0.28s ease";
      });
    }
  }

  function goNext() {
    if (!slides.length) return;
    currentIndex = (currentIndex + 1) % slides.length;
    updateGallery();
  }

  function goPrev() {
    if (!slides.length) return;
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    updateGallery();
  }

  openButtons.forEach(btn => btn.addEventListener("click", openModal));
  closeButtons.forEach(btn => btn.addEventListener("click", closeModal));

  if (prevBtn) prevBtn.addEventListener("click", goPrev);
  if (nextBtn) nextBtn.addEventListener("click", goNext);

  // Clique na imagem para avançar
  slides.forEach(img => img.addEventListener("click", goNext));

  // Teclado
  document.addEventListener("keydown", (e) => {
    if (!modal.classList.contains("is-open")) return;

    if (e.key === "Escape") closeModal();
    if (e.key === "ArrowRight") goNext();
    if (e.key === "ArrowLeft") goPrev();
  });

  // Swipe (touch)
  if (gallery) {
    gallery.addEventListener("touchstart", (e) => {
      if (!modal.classList.contains("is-open")) return;
      startX = e.touches[0].clientX;
      isDragging = true;
    }, { passive: true });

    gallery.addEventListener("touchmove", (e) => {
      if (!isDragging) return;
    }, { passive: true });

    gallery.addEventListener("touchend", (e) => {
      if (!isDragging) return;
      const endX = e.changedTouches[0].clientX;
      const diff = endX - startX;

      // threshold
      if (Math.abs(diff) > 45) {
        if (diff < 0) goNext();
        else goPrev();
      }

      isDragging = false;
    });
  }

  // Resize
  window.addEventListener("resize", () => updateGallery(true));

  // Inicializa meta
  updateMeta();
})();

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initLandingScripts);
} else {
  initLandingScripts();
}

/*---------Seção Contato------------*/

(() => {
  const root = document.querySelector('#contato.lk-contact');
  if (!root) return;

  const buttons = root.querySelectorAll('.lk-toggle-btn');
  const panels = root.querySelectorAll('.lk-contact-panel');

  function show(target) {
    // ativa botão
    buttons.forEach(btn => {
      const active = btn.dataset.target === target;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    // ativa painel
    panels.forEach(p => {
      p.classList.toggle('is-active', p.dataset.panel === target);
    });
  }

  // clique
  buttons.forEach(btn => {
    btn.addEventListener('click', () => show(btn.dataset.target));
  });

  // default: whatsapp
  show('whatsapp');
})();

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('contactForm');
  const whatsappInput = document.getElementById('whatsapp');

  if (!form) return;

  // Evita envio duplicado
  let sending = false;

  // URL da API (respeita BASE_URL do PHP)
  const apiUrl =
    (window.APP_BASE_URL ? `${window.APP_BASE_URL}/api/contato/enviar` : 'http://localhost/lukrato/public/api/contato/enviar');

  /* ===============================
   * Máscara de WhatsApp (BR)
   * =============================== */
  if (whatsappInput) {
    const formatPhone = (digits) => {
      // 10 ou 11 dígitos: (44) 99999-9999 ou (44) 9999-9999
      if (digits.length <= 2) return `(${digits}`;
      if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
      if (digits.length <= 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
      }
      return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7, 11)}`;
    };

    whatsappInput.addEventListener('input', (e) => {
      const digits = e.target.value.replace(/\D/g, '').slice(0, 11);
      e.target.value = formatPhone(digits);
    });
  }

  /* ===============================
   * Submit (ÚNICO)
   * =============================== */
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (sending) return;

    sending = true;

    // (opcional) desabilita botão submit enquanto envia
    const submitBtn = form.querySelector('[type="submit"]');
    const oldBtnText = submitBtn ? submitBtn.textContent : null;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Enviando...';
    }

    try {
      const res = await fetch(apiUrl, {
        method: 'POST',
        body: new FormData(form),
      });

      // tenta ler como JSON, mas sem quebrar caso venha texto/html
      const raw = await res.text();
      let payload = null;
      try { payload = JSON.parse(raw); } catch (_) { }

      // Se seu Response::success retorna {status:"success", data:{...}}
      const okByStatus = payload?.status === 'success';
      const okBySuccess = payload?.success === true;
      const message =
        payload?.message ??
        payload?.data?.message ??
        'Mensagem enviada com sucesso.';

      if (res.ok && (okByStatus || okBySuccess)) {
        await Swal.fire({
          icon: 'success',
          title: 'Mensagem enviada! ',
          text: 'message',
          confirmButtonText: 'Ok',
          confirmButtonColor: '#e67e22'
        });
        form.reset();
        return;
      }

      // Erro de validação (422) ou outro
      const errorMsg =
        payload?.message ??
        payload?.data?.message ??
        `Erro ao enviar (status ${res.status}).`;

      await Swal.fire({
        icon: res.status === 422 ? 'warning' : 'error',
        title: res.status === 422 ? 'Verifique os campos' : 'Não foi possível enviar',
        text: errorMsg,
        confirmButtonColor: '#e67e22'
      });

      // Se quiser, loga o retorno bruto pra depurar
      console.log('Contato API STATUS:', res.status);
      console.log('Contato API BODY:', raw);

    } catch (err) {
      console.error(err);
      await Swal.fire({
        icon: 'error',
        title: 'Erro de conexão',
        text: 'Não foi possível enviar sua mensagem agora. Tente novamente.',
        confirmButtonColor: '#e67e22'
      });
    } finally {
      sending = false;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = oldBtnText ?? 'Enviar';
      }
    }
  });
});

/* ------- Back to Top ------- */
(function setupBackToTop() {
  const btn = document.getElementById('lkBackToTop');
  if (!btn) return;

  // Quanto rolar pra aparecer (ajuste se quiser)
  const SHOW_AFTER = 420;

  // Throttle simples pra performance
  let ticking = false;

  function update() {
    const y = window.scrollY || document.documentElement.scrollTop || 0;

    if (y > SHOW_AFTER) btn.classList.add('is-visible');
    else btn.classList.remove('is-visible');

    ticking = false;
  }

  window.addEventListener('scroll', () => {
    if (!ticking) {
      window.requestAnimationFrame(update);
      ticking = true;
    }
  }, { passive: true });

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // Estado inicial (caso carregue já no meio da página)
  update();
})();
