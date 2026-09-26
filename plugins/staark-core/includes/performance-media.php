<?php
/**
 * Staark Performance — Images & Cache.
 *
 * - WebP derivatives for JPEG/PNG uploads.
 * - Configurable WebP quality.
 * - Conservative WordPress-native lazy-loading tuning.
 * - Existing-media optimization in bounded batches.
 * - Static asset cache diagnostics.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_performance_webp_supported(): bool
{
    return function_exists('wp_image_editor_supports')
        && wp_image_editor_supports(['mime_type' => 'image/webp']);
}

/**
 * Detect whether an attachment already has at least one WebP derivative.
 */
function staark_hub_performance_attachment_has_webp(int $attachment_id): bool
{
    $metadata = wp_get_attachment_metadata($attachment_id);

    if (! is_array($metadata) || empty($metadata['sizes']) || ! is_array($metadata['sizes'])) {
        return false;
    }

    foreach ($metadata['sizes'] as $size) {
        if (! is_array($size)) {
            continue;
        }

        $mime = strtolower((string) ($size['mime-type'] ?? $size['mime_type'] ?? ''));
        $file = strtolower((string) ($size['file'] ?? ''));

        if ($mime === 'image/webp' || str_ends_with($file, '.webp')) {
            return true;
        }
    }

    return false;
}

/**
 * @return array{
 *   sample:int,
 *   legacyOriginals:int,
 *   modernOriginals:int,
 *   webpDerivativeSets:int,
 *   webpSupported:bool
 * }
 */
function staark_hub_performance_media_audit(int $limit = 40): array
{
    $limit = max(10, min(80, $limit));

    $ids = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => 'image',
        'posts_per_page' => $limit,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
        'suppress_filters' => true,
    ]);

    $legacy = 0;
    $modern = 0;
    $webp_sets = 0;

    foreach ($ids as $id) {
        $id = (int) $id;
        $mime = strtolower((string) get_post_mime_type($id));

        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            ++$legacy;

            if (staark_hub_performance_attachment_has_webp($id)) {
                ++$webp_sets;
            }
        } elseif (in_array($mime, ['image/webp', 'image/avif'], true)) {
            ++$modern;
        }
    }

    /*
     * Theme images are not WordPress attachments.
     * Include JPEG/PNG source assets and their side-by-side WebP derivatives
     * so the audit reflects what the frontend actually uses.
     */
    $theme_root = get_theme_file_path('assets/images');
    $theme_legacy = 0;
    $theme_webp = 0;
    $theme_scanned = 0;

    if (is_dir($theme_root)) {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $theme_root,
                    FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if ($theme_scanned >= 250) {
                    break;
                }

                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                $extension = strtolower((string) $file->getExtension());

                if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                    continue;
                }

                ++$theme_scanned;
                ++$theme_legacy;

                $webp_file = $file->getPath()
                    . DIRECTORY_SEPARATOR
                    . $file->getBasename('.' . $file->getExtension())
                    . '.webp';

                if (is_file($webp_file)) {
                    ++$theme_webp;
                }
            }
        } catch (Throwable $error) {
            // Theme filesystem problems must not break wp-admin.
        }
    }

    $legacy += $theme_legacy;
    $webp_sets += $theme_webp;

    return [
        'sample' => count($ids) + $theme_scanned,
        'legacyOriginals' => $legacy,
        'modernOriginals' => $modern,
        'webpDerivativeSets' => $webp_sets,
        'webpSupported' => staark_hub_performance_webp_supported(),
        'themeLegacy' => $theme_legacy,
        'themeWebp' => $theme_webp,
    ];
}

function staark_hub_performance_webp_output_format(
    array $formats,
    string $filename = '',
    string $mime_type = ''
): array {
    $settings = staark_hub_performance_settings();

    if (
        ! staark_hub_module_enabled('performance', true)
        || empty($settings['generate_webp'])
        || ! staark_hub_performance_webp_supported()
    ) {
        return $formats;
    }

    $formats['image/jpeg'] = 'image/webp';
    $formats['image/png'] = 'image/webp';

    return $formats;
}

function staark_hub_performance_webp_quality(
    int $quality,
    string $mime_type,
    array $size = []
): int {
    if ($mime_type !== 'image/webp') {
        return $quality;
    }

    $settings = staark_hub_performance_settings();

    return max(60, min(95, (int) ($settings['webp_quality'] ?? 82)));
}

/**
 * Keep WordPress Core's native loading heuristics but reduce the default
 * number of early images excluded from lazy loading.
 *
 * We deliberately keep the first two eligible images eager so logos/heroes/LCP
 * candidates are not blindly lazy-loaded.
 */
function staark_hub_performance_lazy_threshold(int $threshold): int
{
    $settings = staark_hub_performance_settings();

    if (
        ! staark_hub_module_enabled('performance', true)
        || empty($settings['smart_lazy_images'])
    ) {
        return $threshold;
    }

    return min($threshold, 2);
}


/**
 * Optimize rendered core/image blocks conservatively.
 *
 * - First two images stay eligible for eager loading / LCP.
 * - Later images receive loading="lazy".
 * - Lazy images receive decoding="async".
 * - Missing intrinsic width/height are recovered from attachment metadata.
 */
function staark_hub_performance_optimize_image_block(
    string $block_content,
    array $block
): string {
    if (
        is_admin()
        || ! staark_hub_module_enabled('performance', true)
        || ($block['blockName'] ?? '') !== 'core/image'
        || ! class_exists('WP_HTML_Tag_Processor')
    ) {
        return $block_content;
    }

    $settings = staark_hub_performance_settings();

    $processor = new WP_HTML_Tag_Processor($block_content);

    if (! $processor->next_tag('IMG')) {
        return $block_content;
    }

    /*
     * Count only actual content-image blocks.
     * Keep the first two conservative so we do not accidentally lazy-load
     * a hero/LCP candidate.
     */
    static $image_index = 0;
    ++$image_index;

    $loading = strtolower(
        (string) ($processor->get_attribute('loading') ?? '')
    );

    $fetchpriority = strtolower(
        (string) ($processor->get_attribute('fetchpriority') ?? '')
    );

    $priority_image = $loading === 'eager'
        || $fetchpriority === 'high'
        || $image_index <= 2;

    if (
        ! empty($settings['smart_lazy_images'])
        && ! $priority_image
    ) {
        $processor->set_attribute('loading', 'lazy');

        if (! $processor->get_attribute('decoding')) {
            $processor->set_attribute('decoding', 'async');
        }
    }

    /*
     * Restore intrinsic dimensions when the block markup does not contain
     * them. Prefer the block attachment ID, then try resolving the src URL.
     */
    $has_width = $processor->get_attribute('width') !== null;
    $has_height = $processor->get_attribute('height') !== null;

    if (! $has_width || ! $has_height) {
        $attachment_id = absint($block['attrs']['id'] ?? 0);

        if ($attachment_id === 0) {
            $src = html_entity_decode(
                (string) ($processor->get_attribute('src') ?? ''),
                ENT_QUOTES
            );

            if ($src !== '') {
                $attachment_id = attachment_url_to_postid($src);
            }
        }

        if ($attachment_id > 0) {
            $size_slug = isset($block['attrs']['sizeSlug'])
                ? sanitize_key((string) $block['attrs']['sizeSlug'])
                : 'full';

            $image = wp_get_attachment_image_src(
                $attachment_id,
                $size_slug !== '' ? $size_slug : 'full'
            );

            if (is_array($image)) {
                if (! $has_width && ! empty($image[1])) {
                    $processor->set_attribute(
                        'width',
                        (string) absint($image[1])
                    );
                }

                if (! $has_height && ! empty($image[2])) {
                    $processor->set_attribute(
                        'height',
                        (string) absint($image[2])
                    );
                }
            }
        }
    }

    return $processor->get_updated_html();
}

add_filter(
    'render_block',
    'staark_hub_performance_optimize_image_block',
    30,
    2
);


/**
 * Resolve a public active-theme asset URL to a safe local file path.
 */
function staark_hub_performance_theme_asset_path(string $url): string
{
    $url_path = wp_parse_url($url, PHP_URL_PATH);

    if (! is_string($url_path) || $url_path === '') {
        return '';
    }

    $roots = [
        [
            'uri' => get_stylesheet_directory_uri(),
            'dir' => get_stylesheet_directory(),
        ],
        [
            'uri' => get_template_directory_uri(),
            'dir' => get_template_directory(),
        ],
    ];

    foreach ($roots as $root) {
        $base_path = wp_parse_url((string) $root['uri'], PHP_URL_PATH);

        if (! is_string($base_path) || $base_path === '') {
            continue;
        }

        $base_path = trailingslashit($base_path);

        if (! str_starts_with($url_path, $base_path)) {
            continue;
        }

        $relative = rawurldecode(
            ltrim(substr($url_path, strlen($base_path)), '/')
        );

        if ($relative === '' || str_contains($relative, '..')) {
            return '';
        }

        $theme_root = realpath((string) $root['dir']);

        if (! is_string($theme_root)) {
            return '';
        }

        $candidate = realpath(
            trailingslashit((string) $root['dir']) . $relative
        );

        if (
            ! is_string($candidate)
            || ! str_starts_with(
                $candidate,
                $theme_root . DIRECTORY_SEPARATOR
            )
            || ! is_file($candidate)
        ) {
            return '';
        }

        $extension = strtolower(
            pathinfo($candidate, PATHINFO_EXTENSION)
        );

        if (! in_array(
            $extension,
            ['jpg', 'jpeg', 'png', 'webp', 'avif'],
            true
        )) {
            return '';
        }

        return $candidate;
    }

    return '';
}

/**
 * Optimize raw <img> elements inside core/html blocks.
 *
 * Staark showcase patterns use raw HTML with theme assets, therefore the
 * regular core/image attachment filter cannot see them.
 */

/**
 * Build responsive WebP attributes for a static theme image.
 *
 * @return array{src:string,srcset:string,sizes:string}
 */
function staark_hub_performance_responsive_webp_data(
    string $source_file,
    string $source_url
): array {
    $result = [
        'src' => '',
        'srcset' => '',
        'sizes' => '',
    ];

    $base_file = dirname($source_file)
        . DIRECTORY_SEPARATOR
        . pathinfo($source_file, PATHINFO_FILENAME);

    $full_file = $base_file . '.webp';

    if (! is_file($full_file)) {
        return $result;
    }

    $full_url = preg_replace(
        '/\.(?:jpe?g|png)(?=([?#]|$))/i',
        '.webp',
        $source_url
    );

    if (! is_string($full_url) || $full_url === '') {
        return $result;
    }

    $full_url = add_query_arg(
        'ver',
        (string) filemtime($full_file),
        $full_url
    );

    $candidates = [];

    foreach ([480, 768] as $width) {
        $variant_file = $base_file
            . '-'
            . $width
            . '.webp';

        if (! is_file($variant_file)) {
            continue;
        }

        // Also for .webp sources (S-Hub Light patterns reference WebP files
        // directly): cta.webp -> cta-480.webp.
        $variant_url = preg_replace(
            '/\.(?:jpe?g|png|webp)(?=([?#]|$))/i',
            '-'
                . $width
                . '.webp',
            $source_url
        );

        if (! is_string($variant_url) || $variant_url === '') {
            continue;
        }

        $variant_url = add_query_arg(
            'ver',
            (string) filemtime($variant_file),
            $variant_url
        );

        $candidates[] = $variant_url
            . ' '
            . $width
            . 'w';
    }

    $full_size = @getimagesize($full_file);

    if (
        is_array($full_size)
        && ! empty($full_size[0])
    ) {
        $candidates[] = $full_url
            . ' '
            . (int) $full_size[0]
            . 'w';
    }

    $result['src'] = $full_url;
    $result['srcset'] = implode(', ', $candidates);

    /*
     * Mobile: viewport width minus normal page gutters.
     * Desktop: current Staark visual image column is capped around 768px.
     */
    $result['sizes'] =
        '(max-width: 782px) calc(100vw - 32px), 768px';

    return $result;
}

function staark_hub_performance_optimize_static_html_images(
    string $block_content,
    array $block
): string {
    if (
        is_admin()
        || ! staark_hub_module_enabled('performance', true)
        || ($block['blockName'] ?? '') !== 'core/html'
        || ! class_exists('WP_HTML_Tag_Processor')
        || ! str_contains($block_content, '<img')
    ) {
        return $block_content;
    }

    $settings = staark_hub_performance_settings();
    $processor = new WP_HTML_Tag_Processor($block_content);

    static $theme_image_index = 0;
    static $dimension_cache = [];

    while ($processor->next_tag('IMG')) {
        $src = html_entity_decode(
            (string) ($processor->get_attribute('src') ?? ''),
            ENT_QUOTES
        );

        if ($src === '') {
            continue;
        }

        $file = staark_hub_performance_theme_asset_path($src);

        /*
         * Only touch images physically owned by the active theme.
         * External images and unrelated HTML remain untouched.
         */
        if ($file === '') {
            continue;
        }

        /*
         * Prefer a side-by-side WebP derivative when available.
         * The original JPEG/PNG remains untouched on disk.
         */
        $settings = staark_hub_performance_settings();
        if (! empty($settings['generate_webp'])) {
            $webp_file = dirname($file)
                . DIRECTORY_SEPARATOR
                . pathinfo($file, PATHINFO_FILENAME)
                . '.webp';

            if (is_file($webp_file)) {
                $webp_url = preg_replace(
                    '/\.(?:jpe?g|png)(?=($|[?#]))/i',
                    '.webp',
                    $src
                );

                if (is_string($webp_url) && $webp_url !== '') {
                    $version = (string) filemtime($webp_file);

                    $processor->set_attribute(
                        'src',
                        add_query_arg('ver', $version, $webp_url)
                    );

                    $responsive = staark_hub_performance_responsive_webp_data(
                        $file,
                        $src
                    );

                    if (! empty($responsive['src'])) {
                        $processor->set_attribute(
                            'src',
                            $responsive['src']
                        );
                    }

                    if (! empty($responsive['srcset'])) {
                        $processor->set_attribute(
                            'srcset',
                            $responsive['srcset']
                        );

                        $processor->set_attribute(
                            'sizes',
                            $responsive['sizes']
                        );
                    }
                }
            }
        }

        ++$theme_image_index;

        /*
         * Restore intrinsic dimensions from the actual file.
         */
        $has_width = $processor->get_attribute('width') !== null;
        $has_height = $processor->get_attribute('height') !== null;

        if (! $has_width || ! $has_height) {
            if (! isset($dimension_cache[$file])) {
                $size = @getimagesize($file);

                $dimension_cache[$file] = is_array($size)
                    ? [
                        'width' => absint($size[0] ?? 0),
                        'height' => absint($size[1] ?? 0),
                    ]
                    : [
                        'width' => 0,
                        'height' => 0,
                    ];
            }

            $dimensions = $dimension_cache[$file];

            if (! $has_width && $dimensions['width'] > 0) {
                $processor->set_attribute(
                    'width',
                    (string) $dimensions['width']
                );
            }

            if (! $has_height && $dimensions['height'] > 0) {
                $processor->set_attribute(
                    'height',
                    (string) $dimensions['height']
                );
            }
        }

        if (empty($settings['smart_lazy_images'])) {
            continue;
        }

        /*
         * First image = likely Hero/LCP.
         */
        if ($theme_image_index === 1) {
            $processor->set_attribute('loading', 'eager');
            $processor->set_attribute('fetchpriority', 'high');
            continue;
        }

        /*
         * Keep one additional image eager as a conservative buffer.
         */
        if ($theme_image_index === 2) {
            $processor->set_attribute('loading', 'eager');
            continue;
        }

        $processor->set_attribute('loading', 'lazy');

        if (! $processor->get_attribute('decoding')) {
            $processor->set_attribute('decoding', 'async');
        }
    }

    return $processor->get_updated_html();
}

add_filter(
    'render_block',
    'staark_hub_performance_optimize_static_html_images',
    35,
    2
);

function staark_hub_performance_apply_media_runtime(): void
{
    if (! staark_hub_module_enabled('performance', true)) {
        return;
    }

    $settings = staark_hub_performance_settings();

    if (! empty($settings['generate_webp'])) {
        add_filter(
            'image_editor_output_format',
            'staark_hub_performance_webp_output_format',
            20,
            3
        );

        add_filter(
            'wp_editor_set_quality',
            'staark_hub_performance_webp_quality',
            20,
            3
        );
    }

    if (! empty($settings['smart_lazy_images'])) {
        add_filter(
            'wp_omit_loading_attr_threshold',
            'staark_hub_performance_lazy_threshold',
            20
        );
    }
}
add_action('init', 'staark_hub_performance_apply_media_runtime', 31);

/**
 * Optimize a bounded batch of legacy JPEG/PNG attachments.
 *
 * Originals are never deleted or replaced. WordPress regenerates derivative
 * sizes using the active image_editor_output_format mapping.
 *
 * @return array{processed:int,optimized:int,skipped:int,failed:int}
 */
function staark_hub_performance_optimize_existing_images(int $limit = 10): array
{
    $result = [
        'processed' => 0,
        'optimized' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    if (! staark_hub_performance_webp_supported()) {
        return $result;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Skip images already handled (optimized or failed) so repeated runs
    // work through the whole library instead of the newest 100 again.
    $ids = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => ['image/jpeg', 'image/png'],
        'posts_per_page' => 100,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [['key' => '_staark_webp_state', 'compare' => 'NOT EXISTS']],
        'suppress_filters' => true,
    ]);

    foreach ($ids as $id) {
        if ($result['processed'] >= $limit) {
            break;
        }

        $id = (int) $id;
        $mime = strtolower((string) get_post_mime_type($id));

        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            continue;
        }

        if (staark_hub_performance_attachment_has_webp($id)) {
            update_post_meta($id, '_staark_webp_state', 'done');
            ++$result['skipped'];
            continue;
        }

        $file = get_attached_file($id);

        if (! is_string($file) || $file === '' || ! is_file($file)) {
            update_post_meta($id, '_staark_webp_state', 'failed');
            ++$result['failed'];
            continue;
        }

        ++$result['processed'];

        $metadata = wp_generate_attachment_metadata($id, $file);

        if (! is_array($metadata) || $metadata === []) {
            update_post_meta($id, '_staark_webp_state', 'failed');
            ++$result['failed'];
            continue;
        }

        wp_update_attachment_metadata($id, $metadata);

        if (staark_hub_performance_attachment_has_webp($id)) {
            update_post_meta($id, '_staark_webp_state', 'done');
            ++$result['optimized'];
        } else {
            update_post_meta($id, '_staark_webp_state', 'failed');
            ++$result['failed'];
        }
    }

    return $result;
}

/**
 * Parse max-age or s-maxage from Cache-Control.
 */
function staark_hub_performance_cache_ttl(string $cache_control): int
{
    if (preg_match('/(?:s-maxage|max-age)\s*=\s*(\d+)/i', $cache_control, $matches) === 1) {
        return max(0, (int) $matches[1]);
    }

    return 0;
}

/**
 * Probe a few representative static files.
 *
 * WordPress/PHP cannot reliably set headers for files served directly by
 * Apache/Nginx/Cloudflare, so this intentionally stays diagnostic.
 *
 * @return array{total:int,healthy:int,recommendedTtl:int,items:array<int,array<string,mixed>>}
 */
function staark_hub_performance_static_asset_probe(): array
{
    $urls = [];

    $theme_css_file = get_theme_file_path('assets/css/theme.css');

    if (is_file($theme_css_file)) {
        $urls['Theme CSS'] = get_theme_file_uri('assets/css/theme.css');
    } else {
        $urls['Theme stylesheet'] = get_stylesheet_uri();
    }

    /*
     * Probe an actual frontend image as well.
     */
    $hero_webp = get_theme_file_path(
        'assets/images/showcase/hero.webp'
    );

    if (is_file($hero_webp)) {
        $urls['Theme WebP'] = get_theme_file_uri(
            'assets/images/showcase/hero.webp'
        );
    }

    /*
     * If Media Library has images, sample one too.
     */
    $image_ids = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => 'image',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
        'suppress_filters' => true,
    ]);

    if (! empty($image_ids)) {
        $image_url = wp_get_attachment_url((int) $image_ids[0]);

        if (is_string($image_url) && $image_url !== '') {
            $urls['Media file'] = $image_url;
        }
    }

    $items = [];
    $healthy = 0;
    $minimum_ttl = 30 * DAY_IN_SECONDS;

    foreach ($urls as $label => $url) {
        $response = wp_remote_head($url, [
            'timeout' => 5,
            'redirection' => 2,
            'sslverify' => true,
            'user-agent' => 'Staark-Performance/'
                . (defined('STAARK_HUB_VERSION')
                    ? STAARK_HUB_VERSION
                    : 'unknown'),
        ]);

        if (is_wp_error($response)) {
            $items[] = [
                'label' => $label,
                'url' => $url,
                'status' => 0,
                'cacheControl' => '',
                'cfCacheStatus' => '',
                'ttl' => 0,
                'healthy' => false,
            ];

            continue;
        }

        $cache_control = sanitize_text_field(
            (string) wp_remote_retrieve_header(
                $response,
                'cache-control'
            )
        );

        $cf_status = sanitize_text_field(
            (string) wp_remote_retrieve_header(
                $response,
                'cf-cache-status'
            )
        );

        $ttl = staark_hub_performance_cache_ttl($cache_control);
        $ok = $ttl >= $minimum_ttl;

        if ($ok) {
            ++$healthy;
        }

        $items[] = [
            'label' => $label,
            'url' => $url,
            'status' => (int) wp_remote_retrieve_response_code($response),
            'cacheControl' => $cache_control,
            'cfCacheStatus' => $cf_status,
            'ttl' => $ttl,
            'healthy' => $ok,
        ];
    }

    return [
        'total' => count($items),
        'healthy' => $healthy,
        'recommendedTtl' => YEAR_IN_SECONDS,
        'items' => $items,
    ];
}

add_action('admin_post_staark_performance_optimize_images', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_performance_optimize_images');

    $result = staark_hub_performance_optimize_existing_images(10);
    staark_hub_performance_run_audit();

    $url = add_query_arg([
        'page' => 'staark-hub-performance',
        'staark_performance' => 'images_optimized',
        'processed' => $result['processed'],
        'optimized' => $result['optimized'],
        'failed' => $result['failed'],
    ], admin_url('admin.php'));

    wp_safe_redirect($url);
    exit;
});
