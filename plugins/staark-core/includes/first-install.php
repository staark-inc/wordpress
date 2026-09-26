<?php
/**
 * Staark First Site Install / Bootstrap.
 *
 * Safe, idempotent starter-site setup:
 * - site identity + branding;
 * - theme preset;
 * - Home / Services / About / Contact / Privacy starter pages;
 * - static front page;
 * - Forms recipient;
 * - LocalBusiness SEO basics;
 * - optional pretty permalinks.
 *
 * Existing pages are never overwritten.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_FIRST_INSTALL_OPTION = 'staark_hub_first_install_state';

/**
 * @return array<string,mixed>
 */
function staark_hub_first_install_state(): array
{
    $saved = get_option(STAARK_HUB_FIRST_INSTALL_OPTION, []);

    return is_array($saved) ? $saved : [];
}

/**
 * @return array<string,string>
 */
function staark_hub_first_install_available_locales(): array
{
    $locales = [
        'en_US' => 'en_US',
    ];

    if (function_exists('get_available_languages')) {
        foreach (get_available_languages() as $locale) {
            $locale = sanitize_text_field((string) $locale);
            if ($locale !== '') {
                $locales[$locale] = $locale;
            }
        }
    }

    $current = get_locale();
    if (is_string($current) && $current !== '') {
        $locales[$current] = $current;
    }

    ksort($locales);

    return $locales;
}

/**
 * Starter page blueprints for the selected design pack.
 *
 * Existing content is never replaced. The installer only inserts a page when
 * its slug does not already exist.
 *
 * @return array<string,array{title:string,content:string}>
 */
function staark_hub_first_install_page_blueprints(string $preset_id): array
{
    $blueprints = staark_hub_first_install_default_page_blueprints($preset_id);

    /**
     * Filters the starter pages created by First Install.
     *
     * Themes and design packs (for example the S-Hub Salong child theme) can
     * supply their own pages for their preset. Each entry is keyed by page
     * slug and holds a title and block content. A "home" entry becomes the
     * static front page when that option is selected.
     *
     * @param array<string,array{title:string,content:string}> $blueprints
     * @param string $preset_id Selected theme preset.
     */
    $filtered = apply_filters('staark_hub_first_install_page_blueprints', $blueprints, $preset_id);

    if (! is_array($filtered)) {
        return $blueprints;
    }

    $clean = [];
    foreach ($filtered as $slug => $page) {
        $slug = sanitize_title((string) $slug);
        if ($slug === '' || ! is_array($page) || ! isset($page['title'], $page['content'])) {
            continue;
        }

        $clean[$slug] = [
            'title' => (string) $page['title'],
            'content' => (string) $page['content'],
        ];
    }

    return $clean !== [] ? $clean : $blueprints;
}

/**
 * Built-in starter pages.
 *
 * @return array<string,array{title:string,content:string}>
 */
function staark_hub_first_install_default_page_blueprints(string $preset_id): array
{
    if ($preset_id === 'local-business') {
        return [
            'home' => [
                'title' => 'Hem',
                'content' => <<<'BLOCKS'
<!-- wp:pattern {"slug":"staark/local-business-home"} /-->
BLOCKS,
            ],
            'tjanster' => [
                'title' => 'Tjänster',
                'content' => <<<'BLOCKS'
<!-- wp:pattern {"slug":"staark/local-business-services"} /-->
<!-- wp:pattern {"slug":"staark/local-business-proof"} /-->
<!-- wp:pattern {"slug":"staark/local-business-cta"} /-->
BLOCKS,
            ],
            'om-oss' => [
                'title' => 'Om oss',
                'content' => <<<'BLOCKS'
<!-- wp:group {"align":"wide","className":"staark-local-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide staark-local-section">
<!-- wp:paragraph {"className":"staark-local-kicker"} --><p class="staark-local-kicker">Om företaget</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Personlig service, tydliga besked och ett arbete vi kan stå för.</h1><!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"lg","textColor":"muted"} --><p class="has-muted-color has-text-color has-lg-font-size">Berätta kort om företaget, erfarenheten och vad som är viktigast i mötet med kunden. Byt den här starttexten innan publicering.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:pattern {"slug":"staark/local-business-proof"} /-->
<!-- wp:pattern {"slug":"staark/local-business-reviews"} /-->
<!-- wp:pattern {"slug":"staark/local-business-cta"} /-->
BLOCKS,
            ],
            'kontakt' => [
                'title' => 'Kontakt',
                'content' => <<<'BLOCKS'
<!-- wp:pattern {"slug":"staark/local-business-contact"} /-->
BLOCKS,
            ],
            'integritetspolicy' => [
                'title' => 'Integritetspolicy',
                'content' => staark_hub_first_install_privacy_content(),
            ],
        ];
    }

    return [
        'home' => [
            'title' => 'Hem',
            'content' => <<<'BLOCKS'
<!-- wp:pattern {"slug":"staark/showcase-hero"} /-->
<!-- wp:pattern {"slug":"staark/showcase-services"} /-->
<!-- wp:pattern {"slug":"staark/showcase-projects"} /-->
<!-- wp:pattern {"slug":"staark/showcase-why"} /-->
<!-- wp:pattern {"slug":"staark/showcase-testimonial"} /-->
<!-- wp:pattern {"slug":"staark/showcase-cta"} /-->
BLOCKS,
        ],
        'tjanster' => [
            'title' => 'Tjänster',
            'content' => <<<'BLOCKS'
<!-- wp:pattern {"slug":"staark/services-three"} /-->
<!-- wp:pattern {"slug":"staark/process-three"} /-->
<!-- wp:pattern {"slug":"staark/cta-light"} /-->
BLOCKS,
        ],
        'projekt' => [
            'title' => 'Projekt',
            'content' => <<<'BLOCKS'
<!-- wp:pattern {"slug":"staark/showcase-projects"} /-->
<!-- wp:pattern {"slug":"staark/showcase-testimonial"} /-->
<!-- wp:pattern {"slug":"staark/showcase-cta"} /-->
BLOCKS,
        ],
        'om-oss' => [
            'title' => 'Om oss',
            'content' => <<<'BLOCKS'
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted","fontSize":"lg"} -->
<p class="staark-page-lead has-muted-color has-text-color has-lg-font-size">Skriv företagets berättelse här. Fokusera på erfarenhet, arbetssätt och varför kunden ska känna sig trygg med att ta kontakt.</p>
<!-- /wp:paragraph -->
<!-- wp:pattern {"slug":"staark/why-us-business"} /-->
<!-- wp:pattern {"slug":"staark/testimonials-business"} /-->
<!-- wp:pattern {"slug":"staark/cta-light"} /-->
BLOCKS,
        ],
        'kontakt' => [
            'title' => 'Kontakt',
            'content' => <<<'BLOCKS'
<!-- wp:pattern {"slug":"staark/contact-business"} /-->
BLOCKS,
        ],
        'integritetspolicy' => [
            'title' => 'Integritetspolicy',
            'content' => staark_hub_first_install_privacy_content(),
        ],
    ];
}

function staark_hub_first_install_privacy_content(): string
{
    return <<<'BLOCKS'
<!-- wp:group {"className":"staark-privacy","layout":{"type":"constrained"}} -->
<div class="wp-block-group staark-privacy">
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted"} --><p class="staark-page-lead has-muted-color has-text-color">Det här är en startmall. Anpassa texten efter företagets faktiska behandling av personuppgifter, formulär, analysverktyg och externa tjänster före publicering.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Vilka uppgifter samlas in?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>När du kontaktar oss kan vi behandla uppgifter som namn, e-postadress, telefonnummer, företag och information som du själv lämnar i ett formulär eller meddelande.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Varför behandlas uppgifterna?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Uppgifterna används för att besvara förfrågningar, hantera kundrelationer och leverera de tjänster som efterfrågas.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Hur länge sparas uppgifterna?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Personuppgifter sparas inte längre än vad som behövs för ändamålet eller vad som krävs enligt tillämpliga regler.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Dina rättigheter</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Kontakta företaget om du har frågor om dina personuppgifter eller vill begära tillgång, rättelse eller radering när det är tillämpligt.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Kontakt</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Komplettera den här sidan med företagets aktuella kontaktuppgifter innan lansering.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
BLOCKS;
}

/**
 * @return array{id:int,created:bool}
 */
function staark_hub_first_install_ensure_page(string $slug, string $title, string $content): array
{
    $existing = get_page_by_path($slug, OBJECT, 'page');

    if ($existing instanceof WP_Post) {
        return [
            'id' => (int) $existing->ID,
            'created' => false,
        ];
    }

    $result = wp_insert_post(
        [
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => sanitize_text_field($title),
            'post_name' => sanitize_title($slug),
            'post_content' => $content,
        ],
        true
    );

    if (is_wp_error($result)) {
        return [
            'id' => 0,
            'created' => false,
        ];
    }

    if (function_exists('staark_hub_starter_managed_mark_page')) {
        staark_hub_starter_managed_mark_page(
            (int) $result,
            $slug,
            [
                'title' => $title,
                'content' => $content,
            ]
        );
    }

    return [
        'id' => (int) $result,
        'created' => true,
    ];
}

/**
 * @param array<string,mixed> $input
 * @return array<string,mixed>
 */
function staark_hub_first_install_apply(array $input): array
{
    $business_name = sanitize_text_field((string) ($input['business_name'] ?? ''));
    $tagline = sanitize_text_field((string) ($input['tagline'] ?? ''));
    $industry = sanitize_text_field((string) ($input['industry'] ?? ''));
    $phone = sanitize_text_field((string) ($input['phone'] ?? ''));
    $email = sanitize_email((string) ($input['email'] ?? ''));
    $street_address = sanitize_text_field((string) ($input['street_address'] ?? ''));
    $locality = sanitize_text_field((string) ($input['locality'] ?? ''));
    $region = sanitize_text_field((string) ($input['region'] ?? ''));
    $postal_code = sanitize_text_field((string) ($input['postal_code'] ?? ''));
    $country = strtoupper(substr(sanitize_text_field((string) ($input['country'] ?? 'SE')), 0, 2));
    $preset_id = sanitize_key((string) ($input['preset_id'] ?? 'scandinavian'));
    $locale = sanitize_text_field((string) ($input['locale'] ?? get_locale()));

    $create_pages = ! empty($input['create_pages']);
    $set_front_page = ! empty($input['set_front_page']);
    $pretty_permalinks = ! empty($input['pretty_permalinks']);

    if ($business_name === '') {
        return [
            'ok' => false,
            'error' => __('Business name is required.', 'staark-core'),
        ];
    }

    if ($email === '' || ! is_email($email)) {
        return [
            'ok' => false,
            'error' => __('A valid contact email is required.', 'staark-core'),
        ];
    }

    if (function_exists('staark_theme_system_registry')) {
        $registry = staark_theme_system_registry();
        if (! isset($registry[$preset_id])) {
            return [
                'ok' => false,
                'error' => __('Unknown theme preset.', 'staark-core'),
            ];
        }
    } else {
        $preset_id = 'scandinavian';
    }

    update_option('blogname', $business_name);

    if ($tagline !== '') {
        update_option('blogdescription', $tagline);
    }

    $available_locales = staark_hub_first_install_available_locales();
    if (isset($available_locales[$locale])) {
        update_option('WPLANG', $locale === 'en_US' ? '' : $locale);
    }

    if (function_exists('staark_hub_branding')) {
        $branding = staark_hub_branding();
        $branding['brand_name'] = $business_name;

        if ($tagline !== '') {
            $branding['tagline'] = $tagline;
        }

        update_option('staark_hub_branding', $branding, false);
    }

    if (function_exists('staark_theme_system_registry')) {
        set_theme_mod('staark_theme_preset', $preset_id);
    }

    $page_ids = [];
    $created = 0;
    $skipped = 0;

    if ($create_pages) {
        foreach (staark_hub_first_install_page_blueprints($preset_id) as $slug => $page) {
            $result = staark_hub_first_install_ensure_page(
                $slug,
                (string) $page['title'],
                (string) $page['content']
            );

            if ($result['id'] > 0) {
                $page_ids[$slug] = $result['id'];
            }

            if ($result['created']) {
                ++$created;
            } else {
                ++$skipped;
            }
        }
    } else {
        foreach (array_keys(staark_hub_first_install_page_blueprints($preset_id)) as $slug) {
            $existing = get_page_by_path($slug, OBJECT, 'page');
            if ($existing instanceof WP_Post) {
                $page_ids[$slug] = (int) $existing->ID;
            }
        }
    }

    if ($set_front_page && ! empty($page_ids['home'])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', (int) $page_ids['home']);
    }

    if (! empty($page_ids['integritetspolicy'])) {
        update_option('wp_page_for_privacy_policy', (int) $page_ids['integritetspolicy']);
    }

    if ($pretty_permalinks) {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules(false);
    }

    if (function_exists('staark_hub_forms_settings') && function_exists('staark_hub_forms_save_settings')) {
        $forms = staark_hub_forms_settings();
        $forms['recipient_email'] = $email;
        $forms['subject_prefix'] = '[' . $business_name . ']';
        $forms['notify'] = true;

        if (! empty($page_ids['integritetspolicy'])) {
            $privacy_url = get_permalink((int) $page_ids['integritetspolicy']);
            if (is_string($privacy_url) && $privacy_url !== '') {
                $forms['privacy_url'] = $privacy_url;
            }
        }

        staark_hub_forms_save_settings($forms);
    }

    if (function_exists('staark_hub_seo_settings') && function_exists('staark_hub_seo_save_settings')) {
        $seo = staark_hub_seo_settings();
        $seo['enable_metadata'] = true;
        $seo['enable_canonical'] = true;
        $seo['enable_open_graph'] = true;
        $seo['enable_schema'] = true;
        $seo['entity_type'] = 'LocalBusiness';
        $seo['organization_name'] = $business_name;
        $seo['organization_description'] = $tagline;
        $seo['phone'] = $phone;
        $seo['email'] = $email;
        $seo['street_address'] = $street_address;
        $seo['locality'] = $locality;
        $seo['region'] = $region;
        $seo['postal_code'] = $postal_code;
        $seo['country'] = $country;

        staark_hub_seo_save_settings($seo);
    }

    $state = [
        'completed' => true,
        'completed_at' => current_time('mysql'),
        'completed_by' => get_current_user_id(),
        'business_name' => $business_name,
        'tagline' => $tagline,
        'industry' => $industry,
        'phone' => $phone,
        'email' => $email,
        'street_address' => $street_address,
        'locality' => $locality,
        'region' => $region,
        'postal_code' => $postal_code,
        'country' => $country,
        'locale' => get_locale(),
        'preset_id' => $preset_id,
        'page_ids' => $page_ids,
        'created_pages' => $created,
        'skipped_pages' => $skipped,
        'front_page_id' => (int) get_option('page_on_front'),
        'permalink_structure' => (string) get_option('permalink_structure'),
    ];

    update_option(STAARK_HUB_FIRST_INSTALL_OPTION, $state, false);

    /**
     * Fires after a successful Staark first-site bootstrap.
     *
     * @param array<string,mixed> $state
     */
    do_action('staark_hub_first_install_completed', $state);

    return [
        'ok' => true,
        'state' => $state,
        'created' => $created,
        'skipped' => $skipped,
    ];
}

/**
 * @return array<string,array{label:string,ok:bool,detail:string}>
 */
function staark_hub_first_install_readiness(): array
{
    $state = staark_hub_first_install_state();
    $front_page_id = (int) get_option('page_on_front');
    $forms_email = function_exists('staark_hub_forms_settings')
        ? (string) staark_hub_forms_settings()['recipient_email']
        : '';
    $seo = function_exists('staark_hub_seo_settings') ? staark_hub_seo_settings() : [];
    $preset_id = function_exists('staark_theme_system_active_preset_id')
        ? staark_theme_system_active_preset_id()
        : 'scandinavian';

    return [
        'identity' => [
            'label' => __('Site identity', 'staark-core'),
            'ok' => trim((string) get_bloginfo('name')) !== '',
            'detail' => (string) get_bloginfo('name'),
        ],
        'theme' => [
            'label' => __('Theme preset', 'staark-core'),
            'ok' => $preset_id !== '',
            'detail' => $preset_id,
        ],
        'front_page' => [
            'label' => __('Static front page', 'staark-core'),
            'ok' => get_option('show_on_front') === 'page' && $front_page_id > 0,
            'detail' => $front_page_id > 0 ? get_the_title($front_page_id) : __('Not configured', 'staark-core'),
        ],
        'forms' => [
            'label' => __('Forms recipient', 'staark-core'),
            'ok' => is_email($forms_email) !== false,
            'detail' => $forms_email !== '' ? $forms_email : __('Not configured', 'staark-core'),
        ],
        'seo' => [
            'label' => __('LocalBusiness SEO', 'staark-core'),
            'ok' => isset($seo['entity_type']) && $seo['entity_type'] === 'LocalBusiness'
                && trim((string) ($seo['organization_name'] ?? '')) !== '',
            'detail' => (string) ($seo['organization_name'] ?? __('Not configured', 'staark-core')),
        ],
        'permalinks' => [
            'label' => __('Pretty permalinks', 'staark-core'),
            'ok' => trim((string) get_option('permalink_structure')) !== '',
            'detail' => (string) get_option('permalink_structure'),
        ],
        'bootstrap' => [
            'label' => __('Bootstrap status', 'staark-core'),
            'ok' => ! empty($state['completed']),
            'detail' => ! empty($state['completed_at'])
                ? (string) $state['completed_at']
                : __('Not run yet', 'staark-core'),
        ],
    ];
}
