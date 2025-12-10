console.log("🚀 JS da landing carregado!");

document.addEventListener("DOMContentLoaded", function () {
    const burger = document.querySelector(".lk-site-burger");
    const header = document.querySelector(".lk-site-header");

    if (!burger || !header) return;

    burger.addEventListener("click", function () {
        header.classList.toggle("is-open");
    });

    // Opcional: fecha o menu ao clicar em um link
    header.addEventListener("click", function (e) {
        const link = e.target.closest(".lk-site-nav-link");
        if (link && header.classList.contains("is-open")) {
            header.classList.remove("is-open");
        }
    });
});

