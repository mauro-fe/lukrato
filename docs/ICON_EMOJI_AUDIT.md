# Lukrato - Comprehensive Icon & Emoji Audit

**Generated:** 2026-02-20  
**Purpose:** Complete migration reference for Font Awesome icons and emoji usage

---

## TABLE OF CONTENTS
1. [Font Awesome CDN Links](#1-font-awesome-cdn-links)
2. [Font Awesome Icons by File](#2-font-awesome-icons-by-file)
3. [Emoji Usage by File](#3-emoji-usage-by-file)
4. [Icon-Related PHP Methods/Enums](#4-icon-related-php-methodsenums)
5. [CSS FA References](#5-css-fa-references)
6. [Summary Statistics](#6-summary-statistics)

---

## 1. Font Awesome CDN Links

| File | Line | Version | URL |
|------|------|---------|-----|
| views/admin/admins/login.php | 24 | 6.7.1 | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css` |
| views/admin/partials/header.php | 52 | 6.7.1 | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css` |
| views/admin/onboarding/index.php | 16 | 6.7.1 | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css` |
| views/admin/onboarding/lancamento.php | 16 | 6.7.1 | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css` |
| views/admin/admins/forgot_password.php | 18 | 6.7.1 | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css` |
| views/admin/admins/reset_password.php | 17 | 6.7.1 | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css` |
| views/site/partials/header.php | 180 | **6.5.2** | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css` |
| views/site/partials/header.php.bak | 54 | **6.5.2** | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css` |
| views/site/card/index.php | 11 | **6.4.0** | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css` |

> ⚠️ **VERSION INCONSISTENCY**: Three different FA versions in use (6.4.0, 6.5.2, 6.7.1)

---

## 2. Font Awesome Icons by File

### views/admin/admins/login.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 242 | `fa-solid fa-eye` | eye | Password toggle |
| 305 | `fa-solid fa-eye` | eye | Password toggle |
| 332 | `fa-solid fa-eye` | eye | Password toggle |
| 335 | `fas fa-check` | check | Match validation icon |
| 344 | `fa-solid fa-gift` | gift | Referral icon |
| 462 | `fas fa-check` / `fas fa-xmark` | check/xmark | Password validation (JS) |
| 550 | `fa-solid fa-check` | check | Referral success (JS) |
| 614 | `fa-eye-slash` | eye-slash | Password toggle (JS classList) |

### views/admin/dashboard/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 316 | `fas fa-times` | times | Close button |
| 436 | `fas fa-check` | check | Checklist item |
| 438 | `fas {item.icon}` | dynamic | Onboarding checklist icon |
| 444 | `fas fa-chevron-right` | chevron-right | Checklist arrow |
| 481 | `fas fa-trophy` | trophy | Gamification trophy |
| 484 | `fas fa-gem` | gem | PRO badge |
| 488 | `fas fa-star` | star | Level star |
| 500 | `fas fa-shield-alt` | shield-alt | Security badge |
| 520 | `fas fa-medal` | medal | Medal badge |
| 560 | `fas fa-rocket` | rocket | PRO CTA |
| 567 | `fas fa-gem` | gem | PRO badge |
| 577 | `fas fa-trash-alt` | trash-alt | Delete (JS) |
| 580 | `fas fa-wallet` | wallet | Balance card |
| 592 | `fas fa-arrow-up` | arrow-up | Income card |
| 604 | `fas fa-arrow-down` | arrow-down | Expense card |
| 616 | `fas fa-balance-scale` | balance-scale | Balance indicator |
| 628 | `fas fa-calendar-check` | calendar-check | Provisioning |
| 636 | `fas fa-exclamation-triangle` | exclamation-triangle | Alert |
| 641,650,659,695 | `fas fa-arrow-right` | arrow-right | Navigation links |
| 645 | `fas fa-info-circle` | info-circle | Info alert |
| 654 | `fas fa-credit-card` | credit-card | Card alert |
| 666 | `fas fa-arrow-up` | arrow-up | Income provisioning |
| 674 | `fas fa-arrow-down` | arrow-down | Expense provisioning |
| 682 | `fas fa-chart-line` | chart-line | Trend chart |
| 694 | `fas fa-clock` | clock | Next due dates |
| 699 | `fas fa-check-circle` | check-circle | Success state |
| 707 | `fas fa-layer-group` | layer-group | Installments |
| 716 | `fas fa-gem` | gem | PRO |
| 721 | `fas fa-rocket` | rocket | PRO CTA |
| 742 | `fas fa-receipt` | receipt | Receipt |

### views/admin/partials/header.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 318 | `fa fa-university` | university | Accounts nav (legacy FA4) |
| 325 | `fa-regular fa-credit-card` | credit-card | Cards nav |
| 332 | `fa-solid fa-file-invoice` | file-invoice | Invoices nav |
| 346 | `fa-solid fa-layer-group` | layer-group | Installments nav |
| 354 | `fa fa-pie-chart` | pie-chart | Budget nav (legacy FA4) |
| 382 | `fa fa-line-chart` | line-chart | Reports nav (legacy FA4) |
| 389 | `fas fa-user-circle` | user-circle | Profile nav |
| 395 | `fa-solid fa-user-shield` | user-shield | Admin link |
| 402 | `fas fa-sign-out-alt` | sign-out-alt | Logout |
| 410 | `fa-solid fa-star` | star | Rating |
| 420 | `fas fa-angle-left` | angle-left | Sidebar toggle |

> ⚠️ **LEGACY FA4 CLASSES**: Lines 318, 354, 382 use `fa fa-*` (Font Awesome 4 syntax)

### views/admin/partials/top-navbar.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 22 | `fa-wallet`, `fa-credit-card`, `fa-chart-bar` | various | Breadcrumb config |
| 39 | `fa fa-bars` | bars | Mobile menu (legacy FA4) |
| 54,69 | `fas fa-chevron-right` | chevron-right | Breadcrumb separator |
| 83 | `fa-solid fa-crown`/`fa-leaf` | crown/leaf | Plan badge (dynamic) |
| 91 | `fa-solid fa-crown` | crown | PRO badge |
| 99 | `fa-solid fa-sun` | sun | Theme toggle (light) |
| 100 | `fa-solid fa-moon` | moon | Theme toggle (dark) |
| 110 | `fa-solid fa-right-from-bracket` | right-from-bracket | Logout |

### views/admin/partials/header_mes.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 425,443 | `fas fa-chevron-left` | chevron-left | Month navigation |
| 430,448 | `fas fa-chevron-down` | chevron-down | Month dropdown |
| 438,451 | `fas fa-chevron-right` | chevron-right | Month navigation |

### views/admin/partials/footer.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 6 | `fas fa-arrow-up` | arrow-up | Back to top |

### views/admin/partials/botao_suporte.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 641 | `fas fa-paper-plane` | paper-plane | Send message button |
| 816 | `fas fa-lightbulb` | lightbulb | Solutions header |

### views/admin/partials/modals/modal_lancamento_v2.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 8 | `fas fa-exchange-alt` | exchange-alt | Transfer icon |
| 57 | `fas fa-arrow-down` | arrow-down | Income type |
| 68 | `fas fa-arrow-up` | arrow-up | Expense type |
| 79 | `fas fa-exchange-alt` | exchange-alt | Transfer type |
| 90 | `fas fa-calendar-plus` | calendar-plus | Schedule type |
| 103 | `fas fa-arrow-left` | arrow-left | Back button |
| 114 | `fas fa-align-left` | align-left | Description |
| 124 | `fas fa-dollar-sign` | dollar-sign | Amount |
| 137,145,223,239,326,350,375,426 | `fas fa-chevron-down` | chevron-down | Select dropdown icons |
| 159 | `fa-brands fa-pix` | pix | PIX payment |
| 163,167 | `fa-solid fa-credit-card` | credit-card | Card payment |
| 171 | `fa-solid fa-money-bill-wave` | money-bill-wave | Cash payment |
| 175 | `fa-solid fa-barcode` | barcode | Boleto payment |
| 185 | `fas fa-hand-holding-usd` | hand-holding-usd | Income form |
| 190 | `fa-brands fa-pix` | pix | PIX (income) |
| 194 | `fa-solid fa-building-columns` | building-columns | Bank transfer |
| 198 | `fa-solid fa-money-bill-wave` | money-bill-wave | Cash |
| 202 | `fa-solid fa-arrow-right-arrow-left` | arrow-right-arrow-left | Transfer |
| 206 | `fa-solid fa-rotate-left` | rotate-left | Refund |
| 215,337 | `fas fa-credit-card` | credit-card | Card select |
| 231,251 | `fas fa-calendar-alt` | calendar-alt | Date |
| 261 | `fas fa-list-ol` | list-ol | Installments |
| 292 | `fas fa-arrow-down` | arrow-down | Income |
| 296 | `fas fa-arrow-up` | arrow-up | Expense |
| 315 | `fas fa-sync-alt` | sync-alt | Recurrence |
| 418 | `fa-solid fa-circle-info` | circle-info | Info tooltip |

### views/admin/partials/modals/modal_lancamento_global.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 8 | `fas fa-exchange-alt` | exchange-alt | Modal title |
| 31,167,259,274,345,363,392,443 | `fas fa-chevron-down` | chevron-down | Select arrows |
| 58 | `fas fa-arrow-down` | arrow-down | Income type |
| 69 | `fas fa-arrow-up` | arrow-up | Expense type |
| 80 | `fas fa-exchange-alt` | exchange-alt | Transfer type |
| 91 | `fas fa-calendar-plus` | calendar-plus | Schedule type |
| 104 | `fas fa-arrow-left` | arrow-left | Back button |
| 122 | `fas fa-arrow-down` | arrow-down | Income tab |
| 126 | `fas fa-arrow-up` | arrow-up | Expense tab |
| 135 | `fas fa-align-left` | align-left | Description |
| 145 | `fas fa-dollar-sign` | dollar-sign | Amount |
| 159 | `fas fa-exchange-alt` | exchange-alt | Payment method |
| 184,221 | `fa-brands fa-pix` | pix | PIX |
| 189,194 | `fa-solid fa-credit-card` | credit-card | Card |
| 199,231 | `fa-solid fa-money-bill-wave` | money-bill-wave | Cash |
| 204 | `fa-solid fa-barcode` | barcode | Boleto |
| 215 | `fas fa-hand-holding-usd` | hand-holding-usd | Income |
| 226 | `fa-solid fa-building-columns` | building-columns | Bank |
| 236 | `fa-solid fa-arrow-right-arrow-left` | arrow-right-arrow-left | Transfer |
| 241 | `fa-solid fa-rotate-left` | rotate-left | Refund |
| 250,430 | `fas fa-credit-card` | credit-card | Card |
| 267 | `fas fa-file-invoice-dollar` | file-invoice-dollar | Invoice |
| 287 | `fas fa-calendar-alt` | calendar-alt | Date |
| 297 | `fas fa-list-ol` | list-ol | Installments |
| 337 | `fa-solid fa-circle-info` | circle-info | Info |
| 352 | `fas fa-sync-alt` | sync-alt | Recurrence |

### views/admin/partials/modals/modal_cartoes.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 8 | `fas fa-credit-card` | credit-card | Modal title |
| 80 | `fas fa-money-bill-wave` | money-bill-wave | Limit |
| 96 | `fas fa-calendar-check` | calendar-check | Due date |
| 102 | `fa-solid fa-circle-info` | circle-info | Info tooltip |
| 114 | `fas fa-calendar-alt` | calendar-alt | Close date |

### views/admin/partials/modals/modal_contas.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 73 | `fas fa-dollar-sign` | dollar-sign | Balance |
| 525 | `fas fa-plus-circle` | plus-circle | Add account |

### views/admin/partials/modals/modal_agendamento.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 573 | `fas fa-calendar-plus` | calendar-plus | Schedule title |
| 603 | `fas fa-align-left` | align-left | Description |
| 622 | `fas fa-dollar-sign` | dollar-sign | Amount |
| 642 | `fas fa-calendar-alt` | calendar-alt | Date |
| 652 | `fas fa-sync-alt` | sync-alt | Recurrence |
| 668,684,965 | `fas fa-credit-card` | credit-card | Payment method |
| 706 | `fas fa-layer-group` | layer-group | Installments |
| 930 | `fas fa-check-circle` | check-circle | Success |

### views/admin/partials/modals/modal-detalhes-faturas.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 25 | `fas fa-credit-card` | credit-card | Card icon |
| 54 | `fas fa-check-double` | check-double | Paid status |
| 75 | `fas fa-hand-holding-usd` | hand-holding-usd | Pay button |
| 97 | `fas fa-arrow-left` | arrow-left | Back |
| 104 | `fas fa-money-bill-wave` | money-bill-wave | Payment |
| 130 | `fas fa-info-circle` | info-circle | Info |

### views/admin/partials/modals/modal_transacao_investimento.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 77 | `fa-solid fa-floppy-disk` | floppy-disk | Save button |

### views/admin/partials/modals/modal_investimentos.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 108 | `fa-solid fa-floppy-disk` | floppy-disk | Save button |

### views/admin/partials/modals/visualizar-lancamento.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 32 | `fas fa-info-circle` | info-circle | Info header |
| 92 | `fas fa-align-left` | align-left | Description header |
| 101 | `fas fa-layer-group` | layer-group | Installment header |

### views/admin/partials/modals/modal_meses.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 453 | `fas fa-chevron-left` | chevron-left | Month nav |
| 457 | `fas fa-chevron-right` | chevron-right | Month nav |

### views/admin/perfil/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 11 | `fa-solid fa-circle-info` | circle-info | Info banner |
| 90 | `fa-regular fa-copy` | copy | Copy support code |
| 294 | `fa-solid {dynamic}` | dynamic | Plan icon |
| 307 | `fa-solid fa-gear`/`fa-rocket` | gear/rocket | Plan action (dynamic) |
| 344,355 | `fa-solid fa-copy` | copy | Copy referral code |
| 391 | `fa-brands fa-whatsapp` | whatsapp | Share WhatsApp |
| 396 | `fa-brands fa-telegram` | telegram | Share Telegram |
| 401 | `fa-brands fa-instagram` | instagram | Share Instagram |
| 434 | `fas fa-trash-alt` | trash-alt | Delete account |
| 855 | `fa-solid fa-check` | check | Copy confirmation (JS) |

### views/admin/financas/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 64 | `fas fa-piggy-bank` | piggy-bank | Savings |
| 100 | `fas fa-flag-checkered` | flag-checkered | Goals |
| 131,173 | `fas fa-chart-pie` | chart-pie | Budget chart |
| 165,214,311,430 | `fas fa-spinner fa-spin` | spinner | Loading |
| 187 | `fas fa-lightbulb` | lightbulb | Insight |
| 201,228,421 | `fas fa-wand-magic-sparkles` | wand-magic-sparkles | AI/Templates |
| 258,452 | `fas fa-dollar-sign` | dollar-sign | Amount |
| 319 | `fas fa-check-double` | check-double | Apply all |
| 346 | `fas fa-info-circle` | info-circle | Hint |
| 442 | `fas fa-plus-circle` | plus-circle | Add contribution |

### views/admin/investimentos/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 19,93 | `fa-solid fa-plus` | plus | Add investment |
| 25 | `fa-solid fa-wallet` | wallet | Total invested |
| 33 | `fa-solid fa-arrow-trend-up` | arrow-trend-up | Returns |
| 41 | `fa-solid fa-briefcase` | briefcase | Portfolio |
| 390 | `fa-solid fa-chevron-down` | chevron-down | Dropdown |
| 394 | `fa-solid fa-cart-plus` | cart-plus | Buy |
| 398 | `fa-solid fa-hand-holding-dollar` | hand-holding-dollar | Withdraw |
| 402,445 | `fa-regular fa-pen-to-square` | pen-to-square | Edit |
| 406,448 | `fa-regular fa-trash-can` | trash-can | Delete |
| 439 | `fa-solid fa-cart-plus` | cart-plus | Buy (mobile) |
| 442 | `fa-solid fa-hand-holding-dollar` | hand-holding-dollar | Withdraw (mobile) |
| 453 | `fa-solid fa-chevron-right` | chevron-right | Expand |

### views/admin/relatorios/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 13 | `fas fa-arrow-trend-up` | arrow-trend-up | Income stats |
| 24 | `fas fa-arrow-trend-down` | arrow-trend-down | Expense stats |
| 35 | `fas fa-wallet` | wallet | Balance |
| 46,191 | `fas fa-credit-card` | credit-card | Card stats |
| 60 | `fas fa-lightbulb` | lightbulb | Insight |
| 81,176 | `fas fa-chart-line` | chart-line | Charts |
| 102 | `fas fa-file-export` | file-export | Export |
| 110,119 | `fas fa-crown` | crown | PRO |
| 131 | `fas fa-chart-bar` | chart-bar | Bar chart |
| 150 | `fas fa-file` | file | File type |
| 160 | `fas fa-download` | download | Download |
| 171,206 | `fas fa-chart-pie` | chart-pie | Pie chart |
| 181 | `fas fa-chart-column` | chart-column | Column chart |
| 186 | `fas fa-wallet` | wallet | Wallet |
| 196 | `fas fa-timeline` | timeline | Timeline |
| 201 | `fas fa-calendar-alt` | calendar-alt | Calendar |
| 229 | `fas fa-building-columns` | building-columns | Bank |

### views/admin/lancamentos/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 16 | `fas fa-file-export` | file-export | Export |
| 50,59 | `fas fa-calendar-alt` | calendar-alt | Date filter |
| 150 | `fas fa-list-ul` | list-ul | List view |
| 162 | `fas fa-trash-alt` | trash-alt | Bulk delete |
| 167 | `fas fa-sync-alt` | sync-alt | Refresh |
| 230,262 | `fas fa-chevron-left` | chevron-left | Pagination |
| 234,269 | `fas fa-chevron-right` | chevron-right | Pagination |
| 257 | `fas fa-angle-double-left` | angle-double-left | First page |
| 274 | `fas fa-angle-double-right` | angle-double-right | Last page |

### views/admin/faturas/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 12 | `fas fa-sliders-h` | sliders-h | Filters |
| 20,40,54,68,94 | `fas fa-chevron-down` | chevron-down | Select arrows |
| 29 | `fas fa-circle-check` | circle-check | Status filter |
| 47,159 | `fas fa-credit-card` | credit-card | Card |
| 75 | `fas fa-calendar-day` | calendar-day | Date filter |
| 121 | `fas fa-file-invoice-dollar` | file-invoice-dollar | Invoices icon |
| 137 | `fas fa-circle-notch fa-spin` | circle-notch | Loading |

### views/admin/parcelamentos/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 28 | `fas fa-toggle-on` | toggle-on | Toggle filter |
| 41 | `fas fa-credit-card` | credit-card | Card filter |
| 51 | `fas fa-calendar-alt` | calendar-alt | Date |
| 70 | `fas fa-circle-notch fa-spin` | circle-notch | Loading |
| 83 | `fas fa-credit-card` | credit-card | Empty state |

### views/admin/billing/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 1108 | `fa-solid fa-inbox` | inbox | Empty state |
| 1150 | `fa-solid fa-{dynamic}` | dynamic | Plan feature icon |
| 1157,1189 | `fa-solid fa-check` | check | Feature included |
| 1198 | `fa-solid fa-times` | times | Feature not included |
| 1207 | `fa-solid fa-circle-info` | circle-info | Feature info |
| 1220 | `fa-solid fa-clock` | clock | Coming soon |
| 1242,1280 | `fa-solid fa-sync-alt` | sync-alt | Change plan |
| 1250 | `fa-solid fa-exclamation-triangle` | exclamation-triangle | Alert |
| 1263 | `fa-solid fa-redo` | redo | Reactivate |
| 1270 | `fa-solid fa-lock` | lock | Locked |
| 1288 | `fa-solid fa-check-circle` | check-circle | Current plan |
| 1302 | `fa-solid fa-times-circle` | times-circle | Cancel |
| 1339 | `fa-solid fa-rocket` | rocket | Upgrade CTA |

### views/admin/billing/modal-pagamento.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 847 | `fa-solid fa-times` | times | Close modal |
| 866,1059,1134 | `fa-solid fa-clock` | clock | Pending |
| 896,1053,1125 | `fa-solid fa-copy` | copy | Copy code |
| 907 | `fa-solid fa-download` | download | Download |
| 911 | `fa-solid fa-copy` | copy | Copy |
| 915 | `fa-solid fa-times-circle` | times-circle | Cancel |
| 926 | `fa-solid fa-credit-card` | credit-card | Card tab |
| 930 | `fa-brands fa-pix` | pix | PIX tab |
| 934 | `fa-solid fa-barcode` | barcode | Boleto tab |
| 1011 | `fa-brands fa-pix` | pix | PIX section |
| 1016,1075 | `fa-solid fa-check-circle` | check-circle | Generated |
| 1070 | `fa-solid fa-barcode` | barcode | Boleto section |
| 1129 | `fa-solid fa-download` | download | Download boleto |
| 1146 | `fa-solid fa-lock` | lock | Secure payment |
| 1398 | `fa-brands fa-pix` (JS) | pix | Dynamic tab icon |
| 1402 | `fa-solid fa-barcode` (JS) | barcode | Dynamic tab icon |
| 1406 | `fa-solid fa-lock` (JS) | lock | Dynamic tab icon |
| 1695 | `fa-solid fa-spinner fa-spin` (JS) | spinner | Cancelling... |
| 1730 | `fa-solid fa-times-circle` (JS) | times-circle | Cancel button |

### views/admin/onboarding/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 405 | `fas fa-exclamation-circle` | exclamation-circle | Error |
| 454 | `fas fa-chevron-down` | chevron-down | Select |
| 475 | `fas fa-info-circle` | info-circle | Info |
| 483 | `fas fa-arrow-right` | arrow-right | Next |

### views/admin/onboarding/lancamento.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 393 | `fas fa-exclamation-circle` | exclamation-circle | Error |
| 442 | `fas fa-check-circle` | check-circle | Success |
| 450 | `fas fa-exchange-alt` | exchange-alt | Transfer |
| 456 | `fas fa-arrow-down` | arrow-down | Expense |
| 459 | `fas fa-arrow-up` | arrow-up | Income |
| 467 | `fas fa-dollar-sign` | dollar-sign | Amount |
| 501 | `fas fa-pencil-alt` | pencil-alt | Description |
| 515 | `fas fa-info-circle` | info-circle | Info |

### views/admin/admins/reset_password.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 613,628 | `fa-solid fa-eye` | eye | Password toggle |

### views/admin/gamification/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 125 | `fas fa-chart-line` | chart-line | Stats chart |

### views/admin/categorias/index.php
No FA icons (uses emoji instead)

### views/admin/contas/index.php
No FA icons directly (uses contas-manager.js)

### views/admin/agendamentos/index.php
No FA icons directly (uses admin-agendamentos-index.js)

---

### views/site/partials/header.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 515 | `fa-regular fa-user` | user | Login button |
| 523 | `fa-solid fa-arrow-right` | arrow-right | CTA button |
| 534 | `fa-solid fa-bars` | bars | Mobile menu open |
| 535 | `fa-solid fa-xmark` | xmark | Mobile menu close |
| 589 | `fa-solid fa-xmark` | xmark | Mobile close |
| 625 | `fa-regular fa-user` | user | Mobile login |
| 632 | `fa-solid fa-arrow-right` | arrow-right | Mobile CTA |

### views/site/partials/footer.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 31 | `fa-brands fa-instagram` | instagram | Social link |
| 36 | `fa-brands fa-tiktok` | tiktok | Social link |
| 41 | `fa-brands fa-facebook-f` | facebook-f | Social link |
| 46 | `fa-brands fa-linkedin-in` | linkedin-in | Social link |
| 106 | `fa-brands fa-whatsapp` | whatsapp | WhatsApp contact |
| 115 | `fa-regular fa-envelope` | envelope | Email contact |
| 131 | `fas fa-heart` | heart | Footer "made with love" |

### views/site/landing/index.php (85+ icons)
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 52,262,578,596,848,1023 | `fa-solid fa-arrow-right` | arrow-right | CTA arrows |
| 60 | `fa-solid fa-play` | play | Video CTA |
| 89-93 | `fa-solid fa-star` (×5) | star | Rating stars |
| 107,111,115,920,924,952,956,1103 | `fa-solid fa-check-circle` | check-circle | Feature checks |
| 146 | `fa-solid fa-dollar-sign` | dollar-sign | Feature icon |
| 161 | `fa-solid fa-chart-line` | chart-line | Feature icon |
| 179 | `fa-solid fa-chevron-down` | chevron-down | Scroll indicator |
| 222 | `fa-solid fa-chart-line` | chart-line | Feature |
| 234 | `fa-regular fa-calendar-check` | calendar-check | Feature |
| 246 | `fa-solid fa-chart-pie` | chart-pie | Feature |
| 269 | `fa-regular fa-images` | images | Gallery button |
| 295 | `fa-solid fa-lightbulb` | lightbulb | Insight card |
| 303 | `fa-solid fa-star` | star | Rating |
| 336 | `fa-solid fa-piggy-bank` | piggy-bank | Feature |
| 348 | `fa-solid fa-chart-line` | chart-line | Feature |
| 405 | `fa-solid fa-xmark` | xmark | Close gallery |
| 428 | `fa-solid fa-chevron-left` | chevron-left | Gallery prev |
| 433 | `fa-solid fa-chevron-right` | chevron-right | Gallery next |
| 492 | `fa-regular fa-eye` | eye | Feature |
| 507 | `fa-regular fa-clock` | clock | Feature |
| 522 | `fa-regular fa-bell` | bell | Feature |
| 537 | `fa-regular fa-chart-bar` | chart-bar | Feature |
| 552 | `fa-regular fa-face-smile` | face-smile | Feature |
| 567 | `fa-solid fa-rocket` | rocket | Feature |
| 686 | `fa-solid fa-check` | check | Pricing check |
| 693 | `fa-solid fa-xmark` | xmark | Pricing no |
| 748 | `fa-solid fa-check` | check | PRO check |
| 765,786 | `fa-solid fa-shield-halved` | shield-halved | Security |
| 809,821,833 | `fa-solid fa-check` | check | Security checks |
| 851 | `fa-regular fa-clock` | clock | Time indicator |
| 1026 | `fa-solid fa-gift` | gift | Gift |
| 1056 | `fa-brands fa-whatsapp` | whatsapp | Contact |
| 1062 | `fa-regular fa-envelope` | envelope | Contact |
| 1079,1089 | `fa-brands fa-whatsapp` | whatsapp | WhatsApp |
| 1096 | `fa-solid fa-headset` | headset | Support |
| 1107 | `fa-solid fa-lock` | lock | Security |
| 1122 | `fa-regular fa-envelope` | envelope | Newsletter |
| 1175 | `fa-regular fa-paper-plane` | paper-plane | Submit |
| 1195 | `fa-solid fa-arrow-up` | arrow-up | Back to top |
| 1258 | `fa-solid fa-spinner fa-spin` | spinner | Sending (JS) |
| 1310 | `fa-solid fa-check-circle` | check-circle | Success (JS) |
| 1321 | `fa-solid fa-exclamation-circle` | exclamation-circle | Error (JS) |

### views/site/landing/gamification_section.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 78 | `fas fa-star` | star | XP icon |
| 88 | `fas fa-trophy` | trophy | Level icon |
| 98 | `fas fa-fire` | fire | Streak icon |
| 108 | `fas fa-chart-line` | chart-line | Progress icon |
| 136,159,182,205 | `fas fa-star` | star | Rating stars |
| 232 | `fas fa-rocket` | rocket | Feature |
| 243 | `fas fa-chart-line` | chart-line | Feature |
| 254 | `fas fa-trophy` | trophy | Feature |
| 267 | `fas fa-arrow-right` | arrow-right | CTA arrow |

### views/site/card/index.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 26 | `fas fa-users` | users | Users count |
| 30 | `fas fa-star` | star | Rating |
| 39 | `fas fa-rocket` | rocket | CTA |
| 48 | `fas fa-sign-in-alt` | sign-in-alt | Login link |
| 54,65,76,87 | `fas fa-chevron-right` | chevron-right | Link arrows |
| 59 | `fas fa-crown` | crown | PRO link |
| 70 | `fas fa-chart-line` | chart-line | Reports link |
| 81 | `fab fa-whatsapp` | whatsapp | WhatsApp link |
| 96 | `fas fa-wallet` | wallet | Feature |
| 101 | `fas fa-chart-pie` | chart-pie | Feature |
| 106 | `fas fa-piggy-bank` | piggy-bank | Feature |
| 111 | `fas fa-mobile-alt` | mobile-alt | Feature |
| 123 | `fab fa-instagram` | instagram | Social |
| 126 | `fab fa-facebook-f` | facebook-f | Social |
| 129 | `fab fa-twitter` | twitter | Social |
| 132 | `fab fa-youtube` | youtube | Social |
| 135 | `fab fa-linkedin-in` | linkedin-in | Social |

### views/modals/card-detail-modal.php
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 8 | `fas fa-credit-card` | credit-card | Title |
| 16 | `fas fa-times` | times | Close |
| 44 | `fas fa-list` | list | Entries list |
| 72 | `fas fa-chart-line` | chart-line | Chart |
| 89 | `fas fa-crystal-ball` | crystal-ball | Forecast (non-standard) |
| 103 | `fas fa-calendar-check` | calendar-check | Schedule |

---

### views/sysAdmin/index.php (80+ icons, all `fas` prefix)
| Key Lines | Icons Used | Context |
|-----------|-----------|---------|
| 9 | `fas fa-users` | Users stat |
| 16 | `fas fa-arrow-up` | Growth |
| 25 | `fas fa-user-shield` | Admins stat |
| 31 | `fas fa-check-circle` | Active |
| 40 | `fas fa-exclamation-triangle` | Issues |
| 46 | `fas fa-clock` | Pending |
| 50 | `fas fa-arrow-right` | View link |
| 57,95 | `fas fa-chart-line` | Revenue chart |
| 60 | `fas fa-sync-alt` | Refresh |
| 68,234,590,612,749,922,1193 | `fas fa-crown` | PRO badge |
| 77,714,927 | `fas fa-user` | User |
| 86 | `fas fa-percentage` | Rate |
| 107 | `fas fa-calendar-day` | Daily |
| 112 | `fas fa-calendar-week` | Weekly |
| 117 | `fas fa-calendar-alt` | Monthly |
| 128 | `fas fa-chart-area` | Area chart |
| 138 | `fas fa-chart-pie` | Pie chart |
| 148 | `fas fa-credit-card` | Cards |
| 160 | `fas fa-sliders-h` | Controls |
| 168 | `fas fa-tools` | Maintenance |
| 176 | `fas fa-broom` | Clear cache |
| 180 | `fas fa-wrench` | Maintenance toggle |
| 192,200 | `fas fa-ticket-alt` | Coupons |
| 209 | `fas fa-bullhorn` | Announcements |
| 217 | `fas fa-paper-plane` | Send |
| 226 | `fas fa-gift` | Gift PRO |
| 243 | `fas fa-ban` | Remove PRO |
| 251 | `fas fa-user-slash` | Remove access |
| 279 | `fas fa-filter` | Filter |
| 529,555 | `fas fa-map-marker-alt` | Address |
| 557,614 | `fas fa-info-circle` | Info |
| 621 | `fas fa-user-circle` | User details |
| 629 | `fas fa-shield-alt` / `fas fa-user` | Admin/User badge |
| 634 | `fas fa-info-circle` | Info |
| 722 | `fas fa-envelope` | Email |
| 730 | `fas fa-lock` | Password |
| 738 | `fas fa-shield-alt` | Admin status |
| 763 | `fas fa-user-edit` | Edit user |
| 769 | `fas fa-save` | Save |
| 770,964,1085 | `fas fa-times` | Cancel |
| 935,952 | `fas fa-calendar-alt` / `fas fa-hashtag` | Period/Custom |
| 963 | `fas fa-check` | Confirm |
| 1061 | `fas fa-ban` | Remove |
| 1074 | `fas fa-exclamation-triangle` | Warning |
| 1084 | `fas fa-ban` | Remove PRO |
| 1189 | `fas fa-inbox` | Empty state |
| 1194 | `fas fa-user` | Free badge |
| 1200 | `fas fa-shield-alt` / `fas fa-user` | Admin/User |
| 1202 | `fas fa-eye` / `fas fa-edit` / `fas fa-trash` | Actions |
| 1227 | `fas fa-angle-double-left` | First page |
| 1229 | `fas fa-angle-left` | Prev page |
| 1237 | `fas fa-angle-right` | Next page |
| 1239 | `fas fa-angle-double-right` | Last page |

### views/sysAdmin/cupons.php (30+ icons, all `fas` prefix)
| Key Lines | Icons Used | Context |
|-----------|-----------|---------|
| 10 | `fas fa-arrow-left` | Back |
| 18,35,88 | `fas fa-ticket-alt` | Coupon icon |
| 26,103 | `fas fa-plus-circle` | Create coupon |
| 44 | `fas fa-check-circle` | Active |
| 53 | `fas fa-chart-line` | Usage stats |
| 65 | `fas fa-list` | List |
| 68 | `fas fa-spinner fa-spin` | Loading |
| 160 | `fas fa-users` | Usage limit |
| 184 | `fas fa-redo-alt` | Win-back |
| 191 | `fas fa-calendar-alt` | Date |
| 212 | `fas fa-save` | Save |
| 309 | `fas fa-check-circle` | Valid |
| 310 | `fas fa-times-circle` | Invalid |
| 313 | `fas fa-percent` | Percentage |
| 314 | `fas fa-dollar-sign` | Fixed amount |
| 321 | `fas fa-chart-pie` | Usage pie |
| 324 | `fas fa-infinity` | Unlimited |
| 332 | `fas fa-calendar-alt` | Validity |
| 337 | `fas fa-eye` | View |
| 340 | `fas fa-chart-bar` | Stats |
| 343,599 | `fas fa-trash-alt` | Delete |
| 595 | `fas fa-chart-bar` | Stats |

### views/sysAdmin/communications.php (40+ icons, all `fas` prefix)
| Key Lines | Icons Used | Context |
|-----------|-----------|---------|
| 9 | `fas fa-bullhorn` | Title |
| 15 | `fas fa-arrow-left` | Back |
| 24 | `fas fa-paper-plane` | Sent stat |
| 34 | `fas fa-bell` | Recipients stat |
| 44 | `fas fa-eye` | Read rate stat |
| 54 | `fas fa-calendar-alt` | Last campaign |
| 69 | `fas fa-plus-circle` | New campaign |
| 71 | `fas fa-users` | Target |
| 80 | `fas fa-tag` | Type |
| 94 | `fas fa-heading` | Title |
| 105 | `fas fa-align-left` | Message |
| 114 | `fas fa-link` | CTA |
| 126 | `fas fa-filter` | Segmentation |
| 133 | `fas fa-crown` | Plan filter |
| 145 | `fas fa-user-check` | Status |
| 157 | `fas fa-calendar-times` | Inactivity |
| 169 | `fas fa-broadcast-tower` | Channels |
| 176 | `fas fa-bell` | Notification |
| 184 | `fas fa-envelope` | Email |
| 193 | `fas fa-eye` | Preview |
| 196 | `fas fa-paper-plane` | Send |
| 207 | `fas fa-history` | History |
| 209 | `fas fa-sync-alt` | Refresh |
| 215,328,439 | `fas fa-spinner fa-spin` | Loading |
| 223 | `fas fa-chevron-left` | Prev page |
| 227 | `fas fa-chevron-right` | Next page |
| 241 | `fas fa-info-circle` | Info |
| 350,359 | `fas fa-exclamation-circle` | Error |
| 373 | `fas fa-inbox` | Empty |
| 383 | `fas {dynamic.icon}` | Campaign icon |
| 388 | `fas fa-users` | Recipients |
| 389 | `fas fa-eye` | Read rate |
| 390 | `fas fa-calendar` | Date |
| 469 | `fas fa-external-link-alt` | External link |
| 494 | `fas fa-filter` | Filters |
| 495 | `fas fa-broadcast-tower` | Channels |

---

### public/assets/js/ Files

### admin-lancamentos-index.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 761-762 | `fas fa-sort-up`/`fa-sort-down` | sort | Column sort |
| 856,1362 | `fas fa-exchange-alt` | exchange-alt | Transfer icon |
| 958,1511,2344 | `fas fa-chevron-right` | chevron-right | Expand |
| 988,1450 | `fa-check-circle`/`fa-clock` | various | Status icons |
| 1023,2381 | `fas fa-ellipsis-v` | ellipsis-v | More menu |
| 2040 | `fas fa-circle-notch fa-spin` | circle-notch | Exporting |
| 2041 | `fas fa-file-export` | file-export | Export |
| 2420 | `fas fa-chevron-right` (JS) | chevron-right | Toggle |
| 2483 | `fas fa-chevron-down` (JS) | chevron-down | Toggle |

### admin-dashboard-index.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 577 | `fas fa-trash-alt` | trash-alt | Delete |
| 928 | `fas fa-credit-card` | credit-card | Invoices |
| 1074 | `fas fa-credit-card` | credit-card | Invoice badge |

### admin-faturas-index.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 359 | `fas fa-calendar-alt` | calendar-alt | Date |
| 410-417 | `fab fa-cc-visa`/`mastercard`/`amex`/`discover`/`diners-club`/`jcb` + `fas fa-credit-card` | card brands | Card brand icons |
| 645 | `fas fa-credit-card` | credit-card | Card fallback |
| 729-730 | `fas fa-sort-up`/`fa-sort-down` | sort | Column sort |
| 886 | `fas fa-shopping-cart` | shopping-cart | Purchase date |
| 1026 | `fas fa-pencil-alt` | pencil-alt | Edit |
| 1445 | `fas fa-exclamation-triangle` | exclamation-triangle | Warning |
| 1678 | `fas fa-check-circle` | check-circle | Success |
| 2566 | `fas fa-exclamation-triangle` | exclamation-triangle | Warning |
| 2605 | `fas fa-check-circle` | check-circle | Success |
| 2758 | `fas fa-eye`/`fa-eye-slash` | eye | Toggle visibility |

### admin-relatorios-relatorios.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 633 | `fas fa-chevron-down` | chevron-down | Expand |
| 639 | `fas fa-info-circle` | info-circle | Info |
| 931 | `fas fa-chart-pie` | chart-pie | Chart |
| 1215-1219 | `fas fa-arrow-up`/`fa-arrow-down` | arrows | Trend |
| 1283 | `fas fa-arrow-trend-up` | arrow-trend-up | Revenue |
| 1304 | `fas fa-arrow-trend-down` | arrow-trend-down | Expense |
| 1357,1453,1557 | `fas fa-credit-card` | credit-card | Card |
| 1368 | `fas fa-file-invoice-dollar` | file-invoice-dollar | Invoice |
| 1391 | `fas fa-chart-pie` | chart-pie | Chart |
| 1401 | `fas fa-money-bill-wave` | money-bill-wave | Cash |
| 1420,1465 | `fas fa-exclamation-triangle` | exclamation-triangle | Warning |
| 1426,1534 | `fas fa-calendar-check` | calendar-check | Paid |
| 1458 | `fas fa-building-columns` | building-columns | Bank |
| 1540 | `fas fa-chart-line` | chart-line | Chart |

### admin-financas-index.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 230 | `fas fa-info-circle` | info-circle | Hint |
| 508 | `fas fa-plus-circle` | plus-circle | Add |
| 717,1067 | `fas fa-spinner fa-spin` | spinner | Loading |
| 1084 | `fas fa-chevron-right` | chevron-right | Template arrow |

### admin-agendamentos-index.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 1027 | `fas fa-calendar-plus` | calendar-plus | Empty state |
| 1113 | `fas fa-arrow-up`/`fa-arrow-down` | arrows | Type indicator |
| 1159,1442 | `fas fa-pencil-alt` | pencil-alt | Edit |
| 1169,1453 | `fas fa-undo-alt` | undo-alt | Cancel |

### cartoes-manager.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 345 | `fas fa-exclamation-triangle` | exclamation-triangle | Error |
| 479-480 | `fa-calendar-times`/`fa-exclamation-triangle` | various | Alert icons |
| 595 | `fas fa-exclamation-circle` | exclamation-circle | Error |
| 607 | `fas fa-credit-card` | credit-card | Brand fallback |
| 623 | `fa-solid fa-circle-info` | circle-info | Info |
| 631 | `fas fa-file-invoice-dollar` | file-invoice-dollar | Invoice |
| 1660,1935 | `fas fa-chevron-left` | chevron-left | Prev |
| 1664,1939 | `fas fa-chevron-right` | chevron-right | Next |
| 1681,1689,1953 | `fas fa-check-circle` | check-circle | Paid |
| 1704,1753,1772,1979 | `fas fa-shopping-cart` | shopping-cart | Purchase |
| 1706,1774,1981,2384 | `fas fa-calendar-check` | calendar-check | Paid date |
| 1764 | `fas fa-check-circle` | check-circle | Paid status |
| 1840 | `fas fa-spinner fa-spin` | spinner | Processing |
| 2364 | `fas fa-arrow-left` | arrow-left | Back |

### contas-manager.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 329 | `fas fa-exclamation-triangle` | exclamation-triangle | Error |
| 415 | `fa-solid fa-circle-info` | circle-info | Info |
| 421,444 | `fas fa-ellipsis-v` | ellipsis-v | More menu |
| 434 | `fas fa-plus-circle` | plus-circle | New entry |
| 1545 | `fas fa-exclamation-circle` | exclamation-circle | Error |
| 1658 | `fas fa-calendar-check` | calendar-check | Schedule |
| 2270 | `fas fa-spinner fa-spin` | spinner | Processing |
| 2626 | `fas fa-spinner fa-spin` | spinner | Saving |

### lancamento-global.js
| Line | Class | Icon | Context |
|------|-------|------|---------|
| 103 | `fas fa-info-circle` | info-circle | Validation info |
| 806 | `fas fa-spinner fa-spin` | spinner | Saving |
| 960 | `fas fa-check-circle` | check-circle | Success |

### Other JS Files
| File | Line | Class | Context |
|------|------|-------|---------|
| lukrato-fetch.js:310 | `fas fa-hourglass-half` | Timeout warning |
| lukrato-fetch.js:340 | `fas fa-sync-alt fa-spin` | Reconnecting |
| mobile.js:190 | `fas fa-arrows-alt-h` | Horizontal scroll |
| mobile.js:280 | `fas fa-arrow-up` | Back to top |
| admin-home-header.js:336-337 | `fa-angle-right`/`fa-angle-left` | Sidebar toggle |
| admin-admins-login.js:62 | `fa-eye-slash` | Password toggle |
| admin-cartoes-arquivadas.js:72,113 | `fas fa-credit-card` | Card fallback |
| admin-cartoes-arquivadas.js:168 | `fas fa-exclamation-triangle` | Error |
| card-detail-modal.js:134,358 | `fas fa-credit-card`/`fa-lightbulb` | Card/Insight |
| card-detail-modal.js:222,242 | `fas fa-chart-line`/`fa-calendar-check` | Chart/Schedule |
| card-detail-modal.js:303 | `fas fa-chevron-down` | Expand |
| card-detail-modal.js:341 | `fas fa-check-circle` | Empty state |
| card-detail-modal.js:347 | `fas fa-crystal-ball` | Forecast |
| card-detail-modal.js:468 | `fas fa-chart-pie` | Chart |
| card-modal-renderers.js:122 | `fas fa-chevron-down` | Expand |
| card-modal-renderers.js:163 | `fas fa-check-circle` | Empty state |
| card-modal-renderers.js:248 | `fas fa-lightbulb` | Insight |
| categorias-manager.js:251 | `fas fa-chart-pie` | Chart |
| gamification-dashboard.js:539 | `fas fa-shield-alt` | Security badge |
| gamification-page.js:151 | Achievement date checkmark `✓` | Status |
| gamification-page.js:152,289 | `🔒` emoji | Locked |
| onboarding.js:various | `fas fa-arrow-right`/`fa-check-circle`/`fa-circle`/`fa-chart-line`/`fa-mobile-alt`/`fa-shield-alt`/`fa-calendar-alt` | Onboarding flow |
| plan-limits.js:312 | `fas fa-exclamation-triangle` | Warning |
| plan-limits.js:455 | `fas fa-info-circle` | Info |
| first-visit-tooltips.js | `fa-chart-bar`/`fa-check-circle`/`fa-money-bill`/`fa-chart-pie`/`fa-file-export`/`fa-lightbulb`/`fa-th-large`/`fa-plus-circle`/`fa-arrow-up`/`fa-arrow-down`/`fa-calendar-check` | Tooltip icons |
| card.js:60 / card/script.js:53 | `fas fa-share-alt` | Share button |

---

## 3. Emoji Usage by File

### views/admin/perfil/index.php (40+ emojis)
| Line | Emoji | Context |
|------|-------|---------|
| 24 | 👤 | Tab: Personal data |
| 29 | 📍 | Tab: Address |
| 34 | 🔒 | Tab: Security |
| 39 | 👑 | Tab: Plan |
| 44 | ⚠️ | Tab: Danger zone |
| 57 | 👤 | Section icon: Personal |
| 66 | ✏️ | Label: Name |
| 74 | 📧 | Label: Email |
| 83 | 🏷️ | Label: Support code |
| 109 | 📅 | Label: Birthday |
| 114 | 📱 | Label: Phone |
| 122 | ⚧️ | Label: Gender |
| 137 | 💾 | Button: Save personal |
| 146 | 📍 | Section icon: Address |
| 155 | 📮 | Label: CEP |
| 160 | 🗺️ | Label: State |
| 168 | 🏙️ | Label: City |
| 176 | 🏘️ | Label: Neighborhood |
| 184 | 🛣️ | Label: Street |
| 192 | 🔢 | Label: Number |
| 196 | 🏢 | Label: Complement |
| 205 | 💾 | Button: Save address |
| 214 | 🔒 | Section icon: Security |
| 227 | 🔑 | Label: Current password |
| 232 | 🔐 | Label: New password |
| 254 | ✅ | Label: Confirm password |
| 267 | 🔐 | Button: Change password |
| 278 | 👑 | Section icon: Plan |
| 316 | 🎁 | Section icon: Referral |
| 327 | 👤 | Reward: You |
| 331 | 👥 | Reward: Friend |
| 413 | ⚠️ | Section icon: Danger zone |
| 422 | 🗑️ | Delete account title |
| 425-430 | 📊💳📂🎯👤💎 | Delete warning list |
| 639 | ✓ | Save status |
| 674,687 | ⚠️ | Confirm delete |
| 688 | 📋 | Delete notice |
| 750 | ✅ | Account deleted |
| 836 | 🔒 | Referral limit |
| 839 | ⚡ | Last referral |
| 882,888 | 🎁 | Share referral |

### views/admin/partials/modals/editar-lancamentos.php
| Line | Emoji | Context |
|------|-------|---------|
| 58 | 📱 | PIX option |
| 59-60 | 💳 | Card options |
| 61 | 💵 | Cash option |
| 62 | 📄 | Boleto option |
| 63-64 | 🏦 | Transfer/Deposit options |

### views/admin/partials/modals/modal_agendamento.php
| Line | Emoji | Context |
|------|-------|---------|
| 597 | 💰 | Expense option |
| 598 | 💵 | Income option |
| 661 | 🔁 | Recurrence hint |
| 673 | 📱 | PIX |
| 674 | 💵 | Cash |
| 675 | 📄 | Boleto |
| 676-677 | 🏦 | Transfer/Deposit |
| 691 | 💳 | Installment icon |
| 969-975 | 💵📱💳📄🏦📋 | Payment method options |

### views/admin/partials/modals/modal_lancamento_v2.php
| Line | Emoji | Context |
|------|-------|---------|
| 94 | 📅 | Schedule badge |
| 328 | 🔁 | Recurrence hint |
| 344-348 | 📱💵📄🏦 | Payment method options |

### views/admin/partials/modals/modal_lancamento_global.php
| Line | Emoji | Context |
|------|-------|---------|
| 95 | 📅 | Schedule badge |
| 365 | 🔁 | Recurrence hint |
| 437-441 | 📱💵📄🏦 | Payment method options |

### views/admin/partials/modals/editar-categorias.php
| Line | Emoji | Context |
|------|-------|---------|
| 17 | 📝 | Name label |
| 23 | 🏷️ | Type label |

### views/admin/partials/modals/aviso-lancamentos.php
| Line | Emoji | Context |
|------|-------|---------|
| 234 | 🔴/⚠️ | Critical/warning icon |

### views/admin/partials/botao_suporte.php
| Line | Emoji | Context |
|------|-------|---------|
| 62 | 💬 | CSS tooltip content |
| 638 | 😊 | Placeholder text |
| 681 | ✍️ | Validation message |
| 687 | 📝 | Validation message |
| 692 | 📱 | Validation message |
| 711-713 | ✨📨🚀 | Loading messages |
| 727 | ✨ | Sending title |
| 756 | 🎉 | Success title |
| 772 | 👍 | OK button |
| 782 | 😕 | Error title |
| 788 | 💡 | Hint |
| 791 | 🔄 | Retry button |
| 809 | 🔌 | Connection error |
| 825 | 🔄 | Retry button |

### views/admin/dashboard/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 320 | 🚀 | Checklist icon |
| 415 | 🎉 | Complete confetti |
| 496 | 🔥 | Streak icon |

### views/admin/financas/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 362-374 | 💰🛒💳🛡️📈✈️🎓🏠🚗🏥🏪🏖️🎯 | Meta type options |
| 380-382 | 🟢🟡🔴 | Priority indicators |

### views/admin/gamification/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 4 | 🏆 | Page icon |
| 27 | 📊 | XP stat |
| 34 | 🔥 | Streak stat |
| 41 | 🎯 | Achievements stat |
| 117 | 🏆 | Ranking title |

### views/admin/lancamentos/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 70-71 | 📄📊 | Export format options |
| 105-106 | 💰💸 | Type filter options |

### views/admin/faturas/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 36 | 🔄 | Partial status |
| 37 | ✅ | Paid status |
| 38 | ❌ | Cancelled status |

### views/admin/parcelamentos/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 34 | 🔵 | Partially paid |
| 35 | ✅ | Paid |

### views/admin/categorias/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 35-36 | 💰💸 | Type options |

### views/admin/agendamentos/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 54 | 📅 | Today filter |
| 56 | ⚠️ | Overdue filter |
| 57 | ❌ | Cancelled filter |

### views/admin/admins/verify_email.php
| Line | Emoji | Context |
|------|-------|---------|
| 215 | ✉️ | Email icon |
| 225 | 📧 | Email |
| 230 | 💡 | Tips title |

### views/admin/billing/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 1097 | 🚀 | Plan title |
| 1223 | ⚠️ | Expired plan warning |

### views/admin/contas/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 166 | ⚠️ | Console warn |
| 175 | ⚠️ | Console warn |
| 196 | 🚫 | Console blocked |

### views/site/partials/header.php
| Line | Emoji | Context |
|------|-------|---------|
| 492,611 | 🎁 | Referral badge |

### views/site/partials/footer.php
| Line | Emoji | Context |
|------|-------|---------|
| 67 | 🎁 | Referral link |

### views/site/landing/index.php
| Line | Emoji | Context |
|------|-------|---------|
| 878 | 🎁 | Referral section |
| 904 | 👤 | Share step |
| 936 | 👥 | Friend step |

### views/site/landing/gamification_section.php
| Line | Emoji | Context |
|------|-------|---------|
| 22 | 🎮 | Section badge |
| 118 | 🏆 | Section title |
| 126 | 🎯 | Achievement |
| 149 | 💰 | Achievement |
| 172 | 🔥 | Achievement |
| 195 | 🔒 | Locked achievement |

### views/site/legal/terms.php, privacy.php, lgpd.php
| File | Line | Emoji | Context |
|------|------|-------|---------|
| terms.php | 122 | 📜 | CSS content |
| privacy.php | 132 | 🔐 | CSS content |
| lgpd.php | 73 | 🔒 | CSS content |

### views/sysAdmin/cupons.php
| Line | Emoji | Context |
|------|-------|---------|
| 483 | ❌ | Console error |
| 548 | ✅/❌ | Valid/Invalid |
| 549 | 📊/💵 | Type |
| 559-586 | 💰📋📅📊✅📝 | Detail labels |

---

### public/assets/js/ Emoji Usage

### gamification-page.js
| Line | Emoji | Context |
|------|-------|---------|
| 151 | ✓ | Unlocked date |
| 152,289 | 🔒 | Locked |
| 247 | 🥇🥈🥉 | Ranking medals |
| 335-374 | 💰✏️🗑️💰🌅🏷️👋✅📊🎯🏆📅💚🔥🔥🏅💳🧾📌 | Action type emojis |

### gamification-global.js
| Line | Emoji | Context |
|------|-------|---------|
| 88 | 🎯 | Log prefix |
| 130,771 | 🏆 | Default achievement icon |
| 158,201,786 | 🎉 | Achievement unlocked title |
| 170,213,798 | 🚀 | Continue button |
| 275,318 | 🎯 | Level up button |
| 355 | ✨ | Points toast |
| 596 | 🎁/🎉 | Referral icon |
| 597 | 🚀 | Continue button |
| 604 | 👥/🌟 | Referral icon |

### gamification-dashboard.js
| Line | Emoji | Context |
|------|-------|---------|
| 145 | 🎉 | Max level |
| 250 | 🎉 | Organized |
| 369 | ✓ | Unlocked check |
| 403 | 🔒 | Locked |
| 491 | 🏆 | Achievements title |
| 529 | 💎 | PRO title |

### birthday-modal.js
| Line | Emoji | Context |
|------|-------|---------|
| 91 | 🎂 | Birthday icon |
| 104 | 🎉 | Celebration |
| 108-112 | 🎈🎁🎊✨🥳 | Birthday emojis |
| 117 | 💪 | Motivation |
| 124 | 🎉 | Button text |

### contas-manager.js
| Line | Emoji | Context |
|------|-------|---------|
| Various | ❌⚠️💥📄 | Console logs |
| 1619 | 💰 | Income title |
| 1630 | 💸 | Expense title |
| 1644 | 🔄 | Transfer title |
| 1657 | 📅 | Schedule title |
| 2517 | 🏆 | Achievement notification |
| 2539 | 🎉 | Level up |

### admin-financas-index.js
| Line | Emoji | Context |
|------|-------|---------|
| 249,416,738 | 📁 | Default category icon |
| 854 | 💡 | Suggestion |
| 1031 | 🎉 | Goal complete |
| 1033 | 🎊 | Celebrate button |
| 1075 | 🎯 | Default template icon |
| 1138 | ⚠️ | Past date warning |
| 1143 | 🎉 | Goal reached |
| 1150 | 💡 | Tip |
| 1246-1250 | 💰🛒💳🛡️📈✈️🎓🏠🚗🏥🏪🏖️🎯 | Meta type icons |
| 1255-1257 | 🟢🟡🔴 | Priority colors |

### plan-limits.js
| Line | Emoji | Context |
|------|-------|---------|
| 31-40 | 📊💳🏦🎯🏷️💰📈📄🚀 | Feature descriptions |
| 221 | 🚀 | Upgrade title |
| 442 | 🔒 | Locked option |

### lukrato-feedback.js
| Line | Emoji | Context |
|------|-------|---------|
| 245 | 🚀 | Upgrade title |
| 258-266 | 📊💳🏦🎯🏷️💰📈🚀 | Feature descriptions |
| 314 | 🚫 | Limit reached |

### onboarding.js
| Line | Emoji | Context |
|------|-------|---------|
| 259 | 💰 | Account icon |
| 260 | 🎉 | Success |
| 367 | 🎉🎊✨🎈🎁 | Confetti |
| 368 | 🏆 | Trophy |
| 457 | 🎯 | Welcome icon |
| 485 | ✨ | Categories hint |
| 583 | 🎉 | Complete icon |
| 679,706 | 👋 | Goodbye |

### admin-faturas-index.js
| Line | Emoji | Context |
|------|-------|---------|
| 692 | 🎯 | Status label |

### admin-dashboard-index.js
| Line | Emoji | Context |
|------|-------|---------|
| 339 | 🎯 | First checklist item |

---

## 4. Icon-Related PHP Methods/Enums

### Application/Enums/PaymentMethod.php (getIcon method)
```php
self::PIX              => 'fa-brands fa-pix'
self::CARTAO_CREDITO   => 'fa-solid fa-credit-card'
self::CARTAO_DEBITO    => 'fa-solid fa-credit-card'
self::DINHEIRO         => 'fa-solid fa-money-bill-wave'
self::BOLETO           => 'fa-solid fa-barcode'
self::DEPOSITO         => 'fa-solid fa-building-columns'
self::TRANSFERENCIA    => 'fa-solid fa-arrow-right-arrow-left'
self::ESTORNO_CARTAO   => 'fa-solid fa-rotate-left'
```

### Application/Enums/AchievementType.php (icon method - all emojis)
```
FIRST_LAUNCH       => 🎯    STREAK_3         => 🔥    STREAK_7          => ⚡
DAYS_30_USING      => 📅    TOTAL_10_LAUNCHES => 📊    TOTAL_5_CATEGORIES => 🎨
MASTER_ORGANIZATION => 👑    ECONOMIST_MASTER => 💎    CONSISTENCY_TOTAL  => 🏆
META_ACHIEVED      => 🎖️    PREMIUM_USER     => ⭐    LEVEL_8            => 🌟
POSITIVE_MONTH     => 💰    TOTAL_100_LAUNCHES => 💯   LEVEL_5            => 🎓
TOTAL_250_LAUNCHES => 📝    TOTAL_500_LAUNCHES => 📚   TOTAL_1000_LAUNCHES => 🏛️
DAYS_50_ACTIVE     => 🌟    DAYS_100_ACTIVE  => 💫    DAYS_365_ACTIVE    => 🌠
SAVER_10           => 💵    SAVER_20         => 💰    SAVER_30           => 🏦
POSITIVE_3_MONTHS  => 📈    POSITIVE_6_MONTHS => 🎯   POSITIVE_12_MONTHS => 🏅
TOTAL_15_CATEGORIES => 🗂️   TOTAL_25_CATEGORIES => 📁 PERFECTIONIST      => ✅
FIRST_CARD         => 💳    FIRST_INVOICE_PAID => 🧾  INVOICES_12_PAID   => 📆
ANNIVERSARY_1_YEAR => 🎂    ANNIVERSARY_2_YEARS => 🏅 LEVEL_10           => 🎖️
LEVEL_12           => 🧙    LEVEL_15         => 👑    EARLY_BIRD         => 🌅
NIGHT_OWL          => 🌙    CHRISTMAS        => 🎄    NEW_YEAR           => 🎆
WEEKEND_WARRIOR    => ⚔️    SPEED_DEMON      => 🚀
```

### Application/Models/Notification.php (getIconAttribute)
```php
TYPE_PROMO    => 'fa-crown'
TYPE_UPDATE   => 'fa-rocket'
TYPE_ALERT    => 'fa-exclamation-triangle'
TYPE_SUCCESS  => 'fa-check-circle'
TYPE_REMINDER => 'fa-bell'
default       => 'fa-info-circle'
```

### Application/Models/MessageCampaign.php (getIconAttribute)
```php
TYPE_PROMO    => 'fa-crown'
TYPE_UPDATE   => 'fa-rocket'
TYPE_ALERT    => 'fa-exclamation-triangle'
TYPE_SUCCESS  => 'fa-check-circle'
TYPE_REMINDER => 'fa-bell'
default       => 'fa-info-circle'
```

### Application/Services/ReportService.php (icon data)
```php
L421: 'icon' => 'fa-arrow-trend-up'
L433: 'icon' => 'fa-arrow-trend-down'
L442: 'icon' => 'fa-minus'
L459: 'icon' => 'fa-calendar-days'
L474: 'icon' => 'fa-circle-check'
L483: 'icon' => 'fa-circle-info'
L492: 'icon' => 'fa-triangle-exclamation'
L501: 'icon' => 'fa-circle-exclamation'
```

### Application/Services/OrcamentoService.php (icon data)
```php
L264: 'icone' => 'fa-triangle-exclamation'
L275: 'icone' => 'fa-circle-exclamation'
L289: 'icone' => 'fa-arrow-trend-up' / 'fa-arrow-trend-down'
L301: 'icone' => 'fa-circle-check'
```

### Application/Services/MetaService.php (template icons)
```php
L155: 'icone' => 'fa-shield-halved'      (Emergency)
L163: 'icone' => 'fa-mobile-screen'      (Phone)
L172: 'icone' => 'fa-plane'              (Travel)
L181: 'icone' => 'fa-hand-holding-dollar' (Investment)
L189: 'icone' => 'fa-car'                (Vehicle)
L198: 'icone' => 'fa-house'              (Housing)
L207: 'icone' => 'fa-graduation-cap'     (Education)
L216: 'icone' => 'fa-store'              (Business)
L225: 'icone' => 'fa-gem'                (Luxury)
L234: 'icone' => 'fa-children'           (Children)
```

### Application/Controllers/Api/OnboardingController.php
```php
L237: 'icon' => 'fa-plus'
L246: 'icon' => 'fa-tags'
L255: 'icon' => 'fa-bullseye'
L264: 'icon' => 'fa-chart-pie'
L273: 'icon' => 'fa-wallet'
L282: 'icon' => 'fa-calendar-check'
```

### Application/Controllers/Api/RelatoriosController.php
```php
L377: 'icon' => 'arrow-trend-up' / 'arrow-trend-down'  (no `fa-` prefix!)
L393: 'icon' => 'exclamation-triangle'                   (no `fa-` prefix!)
L402: 'icon' => 'piggy-bank'
L424: 'icon' => 'chart-pie'
L457: 'icon' => 'credit-card'
L472: 'icon' => 'check-circle'
```

> ⚠️ **NOTE**: RelatoriosController uses icon names WITHOUT `fa-` prefix

### Application/Services/Auth/AuthService.php (category seed emojis)
```
🏠 Moradia, 🍔 Alimentação, 🚗 Transporte, 💡 Contas e Serviços
🏥 Saúde, 🎓 Educação, 👕 Vestuário, 🎬 Lazer, 💳 Cartão de Crédito
📱 Assinaturas, 🛒 Compras, 💰 Outros Gastos
💼 Salário, 💰 Freelance, 📈 Investimentos, 🎁 Bônus
💸 Vendas, 🏆 Prêmios, 💵 Outras Receitas
```

### Application/Config/Billing.php (emojis in messages)
```
🚫 Limit reached, 🎁 Grace period, 💡 Soft warning
🔴 Critical warning, 🚀 Upgrade CTA
📊 Relatórios, 💳 Cartões, 🏦 Contas, 🎯 Metas
🏷️ Categorias, 💰 Lançamentos, 📈 Dashboard, 🚀 Default
```

---

## 5. CSS FA References

| File | Line | Reference | Context |
|------|------|-----------|---------|
| admin-categorias-index.css | 462 | `.fa-spin` | Animation override |
| admin-relatorios-relatorios.css | 6826,6893,6922 | `.fa-crown` | PRO badge styling |
| admin-agendamentos-index.css | 1839 | `.fa-crown` | Paywall styling |
| admin-agendamentos-index.css | 2617 | `\f0de` (fa-sort-up) | CSS content |
| admin-agendamentos-index.css | 2621 | `\f0dd` (fa-sort-down) | CSS content |
| modal-lancamento-mobile.css | 157 | `\f078` (fa-chevron-down) | CSS content |
| modal-lancamento.css | 1600,1604 | `.fa-pix` | PIX icon color |
| plan-limits.css | 125 | `.fa-crown` | Crown styling |
| lancamentos-modern.css | 476,480 | `\f0de`/`\f0dd` | Sort icon content |
| lancamentos-modern.css | 1364,1431,1460 | `.fa-crown` | PRO badge styling |
| categorias-modern.css | 37-38 | `.fa-plus-circle`/`.fa-circle-plus` | Add button |
| components.css | 505+ | `.fab-*` classes | FAB (Floating Action Button, NOT Font Awesome brands) |

---

## 6. Summary Statistics

### Font Awesome Usage
| Style | Count (approx) | Files |
|-------|-----------------|-------|
| `fa-solid` / `fas` | ~400+ occurrences | 35+ files |
| `fa-regular` / `far` | ~15 occurrences | 5 files |
| `fa-brands` / `fab` | ~25 occurrences | 8 files |
| `fa` (legacy FA4) | 4 occurrences | 2 files |
| Utility: `fa-spin` | ~25 occurrences | 12 files |
| Utility: `fa-fw` | 0 occurrences | - |

### Most Used FA Icons (Top 20)
1. `fa-credit-card` — ~40 occurrences (payments, cards)
2. `fa-chevron-right` — ~30 occurrences (navigation, expand)
3. `fa-chevron-down` — ~25 occurrences (dropdowns)
4. `fa-check-circle` — ~25 occurrences (success states)
5. `fa-arrow-right` — ~20 occurrences (CTAs, links)
6. `fa-spinner fa-spin` — ~18 occurrences (loading)
7. `fa-exclamation-triangle` — ~15 occurrences (warnings)
8. `fa-chart-line` — ~15 occurrences (charts, trends)
9. `fa-crown` — ~14 occurrences (PRO plan)
10. `fa-arrow-up` / `fa-arrow-down` — ~12 each (income/expense)
11. `fa-calendar-alt` / `fa-calendar-check` — ~12 each (dates)
12. `fa-info-circle` — ~12 occurrences (info tooltips)
13. `fa-eye` / `fa-eye-slash` — ~10 occurrences (visibility)
14. `fa-wallet` — ~8 occurrences (balance)
15. `fa-chart-pie` — ~8 occurrences (charts)
16. `fa-pix` — ~8 occurrences (PIX payment)
17. `fa-rocket` — ~8 occurrences (PRO CTAs)
18. `fa-dollar-sign` — ~7 occurrences (amounts)
19. `fa-money-bill-wave` — ~7 occurrences (cash)
20. `fa-building-columns` — ~5 occurrences (bank)

### Brand Icons Used
- `fa-brands fa-pix` — PIX payment method
- `fa-brands fa-whatsapp` — WhatsApp contact/share
- `fa-brands fa-instagram` — Social link
- `fa-brands fa-tiktok` — Social link
- `fa-brands fa-facebook-f` — Social link
- `fa-brands fa-linkedin-in` — Social link
- `fa-brands fa-telegram` — Share
- `fab fa-twitter` — Social link (site card only)
- `fab fa-youtube` — Social link (site card only)
- `fab fa-cc-visa` — Card brand
- `fab fa-cc-mastercard` — Card brand
- `fab fa-cc-amex` — Card brand
- `fab fa-cc-discover` — Card brand
- `fab fa-cc-diners-club` — Card brand
- `fab fa-cc-jcb` — Card brand

### Emoji Count by Category
| Category | Count (approx) | Primary Location |
|----------|-----------------|-----------------|
| Finance (💰💳💵💸📈) | ~60 | Views, Services, Config |
| UI/Status (✅❌⚠️🔒🔴🟢) | ~40 | Modals, filters |
| Achievement (🎯🏆🔥⭐💎) | ~50 | Enums, gamification JS |
| Category Seeds (🏠🍔🚗🎓) | ~40 | AuthService, AchievementService |
| Celebration (🎉🎊🎂🎁) | ~25 | Notifications, birthday |
| UI Labels (📝📅📧🔑) | ~30 | Perfil, forms |
| Console/Debug (❌⚠️🚫💥) | ~30 | JS console logs |
| Email/Comms (🎉✉️👋🚀) | ~25 | MailService |

### Key Migration Concerns
1. **Three different FA CDN versions** (6.4.0, 6.5.2, 6.7.1) — standardize
2. **Legacy FA4 classes** in `views/admin/partials/header.php` (lines 318, 354, 382) and `top-navbar.php` (line 39)
3. **Mixed prefix styles**: `fas`/`far`/`fab` vs `fa-solid`/`fa-regular`/`fa-brands` used inconsistently
4. **Non-standard icon**: `fa-crystal-ball` (line 89) in card-detail-modal.php — not a real FA icon
5. **CSS Unicode references** to FA codepoints in 3 CSS files
6. **Dynamic icon injection** in 6+ locations where icon class comes from PHP/API data
7. **`RelatoriosController`** uses icon names WITHOUT `fa-` prefix (inconsistent with other services)
8. **Emojis deeply embedded in**: category seeds, achievement definitions, email templates, billing config
