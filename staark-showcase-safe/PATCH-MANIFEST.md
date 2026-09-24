# Staark Showcase Safe Patch

Base inspected from GitHub:
`49b1a9932f31c0acaacc96f25db5d29c620a991e`

## This patch DOES modify
- `themes/staark/style.css` (version/cache bust -> 0.6.0)
- `themes/staark/assets/css/theme.css` (existing CSS preserved + Showcase layer appended)
- `themes/staark/parts/header.html`
- `themes/staark/parts/footer.html`
- `themes/staark/templates/front-page.html`

## This patch ONLY adds
- `themes/staark/patterns/showcase-*.php`
- `themes/staark/assets/images/showcase/*`
- `themes/staark/screenshot.png`

## This patch NEVER modifies
- `themes/staark/functions.php`
- `themes/staark/theme.json`
- `themes/staark/templates/index.html`
- `themes/staark/templates/page.html`
- `themes/staark/templates/single.html`
- any existing old pattern file
- anything under `plugins/`

## Important
The project cards and testimonial are explicitly marked as concept/demo content.
Replace them with verified client material before publishing them as real work/testimonials.
