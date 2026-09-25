# S-Hub Light

S-Hub Light is the display name of the existing `themes/staark` block theme. The directory slug stays `staark` so the Staark Hub update channel, release workflow and existing managed installations can still identify it. The 0.6.5 theme package updates the existing theme; it does not install alongside it.

## Stack

- **Theme:** layouts, block patterns, design tokens, images, frontend rendering and Scandinavian preset.
- **Staark Hub plugin:** site branding (`[staark_brand]` and `[staark_brand_copyright]`), native form and local submissions, SEO, security, performance, support, updates, managed mode and the signed Hub connector.
- **Hub connection:** configured through the plugin's Connect screen. The theme never stores secrets or makes connector requests itself.

The homepage Contact pattern uses `[staark_contact_form form_id="staark-home"]` when Staark Hub is active. It links to `contact@staarkinc.com` either way. Submissions are stored and notifications are delivered according to **Staark Hub → Forms** settings; the theme does not send mail. Configure the recipient and SMTP transport there. Existing saved forms settings are intentionally preserved. On older Hub builds the theme loads the plugin's form stylesheet itself; newer Hub builds detect template forms through a filter.

The pattern is conditionally registered with the form when the shortcode is present. If the plugin is deactivated, the form is omitted and the mail link remains. Site Editor customizations stored in the database override the files in a new theme package; reset the affected template/part in Site Editor if an earlier saved copy hides the new contact section.

## Install and edit

1. Install and activate the Staark Hub plugin from `plugins/staark-core`.
2. Install S-Hub Light from the theme ZIP (root folder `staark`) and activate it. An installed `Staark` theme in the same folder will be upgraded.
3. Set the site name and branding in Staark Hub → Branding. Review Forms recipient and mail delivery; pair the site under Connect if managed.
4. Edit the home template and patterns under Appearance → Editor. Set real content and images before publishing. Add the same shortcode to a dedicated Contact page if needed.
5. Use Staark Hub's SEO, Security, Performance and Updates screens to configure each module. No parallel implementation is bundled in the theme.

Requires WordPress 6.6+, PHP 8.1+ and the supported Staark Hub build for integrated features. The theme renders without the plugin but its branding shortcodes and form require Staark Hub.

## Verification

The theme ZIP must contain `staark/style.css`, `staark/theme.json`, templates, patterns and assets. Run `bash scripts/rc-smoke.sh` and visual checks at 1440, 1024, 782, 600 and 390 px in a Docker-backed wp-env before a production release. Confirm the form submission is stored locally and its configured notification arrives before accepting client traffic.
