console.log("dYs? JS da landing carregado!");

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

  /*------- Modal / Galeria -------*/
  (function setupGallery() {
    const modalId = "func-gallery";
    const modal = document.getElementById(modalId);

    if (!modal) return;

    const openButtons = document.querySelectorAll('[data-open="' + modalId + '"]');
    const closeButtons = modal.querySelectorAll('[data-close="' + modalId + '"]');
    const track = modal.querySelector(".lk-gallery-track");
    const slides = track ? track.querySelectorAll("img") : [];
    const prevBtn = modal.querySelector(".lk-gallery-prev");
    const nextBtn = modal.querySelector(".lk-gallery-next");

    let currentIndex = 0;

    function openModal() {
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
      document.body.style.overflow = "hidden";

      requestAnimationFrame(updateGallery);
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
      document.body.style.overflow = "";
    }

    openButtons.forEach((btn) => btn.addEventListener("click", openModal));
    closeButtons.forEach((btn) => btn.addEventListener("click", closeModal));

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal.classList.contains("is-open")) {
        closeModal();
      }
    });

    function updateGallery() {
      if (!track || slides.length === 0) return;
      const width = slides[0].clientWidth;
      track.style.transform = `translateX(-${currentIndex * width}px)`;
    }

    if (prevBtn) {
      prevBtn.addEventListener("click", () => {
        if (slides.length === 0) return;
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        updateGallery();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        if (slides.length === 0) return;
        currentIndex = (currentIndex + 1) % slides.length;
        updateGallery();
      });
    }

    updateGallery();
    window.addEventListener("resize", updateGallery);
  })();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initLandingScripts);
} else {
  initLandingScripts();
}
