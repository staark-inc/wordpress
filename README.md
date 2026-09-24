# Staark WordPress

Staark WordPress is the maintained WordPress stack used for websites built and supported by **Staark Inc.**

The repository contains two first-party parts designed to work together:

- **Staark Theme** — a native WordPress block theme with reusable patterns and a performance-first design system.
- **Staark Hub** — the site-management plugin for branding, website controls, support, security, SEO, performance and Hub connectivity.

The product direction is intentionally simple: one coherent Staark stack instead of a pile of overlapping themes, page builders and utility plugins.

## Current components

| Component | Current line |
| --- | --- |
| Staark Hub | `0.5.12.x` |
| Staark Theme | `0.5.x` |

## Repository structure

```text
.
├── .wp-env.json
├── package.json
├── RC-CHECKLIST.md
├── plugins/
│   └── staark-core/
├── scripts/
└── themes/
    └── staark/
        ├── assets/css/theme.css
        ├── parts/
        ├── patterns/
        ├── templates/
        ├── functions.php
        ├── style.css
        └── theme.json
```

## Requirements

- WordPress `6.6+`
- PHP `8.1+` for the theme
- Node.js + npm
- Docker for `@wordpress/env`

## Local development

Install dependencies:

```bash
npm install
```

Start the WordPress development environment:

```bash
npm run dev
```

Stop or destroy it:

```bash
npm run stop
npm run destroy
```

Useful wp-env examples:

```bash
npm run wp -- run cli wp plugin list
npm run wp -- run cli wp theme list
npm run wp -- run cli wp staark rc-check
```

The current `.wp-env.json` mounts:

- `./themes/staark`
- `./plugins/staark-core`

and uses port `8888`.

> `.wp-env.json` currently sets `WP_HOME` and `WP_SITEURL` to `https://wp.staarkinc.com`. Keep that when using the shared Staark development URL. Override/remove those constants if you want a localhost-only environment.

## Staark Theme

The theme is a native **block theme**. Global design tokens live in `theme.json`, while reusable component styling lives in `assets/css/theme.css`.

### Design principles

- Scandinavian restraint
- Strong typography and generous whitespace
- Clear hierarchy before decoration
- Mobile-first responsive behavior
- Accessible contrast and visible focus states
- Native Site Editor compatibility
- No page-builder dependency
- Small frontend dependency surface

### Included pattern families

The theme ships reusable sections for:

- Hero
- Services
- Process
- Why Staark
- Testimonials
- FAQ
- CTA
- Contact

The homepage is composed from registered WordPress patterns rather than a hard-coded page builder.

## Staark Hub

Staark Hub is the management layer for sites built and maintained by Staark Inc.

Current product areas include:

- Overview and website status
- Website management
- Branding
- Support
- Leads
- Connect to Staark
- Security
- SEO and local SEO
- Performance
- Responsive/accessibility cleanup
- Release-candidate diagnostics

Security, SEO and Performance are first-party Staark modules so managed client sites do not need a large stack of overlapping admin plugins for the basic workflow.

## Release candidate checks

Run the automated smoke test:

```bash
bash scripts/rc-smoke.sh
```

Run WordPress-side RC diagnostics:

```bash
npx wp-env run cli wp staark rc-check
```

JSON output:

```bash
npx wp-env run cli wp staark rc-check --format=json
```

Also follow [`RC-CHECKLIST.md`](./RC-CHECKLIST.md) before promoting a release.

## Theme workflow

1. Put global color, typography, layout and spacing decisions in `themes/staark/theme.json`.
2. Put reusable visual components and responsive behavior in `themes/staark/assets/css/theme.css`.
3. Keep homepage sections as registered patterns under `themes/staark/patterns/`.
4. Keep template files compositional and small.
5. Avoid adding frontend dependencies unless the interaction cannot be delivered cleanly with WordPress core and CSS.
6. Test at `1440`, `1024`, `782`, `600` and `390` px.

## Coding rules

- Escape dynamic output.
- Sanitize persisted user input.
- Use nonces and capability checks for mutations.
- Never expose connector secrets in HTML, JS, diagnostics or logs.
- Keep destructive uninstall opt-in only.
- Prefer WordPress APIs over custom replacements.
- Keep client-facing copy understandable without WordPress jargon.

## Versioning

Staark Hub and the Staark Theme are versioned independently.

For theme releases, update:

```text
themes/staark/style.css
```

For Staark Hub releases, keep the plugin header version and `STAARK_HUB_VERSION` synchronized.

## License

Copyright © Staark Inc. Internal product code and branding remain the property of Staark Inc. unless a separate license is provided for a specific distribution.
