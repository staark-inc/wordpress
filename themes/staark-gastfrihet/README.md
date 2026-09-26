# S-Hub Gästfrihet

Child theme of **S-Hub Light** (`themes/staark`) for restaurants and hotels.
It ships one theme with two design presets, chosen in Staark First Install:

| Preset | For | Pages | Accent |
| --- | --- | --- | --- |
| **Restaurang** (`restaurang`) | restaurants, bistros, cafés | Hem, Meny, Sällskap & event, Om oss, Boka bord, Kontakt | terracotta on warm cream |
| **Hotell** (`hotell`) | hotels, B&Bs, guesthouses | Hem, Rum, Erbjudanden, Om oss, Boka rum, Kontakt | deep teal on linen |

Both presets use the same components: hero, cards, info panels, FAQ, reviews, the booking section, header, footer and mobile bar.
Copy, pages and menus follow the active preset.
Headings use Fraunces (bundled, OFL) and body text uses Inter.

## Requirements

- Parent theme `staark` (S-Hub Light) installed
- WordPress 6.6+ (tested on 7.1), PHP 8.1+
- Staark Hub (`staark-core`) for the booking request form, branding and contact details

## Install

1. Install `staark` and `staark-gastfrihet` (the release workflow builds `staark-gastfrihet-theme.zip`).
2. Activate **S-Hub Gästfrihet**. A fresh activation starts on the **Restaurang** preset.
3. Run **Staark Hub → First Install** and pick the **Restaurang** or **Hotell** preset.
   It creates the pages for that preset, the static front page, the forms recipient and LocalBusiness SEO.
   Phone, email and address feed the top bar, booking section, "Hitta hit" panel, footer and mobile bar.
4. Open **Appearance → Bokning** and add the booking link (see below).

## Switching preset

If you switch between Restaurang and Hotell later, an admin notice offers **Set up pages for …**. It then:

- rebuilds pages built for the other preset (a menu on a hotel homepage), saving the current content as a revision first;
- creates the pages the new menu links to (`/rum/`, `/erbjudanden/` or `/meny/`, `/sallskap/`);
- renames default titles, for example "Boka bord" → "Boka rum".

The same notice picks up pages created for another design pack (Local Business, Showcase …).
Pages whose sections come from another Staark theme are handled by Staark Hub's own **Rebuild pages** notice.

## Booking

Guests can book in two ways, and you can show one or both.

**Booking link.** Set it in **Appearance → Bokning**.
It works with the public booking page of any system:

- restaurants: TheFork, Quandoo, Bokabord, Waiteraid …
- hotels: Booking.com, SiteMinder, Mews …

The button reads "Boka via {provider}", or "Boka online" if no provider name is set.
It opens in a new tab.
If no link is set, editors see a hint where the button would be; visitors do not.

**Booking request.** The `staark/gast-booking` section wraps the Staark Hub form: `form_id="gast-bord"` for the restaurant, `gast-rum` for the hotel.
`assets/js/gast-booking.js` adds these fields:

- Restaurang: date, time, number of guests and occasion. Times come from the hidden list in the section, and **Fler än 10** shows a note about larger groups.
- Hotell: check-in and check-out (check-out is always at least one day later, and the form shows the number of nights), adults, children, room type and number of rooms. Room types come from the hidden list in the section.

Requests arrive in **S-Hub Inbox**:

```
Bordsbokning (förfrågan)
Datum: fredag 2 oktober 2026 (2026-10-02)
Tid: 19:00
Antal gäster: 4
Tillfälle: Födelsedag

Önskemål:
Glutenfritt för en person.
```

```
Rumsbokning (förfrågan)
Incheckning: fredag 2 oktober 2026 (2026-10-02)
Utcheckning: söndag 4 oktober 2026 (2026-10-04)
Nätter: 2 nätter
Gäster: 2 vuxna, 1 barn
Rumstyp: Dubbelrum
Antal rum: 1
```

Requests are not confirmed automatically, and the page says so. Reply to the guest to confirm.

To change the times or room types, edit the list in the booking section in the block editor.
It is labelled "Tider / Rumstyper i bokningsformuläret" and is visible only in the editor.
Keep the room names in line with the **Rum** section.

**Show on the booking page** in Appearance → Bokning has three options:

- link and form;
- only the link, which hides the form;
- only the form.

## Sections (patterns)

Shared by both presets (copy follows the active preset):

| Pattern | Slug | Anchor |
| --- | --- | --- |
| Hero + facts | `staark/gast-hero` | |
| Om oss | `staark/gast-about` | `#om-oss` |
| Galleri | `staark/gast-gallery` | `#galleri` |
| Omdömen (examples, replace before publishing) | `staark/gast-reviews` | |
| Öppettider & hitta hit / Praktiskt | `staark/gast-info` | `#hitta-hit` |
| Vanliga frågor | `staark/gast-faq` | `#fragor` |
| Boka bord / Boka rum | `staark/gast-booking` | `#boka` |
| Kontakt | `staark/gast-contact` | `#kontakt` |
| CTA | `staark/gast-cta` | |

Restaurang:

| Pattern | Slug | Anchor |
| --- | --- | --- |
| Från köket (3 signature dishes) | `staark/gast-highlights` | |
| Meny (à la carte, prices, diet tags) | `staark/gast-menu` | `#meny` |
| Veckans lunch | `staark/gast-lunch` | `#lunch` |
| Sällskap & event | `staark/gast-events` | `#sallskap` |

Hotell:

| Pattern | Slug | Anchor |
| --- | --- | --- |
| Rum & sviter | `staark/gast-rooms` | `#rum` |
| Faciliteter | `staark/gast-amenities` | `#faciliteter` |
| Erbjudanden & paket | `staark/gast-offers` | `#erbjudanden` |

All sections are made from core blocks, so they can be edited in the block editor.
They are listed in the inserter under **Staark — Restaurang & Hotell**.
The front-page template (used before a static front page exists) renders `staark/gast-home`, which holds the homepage sections for the active preset.

## Header and footer

- Restaurang uses the `header` and `footer` template parts.
- Hotell uses `header-hotell` and `footer-hotell`. The swap happens at render time.
- Edit either pair in **Appearance → Editor → Patterns → Template parts**.
- Both show the contact top bar, a sticky header with the booking button, a footer with hours or reception times, and a mobile bar with "Ring oss" and the booking button.

## Shortcodes

- `[gast_contact field="phone|email|address|street|city|name" link="1" label="" fallback="" hint=""]`. Shows contact details from Staark Hub (SEO / First Install). `hint` is shown only to editors when a value is missing.
- `[gast_booking_link label="" class=""]`. Shows the external booking button from Appearance → Bokning.
- `[gast_year]`. Shows the current year.

## Images

The bundled images are flat illustrations: dishes, table settings, hotel rooms, spa and facade.
Their alt texts say "ersätt med …". Replace them with your own photos before launch.
