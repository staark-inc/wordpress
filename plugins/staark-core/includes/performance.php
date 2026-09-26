<?php
/**
 * Staark Performance Core.
 *
 * Read-only performance diagnostics plus a small set of conservative,
 * reversible WordPress optimizations. The module deliberately avoids generic
 * minification/defer rules that can break client sites.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_PERFORMANCE_AUDIT_HOOK = 'staark_hub_performance_daily_audit';

/**
 * @return array{disable_emojis:bool,disable_embeds:bool,heartbeat_mode:string,generate_webp:bool,webp_quality:int,smart_lazy_images:bool}
 */
function staark_hub_performance_settings(): array
{
    $saved = get_option('staark_hub_performance_settings', []);
    if (! is_array($saved)) {
        $saved = [];
    }

    $settings = array_merge(
        [
            'disable_emojis' => true,
            'disable_embeds' => false,
            'heartbeat_mode' => 'standard',
            'generate_webp' => true,
            'webp_quality' => 82,
            'smart_lazy_images' => true,
        ],
        $saved
    );

    $settings['disable_emojis'] = (bool) $settings['disable_emojis'];
    $settings['disable_embeds'] = (bool) $settings['disable_embeds'];
    $settings['heartbeat_mode'] = in_array((string) $settings['heartbeat_mode'], ['standard', 'reduced'], true)
        ? (string) $settings['heartbeat_mode']
        : 'standard';
    $settings['generate_webp'] = (bool) $settings['generate_webp'];
    $settings['webp_quality'] = max(60, min(95, (int) $settings['webp_quality']));
    $settings['smart_lazy_images'] = (bool) $settings['smart_lazy_images'];

    return $settings;
}

/**
 * @param array<string,mixed> $settings
 */
function staark_hub_performance_save_settings(array $settings): void
{
    $heartbeat_mode = isset($settings['heartbeat_mode']) && in_array((string) $settings['heartbeat_mode'], ['standard', 'reduced'], true)
        ? (string) $settings['heartbeat_mode']
        : 'standard';

    update_option(
        'staark_hub_performance_settings',
        [
            'disable_emojis' => ! empty($settings['disable_emojis']),
            'disable_embeds' => ! empty($settings['disable_embeds']),
            'heartbeat_mode' => $heartbeat_mode,
            'generate_webp' => ! empty($settings['generate_webp']),
            'webp_quality' => max(60, min(95, (int) ($settings['webp_quality'] ?? 82))),
            'smart_lazy_images' => ! empty($settings['smart_lazy_images']),
        ],
        true
    );
}

/**
 * Detect page-cache signals without forcing a cache plugin or changing server
 * configuration.
 *
 * @return array{active:bool,label:string,signals:string[]}
 */
function staark_hub_performance_page_cache(): array
{
    $signals = [];

    if (defined('WP_CACHE') && WP_CACHE) {
        $signals[] = 'WP_CACHE';
    }

    $advanced_cache = WP_CONTENT_DIR . '/advanced-cache.php';
    if (is_file($advanced_cache)) {
        $signals[] = 'advanced-cache.php';
    }

    $plugins = get_option('active_plugins', []);
    $plugins = is_array($plugins) ? $plugins : [];
    if (is_multisite()) {
        $network = get_site_option('active_sitewide_plugins', []);
        if (is_array($network)) {
            $plugins = array_values(array_unique(array_merge($plugins, array_keys($network))));
        }
    }

    $known = [
        'wp-rocket/wp-rocket.php' => 'WP Rocket',
        'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
        'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
        'wp-super-cache/wp-cache.php' => 'WP Super Cache',
        'cache-enabler/cache-enabler.php' => 'Cache Enabler',
        'breeze/breeze.php' => 'Breeze',
    ];

    $label = '';
    foreach ($known as $plugin => $name) {
        if (in_array($plugin, $plugins, true)) {
            $signals[] = $name;
            $label = $name;
            break;
        }
    }

    $active = $signals !== [];
    if ($label === '') {
        $label = $active ? implode(', ', $signals) : 'Not detected';
    }

    return [
        'active' => $active,
        'label' => $label,
        'signals' => $signals,
    ];
}

/**
 * @return array{active:bool,label:string}
 */
function staark_hub_performance_object_cache(): array
{
    $dropin = WP_CONTENT_DIR . '/object-cache.php';
    $external = function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache();
    $active = $external || is_file($dropin);

    return [
        'active' => $active,
        'label' => $external ? 'External object cache' : (is_file($dropin) ? 'object-cache.php' : 'Not detected'),
    ];
}

/**
 * Inspect a bounded sample of recent image attachments.
 *
 * @return array{sample:int,large:int,bytes:int,modern:int}
 */
function staark_hub_performance_image_audit(int $limit = 40): array
{
    $limit = max(10, min(80, $limit));
    $ids = get_posts(
        [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => true,
        ]
    );

    $large = 0;
    $bytes = 0;
    $modern = 0;

    foreach ($ids as $id) {
        $id = (int) $id;
        $file = get_attached_file($id);
        if (is_string($file) && $file !== '' && is_file($file)) {
            $size = filesize($file);
            if (is_int($size)) {
                $bytes += $size;
                if ($size > 1024 * 1024) {
                    ++$large;
                }
            }
        }

        $mime = (string) get_post_mime_type($id);
        if (in_array($mime, ['image/webp', 'image/avif'], true)) {
            ++$modern;
        }
    }

    return [
        'sample' => count($ids),
        'large' => $large,
        'bytes' => $bytes,
        'modern' => $modern,
    ];
}

/**
 * Scan only small text assets in the active theme for remote font providers and
 * local WOFF/WOFF2 usage. This is intentionally bounded to avoid recursive,
 * expensive filesystem scans.
 *
 * @return array{remote:bool,remoteProviders:string[],localWoff2:bool,filesScanned:int}
 */
function staark_hub_performance_font_audit(): array
{
    $theme = wp_get_theme();
    $root = $theme->get_stylesheet_directory();
    $files_scanned = 0;
    $remote = [];
    $local_woff2 = false;

    if (! is_dir($root)) {
        return ['remote' => false, 'remoteProviders' => [], 'localWoff2' => false, 'filesScanned' => 0];
    }

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($files_scanned >= 30 || ! $file instanceof SplFileInfo || ! $file->isFile()) {
                if ($files_scanned >= 30) {
                    break;
                }
                continue;
            }

            $extension = strtolower((string) $file->getExtension());
            if (! in_array($extension, ['css', 'json', 'html', 'php'], true)) {
                continue;
            }

            if ($file->getSize() > 512 * 1024) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! is_string($contents)) {
                continue;
            }

            ++$files_scanned;
            $haystack = strtolower($contents);

            if (str_contains($haystack, 'fonts.googleapis.com')) {
                $remote[] = 'Google Fonts CSS';
            }
            if (str_contains($haystack, 'fonts.gstatic.com')) {
                $remote[] = 'Google Fonts CDN';
            }
            if (str_contains($haystack, 'use.typekit.net') || str_contains($haystack, 'use.typekit.com')) {
                $remote[] = 'Adobe Fonts';
            }
            if (str_contains($haystack, '.woff2')) {
                $local_woff2 = true;
            }
        }
    } catch (Throwable $error) {
        // Treat an unreadable theme tree as unknown rather than failing wp-admin.
    }

    $remote = array_values(array_unique($remote));

    return [
        'remote' => $remote !== [],
        'remoteProviders' => $remote,
        'localWoff2' => $local_woff2,
        'filesScanned' => $files_scanned,
    ];
}

/**
 * Request the public homepage as a logged-out HTTP client and collect a small
 * synthetic snapshot. The elapsed value is a server-side round trip, not LCP,
 * INP or browser TTFB.
 *
 * @return array{ok:bool,status:int,responseMs:int,htmlBytes:int,scripts:int,styles:int,images:int,missingDimensions:int,lazyImages:int,compression:string,cacheControl:string,error:string}
 */
function staark_hub_performance_homepage_probe(): array
{
    $url = home_url('/');
    $start = microtime(true);
    $response = wp_remote_get(
        $url,
        [
            'timeout' => 10,
            'redirection' => 3,
            'sslverify' => true,
            'headers' => [
                'X-Staark-Performance-Probe' => '1',
            ],
            'user-agent' => 'Staark-Performance/' . (defined('STAARK_HUB_VERSION') ? STAARK_HUB_VERSION : 'unknown'),
        ]
    );
    $elapsed = (int) round((microtime(true) - $start) * 1000);

    $empty = [
        'ok' => false,
        'status' => 0,
        'responseMs' => $elapsed,
        'htmlBytes' => 0,
        'scripts' => 0,
        'styles' => 0,
        'images' => 0,
        'missingDimensions' => 0,
        'lazyImages' => 0,
        'compression' => '',
        'cacheControl' => '',
        'error' => '',
    ];

    if (is_wp_error($response)) {
        $empty['error'] = sanitize_text_field($response->get_error_message());
        return $empty;
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    $scripts = preg_match_all('/<script\b/i', $body, $script_matches);
    $styles = preg_match_all('/<link\b[^>]*rel=["\'][^"\']*stylesheet/i', $body, $style_matches);
    preg_match_all('/<img\b[^>]*>/i', $body, $image_matches);

    $images = isset($image_matches[0]) ? count($image_matches[0]) : 0;
    $missing_dimensions = 0;
    $lazy_images = 0;

    foreach ($image_matches[0] ?? [] as $tag) {
        $tag = (string) $tag;
        $has_width = preg_match('/\swidth\s*=\s*["\'][^"\']+["\']/i', $tag) === 1;
        $has_height = preg_match('/\sheight\s*=\s*["\'][^"\']+["\']/i', $tag) === 1;
        if (! $has_width || ! $has_height) {
            ++$missing_dimensions;
        }
        if (preg_match('/\sloading\s*=\s*["\']lazy["\']/i', $tag) === 1) {
            ++$lazy_images;
        }
    }

    return [
        'ok' => $status >= 200 && $status < 400,
        'status' => $status,
        'responseMs' => $elapsed,
        'htmlBytes' => strlen($body),
        'scripts' => is_int($scripts) ? $scripts : 0,
        'styles' => is_int($styles) ? $styles : 0,
        'images' => $images,
        'missingDimensions' => $missing_dimensions,
        'lazyImages' => $lazy_images,
        'compression' => sanitize_text_field((string) wp_remote_retrieve_header($response, 'content-encoding')),
        'cacheControl' => sanitize_text_field((string) wp_remote_retrieve_header($response, 'cache-control')),
        'error' => '',
    ];
}

/**
 * Build one bounded audit snapshot so a manual audit never probes the homepage
 * or scans the theme twice.
 *
 * @return array<string,mixed>
 */
function staark_hub_performance_snapshot(): array
{
    return [
        'pageCache' => staark_hub_performance_page_cache(),
        'objectCache' => staark_hub_performance_object_cache(),
        'images' => staark_hub_performance_image_audit(),
        'media' => function_exists('staark_hub_performance_media_audit')
            ? staark_hub_performance_media_audit()
            : [],
        'assetCache' => function_exists('staark_hub_performance_static_asset_probe')
            ? staark_hub_performance_static_asset_probe()
            : [],
        'fonts' => staark_hub_performance_font_audit(),
        'probe' => staark_hub_performance_homepage_probe(),
    ];
}

/**
 * @param array<string,mixed> $snapshot
 * @return array<string,mixed>[]
 */
function staark_hub_performance_checks(array $snapshot = []): array
{
    if ($snapshot === []) {
        $snapshot = staark_hub_performance_snapshot();
    }

    $settings = staark_hub_performance_settings();
    $page_cache = isset($snapshot['pageCache']) && is_array($snapshot['pageCache']) ? $snapshot['pageCache'] : ['active' => false, 'label' => 'Unknown'];
    $object_cache = isset($snapshot['objectCache']) && is_array($snapshot['objectCache']) ? $snapshot['objectCache'] : ['active' => false, 'label' => 'Unknown'];
    $images = isset($snapshot['images']) && is_array($snapshot['images']) ? $snapshot['images'] : ['sample' => 0, 'large' => 0, 'bytes' => 0, 'modern' => 0];
    $fonts = isset($snapshot['fonts']) && is_array($snapshot['fonts']) ? $snapshot['fonts'] : ['remote' => false, 'remoteProviders' => [], 'localWoff2' => false, 'filesScanned' => 0];
    $media = isset($snapshot['media']) && is_array($snapshot['media']) ? $snapshot['media'] : ['sample' => 0, 'legacyOriginals' => 0, 'modernOriginals' => 0, 'webpDerivativeSets' => 0, 'webpSupported' => false];
    $asset_cache = isset($snapshot['assetCache']) && is_array($snapshot['assetCache']) ? $snapshot['assetCache'] : ['total' => 0, 'healthy' => 0, 'items' => []];
    $probe = isset($snapshot['probe']) && is_array($snapshot['probe']) ? $snapshot['probe'] : ['ok' => false, 'responseMs' => 0, 'htmlBytes' => 0, 'images' => 0, 'missingDimensions' => 0, 'lazyImages' => 0, 'compression' => '', 'cacheControl' => '', 'error' => 'No probe data'];
    $environment = staark_hub_environment_type();

    $cache_control = strtolower((string) $probe['cacheControl']);
    $cache_header_ok = $cache_control !== '' && (
        str_contains($cache_control, 'max-age=')
        || str_contains($cache_control, 's-maxage=')
        || str_contains($cache_control, 'public')
    );

    $compression = strtolower((string) $probe['compression']);
    $compression_ok = in_array($compression, ['gzip', 'br', 'zstd'], true);
    $large_ratio_ok = $images['sample'] === 0 || $images['large'] <= max(1, (int) floor($images['sample'] * 0.2));
    $dimensions_ok = ! $probe['ok'] || $probe['images'] === 0 || $probe['missingDimensions'] === 0;
    $response_ok = ! $probe['ok'] || $probe['responseMs'] <= 1200;
    $html_ok = ! $probe['ok'] || $probe['htmlBytes'] <= 300 * 1024;

    $webp_delivery_ok = ! $settings['generate_webp']
        || ! $media['webpSupported']
        || $media['legacyOriginals'] === 0
        || $media['webpDerivativeSets'] >= $media['legacyOriginals'];

    $lazy_ok = ! $settings['smart_lazy_images']
        || ! $probe['ok']
        || $probe['images'] <= 2
        || $probe['lazyImages'] > 0;

    $static_cache_ok = $asset_cache['total'] > 0
        && $asset_cache['healthy'] === $asset_cache['total'];

    return [
        [
            'id' => 'page_cache',
            'label' => 'Page cache',
            'ok' => $page_cache['active'] || $environment !== 'production',
            'points' => 14,
            'severity' => 'high',
            'detail' => $page_cache['active'] ? 'Detected: ' . $page_cache['label'] . '.' : 'No page-cache signal was detected.',
            'recommendation' => 'Use host-level caching or a compatible WordPress page cache for production traffic.',
        ],
        [
            'id' => 'object_cache',
            'label' => 'Persistent object cache',
            'ok' => $object_cache['active'] || $environment !== 'production',
            'points' => 8,
            'severity' => 'medium',
            'detail' => $object_cache['active'] ? $object_cache['label'] . ' is available.' : 'No persistent object-cache drop-in was detected.',
            'recommendation' => 'Redis/Memcached can help dynamic or query-heavy sites; small brochure sites may not need it.',
        ],
        [
            'id' => 'origin_response',
            'label' => 'Homepage server round trip',
            'ok' => $response_ok,
            'points' => 14,
            'severity' => 'high',
            'detail' => $probe['ok'] ? $probe['responseMs'] . ' ms from the WordPress server to the public homepage.' : 'Homepage probe unavailable' . ($probe['error'] !== '' ? ': ' . $probe['error'] : '.'),
            'recommendation' => 'This is not browser TTFB or LCP. Investigate hosting, PHP, database and cache layers if consistently slow.',
        ],
        [
            'id' => 'html_size',
            'label' => 'HTML document size',
            'ok' => $html_ok,
            'points' => 8,
            'severity' => 'medium',
            'detail' => $probe['ok'] ? size_format((int) $probe['htmlBytes'], 1) . ' HTML response.' : 'No HTML response was available for measurement.',
            'recommendation' => 'Large HTML documents can delay parsing. Review oversized inline markup and repeated blocks.',
        ],
        [
            'id' => 'image_dimensions',
            'label' => 'Image dimensions in homepage markup',
            'ok' => $dimensions_ok,
            'points' => 10,
            'severity' => 'high',
            'detail' => $probe['ok'] ? sprintf('%d image(s), %d missing width or height.', $probe['images'], $probe['missingDimensions']) : 'Homepage markup could not be inspected.',
            'recommendation' => 'Explicit dimensions reserve layout space and reduce CLS risk.',
        ],
        [
            'id' => 'source_images',
            'label' => 'Large source images',
            'ok' => $large_ratio_ok,
            'points' => 10,
            'severity' => 'medium',
            'detail' => sprintf('%d of %d recent image(s) exceed 1 MB.', $images['large'], $images['sample']),
            'recommendation' => 'Resize/compress oversized originals and consider WebP/AVIF delivery through WordPress or the CDN.',
        ],
        [
            'id' => 'modern_image_delivery',
            'label' => 'Modern image delivery',
            'ok' => $webp_delivery_ok,
            'points' => 8,
            'severity' => 'medium',
            'detail' => sprintf(
                '%d legacy source image(s), %d WebP derivative set(s).',
                $media['legacyOriginals'],
                $media['webpDerivativeSets']
            ),
            'recommendation' => $media['webpSupported']
                ? 'Generate WebP derivatives for legacy JPEG/PNG media while keeping originals as fallbacks.'
                : 'The current PHP image editor does not report WebP support.',
        ],
        [
            'id' => 'smart_lazy_images',
            'label' => 'Smart image lazy loading',
            'ok' => $lazy_ok,
            'points' => 5,
            'severity' => 'medium',
            'detail' => $probe['ok']
                ? sprintf('%d of %d homepage image(s) currently use loading="lazy".', $probe['lazyImages'], $probe['images'])
                : 'Homepage image loading attributes could not be inspected.',
            'recommendation' => 'Keep likely LCP images eager and lazy-load images further down the page.',
        ],
        [
            'id' => 'static_asset_cache',
            'label' => 'Static asset cache lifetime',
            'ok' => $static_cache_ok,
            'points' => 8,
            'severity' => 'high',
            'detail' => sprintf(
                '%d of %d sampled static asset(s) have a cache lifetime of at least 30 days.',
                $asset_cache['healthy'],
                $asset_cache['total']
            ),
            'recommendation' => 'For versioned static assets use Cache-Control: public, max-age=31536000, immutable at the server/CDN layer.',
        ],
        [
            'id' => 'remote_fonts',
            'label' => 'Font delivery',
            'ok' => ! $fonts['remote'],
            'points' => 8,
            'severity' => 'medium',
            'detail' => $fonts['remote'] ? 'Remote provider detected: ' . implode(', ', $fonts['remoteProviders']) . '.' : ($fonts['localWoff2'] ? 'Local WOFF2 usage detected.' : 'No known remote font provider found in the active theme sample.'),
            'recommendation' => 'Prefer locally hosted WOFF2 when licensing permits to reduce third-party connection cost and privacy exposure.',
        ],
        [
            'id' => 'compression',
            'label' => 'HTTP compression',
            'ok' => $compression_ok || $environment !== 'production',
            'points' => 8,
            'severity' => 'medium',
            'detail' => $compression !== '' ? 'Content-Encoding: ' . $compression . '.' : 'No compression header observed on the synthetic homepage request.',
            'recommendation' => 'Enable Brotli/gzip at the web server or CDN on production responses.',
        ],
        [
            'id' => 'cache_headers',
            'label' => 'Public cache headers',
            'ok' => $cache_header_ok,
            'points' => 7,
            'severity' => 'medium',
            'detail' => $probe['cacheControl'] !== '' ? 'Cache-Control: ' . $probe['cacheControl'] . '.' : 'No Cache-Control header observed.',
            'recommendation' => 'Production page caching is often best configured at the host/CDN layer.',
        ],
        [
            'id' => 'emoji_assets',
            'label' => 'Emoji asset cleanup',
            'ok' => $settings['disable_emojis'],
            'points' => 5,
            'severity' => 'low',
            'detail' => $settings['disable_emojis'] ? 'WordPress emoji frontend/admin support assets are disabled by Staark.' : 'WordPress emoji support assets remain enabled.',
            'recommendation' => 'Disable only when the site does not rely on WordPress emoji SVG fallback behavior.',
        ],
        [
            'id' => 'heartbeat',
            'label' => 'Heartbeat policy',
            'ok' => $settings['heartbeat_mode'] === 'reduced',
            'points' => 5,
            'severity' => 'low',
            'detail' => $settings['heartbeat_mode'] === 'reduced' ? 'Heartbeat interval is capped at 60 seconds.' : 'WordPress default Heartbeat behavior is preserved.',
            'recommendation' => 'Reduced mode lowers background admin requests while preserving autosave/session behavior.',
        ],
        [
            'id' => 'embed_assets',
            'label' => 'Embed frontend asset',
            'ok' => $settings['disable_embeds'],
            'points' => 3,
            'severity' => 'low',
            'detail' => $settings['disable_embeds'] ? 'Frontend embed discovery and wp-embed script are disabled.' : 'WordPress embed frontend support is enabled.',
            'recommendation' => 'Leave enabled if the site needs WordPress post embedding or discovery links.',
        ],
    ];
}

/**
 * @return array{score:int,passed:int,warnings:int,checked_at:string,checks:array<string,mixed>[],snapshot:array<string,mixed>}
 */
function staark_hub_performance_run_audit(): array
{
    $snapshot = staark_hub_performance_snapshot();
    $checks = staark_hub_performance_checks($snapshot);
    $earned = 0;
    $possible = 0;
    $passed = 0;
    $warnings = 0;

    foreach ($checks as $check) {
        $points = isset($check['points']) ? max(0, (int) $check['points']) : 0;
        $possible += $points;
        if (! empty($check['ok'])) {
            $earned += $points;
            ++$passed;
        } else {
            ++$warnings;
        }
    }

    $report = [
        'score' => $possible > 0 ? (int) round(($earned / $possible) * 100) : 0,
        'passed' => $passed,
        'warnings' => $warnings,
        'checked_at' => current_time('mysql'),
        'checks' => $checks,
        'snapshot' => $snapshot,
    ];

    update_option('staark_hub_performance_report', $report, false);
    return $report;
}

/**
 * @return array<string,mixed>
 */
function staark_hub_performance_last_report(): array
{
    $report = get_option('staark_hub_performance_report', []);
    return is_array($report) ? $report : [];
}

/**
 * Compact status attached to Staark Hub connector metadata.
 *
 * @return array<string,mixed>
 */
function staark_hub_performance_summary(): array
{
    if (! staark_hub_module_enabled('performance', true)) {
        return [
            'enabled' => false,
            'score' => 0,
            'warnings' => 0,
            'lastAudit' => '',
        ];
    }

    $report = staark_hub_performance_last_report();
    if ($report === []) {
        return [
            'enabled' => true,
            'score' => 0,
            'warnings' => 0,
            'lastAudit' => '',
            'pageCache' => false,
            'objectCache' => false,
            'responseMs' => 0,
            'htmlBytes' => 0,
        ];
    }

    $snapshot = isset($report['snapshot']) && is_array($report['snapshot']) ? $report['snapshot'] : [];
    $probe = isset($snapshot['probe']) && is_array($snapshot['probe']) ? $snapshot['probe'] : [];
    $page_cache = isset($snapshot['pageCache']) && is_array($snapshot['pageCache']) ? $snapshot['pageCache'] : [];
    $object_cache = isset($snapshot['objectCache']) && is_array($snapshot['objectCache']) ? $snapshot['objectCache'] : [];

    return [
        'enabled' => true,
        'score' => isset($report['score']) ? (int) $report['score'] : 0,
        'warnings' => isset($report['warnings']) ? (int) $report['warnings'] : 0,
        'lastAudit' => isset($report['checked_at']) ? sanitize_text_field((string) $report['checked_at']) : '',
        'pageCache' => ! empty($page_cache['active']),
        'objectCache' => ! empty($object_cache['active']),
        'responseMs' => isset($probe['responseMs']) ? (int) $probe['responseMs'] : 0,
        'htmlBytes' => isset($probe['htmlBytes']) ? (int) $probe['htmlBytes'] : 0,
    ];
}

function staark_hub_performance_apply_runtime(): void
{
    if (! staark_hub_module_enabled('performance', true)) {
        return;
    }

    $settings = staark_hub_performance_settings();

    if ($settings['disable_emojis']) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_head', 'wp_enqueue_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'wp_enqueue_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('emoji_svg_url', '__return_false');
    }

    if ($settings['disable_embeds']) {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        add_action('wp_footer', static function (): void {
            wp_deregister_script('wp-embed');
        }, 1);
    }

    if ($settings['heartbeat_mode'] === 'reduced') {
        add_filter('heartbeat_settings', static function (array $settings): array {
            $current = isset($settings['interval']) ? (int) $settings['interval'] : 60;
            $settings['interval'] = max(60, $current);
            return $settings;
        });
    }
}
add_action('init', 'staark_hub_performance_apply_runtime', 30);

function staark_hub_performance_schedule(): void
{
    $enabled = staark_hub_module_enabled('performance', true);
    $scheduled = wp_next_scheduled(STAARK_HUB_PERFORMANCE_AUDIT_HOOK);

    if ($enabled && ! $scheduled) {
        wp_schedule_event(time() + 2 * HOUR_IN_SECONDS, 'daily', STAARK_HUB_PERFORMANCE_AUDIT_HOOK);
    } elseif (! $enabled && $scheduled) {
        wp_unschedule_event($scheduled, STAARK_HUB_PERFORMANCE_AUDIT_HOOK);
    }
}
add_action('init', 'staark_hub_performance_schedule', 40);
add_action(STAARK_HUB_PERFORMANCE_AUDIT_HOOK, static function (): void {
    staark_hub_performance_run_audit();
});

function staark_hub_performance_deactivate(): void
{
    $timestamp = wp_next_scheduled(STAARK_HUB_PERFORMANCE_AUDIT_HOOK);
    if ($timestamp) {
        wp_unschedule_event($timestamp, STAARK_HUB_PERFORMANCE_AUDIT_HOOK);
    }
}
// Scheduled events are cleared by staark_hub_deactivate() (includes/lifecycle.php).

add_action('admin_post_staark_performance_audit', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_performance_audit');
    staark_hub_performance_run_audit();
    wp_safe_redirect(admin_url('admin.php?page=staark-hub-performance&staark_performance=audited'));
    exit;
});

add_action('admin_post_staark_performance_save', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_performance_save');
    staark_hub_set_module_enabled('performance', staark_hub_checkbox_value('module_enabled'));
    staark_hub_performance_save_settings(
        [
            'disable_emojis' => staark_hub_checkbox_value('disable_emojis'),
            'disable_embeds' => staark_hub_checkbox_value('disable_embeds'),
            'heartbeat_mode' => isset($_POST['heartbeat_mode']) ? sanitize_key(wp_unslash($_POST['heartbeat_mode'])) : 'standard',
            'generate_webp' => staark_hub_checkbox_value('generate_webp'),
            'webp_quality' => isset($_POST['webp_quality']) ? absint($_POST['webp_quality']) : 82,
            'smart_lazy_images' => staark_hub_checkbox_value('smart_lazy_images'),
        ]
    );

    staark_hub_performance_run_audit();
    wp_safe_redirect(admin_url('admin.php?page=staark-hub-performance&staark_performance=saved'));
    exit;
});
