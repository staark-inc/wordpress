<?php
/**
 * Staark SEO Core.
 *
 * Lightweight managed-site SEO primitives:
 * - metadata + canonical URLs;
 * - Open Graph / Twitter cards;
 * - JSON-LD WebSite, Organization/LocalBusiness, Breadcrumb and Article schema;
 * - per-content title, description, canonical and noindex controls;
 * - WordPress core sitemap awareness without creating a duplicate sitemap.
 *
 * If a known full SEO plugin is active, Staark SEO pauses frontend output to
 * avoid duplicate metadata. Settings and audits remain available.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return array{enable_metadata:bool,enable_canonical:bool,enable_open_graph:bool,enable_schema:bool,entity_type:string,organization_name:string,organization_description:string,phone:string,email:string,street_address:string,locality:string,region:string,postal_code:string,country:string,twitter_handle:string,default_social_image_id:int}
 */
function staark_hub_seo_settings(): array
{
    $saved = get_option('staark_hub_seo_settings', []);
    if (! is_array($saved)) {
        $saved = [];
    }

    $branding = function_exists('staark_hub_branding') ? staark_hub_branding() : [];
    $brand_name = isset($branding['brand_name']) ? (string) $branding['brand_name'] : (string) get_bloginfo('name');
    $tagline = isset($branding['tagline']) ? (string) $branding['tagline'] : (string) get_bloginfo('description');
    $logo_id = isset($branding['logo_id']) ? absint($branding['logo_id']) : 0;

    $defaults = [
        'enable_metadata' => true,
        'enable_canonical' => true,
        'enable_open_graph' => true,
        'enable_schema' => true,
        'entity_type' => 'LocalBusiness',
        'organization_name' => $brand_name,
        'organization_description' => $tagline,
        'phone' => '',
        'email' => '',
        'street_address' => '',
        'locality' => '',
        'region' => '',
        'postal_code' => '',
        'country' => 'SE',
        'twitter_handle' => '',
        'default_social_image_id' => $logo_id,
    ];

    $settings = array_merge($defaults, $saved);
    $settings['enable_metadata'] = (bool) $settings['enable_metadata'];
    $settings['enable_canonical'] = (bool) $settings['enable_canonical'];
    $settings['enable_open_graph'] = (bool) $settings['enable_open_graph'];
    $settings['enable_schema'] = (bool) $settings['enable_schema'];
    $settings['entity_type'] = in_array((string) $settings['entity_type'], ['Organization', 'LocalBusiness'], true)
        ? (string) $settings['entity_type']
        : 'LocalBusiness';
    $settings['organization_name'] = sanitize_text_field((string) $settings['organization_name']);
    $settings['organization_description'] = sanitize_textarea_field((string) $settings['organization_description']);
    $settings['phone'] = sanitize_text_field((string) $settings['phone']);
    $settings['email'] = sanitize_email((string) $settings['email']);
    $settings['street_address'] = sanitize_text_field((string) $settings['street_address']);
    $settings['locality'] = sanitize_text_field((string) $settings['locality']);
    $settings['region'] = sanitize_text_field((string) $settings['region']);
    $settings['postal_code'] = sanitize_text_field((string) $settings['postal_code']);
    $settings['country'] = strtoupper(substr(sanitize_text_field((string) $settings['country']), 0, 2));
    $settings['twitter_handle'] = ltrim(sanitize_text_field((string) $settings['twitter_handle']), '@');
    $settings['default_social_image_id'] = absint($settings['default_social_image_id']);

    return $settings;
}

/**
 * @param array<string,mixed> $settings
 */
function staark_hub_seo_save_settings(array $settings): void
{
    $entity_type = isset($settings['entity_type']) && in_array((string) $settings['entity_type'], ['Organization', 'LocalBusiness'], true)
        ? (string) $settings['entity_type']
        : 'LocalBusiness';

    $country = isset($settings['country']) ? strtoupper(substr(sanitize_text_field((string) $settings['country']), 0, 2)) : '';
    $social_image_id = isset($settings['default_social_image_id']) ? absint($settings['default_social_image_id']) : 0;
    if ($social_image_id > 0 && ! wp_attachment_is_image($social_image_id)) {
        $social_image_id = 0;
    }

    update_option(
        'staark_hub_seo_settings',
        [
            'enable_metadata' => ! empty($settings['enable_metadata']),
            'enable_canonical' => ! empty($settings['enable_canonical']),
            'enable_open_graph' => ! empty($settings['enable_open_graph']),
            'enable_schema' => ! empty($settings['enable_schema']),
            'entity_type' => $entity_type,
            'organization_name' => sanitize_text_field((string) ($settings['organization_name'] ?? '')),
            'organization_description' => sanitize_textarea_field((string) ($settings['organization_description'] ?? '')),
            'phone' => sanitize_text_field((string) ($settings['phone'] ?? '')),
            'email' => sanitize_email((string) ($settings['email'] ?? '')),
            'street_address' => sanitize_text_field((string) ($settings['street_address'] ?? '')),
            'locality' => sanitize_text_field((string) ($settings['locality'] ?? '')),
            'region' => sanitize_text_field((string) ($settings['region'] ?? '')),
            'postal_code' => sanitize_text_field((string) ($settings['postal_code'] ?? '')),
            'country' => $country,
            'twitter_handle' => ltrim(sanitize_text_field((string) ($settings['twitter_handle'] ?? '')), '@'),
            'default_social_image_id' => $social_image_id,
        ],
        true
    );
}

/**
 * Detect common full SEO plugins so Staark does not emit duplicate tags.
 *
 * @return array{active:bool,name:string}
 */
function staark_hub_seo_conflict(): array
{
    $plugins = get_option('active_plugins', []);
    $plugins = is_array($plugins) ? $plugins : [];

    if (is_multisite()) {
        $network = get_site_option('active_sitewide_plugins', []);
        if (is_array($network)) {
            $plugins = array_values(array_unique(array_merge($plugins, array_keys($network))));
        }
    }

    $known = [
        'wordpress-seo/wp-seo.php' => 'Yoast SEO',
        'seo-by-rank-math/rank-math.php' => 'Rank Math',
        'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
        'wp-seopress/seopress.php' => 'SEOPress',
    ];

    foreach ($known as $plugin => $name) {
        if (in_array($plugin, $plugins, true)) {
            return ['active' => true, 'name' => $name];
        }
    }

    if (defined('WPSEO_VERSION')) {
        return ['active' => true, 'name' => 'Yoast SEO'];
    }
    if (defined('RANK_MATH_VERSION')) {
        return ['active' => true, 'name' => 'Rank Math'];
    }
    if (defined('AIOSEO_VERSION')) {
        return ['active' => true, 'name' => 'All in One SEO'];
    }
    if (defined('SEOPRESS_VERSION')) {
        return ['active' => true, 'name' => 'SEOPress'];
    }

    return ['active' => false, 'name' => ''];
}

function staark_hub_seo_runtime_active(): bool
{
    $conflict = staark_hub_seo_conflict();

    return staark_hub_module_enabled('seo', true) && ! $conflict['active'];
}

function staark_hub_seo_post_meta(int $post_id, string $key): string
{
    return sanitize_text_field((string) get_post_meta($post_id, '_staark_seo_' . $key, true));
}

function staark_hub_seo_post_description(int $post_id): string
{
    return sanitize_textarea_field((string) get_post_meta($post_id, '_staark_seo_description', true));
}

function staark_hub_seo_post_noindex(int $post_id): bool
{
    return (string) get_post_meta($post_id, '_staark_seo_noindex', true) === '1';
}

/**
 * Return a useful description without inventing copy. Custom SEO description
 * wins; then the authored excerpt/content; finally the site tagline.
 */
function staark_hub_seo_description(): string
{
    if (is_singular()) {
        $post_id = (int) get_queried_object_id();
        $custom = staark_hub_seo_post_description($post_id);
        if ($custom !== '') {
            return $custom;
        }

        $post = get_post($post_id);
        if ($post instanceof WP_Post) {
            $source = trim((string) $post->post_excerpt);
            if ($source === '') {
                $source = trim(wp_strip_all_tags(strip_shortcodes((string) $post->post_content)));
            }
            if ($source !== '') {
                return trim(wp_html_excerpt($source, 160, '…'));
            }
        }
    }

    return trim(sanitize_text_field((string) get_bloginfo('description')));
}

function staark_hub_seo_canonical_url(): string
{
    if (is_singular()) {
        $post_id = (int) get_queried_object_id();
        $custom = esc_url_raw((string) get_post_meta($post_id, '_staark_seo_canonical', true));
        if ($custom !== '' && wp_http_validate_url($custom)) {
            return $custom;
        }

        $permalink = get_permalink($post_id);
        return is_string($permalink) ? $permalink : '';
    }

    if (is_front_page()) {
        return home_url('/');
    }

    if (is_home()) {
        $posts_page = (int) get_option('page_for_posts');
        if ($posts_page > 0) {
            $url = get_permalink($posts_page);
            return is_string($url) ? $url : '';
        }
        return home_url('/');
    }

    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $url = get_term_link($term);
            return is_wp_error($url) ? '' : (string) $url;
        }
    }

    if (is_post_type_archive()) {
        $post_type = get_query_var('post_type');
        if (is_array($post_type)) {
            $post_type = reset($post_type);
        }
        if (is_string($post_type) && $post_type !== '') {
            $url = get_post_type_archive_link($post_type);
            return is_string($url) ? $url : '';
        }
    }

    return '';
}

function staark_hub_seo_title_filter(string $title): string
{
    if (! is_singular()) {
        return $title;
    }

    $custom = staark_hub_seo_post_meta((int) get_queried_object_id(), 'title');
    return $custom !== '' ? $custom : $title;
}

/**
 * @param array<string,bool> $robots
 * @return array<string,bool>
 */
function staark_hub_seo_robots(array $robots): array
{
    if (is_singular() && staark_hub_seo_post_noindex((int) get_queried_object_id())) {
        unset($robots['index']);
        $robots['noindex'] = true;
    }

    return $robots;
}

function staark_hub_seo_social_image_url(): string
{
    $post_id = is_singular() ? (int) get_queried_object_id() : 0;
    if ($post_id > 0 && has_post_thumbnail($post_id)) {
        $url = get_the_post_thumbnail_url($post_id, 'full');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $settings = staark_hub_seo_settings();
    $image_id = absint($settings['default_social_image_id']);
    if ($image_id > 0) {
        $url = wp_get_attachment_image_url($image_id, 'full');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $icon = get_site_icon_url(512);
    return is_string($icon) ? $icon : '';
}

/**
 * @return array<int,array<string,mixed>>
 */
function staark_hub_seo_breadcrumb_items(): array
{
    if (! is_singular()) {
        return [];
    }

    $post_id = (int) get_queried_object_id();
    $items = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => (string) get_bloginfo('name'),
            'item' => home_url('/'),
        ],
    ];

    $position = 2;
    if (is_page($post_id)) {
        $ancestors = array_reverse(get_post_ancestors($post_id));
        foreach ($ancestors as $ancestor_id) {
            $url = get_permalink($ancestor_id);
            if (! is_string($url) || $url === '') {
                continue;
            }
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($ancestor_id),
                'item' => $url,
            ];
        }
    }

    $current_url = get_permalink($post_id);
    if (is_string($current_url) && $current_url !== '') {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_the_title($post_id),
            'item' => $current_url,
        ];
    }

    return count($items) > 1 ? $items : [];
}

/**
 * Build a conservative Schema.org graph from explicit site information.
 *
 * @return array<int,array<string,mixed>>
 */
function staark_hub_seo_schema_graph(): array
{
    $settings = staark_hub_seo_settings();
    $home = home_url('/');
    $canonical = staark_hub_seo_canonical_url();
    $entity_id = $home . '#organization';
    $website_id = $home . '#website';
    $graph = [];

    $entity = [
        '@type' => $settings['entity_type'],
        '@id' => $entity_id,
        'name' => $settings['organization_name'] !== '' ? $settings['organization_name'] : (string) get_bloginfo('name'),
        'url' => $home,
    ];

    if ($settings['organization_description'] !== '') {
        $entity['description'] = $settings['organization_description'];
    }
    if ($settings['phone'] !== '') {
        $entity['telephone'] = $settings['phone'];
    }
    if ($settings['email'] !== '') {
        $entity['email'] = $settings['email'];
    }

    $logo_url = '';
    if ($settings['default_social_image_id'] > 0) {
        $candidate = wp_get_attachment_image_url($settings['default_social_image_id'], 'full');
        $logo_url = is_string($candidate) ? $candidate : '';
    }
    if ($logo_url !== '') {
        $entity['logo'] = [
            '@type' => 'ImageObject',
            'url' => $logo_url,
        ];
        $entity['image'] = $logo_url;
    }

    if ($settings['entity_type'] === 'LocalBusiness') {
        $address = array_filter(
            [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings['street_address'],
                'addressLocality' => $settings['locality'],
                'addressRegion' => $settings['region'],
                'postalCode' => $settings['postal_code'],
                'addressCountry' => $settings['country'],
            ],
            static fn ($value, $key): bool => $key === '@type' || (string) $value !== '',
            ARRAY_FILTER_USE_BOTH
        );

        if (count($address) > 1) {
            $entity['address'] = $address;
        }
    }

    $graph[] = $entity;
    $graph[] = [
        '@type' => 'WebSite',
        '@id' => $website_id,
        'url' => $home,
        'name' => (string) get_bloginfo('name'),
        'publisher' => ['@id' => $entity_id],
        'inLanguage' => get_bloginfo('language'),
    ];

    if ($canonical !== '') {
        $webpage_id = $canonical . '#webpage';
        $webpage = [
            '@type' => 'WebPage',
            '@id' => $webpage_id,
            'url' => $canonical,
            'name' => wp_get_document_title(),
            'isPartOf' => ['@id' => $website_id],
            'about' => ['@id' => $entity_id],
            'inLanguage' => get_bloginfo('language'),
        ];

        $description = staark_hub_seo_description();
        if ($description !== '') {
            $webpage['description'] = $description;
        }

        $graph[] = $webpage;

        $breadcrumbs = staark_hub_seo_breadcrumb_items();
        if ($breadcrumbs !== []) {
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $canonical . '#breadcrumb',
                'itemListElement' => $breadcrumbs,
            ];
        }

        if (is_singular('post')) {
            $post_id = (int) get_queried_object_id();
            $article = [
                '@type' => 'Article',
                '@id' => $canonical . '#article',
                'headline' => get_the_title($post_id),
                'mainEntityOfPage' => ['@id' => $webpage_id],
                'publisher' => ['@id' => $entity_id],
                'datePublished' => get_post_time(DATE_W3C, true, $post_id),
                'dateModified' => get_post_modified_time(DATE_W3C, true, $post_id),
            ];

            $image = staark_hub_seo_social_image_url();
            if ($image !== '') {
                $article['image'] = [$image];
            }

            $graph[] = $article;
        }
    }

    /** @var array<int,array<string,mixed>> $graph */
    return apply_filters('staark_hub_seo_schema_graph', $graph);
}

function staark_hub_seo_render_head(): void
{
    if (! staark_hub_seo_runtime_active() || is_feed() || is_404() || (function_exists('is_robots') && is_robots())) {
        return;
    }

    $settings = staark_hub_seo_settings();
    $description = staark_hub_seo_description();
    $canonical = staark_hub_seo_canonical_url();
    $title = wp_get_document_title();
    $image = staark_hub_seo_social_image_url();

    echo "\n<!-- Staark SEO -->\n";

    if ($settings['enable_metadata'] && $description !== '') {
        printf("<meta name=\"description\" content=\"%s\">\n", esc_attr($description));
    }

    if ($settings['enable_canonical'] && $canonical !== '') {
        printf("<link rel=\"canonical\" href=\"%s\">\n", esc_url($canonical));
    }

    if ($settings['enable_open_graph']) {
        printf("<meta property=\"og:type\" content=\"%s\">\n", is_singular('post') ? 'article' : 'website');
        printf("<meta property=\"og:title\" content=\"%s\">\n", esc_attr($title));
        if ($description !== '') {
            printf("<meta property=\"og:description\" content=\"%s\">\n", esc_attr($description));
        }
        if ($canonical !== '') {
            printf("<meta property=\"og:url\" content=\"%s\">\n", esc_url($canonical));
        }
        printf("<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr((string) get_bloginfo('name')));
        printf("<meta property=\"og:locale\" content=\"%s\">\n", esc_attr(str_replace('-', '_', (string) get_bloginfo('language'))));
        if ($image !== '') {
            printf("<meta property=\"og:image\" content=\"%s\">\n", esc_url($image));
        }

        echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        printf("<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr($title));
        if ($description !== '') {
            printf("<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr($description));
        }
        if ($image !== '') {
            printf("<meta name=\"twitter:image\" content=\"%s\">\n", esc_url($image));
        }
        if ($settings['twitter_handle'] !== '') {
            printf("<meta name=\"twitter:site\" content=\"@%s\">\n", esc_attr($settings['twitter_handle']));
        }
    }

    if ($settings['enable_schema']) {
        $graph = staark_hub_seo_schema_graph();
        if ($graph !== []) {
            $json = wp_json_encode(
                [
                    '@context' => 'https://schema.org',
                    '@graph' => $graph,
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
            if (is_string($json) && $json !== '') {
                echo '<script type="application/ld+json">' . $json . "</script>\n";
            }
        }
    }

    echo "<!-- /Staark SEO -->\n";
}

function staark_hub_seo_sitemap_enabled(): bool
{
    return (bool) apply_filters('wp_sitemaps_enabled', true);
}

/**
 * @return array{total:int,customTitles:int,customDescriptions:int,noindex:int,truncated:bool}
 */
function staark_hub_seo_content_stats(int $limit = 500): array
{
    $ids = get_posts(
        [
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => $limit + 1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => true,
        ]
    );

    $truncated = count($ids) > $limit;
    $ids = array_slice(array_map('intval', $ids), 0, $limit);
    $titles = 0;
    $descriptions = 0;
    $noindex = 0;

    foreach ($ids as $id) {
        if (staark_hub_seo_post_meta($id, 'title') !== '') {
            ++$titles;
        }
        if (staark_hub_seo_post_description($id) !== '') {
            ++$descriptions;
        }
        if (staark_hub_seo_post_noindex($id)) {
            ++$noindex;
        }
    }

    return [
        'total' => count($ids),
        'customTitles' => $titles,
        'customDescriptions' => $descriptions,
        'noindex' => $noindex,
        'truncated' => $truncated,
    ];
}

/**
 * @return array{checked:int,missing:int,truncated:bool}
 */
function staark_hub_seo_alt_stats(int $limit = 250): array
{
    $ids = get_posts(
        [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit + 1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'DESC',
            'suppress_filters' => true,
        ]
    );

    $truncated = count($ids) > $limit;
    $ids = array_slice(array_map('intval', $ids), 0, $limit);
    $missing = 0;

    foreach ($ids as $id) {
        if (trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)) === '') {
            ++$missing;
        }
    }

    return [
        'checked' => count($ids),
        'missing' => $missing,
        'truncated' => $truncated,
    ];
}

/**
 * @return array{complete:int,total:int,percent:int}
 */
function staark_hub_seo_local_completeness(): array
{
    $settings = staark_hub_seo_settings();
    $fields = [
        $settings['organization_name'],
        $settings['phone'] !== '' || $settings['email'] !== '' ? 'contact' : '',
    ];

    if ($settings['entity_type'] === 'LocalBusiness') {
        $fields[] = $settings['street_address'];
        $fields[] = $settings['locality'];
        $fields[] = $settings['postal_code'];
        $fields[] = $settings['country'];
    }

    $total = count($fields);
    $complete = count(array_filter($fields, static fn ($value): bool => trim((string) $value) !== ''));

    return [
        'complete' => $complete,
        'total' => $total,
        'percent' => $total > 0 ? (int) round(($complete / $total) * 100) : 100,
    ];
}

/**
 * Connector-safe SEO health summary. No page content or private metadata is sent.
 *
 * @return array{enabled:bool,conflict:string,indexable:bool,sitemap:bool,entityType:string,localCompleteness:int}
 */
function staark_hub_seo_summary(): array
{
    $conflict = staark_hub_seo_conflict();
    $settings = staark_hub_seo_settings();
    $local = staark_hub_seo_local_completeness();

    return [
        'enabled' => staark_hub_module_enabled('seo', true),
        'conflict' => $conflict['active'] ? $conflict['name'] : '',
        'indexable' => (string) get_option('blog_public', '1') === '1',
        'sitemap' => staark_hub_seo_sitemap_enabled(),
        'entityType' => $settings['entity_type'],
        'localCompleteness' => $local['percent'],
    ];
}

/**
 * Lightweight editorial hints. These are counts, not SEO grades: the active
 * theme may render headings/navigation outside post_content.
 *
 * @return array{headings:int,links:int,images:int,imagesMissingAlt:int}
 */
function staark_hub_seo_content_hints(WP_Post $post): array
{
    $html = do_blocks((string) $post->post_content);
    $headings = preg_match_all('/<h[1-6]\b/i', $html, $heading_matches);
    $links = preg_match_all('/<a\s[^>]*href=/i', $html, $link_matches);
    preg_match_all('/<img\b[^>]*>/i', $html, $image_matches);
    $images = isset($image_matches[0]) ? count($image_matches[0]) : 0;
    $missing_alt = 0;

    foreach ($image_matches[0] ?? [] as $tag) {
        if (! preg_match('/\salt\s*=\s*(["\'])[^"\']+\1/i', (string) $tag)) {
            ++$missing_alt;
        }
    }

    return [
        'headings' => is_int($headings) ? $headings : 0,
        'links' => is_int($links) ? $links : 0,
        'images' => $images,
        'imagesMissingAlt' => $missing_alt,
    ];
}

function staark_hub_seo_register_meta_boxes(): void
{
    $post_types = get_post_types(['public' => true], 'names');
    foreach ($post_types as $post_type) {
        if ($post_type === 'attachment') {
            continue;
        }

        add_meta_box(
            'staark-seo-meta',
            __('Staark SEO', 'staark-core'),
            'staark_hub_seo_render_meta_box',
            $post_type,
            'normal',
            'default'
        );
    }
}

function staark_hub_seo_render_meta_box(WP_Post $post): void
{
    $title = (string) get_post_meta($post->ID, '_staark_seo_title', true);
    $description = (string) get_post_meta($post->ID, '_staark_seo_description', true);
    $canonical = (string) get_post_meta($post->ID, '_staark_seo_canonical', true);
    $noindex = staark_hub_seo_post_noindex($post->ID);
    $conflict = staark_hub_seo_conflict();
    $hints = staark_hub_seo_content_hints($post);

    wp_nonce_field('staark_seo_post_' . $post->ID, 'staark_seo_nonce');
    ?>
    <?php if ($conflict['active']) : ?>
        <p><strong><?php echo esc_html($conflict['name']); ?> detected.</strong> Staark frontend SEO output is paused to prevent duplicate metadata. These values remain stored in case that plugin is removed later.</p>
    <?php endif; ?>
    <p>
        <label for="staark_seo_title"><strong>SEO title</strong></label><br>
        <input class="widefat" type="text" id="staark_seo_title" name="staark_seo_title" value="<?php echo esc_attr($title); ?>" maxlength="200" placeholder="Leave blank to use the WordPress title">
        <small><?php echo esc_html(strlen($title) . ' characters'); ?> · Usually keep the visible search title concise and specific.</small>
    </p>
    <p>
        <label for="staark_seo_description"><strong>Meta description</strong></label><br>
        <textarea class="widefat" id="staark_seo_description" name="staark_seo_description" rows="3" maxlength="320" placeholder="Short search/social description"><?php echo esc_textarea($description); ?></textarea>
        <small><?php echo esc_html(strlen($description) . ' characters'); ?> · A custom description is optional; Staark otherwise uses authored excerpt/content or the site tagline.</small>
    </p>
    <p>
        <label for="staark_seo_canonical"><strong>Canonical URL</strong></label><br>
        <input class="widefat" type="url" id="staark_seo_canonical" name="staark_seo_canonical" value="<?php echo esc_attr($canonical); ?>" placeholder="Automatic permalink">
        <small>Leave blank unless this content intentionally points to another canonical URL.</small>
    </p>
    <p>
        <label><input type="checkbox" name="staark_seo_noindex" value="1" <?php checked($noindex); ?>> <strong>Exclude this content from search indexing</strong></label><br>
        <small>Adds a noindex robots directive. It does not delete the page or password-protect it.</small>
    </p>
    <p>
        <strong>Content hints:</strong>
        <?php echo esc_html(sprintf('%d heading(s) · %d link(s) · %d image(s) · %d image(s) missing non-empty alt text in post content', $hints['headings'], $hints['links'], $hints['images'], $hints['imagesMissingAlt'])); ?>
        <br><small>Informational only. Theme templates can add titles, navigation and images outside the editor content.</small>
    </p>
    <?php
}

function staark_hub_seo_save_post(int $post_id, WP_Post $post): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id) || ! isset($_POST['staark_seo_nonce'])) {
        return;
    }
    if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['staark_seo_nonce'])), 'staark_seo_post_' . $post_id)) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $title = isset($_POST['staark_seo_title']) ? sanitize_text_field(wp_unslash($_POST['staark_seo_title'])) : '';
    $description = isset($_POST['staark_seo_description']) ? sanitize_textarea_field(wp_unslash($_POST['staark_seo_description'])) : '';
    $canonical = isset($_POST['staark_seo_canonical']) ? esc_url_raw(wp_unslash($_POST['staark_seo_canonical'])) : '';
    if ($canonical !== '' && ! wp_http_validate_url($canonical)) {
        $canonical = '';
    }

    $pairs = [
        '_staark_seo_title' => $title,
        '_staark_seo_description' => $description,
        '_staark_seo_canonical' => $canonical,
        '_staark_seo_noindex' => isset($_POST['staark_seo_noindex']) ? '1' : '',
    ];

    foreach ($pairs as $key => $value) {
        if ($value === '') {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }
}

/**
 * Keep explicitly noindexed posts/pages out of the native WordPress sitemap.
 *
 * @param array<string,mixed> $args
 * @return array<string,mixed>
 */
function staark_hub_seo_sitemap_post_args(array $args, string $post_type): array
{
    if (! post_type_exists($post_type)) {
        return $args;
    }

    $meta_query = isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : [];
    $meta_query[] = [
        'relation' => 'OR',
        [
            'key' => '_staark_seo_noindex',
            'compare' => 'NOT EXISTS',
        ],
        [
            'key' => '_staark_seo_noindex',
            'value' => '1',
            'compare' => '!=',
        ],
    ];
    $args['meta_query'] = $meta_query;

    return $args;
}

function staark_hub_seo_register_frontend(): void
{
    add_action('add_meta_boxes', 'staark_hub_seo_register_meta_boxes');
    add_action('save_post', 'staark_hub_seo_save_post', 10, 2);

    if (! staark_hub_seo_runtime_active()) {
        return;
    }

    $settings = staark_hub_seo_settings();
    add_filter('pre_get_document_title', 'staark_hub_seo_title_filter', 20);
    add_filter('wp_robots', 'staark_hub_seo_robots', 20);
    add_filter('wp_sitemaps_posts_query_args', 'staark_hub_seo_sitemap_post_args', 20, 2);

    if ($settings['enable_canonical']) {
        remove_action('wp_head', 'rel_canonical');
    }

    add_action('wp_head', 'staark_hub_seo_render_head', 2);
}

add_action('init', 'staark_hub_seo_register_frontend', 20);

add_action('admin_post_staark_seo_save', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_seo_save');

    staark_hub_set_module_enabled('seo', staark_hub_checkbox_value('module_enabled'));
    staark_hub_seo_save_settings(
        [
            'enable_metadata' => staark_hub_checkbox_value('enable_metadata'),
            'enable_canonical' => staark_hub_checkbox_value('enable_canonical'),
            'enable_open_graph' => staark_hub_checkbox_value('enable_open_graph'),
            'enable_schema' => staark_hub_checkbox_value('enable_schema'),
            'entity_type' => isset($_POST['entity_type']) ? sanitize_text_field(wp_unslash($_POST['entity_type'])) : 'LocalBusiness',
            'organization_name' => isset($_POST['organization_name']) ? wp_unslash($_POST['organization_name']) : '',
            'organization_description' => isset($_POST['organization_description']) ? wp_unslash($_POST['organization_description']) : '',
            'phone' => isset($_POST['phone']) ? wp_unslash($_POST['phone']) : '',
            'email' => isset($_POST['email']) ? wp_unslash($_POST['email']) : '',
            'street_address' => isset($_POST['street_address']) ? wp_unslash($_POST['street_address']) : '',
            'locality' => isset($_POST['locality']) ? wp_unslash($_POST['locality']) : '',
            'region' => isset($_POST['region']) ? wp_unslash($_POST['region']) : '',
            'postal_code' => isset($_POST['postal_code']) ? wp_unslash($_POST['postal_code']) : '',
            'country' => isset($_POST['country']) ? wp_unslash($_POST['country']) : '',
            'twitter_handle' => isset($_POST['twitter_handle']) ? wp_unslash($_POST['twitter_handle']) : '',
            'default_social_image_id' => isset($_POST['default_social_image_id']) ? absint($_POST['default_social_image_id']) : 0,
        ]
    );

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-seo&staark_seo=saved'));
    exit;
});
