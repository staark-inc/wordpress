<?php
/**
 * Staark Performance admin screen.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_performance_score_label(int $score): string
{
    if ($score >= 90) {
        return 'Excellent';
    }
    if ($score >= 75) {
        return 'Healthy';
    }
    if ($score >= 55) {
        return 'Needs tuning';
    }

    return 'Needs attention';
}

function staark_hub_performance_score_class(int $score): string
{
    if ($score >= 90) {
        return 'is-strong';
    }
    if ($score >= 75) {
        return 'is-good';
    }
    if ($score >= 55) {
        return 'is-warn';
    }

    return 'is-risk';
}

function staark_hub_render_performance(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $enabled = staark_hub_module_enabled('performance', true);
    $settings = staark_hub_performance_settings();
    $report = staark_hub_performance_last_report();
    if ($report === []) {
        $report = staark_hub_performance_run_audit();
    }

    $score = isset($report['score']) ? (int) $report['score'] : 0;
    $passed = isset($report['passed']) ? (int) $report['passed'] : 0;
    $warnings = isset($report['warnings']) ? (int) $report['warnings'] : 0;
    $checked_at = isset($report['checked_at']) ? (string) $report['checked_at'] : '';
    $checks = isset($report['checks']) && is_array($report['checks']) ? $report['checks'] : [];
    $snapshot = isset($report['snapshot']) && is_array($report['snapshot']) ? $report['snapshot'] : [];
    $probe = isset($snapshot['probe']) && is_array($snapshot['probe']) ? $snapshot['probe'] : [];
    $images = isset($snapshot['images']) && is_array($snapshot['images']) ? $snapshot['images'] : [];
    $fonts = isset($snapshot['fonts']) && is_array($snapshot['fonts']) ? $snapshot['fonts'] : [];
    $media = isset($snapshot['media']) && is_array($snapshot['media']) ? $snapshot['media'] : [];
    $asset_cache = isset($snapshot['assetCache']) && is_array($snapshot['assetCache']) ? $snapshot['assetCache'] : [];
    $page_cache = isset($snapshot['pageCache']) && is_array($snapshot['pageCache']) ? $snapshot['pageCache'] : [];
    $object_cache = isset($snapshot['objectCache']) && is_array($snapshot['objectCache']) ? $snapshot['objectCache'] : [];
    $state = isset($_GET['staark_performance']) ? sanitize_key(wp_unslash($_GET['staark_performance'])) : '';
    ?>
    <div class="wrap staark-hub-wrap staark-performance-page">
        <?php staark_hub_header('Performance'); ?>

        <?php if ($state === 'audited') : ?>
            <div class="notice notice-success is-dismissible"><p>Performance audit completed and the local snapshot was updated.</p></div>
        <?php elseif ($state === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p>Performance settings saved. Conservative runtime optimizations are active on the next request.</p></div>
        <?php elseif ($state === 'images_optimized') : ?>
            <div class="notice notice-success is-dismissible"><p>
                Image optimization finished:
                <strong><?php echo esc_html((string) absint($_GET['optimized'] ?? 0)); ?></strong> optimized,
                <?php echo esc_html((string) absint($_GET['processed'] ?? 0)); ?> processed,
                <?php echo esc_html((string) absint($_GET['failed'] ?? 0)); ?> failed.
            </p></div>
        <?php endif; ?>

        <section class="staark-performance-hero">
            <div>
                <span class="staark-hub-card-label">Performance Core</span>
                <h2>Measure first. Optimize without breaking the site.</h2>
                <p>Staark audits cache signals, homepage weight, image markup, source images and font delivery. The score is a local readiness heuristic — not a Lighthouse or Core Web Vitals score.</p>
            </div>
            <div class="staark-performance-hero-actions">
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="staark_performance_audit">
                    <?php wp_nonce_field('staark_performance_audit'); ?>
                    <button type="submit" class="button button-primary">Run performance audit</button>
                </form>
                <span class="staark-hub-mini-status <?php echo $enabled ? 'staark-hub-mini-status--ok' : 'staark-hub-mini-status--muted'; ?>">
                    <?php echo $enabled ? 'Module active' : 'Module disabled'; ?>
                </span>
            </div>
        </section>

        <div class="staark-performance-summary">
            <section class="staark-hub-card staark-performance-score-card">
                <div class="staark-performance-score <?php echo esc_attr(staark_hub_performance_score_class($score)); ?>" style="--staark-performance-score:<?php echo esc_attr((string) $score); ?>">
                    <div><strong><?php echo esc_html((string) $score); ?></strong><span>/100</span></div>
                </div>
                <div>
                    <span class="staark-hub-card-label">Readiness score</span>
                    <h2><?php echo esc_html(staark_hub_performance_score_label($score)); ?></h2>
                    <p><?php echo esc_html((string) $passed); ?> passing · <?php echo esc_html((string) $warnings); ?> to review.</p>
                </div>
            </section>

            <section class="staark-hub-card staark-performance-stat">
                <span class="staark-hub-card-label">Server round trip</span>
                <strong><?php echo isset($probe['responseMs']) && (int) $probe['responseMs'] > 0 ? esc_html((string) $probe['responseMs']) . ' ms' : '—'; ?></strong>
                <p>Server-side synthetic request, not browser TTFB/LCP.</p>
            </section>

            <section class="staark-hub-card staark-performance-stat">
                <span class="staark-hub-card-label">Homepage HTML</span>
                <strong><?php echo isset($probe['htmlBytes']) && (int) $probe['htmlBytes'] > 0 ? esc_html(size_format((int) $probe['htmlBytes'], 1)) : '—'; ?></strong>
                <p><?php echo esc_html((string) ((int) ($probe['scripts'] ?? 0))); ?> scripts · <?php echo esc_html((string) ((int) ($probe['styles'] ?? 0))); ?> stylesheets observed.</p>
            </section>

            <section class="staark-hub-card staark-performance-stat">
                <span class="staark-hub-card-label">Caching</span>
                <strong><?php echo ! empty($page_cache['active']) ? 'Page cache' : 'Review'; ?></strong>
                <p><?php echo ! empty($object_cache['active']) ? 'Persistent object cache detected.' : 'Object cache not detected.'; ?></p>
            </section>
        </div>

        <div class="staark-hub-grid staark-hub-grid--split staark-performance-layout">
            <section class="staark-hub-card">
                <div class="staark-hub-card-heading">
                    <div>
                        <span class="staark-hub-card-label">Audit</span>
                        <h2>Performance checks</h2>
                        <p>Read-only checks. Production cache/CDN configuration stays where it belongs: the hosting and edge layer.</p>
                    </div>
                    <span class="staark-hub-mini-status staark-hub-mini-status--muted"><?php echo $checked_at !== '' ? 'Audited ' . esc_html($checked_at) : 'No audit yet'; ?></span>
                </div>

                <div class="staark-performance-checks">
                    <?php foreach ($checks as $check) :
                        $ok = ! empty($check['ok']);
                        $severity = isset($check['severity']) ? sanitize_key((string) $check['severity']) : 'info';
                        ?>
                        <article class="staark-performance-check <?php echo $ok ? 'is-pass' : 'is-warning'; ?>">
                            <span class="staark-performance-check-icon" aria-hidden="true"><?php echo $ok ? '✓' : '!'; ?></span>
                            <div>
                                <div class="staark-performance-check-title">
                                    <strong><?php echo esc_html((string) ($check['label'] ?? 'Check')); ?></strong>
                                    <span class="staark-performance-severity is-<?php echo esc_attr($severity); ?>"><?php echo esc_html(ucfirst($severity)); ?></span>
                                </div>
                                <p><?php echo esc_html((string) ($check['detail'] ?? '')); ?></p>
                                <?php if (! $ok && ! empty($check['recommendation'])) : ?>
                                    <small><?php echo esc_html((string) $check['recommendation']); ?></small>
                                <?php endif; ?>
                            </div>
                            <b><?php echo esc_html((string) ((int) ($check['points'] ?? 0))); ?> pt</b>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="staark-performance-sidebar">
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-card staark-performance-settings">
                    <input type="hidden" name="action" value="staark_performance_save">
                    <?php wp_nonce_field('staark_performance_save'); ?>

                    <span class="staark-hub-card-label">Module control</span>
                    <h2>Safe runtime optimizations</h2>
                    <p>Only small, reversible changes live here. Staark does not blanket-defer scripts, concatenate CSS or rewrite images automatically.</p>

                    <label class="staark-performance-toggle staark-performance-toggle-master">
                        <span>
                            <strong>Performance module</strong>
                            <small>Disable Staark runtime optimizations and scheduled audits while keeping the stored report.</small>
                        </span>
                        <input type="checkbox" name="module_enabled" value="1" <?php checked($enabled); ?>>
                    </label>

                    <div class="staark-performance-toggle-list">
                        <label class="staark-performance-toggle">
                            <span>
                                <strong>Disable WordPress emoji assets</strong>
                                <small>Removes WordPress emoji fallback scripts/styles. Native browser emoji still work.</small>
                            </span>
                            <input type="checkbox" name="disable_emojis" value="1" <?php checked($settings['disable_emojis']); ?>>
                        </label>

                        <label class="staark-performance-toggle">
                            <span>
                                <strong>Disable frontend embed asset</strong>
                                <small>Removes oEmbed discovery/host JS and wp-embed. Leave off if WordPress post embedding is used.</small>
                            </span>
                            <input type="checkbox" name="disable_embeds" value="1" <?php checked($settings['disable_embeds']); ?>>
                        </label>

                        <label class="staark-performance-toggle">
                            <span>
                                <strong>Generate WebP derivatives</strong>
                                <small>JPEG/PNG originals stay untouched. WordPress-generated image sizes use WebP when supported.</small>
                            </span>
                            <input type="checkbox" name="generate_webp" value="1" <?php checked($settings['generate_webp']); ?>>
                        </label>

                        <label class="staark-performance-field">
                            <span>
                                <strong>WebP quality</strong>
                                <small>Recommended range: 78–85. Default is 82.</small>
                            </span>
                            <input type="number" name="webp_quality" min="60" max="95" value="<?php echo esc_attr((string) $settings['webp_quality']); ?>">
                        </label>

                        <label class="staark-performance-toggle">
                            <span>
                                <strong>Smart lazy loading</strong>
                                <small>Keeps the first two eligible images eager and lets WordPress lazy-load later images.</small>
                            </span>
                            <input type="checkbox" name="smart_lazy_images" value="1" <?php checked($settings['smart_lazy_images']); ?>>
                        </label>

                        <label class="staark-performance-field">
                            <span>
                                <strong>Heartbeat</strong>
                                <small>Reduced mode keeps WordPress Heartbeat but caps it at a 60-second interval.</small>
                            </span>
                            <select name="heartbeat_mode">
                                <option value="standard" <?php selected($settings['heartbeat_mode'], 'standard'); ?>>WordPress default</option>
                                <option value="reduced" <?php selected($settings['heartbeat_mode'], 'reduced'); ?>>Reduced · 60 seconds</option>
                            </select>
                        </label>
                    </div>

                    <button type="submit" class="button button-primary">Save performance settings</button>
                </form>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">Images & fonts</span>
                    <h2>Current sample</h2>
                    <dl class="staark-hub-details">
                        <div><dt>Recent images</dt><dd><?php echo esc_html((string) ((int) ($images['sample'] ?? 0))); ?></dd></div>
                        <div><dt>&gt; 1 MB</dt><dd><?php echo esc_html((string) ((int) ($images['large'] ?? 0))); ?></dd></div>
                        <div><dt>WebP / AVIF originals</dt><dd><?php echo esc_html((string) ((int) ($images['modern'] ?? 0))); ?></dd></div>
                        <div><dt>Legacy JPEG / PNG</dt><dd><?php echo esc_html((string) ((int) ($media['legacyOriginals'] ?? 0))); ?></dd></div>
                        <div><dt>WebP derivative sets</dt><dd><?php echo esc_html((string) ((int) ($media['webpDerivativeSets'] ?? 0))); ?></dd></div>
                        <div><dt>WebP support</dt><dd><?php echo ! empty($media['webpSupported']) ? 'Available' : 'Unavailable'; ?></dd></div>
                        <div><dt>Lazy homepage images</dt><dd><?php echo esc_html((string) ((int) ($probe['lazyImages'] ?? 0))); ?> / <?php echo esc_html((string) ((int) ($probe['images'] ?? 0))); ?></dd></div>
                        <div><dt>Remote fonts</dt><dd><?php echo ! empty($fonts['remote']) ? 'Detected' : 'Not detected'; ?></dd></div>
                        <div><dt>Local WOFF2</dt><dd><?php echo ! empty($fonts['localWoff2']) ? 'Detected' : 'Not detected'; ?></dd></div>
                    </dl>

                    <?php if (! empty($media['webpSupported'])) : ?>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" style="margin-top:16px">
                            <input type="hidden" name="action" value="staark_performance_optimize_images">
                            <?php wp_nonce_field('staark_performance_optimize_images'); ?>
                            <button type="submit" class="button">Optimize next 10 legacy images</button>
                        </form>
                    <?php endif; ?>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">Browser cache</span>
                    <h2>Static asset lifetime</h2>
                    <p>Theme/media files are served directly by the web server or CDN. Staark audits them without rewriting server configuration.</p>
                    <dl class="staark-hub-details">
                        <div><dt>Healthy assets</dt><dd><?php echo esc_html((string) ((int) ($asset_cache['healthy'] ?? 0))); ?> / <?php echo esc_html((string) ((int) ($asset_cache['total'] ?? 0))); ?></dd></div>
                        <div><dt>Recommended TTL</dt><dd>1 year</dd></div>
                        <div><dt>Recommended header</dt><dd><code>public, max-age=31536000, immutable</code></dd></div>
                    </dl>
                </section>

                <section class="staark-hub-card staark-performance-cwv-card">
                    <span class="staark-hub-card-label">Core Web Vitals</span>
                    <h2>No fake CWV numbers</h2>
                    <p>LCP, CLS and INP require browser field/lab data. This patch checks prerequisites such as dimensions, response weight, cache and font delivery instead of pretending a server-side PHP request is Lighthouse.</p>
                    <div class="staark-hub-feature-list staark-hub-feature-list--stacked">
                        <span><i aria-hidden="true">✓</i>CLS-risk image dimension check</span>
                        <span><i aria-hidden="true">✓</i>Homepage HTML/asset snapshot</span>
                        <span><i aria-hidden="true">✓</i>Cache and compression awareness</span>
                        <span><i aria-hidden="true">✓</i>Ready for Hub/PageSpeed telemetry later</span>
                    </div>
                </section>
            </aside>
        </div>

        <section class="staark-hub-card staark-performance-safety">
            <span class="staark-hub-card-label">Guardrails</span>
            <h2>What Staark Performance intentionally does not auto-change</h2>
            <div class="staark-performance-guardrails">
                <span>Generic JS defer/delay</span>
                <span>CSS concatenation</span>
                <span>Database cleanup</span>
                <span>Original image deletion</span>
                <span>Blind full-library rewrites</span>
                <span>CDN/cache server rules</span>
                <span>Font licensing/files</span>
            </div>
        </section>
    </div>
    <?php
}
