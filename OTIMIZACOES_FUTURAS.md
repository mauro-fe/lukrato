# Plano de Otimizações Futuras 🚀

## 1. Imagens Responsivas (Próximas Fases)

### Implementar `<picture>` e `srcset`

```html
<!-- Para o mockup principal -->
<picture>
  <source
    media="(min-width: 1024px)"
    srcset="
      /assets/img/mockups/mockup-lg.png    1x,
      /assets/img/mockups/mockup-lg@2x.png 2x
    "
  />
  <source
    media="(min-width: 768px)"
    srcset="
      /assets/img/mockups/mockup-md.png    1x,
      /assets/img/mockups/mockup-md@2x.png 2x
    "
  />
  <source
    media="(max-width: 767px)"
    srcset="
      /assets/img/mockups/mockup-sm.png    1x,
      /assets/img/mockups/mockup-sm@2x.png 2x
    "
  />
  <img
    src="/assets/img/mockups/mockup.png"
    alt="Dashboard do Lukrato no computador"
    loading="lazy"
    decoding="async"
  />
</picture>
```

### WebP com Fallback

```html
<picture>
  <source srcset="/assets/img/mockups/mockup.webp" type="image/webp" />
  <source srcset="/assets/img/mockups/mockup.png" type="image/png" />
  <img src="/assets/img/mockups/mockup.png" alt="..." loading="lazy" />
</picture>
```

### Otimizar Imagens

```bash
# Usar ferramentas como:
# - ImageOptim (Mac)
# - OptiPNG / JPEGtran (Linux/Windows)
# - Squoosh (Google, web-based)

# Exemplo com ImageMagick:
convert mockup.png -quality 85 -strip mockup-optimized.png
cwebp mockup.png -o mockup.webp -q 85
```

---

## 2. CSS Avançado

### CSS Containment para Performance

```css
.lk-benefit-card {
  contain: layout style paint;
}

.lk-plans-grid {
  contain: layout style paint;
}
```

### CSS Subgrid (quando suportado)

```css
.lk-benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.lk-benefit-card {
  display: grid;
  grid-template-columns: subgrid;
}
```

### Dark Mode Support

```css
@media (prefers-color-scheme: dark) {
  body {
    background: #0f172a;
    color: #f0f5ff;
  }

  .lk-benefit-card {
    background: rgba(30, 41, 59, 0.7);
    border-color: rgba(226, 232, 240, 0.1);
  }
}
```

### High Contrast Mode

```css
@media (prefers-contrast: more) {
  .lk-btn-primary {
    border: 2px solid currentColor;
  }

  a {
    text-decoration: underline;
  }
}
```

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## 3. JavaScript Enhancements

### Lazy Loading Avançado

```javascript
// Usar Intersection Observer para melhor performance
const imageObserver = new IntersectionObserver(
  (entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        img.classList.add("loaded");
        observer.unobserve(img);
      }
    });
  },
  {
    rootMargin: "50px",
  }
);

document.querySelectorAll("img[data-src]").forEach((img) => {
  imageObserver.observe(img);
});
```

### Smooth Scroll com JS (Fallback)

```javascript
// Para navegadores que não suportam scroll-behavior: smooth
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      target.scrollIntoView({ behavior: "smooth" });
    }
  });
});
```

### Form Validation Avançada

```javascript
class FormValidator {
  constructor(formSelector) {
    this.form = document.querySelector(formSelector);
    this.fields = this.form.querySelectorAll("input, textarea");
    this.init();
  }

  init() {
    this.fields.forEach((field) => {
      field.addEventListener("blur", () => this.validate(field));
      field.addEventListener("input", () => this.clearError(field));
    });
  }

  validate(field) {
    let isValid = true;

    if (field.required && !field.value.trim()) {
      this.showError(field, "Campo obrigatório");
      isValid = false;
    }

    if (field.type === "email" && field.value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(field.value)) {
        this.showError(field, "Email inválido");
        isValid = false;
      }
    }

    return isValid;
  }

  showError(field, message) {
    field.classList.add("is-invalid");
    field.setAttribute("aria-invalid", "true");

    let errorElement = field.parentElement.querySelector(".error-message");
    if (!errorElement) {
      errorElement = document.createElement("span");
      errorElement.className = "error-message";
      field.parentElement.appendChild(errorElement);
    }
    errorElement.textContent = message;
  }

  clearError(field) {
    field.classList.remove("is-invalid");
    field.setAttribute("aria-invalid", "false");
    const errorElement = field.parentElement.querySelector(".error-message");
    if (errorElement) errorElement.remove();
  }
}

// Usar
const validator = new FormValidator("#contactForm");
```

### Analytics de Interação

```javascript
// Rastrear cliques em CTA
document.querySelectorAll(".lk-btn-primary").forEach((btn) => {
  btn.addEventListener("click", (e) => {
    const sectionName = e.target.closest("section")?.id || "unknown";
    console.log(`CTA clicked in ${sectionName}`);
    // Enviar para analytics
  });
});

// Rastrear visibilidade de seções
const sectionObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      console.log(`Section visible: ${entry.target.id}`);
    }
  });
});

document.querySelectorAll("section[id]").forEach((section) => {
  sectionObserver.observe(section);
});
```

---

## 4. Core Web Vitals Otimizações

### LCP (Largest Contentful Paint) < 2.5s

```css
/* Preload fonts críticas */
@font-face {
  font-family: "Primary Font";
  src: url("/fonts/primary.woff2") format("woff2");
  font-display: swap;
}

/* Evitar layout shifts */
.lk-device-card img {
  width: 100%;
  height: auto;
  aspect-ratio: 16 / 9;
}
```

### FID (First Input Delay) < 100ms

```javascript
// Usar event delegation para menos listeners
document.addEventListener("click", (e) => {
  if (e.target.closest(".lk-btn-primary")) {
    handleButtonClick(e.target);
  }
});

// Debounce heavy operations
function debounce(func, delay) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, delay);
  };
}

window.addEventListener(
  "resize",
  debounce(() => {
    // Atualizar layout
  }, 250)
);
```

### CLS (Cumulative Layout Shift) < 0.1

```css
/* Evitar layout shifts */
.lk-section {
  padding: 80px 0;
  /* Não mudar padding em media queries */
}

/* Use aspect-ratio para imagens */
.lk-device-card img {
  aspect-ratio: 580 / 400;
}

/* Reserve espaço para fonts */
@font-face {
  font-family: "Primary";
  src: url("/fonts/primary.woff2");
  font-display: swap;
}

/* Não adicione borders que movem elementos */
.lk-btn-primary {
  border: 2px solid transparent; /* Reservar espaço */
}
```

---

## 5. Bundle & Minificação

### CSS Minificação

```bash
# Usar cssnano
npm install -g cssnano

# Minificar
cssnano landing-base.css -o landing-base.min.css

# Resultado esperado: -30% do tamanho original
```

### Purge CSS (Remover CSS não usado)

```bash
npm install -D purgecss

# Configurar purgecss.config.js
module.exports = {
    content: ['views/**/*.php'],
    css: ['public/assets/css/site/landing-base.css'],
    output: 'public/assets/css/site/landing-base.purged.css'
};
```

### Compress Files

```bash
# Gzip compression
gzip -k landing-base.css
# landing-base.css.gz (~30% do original)

# Brotli (melhor que Gzip)
brotli landing-base.css
# landing-base.css.br (~20% do original)
```

---

## 6. Server-Side Otimizações

### HTTP Headers

```apache
# .htaccess

# Enable Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript
</IfModule>

# Cache Control
<FilesMatch "\.(jpg|jpeg|png|gif|svg|css|js)$">
    Header set Cache-Control "max-age=31536000, public"
</FilesMatch>

# GZIP Encoding
AddEncoding gzip .gz

# Security Headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
```

### PHP Output Buffering

```php
<!-- Na seção de layout/header -->
<?php
ob_start();
ob_implicit_flush(false);
?>

<!-- ... HTML content ... -->

<?php
$output = ob_get_clean();
// Pode minificar aqui se necessário
echo $output;
?>
```

---

## 7. Monitoramento & Analytics

### Implementar Web Vitals Tracking

```javascript
import { getCLS, getFID, getFCP, getLCP, getTTFB } from "web-vitals";

getCLS(console.log);
getFID(console.log);
getFCP(console.log);
getLCP(console.log);
getTTFB(console.log);
```

### Google Analytics 4

```html
<!-- Add GA4 script -->
<script
  async
  src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"
></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag() {
    dataLayer.push(arguments);
  }
  gtag("js", new Date());
  gtag("config", "GA_MEASUREMENT_ID");
</script>
```

### Custom Events

```javascript
// Track CTA clicks
gtag("event", "cta_click", {
  section: "features",
  button_text: "Começar grátis",
});

// Track form submissions
gtag("event", "form_submit", {
  form_name: "contact_form",
});
```

---

## 8. SEO Enhancements

### Schema Markup (Structured Data)

```html
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "Lukrato",
    "description": "Sistema de controle financeiro pessoal",
    "url": "https://lukrato.com",
    "offers": {
      "@type": "Offer",
      "price": "0.00",
      "priceCurrency": "BRL"
    }
  }
</script>
```

### Open Graph Meta Tags

```html
<meta property="og:title" content="Lukrato - Controle Financeiro Pessoal" />
<meta property="og:description" content="..." />
<meta property="og:image" content="/assets/img/og-image.png" />
<meta property="og:url" content="https://lukrato.com" />
```

### Twitter Card

```html
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Lukrato" />
<meta name="twitter:description" content="..." />
<meta name="twitter:image" content="/assets/img/twitter-image.png" />
```

---

## 9. Roadmap de Implementação

### Phase 1 (Sprint 1-2)

- [ ] Implementar picture/srcset para imagens
- [ ] Adicionar dark mode support
- [ ] Minificar CSS e JS
- [ ] Configurar caching headers

### Phase 2 (Sprint 3-4)

- [ ] Implementar Service Worker
- [ ] Adicionar PWA manifest
- [ ] Otimizar fonts (WOFF2)
- [ ] Implementar analytics

### Phase 3 (Sprint 5+)

- [ ] A/B testing de CTA
- [ ] Dynamic content loading
- [ ] Multi-language support
- [ ] Advanced SEO

---

## 10. Métricas Alvo (2025)

| Métrica                | Atual | Alvo   |
| ---------------------- | ----- | ------ |
| Lighthouse Performance | 85    | 95+    |
| Core Web Vitals        | Good  | Good   |
| LCP                    | 2.2s  | < 1.8s |
| FID                    | 80ms  | < 50ms |
| CLS                    | 0.05  | < 0.05 |
| Accessibility          | 95    | 98+    |
| SEO                    | 92    | 98+    |

---

**Documento**: Plano de Otimizações Futuras  
**Versão**: 1.0.0  
**Data**: 16 de Dezembro de 2025  
**Status**: Em planejamento 📋
