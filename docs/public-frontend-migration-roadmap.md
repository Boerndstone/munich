# Public UI, API, and future split (roadmap)

This document backs the **Public UI first** plan: Tailwind + shadcn-aligned Twig on the public site, Bootstrap only on the admin Encore entry, then optional Symfony split and Next when ready.

## Bootstrap audit (public)

| Location | Finding |
|----------|---------|
| [`assets/app.js`](../assets/app.js) | Imports `./bootstrap` = **Stimulus bridge only**; no Bootstrap CSS/JS. |
| [`assets/admin.js`](../assets/admin.js) | Imports `bootstrap` for EasyAdmin / `data-bs-*`; keep as-is. |
| [`assets/styles/global.scss`](../assets/styles/global.scss) | No Bootstrap SCSS; comment notes removal from public bundle. |
| [`config/packages/twig.yaml`](../config/packages/twig.yaml) | `when@test` uses [`form/tailwind_form_layout.html.twig`](../templates/form/tailwind_form_layout.html.twig) (not `bootstrap_5_layout`). **Do not** set global Tailwind form theme: EasyAdmin expects its own markup + admin Bootstrap. |
| Public Twig | Forms that need styling use per-template `{% form_theme ... 'form/tailwind_form_layout.html.twig' %}`. Remaining Bootstrap-style classes were migrated (e.g. area card title, chart flex utility, `sr-only` instead of `visually-hidden`). |
| [`RockImprovementSuggestionType`](../src/Form/RockImprovementSuggestionType.php) | Widget `attr`/`label_attr` no longer use Bootstrap (`form-control`, `form-label`, …); layout comes from [`tailwind_form_layout.html.twig`](../templates/form/tailwind_form_layout.html.twig). |
| [`templates/admin/`](../templates/admin/) | Bootstrap-heavy; intentionally unchanged. |

## Public pages vs data (inventory)

Rough map for a later **HTTP-only frontend** or **Next** consumer: today most HTML is rendered server-side with repositories/services in [`FrontendController`](../src/Controller/FrontendController.php), [`StaticPagesController`](../src/Controller/StaticPagesController.php), [`SearchController`](../src/Controller/SearchController.php), etc.

| Public route(s) | Primary data |
|-----------------|---------------|
| `/`, `/en` | Cached latest routes, comments, banned rocks |
| `/{slug}`, `/en/{slug}` | Area + rocks + grades + maps |
| `/{areaSlug}/{slug}`, `/en/...` | Rock + routes + photos + comments |
| `/neuesteRouten`, `/en/latest-routes` | Latest routes (repository) |
| `/free-climbing-grade-comparison`, `/en/...` | Static + comparison table |
| Impressum / Datenschutz | `ContactFormType` + mailer |
| Upload photo | `PhotoUploadType` |
| Search modal | `search_autocomplete` + JSON |

API today: **Api Platform** on entities under `/api/` (see [`config/packages/api_platform.yaml`](../config/packages/api_platform.yaml)). OpenAPI: `application/vnd.openapi+json` on docs; export locally with:

```bash
php bin/console api:openapi:export --output=var/openapi.json
```

## API hardening (checklist for later)

- Tighten serialization **groups** per resource; avoid over-exposing relations.
- **CORS**: [`nelmio_cors.yaml`](../config/packages/nelmio_cors.yaml) is pinned to `http://localhost:3000` for `/api/`; introduce `CORS_ALLOW_ORIGIN` (or regex) before a non-local Next deploy.
- Publish **OpenAPI** as the contract for generated clients.
- Review write operations (if any) + **security** attributes on operations.

## Optional: two Symfony apps + one DB

When splitting:

- **Backend** app: EasyAdmin, Api Platform, **sole owner of Doctrine migrations**.
- **Frontend** app: Twig + Encore public bundle only; **no** migration generation.
- **Shared** PHP package: entities + DTOs + domain services both apps depend on.
- Both apps: same `DATABASE_URL` (or read replica later).

## Next.js pilot (deferred)

Prerequisites:

1. Public UI **Bootstrap-free** on the `app` Encore entry (this milestone).
2. Stable **OpenAPI** + CORS for the Next origin.

Then: one pilot route, visual + SEO parity checklist, expand slices, retire Twig only after parity.

## Related commands

```bash
npm run build
php bin/phpunit
```
