# S-Hub Bygg

Child theme of **S-Hub Light** (`themes/staark`) for builders and craftsmen (bygg / hantverkare).
It builds on the **Local Business** direction: deep forest, brick accent, bold serif headings and pill buttons.
Its sections are made for builders: services, projects, a step-by-step process, service area, FAQ and quote requests.

## Requirements

- Parent theme `staark` (S-Hub Light) installed
- WordPress 6.6+ (tested on 6.9 and 7.1), PHP 8.1+
- Staark Hub (`staark-core`) for the quote form, branding and contact details

## Install

1. Install `staark` and `staark-bygg` (the release workflow builds `staark-bygg-theme.zip`).
2. Activate **S-Hub Bygg**. The `bygg` preset is the only preset in this child theme, so it is active straight away.
3. Run **Staark Hub → First Install** with the preset **Bygg**. It creates:
   - Hem, Tjänster, Projekt, Arbetsområde, Om oss, Begär offert and Integritetspolicy
   - the static front page, the forms recipient and LocalBusiness SEO
   - phone, email, address and town, which feed the top bar, quote section, area text, footer and mobile bar

If the pages were created earlier for another pack (Local Business, Showcase, Salong), an admin notice offers **Use the Bygg layout for these pages**.
It saves the current content as a revision first.

## Homepage sections (patterns)

| Pattern | Slug | Anchor |
| --- | --- | --- |
| Hero + trust bar | `staark/bygg-hero` | |
| Tjänster | `staark/bygg-services` | `#tjanster` |
| Projekt | `staark/bygg-projects` | `#projekt` |
| Så jobbar vi | `staark/bygg-process` | `#sa-jobbar-vi` |
| Arbetsområde | `staark/bygg-area` | `#omrade` |
| Omdömen | `staark/bygg-reviews` | |
| Vanliga frågor | `staark/bygg-faq` | `#fragor` |
| Begär offert | `staark/bygg-quote` | `#offert` |
| Om oss | `staark/bygg-about` | `#om-oss` (Om oss page) |
| CTA | `staark/bygg-cta` | (inner pages) |

All sections are made from core blocks, so they can be edited in the block editor.
The FAQ uses the core Details block.
They are listed in the inserter under **Staark — Bygg**.

## Quote requests

`staark/bygg-quote` wraps the Staark Hub form (`form_id="bygg-offert"`).
`assets/js/bygg-quote.js` adds these fields to it:

- job type (taken from the **"Jobbtyper i offertformuläret"** list, which is hidden on the site)
- town / postcode
- property type
- preferred start
- budget
- ROT

The request arrives in **S-Hub Inbox** like this:

```
Offertförfrågan
Typ av jobb: Badrum / våtrum
Ort / postnummer: Värnamo
Fastighet: Villa / radhus
Önskad start: Inom 1–3 månader
Budget: 150 000–400 000 kr
ROT-avdrag: Ja
```

## Before publishing

- Replace the drawing-style placeholder images with real project photos.
- Replace the project texts with real references.
- The reviews are labelled "Exempelomdöme · ersätt före publicering". Replace them with verified reviews.
- Check the trust claims (F-skatt, ansvarsförsäkring, ABS 18) and the town list against the actual company.

## Contact shortcodes

`[bygg_contact field="phone|email|address|street|city|name" link="1" label="" fallback="" hint=""]`

The values come from Staark Hub SEO settings or First Install. `hint` is shown only to logged-in editors.
