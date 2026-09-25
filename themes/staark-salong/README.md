# S-Hub Salong

Child theme of **S-Hub Light** (`themes/staark`) for hair salons and barbers (frisör / salong).

It keeps everything the parent provides (Staark Hub integration, theme system,
critical CSS, branding shortcodes) and adds a salon look with services, a price list,
a gallery with lightbox and booking requests.

## Requirements

- Parent theme `staark` (S-Hub Light) installed
- WordPress 6.6+, PHP 8.1+
- Staark Hub (`staark-core`) for the booking form, branding and contact details

## Install

1. Install `staark` and `staark-salong` (the release workflow builds `staark-salong-theme.zip`).
2. Activate **S-Hub Salong**. The `salong` preset is the only preset in this child theme, so it is active straight away.
3. Run **Staark Hub → First Install** with the preset **Salong**. It fills in:
   - pages: Hem, Priser, Galleri, Om oss, Boka tid, Integritetspolicy
   - the static front page, the forms recipient and LocalBusiness SEO
   - phone, email and address, which the theme shows in the booking section, footer and mobile booking bar

Without First Install, the front page still shows the whole salon homepage from `templates/front-page.html`.

## Homepage sections (patterns)

| Pattern | Slug | Anchor |
| --- | --- | --- |
| Hero | `staark/salong-hero` | |
| Tjänster | `staark/salong-services` | `#tjanster` |
| Prislista | `staark/salong-prices` | `#priser` |
| Galleri | `staark/salong-gallery` | `#galleri` |
| Om salongen | `staark/salong-about` | `#om-oss` |
| Omdömen | `staark/salong-reviews` | |
| Boka tid | `staark/salong-booking` | `#boka` |

All sections are made from core blocks (no raw HTML blocks), so prices, texts and images can be edited in the block editor.
They are listed in the inserter under **Staark — Salong**.

## Booking requests

`staark/salong-booking` wraps the Staark Hub form (`[staark_contact_form form_id="salong-bokning"]`).
`assets/js/salong-booking.js` adds these fields to it:

- treatment (taken from the **"Tjänster i bokningsformuläret"** list in the same section; that list is visible in the editor but hidden on the site)
- preferred date and time of day
- "new customer"

On submit, the fields are added to the start of the message, so the request arrives in **Staark Hub → Forms** like this:

```
Bokningsförfrågan
Behandling: Slingor / balayage
Önskat datum: fredag 2 oktober (2026-10-02)
Tid på dagen: Eftermiddag (14–17)
Ny kund: Ja
```

Without JavaScript, the plain Hub form still works. To use an external booking system (Bokadirekt, Timma …) instead, point the **Boka tid** buttons at its URL.

## Contact shortcodes

`[salong_contact field="phone|email|address|street|city|name" link="1" label="" fallback="" hint=""]`

The values come from Staark Hub SEO settings or First Install. `hint` is shown only to logged-in editors when a value is missing.

## Templates and colors

- `page-canvas` renders page content full width without a title. Pages built from salon patterns and the static front page use it automatically.
- The accent is the preset's clay color. It changes only when a custom primary color is saved in **Staark Hub → Branding**.

## Assets

- Fonts: Fraunces and Inter (variable, latin subset), bundled locally under the SIL OFL. No Google Fonts requests are made.
- Images in `assets/images` are generated placeholders. Replace them with the salon's own photos, which also works from the editor.
