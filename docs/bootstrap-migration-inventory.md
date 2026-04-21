# Bootstrap removal inventory (public frontend)

Generated for the Tailwind + Symfony UX Toolkit migration. **Admin** (`templates/admin/`, `webpack.admin.config.js`) intentionally keeps Bootstrap until a separate EasyAdmin pass.

## Stimulus controllers (public bundle — no Bootstrap JS)

| File | Notes |
|------|--------|
| [assets/controllers/search_modal_controller.js](../assets/controllers/search_modal_controller.js) | Native `<dialog>` + `showModal()` / `close()` |
| [assets/controllers/modal-route-information_controller.js](../assets/controllers/modal-route-information_controller.js) | Native `<dialog>` |
| [assets/controllers/route-information-tooltip_controller.js](../assets/controllers/route-information-tooltip_controller.js) | Custom floating hint (no Bootstrap) |

## `data-bs-*` in Twig

**Public / frontend templates:** none remaining (`rg 'data-bs-' templates --glob '*.twig'` only hits admin).

**Previously migrated (reference):** area hero, rock offcanvas, navigation, search modal, static modals, flash messages, upload form — replaced with `<dialog>`, `<details>`, or Tailwind patterns.

## Admin (out of scope for `app` bundle)

- [templates/admin/field/routes.html.twig](../templates/admin/field/routes.html.twig) and other admin templates still use `data-bs-toggle` / modals.
- [assets/admin.js](../assets/admin.js) imports **`bootstrap`** (npm) for Bootstrap JS on admin pages; the public [assets/app.js](../assets/app.js) entry does not.

## Re-audit command

```bash
rg 'data-bs-' templates --glob '*.twig'
rg "from [\"']bootstrap[\"']" assets/controllers
```

## Phase 4 — npm `bootstrap` package

The **`bootstrap`** npm dependency is **kept** because the **admin** Encore entry imports it for `data-bs-*` on EasyAdmin-related pages. Removing it requires an admin/EasyAdmin-specific migration.

[assets/css/app.scss](../assets/css/app.scss) no longer imports Bootstrap SCSS (that file is not wired into Encore).
