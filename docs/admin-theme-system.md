# Admin Theme System

## Goal

The admin visual language should be controlled from one place: `resources/css/admin/core/variables.css`.

## Token layers

1. `--color-*`
   Raw brand and theme primitives.
   Keep these inside `variables.css` as the source palette for light and dark mode.

2. `--theme-*`
   Semantic UI tokens.
   These are the variables that shared CSS files should consume.

## Rule of thumb

- In shared components and layouts, prefer `--theme-*`.
- Only `variables.css` should decide which primitive color maps to each semantic token.
- Keep legacy aliases such as `--gradient-primary` and `--laranja` only for compatibility while migration is in progress.

## Current semantic groups

- Shell: sidebar, body background, nav states, suggestion cards.
- Topbar: header background, title, panels, avatar, plan badges.
- Surface: cards, chips, filters, modals, dropdowns.
- Controls: buttons, inputs, hover states, focus states.
- Support: floating action button, speed dial, support modal.
- Dashboard: hero, KPI cards, widgets, gauges, list rows, summary strips.
- Auth: page background, cards, inputs, buttons, messages, verify-email states.

## Files already migrated

- `resources/css/admin/core/components/_surface.css`
- `resources/css/admin/layout/admin-partials-header.css`
- `resources/css/admin/layout/top-navbar.css`
- `resources/css/admin/modules/support-button.css`
- `resources/css/admin/core/enhancements.css`
- `resources/css/admin/layout/_hierarchy-preview.css`
- `resources/css/admin/dashboard/_hierarchy-preview.css`
- `resources/css/admin/layout/header-month-picker.css`
- `resources/css/admin/auth/admin-auth-login.css`
- `resources/css/admin/auth/auth-shared.css`
- `resources/css/admin/auth/auth-verify-email.css`
- `views/admin/auth/login.php`
- `views/admin/auth/forgot-password.php`
- `views/admin/auth/reset-password.php`
- `views/admin/auth/verify-email.php`

## Next migration targets

- Older isolated admin modules should follow the same contract as they are touched.
- When a token feels too specific to one screen, keep it local until it proves reusable.
- Auth pages must bootstrap `html[data-theme]` before CSS loads, using `lukrato-theme` from `localStorage` with a safe `dark` fallback.

## Safe maintenance workflow

1. Adjust the palette or semantic token in `variables.css`.
2. Check light and dark mode in the shared shell first.
3. Only create a new semantic token when an existing one no longer matches the intent.
4. Avoid introducing direct hex values in shared admin CSS unless the value is truly one-off.