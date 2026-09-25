# Staark WordPress

Staark WordPress is the maintained WordPress stack used for websites built and supported by **Staark Inc.**

It is intentionally small: one first-party block theme plus one first-party management plugin, backed by the main Staark Hub.

## Current release line

| Component | Version |
| --- | --- |
| Staark Hub / Core | `0.6.1.1` |
| Staark Theme | `0.6.0` |
| Update manifest schema | `1` |

## Components

- **Staark Theme** — native WordPress block theme, reusable patterns, `theme.json` design tokens and a small frontend surface.
- **Staark Hub** — website management, support, security, SEO, performance, branding, managed runtime and update channel.
- **S-Hub Salong** — child theme of the Staark Theme for hair salons and barbers: services, price list, gallery and booking requests. See `themes/staark-salong/README.md`.
- **S-Hub Bygg** — child theme of the Staark Theme for builders and craftsmen: services, projects, service area, process, FAQ and quote requests. See `themes/staark-bygg/README.md`.
- **S-Hub Gästfrihet** — child theme of the Staark Theme for restaurants and hotels, with two presets (Restaurang, Hotell): menu, lunch, private dining, rooms, amenities, offers and bookings through an external booking link and/or booking requests. See `themes/staark-gastfrihet/README.md`.
- **Staark Hub connector** — HMAC-signed link between a managed WordPress installation and the main Staark Hub.

## Repository structure

```text
.
├── .github/workflows/wordpress-release.yml
├── .wp-env.json
├── RC-CHECKLIST.md
├── WP-6.1-UPDATE-CHANNEL.md
├── plugins/
│   └── staark-core/
│       ├── admin/
│       ├── assets/
│       ├── deployment/
│       ├── includes/
│       └── staark-core.php
├── scripts/
│   ├── managed-recover.sh
│   └── rc-smoke.sh
└── themes/
    └── staark/
```

## Requirements

- WordPress `6.6+`
- PHP `8.0+` for Staark Hub
- PHP `8.1+` for Staark Theme
- Node.js + npm for local tooling
- Docker for `@wordpress/env`

## Local development

```bash
npm install
npm run dev
```

Useful commands:

```bash
npx wp-env run cli wp plugin list
npx wp-env run cli wp theme list
npx wp-env run cli wp staark rc-check
```

The shared development environment uses port `8888` and `https://wp.staarkinc.com`.

## Staark Hub product areas

```text
Staark Hub
├── Overview
├── Website
├── Security
├── SEO
├── Performance
├── Support
├── Branding
├── Updates
├── Managed
└── Connect
```

Security, SEO and Performance are first-party modules so a managed client site does not need a pile of overlapping utility plugins for the normal Staark workflow.

## Managed Mode

Staark Hub supports three operating modes:

- **Normal** — standard WordPress plugin lifecycle.
- **Managed** — client-facing restrictions and Staark operator controls.
- **Locked** — MU-loader controlled runtime that remains available independently of normal plugin activation state.

Managed/Locked mode requires at least one explicit Staark Operator. A normal WordPress administrator is not automatically a Staark Operator.

Useful commands:

```bash
npx wp-env run cli wp staark managed status
npx wp-env run cli wp staark managed operators
npx wp-env run cli wp staark managed grant admin
npx wp-env run cli wp staark managed mode managed
npx wp-env run cli wp staark managed mode locked
npx wp-env run cli wp staark managed mode normal
npx wp-env run cli wp staark managed loader status
```

Emergency/recovery constants are documented in `MANAGED-MODE.md`.

## Managed releases and rollback

Managed runtime releases live under:

```text
wp-content/staark-managed/
├── current  -> releases/<release-id>
├── previous -> releases/<release-id>
└── releases/
```

Deployment is intentionally explicit and recoverable:

```bash
npx wp-env run cli wp staark deployment status
npx wp-env run cli wp staark deployment deploy
npx wp-env run cli wp staark deployment verify
npx wp-env run cli wp staark deployment rollback
```

The shell recovery helper is:

```bash
bash scripts/managed-recover.sh status
```

## Support sync

Support requests are created and stored locally in WordPress first.

After the site is paired with Staark Hub:

```text
WordPress ticket
      │
      │ HMAC signed sync
      ▼
Main Staark Hub
      │
      ├── status change
      └── public Staark reply
              │
              │ next signed sync
              ▼
WordPress ticket detail
```

The Hub is authoritative for remote ticket status after the request is synced.

Supported states:

```text
Open
In progress
Waiting client
Resolved
```

Public Hub responses appear in the WordPress ticket detail under **Staark responses**. Internal Hub work notes are never exposed through the WordPress support sync.

Support screens perform a rate-limited background refresh when opened, and the ticket detail also provides **Refresh from Staark** for an explicit sync. Remote status/replies can therefore travel Hub → WordPress even when there are no locally pending tickets.

## Connector security

Pairing creates a per-site integration identity.

Subsequent connector requests use:

- site ID
- per-site secret
- timestamp
- SHA256 body hash
- HMAC-SHA256 signature
- HTTPS in production

The connector secret is not rendered in the WordPress admin UI or RC diagnostics.

## Update Channel

Staark updates are distributed through the main Hub, not through the public WordPress.org updater.

The release path is:

```text
Git tag
   │
   ▼
GitHub Actions
   │
   ├── staark-core.zip
   ├── staark-theme.zip
   └── staark-wordpress-manifest.json
              │
              ▼
Staark Hub release endpoint
              │
              │ signed connector request
              ▼
Managed WordPress site
```

The Hub endpoint is:

```text
/api/hub/wordpress/releases
```

The manifest contains Core/Theme versions, HTTPS package URLs, SHA256 hashes and compatibility requirements.

### Channels

- `stable` — default for client sites.
- `beta` — opt-in test channel.

Development/beta can be selected with:

```php
define('STAARK_HUB_UPDATE_CHANNEL', 'beta');
```

### Discovery vs installation

Update discovery runs automatically on a twice-daily WordPress cron.

```bash
npx wp-env run cli wp staark updates status
npx wp-env run cli wp staark updates check
```

Discovery does **not** automatically install a release.

Installation remains an explicit Staark operator/server action:

```bash
npx wp-env run cli wp staark updates install core --yes
npx wp-env run cli wp staark updates install theme --yes
```

Core packages are downloaded into staging, verified, switched atomically, deployed into the managed runtime, verified and then health-checked on the next WordPress request. A failed health check can restore the previous Core package and managed release.

Theme packages are staged and verified independently and are restored immediately if post-install verification fails.

Package checks include HTTPS policy, SHA256, structure/version validation, PHP lint and optional Ed25519 signatures. See `WP-6.1-UPDATE-CHANNEL.md`.

## Publishing a stable release

1. Update the Core version header and `STAARK_HUB_VERSION`.
2. Complete `RC-CHECKLIST.md`.
3. Commit and push `main`.
4. Create/push the matching tag:

```bash
git tag -a v0.6.1.1 -m "WP-6.1.1 final polish"
git push origin v0.6.1.1
```

The release workflow builds the Core ZIP, Theme ZIP and manifest and publishes them as GitHub Release assets.

After publication:

```bash
npx wp-env run cli wp staark updates check
npx wp-env run cli wp staark updates status
```

## Release candidate checks

```bash
bash scripts/rc-smoke.sh
npx wp-env run cli wp staark rc-check
npx wp-env run cli wp staark rc-check --format=json
```

Also complete `RC-CHECKLIST.md`, including Managed/Locked runtime, support round-trip and production-style update install/rollback gates.

## Theme workflow

1. Put global colors, typography, layout and spacing in `themes/staark/theme.json`.
2. Put reusable visual behavior in `themes/staark/assets/css/theme.css`.
3. Keep homepage sections as registered patterns under `themes/staark/patterns/`.
4. Keep templates compositional and small.
5. Avoid frontend dependencies unless WordPress core and CSS cannot deliver the interaction cleanly.
6. Test at `1440`, `1024`, `782`, `600` and `390` px.

## Coding rules

- Escape dynamic output.
- Sanitize persisted input.
- Use nonces and capability checks for mutations.
- Never expose connector secrets in HTML, JS, diagnostics or logs.
- Keep destructive uninstall opt-in only.
- Prefer WordPress APIs over custom replacements.
- Keep client-facing copy understandable without WordPress jargon.
- Keep Staark features modular; do not grow `staark-core.php` indefinitely.

## Versioning

Staark Hub and Staark Theme are versioned independently.

For the theme, update:

```text
themes/staark/style.css
```

For Staark Hub, keep the plugin header and `STAARK_HUB_VERSION` synchronized.

## License

Copyright © Staark Inc. Internal product code and branding remain the property of Staark Inc. unless a separate license is provided for a specific distribution.
