<?php
/**
 * Staark SEO admin screen.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_render_seo(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $enabled = staark_hub_module_enabled('seo', true);
    $settings = staark_hub_seo_settings();
    $conflict = staark_hub_seo_conflict();
    $content = staark_hub_seo_content_stats();
    $alt = staark_hub_seo_alt_stats();
    $local = staark_hub_seo_local_completeness();
    $indexable = (string) get_option('blog_public', '1') === '1';
    $sitemap = staark_hub_seo_sitemap_enabled();
    $pretty_permalinks = (string) get_option('permalink_structure') !== '';
    $state = isset($_GET['staark_seo']) ? sanitize_key(wp_unslash($_GET['staark_seo'])) : '';
    $social_image = $settings['default_social_image_id'] > 0
        ? wp_get_attachment_image_url($settings['default_social_image_id'], 'medium')
        : false;
    $description_coverage = $content['total'] > 0
        ? (int) round(($content['customDescriptions'] / $content['total']) * 100)
        : 100;
    ?>
    <div class="wrap staark-hub-wrap staark-seo-page">
        <?php staark_hub_header('SEO'); ?>

        <?php if ($state === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p>SEO settings saved.</p></div>
        <?php endif; ?>

        <?php if ($conflict['active']) : ?>
            <div class="notice notice-warning"><p><strong><?php echo esc_html($conflict['name']); ?> is active.</strong> Staark SEO frontend metadata is paused to avoid duplicate titles, canonicals, Open Graph and schema. The audit/settings screen remains available.</p></div>
        <?php endif; ?>

        <section class="staark-seo-hero staark-hub-card">
            <div>
                <span class="staark-hub-card-label">Managed SEO foundation</span>
                <h2>Search metadata without another SEO stack</h2>
                <p>Staark handles the technical baseline: canonical URLs, social metadata, Schema.org data, indexability checks and WordPress core sitemap awareness. Content stays editable where it belongs.</p>
            </div>
            <div class="staark-seo-hero-status">
                <span class="staark-hub-mini-status <?php echo $enabled && ! $conflict['active'] ? 'staark-hub-mini-status--ok' : 'staark-hub-mini-status--warn'; ?>">
                    <?php echo $enabled ? ($conflict['active'] ? 'Output paused' : 'SEO active') : 'Module disabled'; ?>
                </span>
                <small><?php echo $conflict['active'] ? esc_html('Managed by ' . $conflict['name']) : 'Staark metadata owns the frontend output'; ?></small>
            </div>
        </section>

        <div class="staark-hub-grid staark-hub-grid--four staark-seo-stats">
            <section class="staark-hub-card staark-seo-stat">
                <span class="staark-hub-card-label">Indexability</span>
                <strong><?php echo $indexable ? 'Public' : 'Blocked'; ?></strong>
                <p><?php echo $indexable ? 'Search engines may index the website.' : 'WordPress is asking search engines not to index the site.'; ?></p>
            </section>

            <section class="staark-hub-card staark-seo-stat">
                <span class="staark-hub-card-label">XML sitemap</span>
                <strong><?php echo $sitemap ? 'Core sitemap' : 'Disabled'; ?></strong>
                <p><?php echo $sitemap ? 'Using WordPress /wp-sitemap.xml — no duplicate sitemap generated.' : 'WordPress core sitemap output is disabled by configuration or a plugin.'; ?></p>
            </section>

            <section class="staark-hub-card staark-seo-stat">
                <span class="staark-hub-card-label">Custom descriptions</span>
                <strong><?php echo esc_html((string) $description_coverage); ?>%</strong>
                <p><?php echo esc_html($content['customDescriptions'] . '/' . $content['total']); ?> audited posts/pages have a custom description.</p>
            </section>

            <section class="staark-hub-card staark-seo-stat">
                <span class="staark-hub-card-label">Local schema</span>
                <strong><?php echo esc_html((string) $local['percent']); ?>%</strong>
                <p><?php echo esc_html($local['complete'] . '/' . $local['total']); ?> core <?php echo esc_html($settings['entity_type']); ?> identity fields are complete.</p>
            </section>
        </div>

        <div class="staark-hub-grid staark-hub-grid--split staark-seo-layout">
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-seo-editor">
                <input type="hidden" name="action" value="staark_seo_save">
                <?php wp_nonce_field('staark_seo_save'); ?>

                <section class="staark-hub-card">
                    <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                        <div>
                            <span class="staark-hub-card-label">Module control</span>
                            <h2>Technical SEO output</h2>
                            <p>Each output group can be switched off independently. A known SEO plugin automatically wins to prevent duplicate markup.</p>
                        </div>
                    </div>

                    <label class="staark-seo-toggle staark-seo-toggle-master">
                        <span><strong>SEO module</strong><small>Disable all Staark frontend SEO output while preserving saved settings and per-page metadata.</small></span>
                        <input type="checkbox" name="module_enabled" value="1" <?php checked($enabled); ?>>
                    </label>

                    <div class="staark-seo-toggle-grid">
                        <label class="staark-seo-toggle">
                            <span><strong>Meta descriptions</strong><small>Custom description first, then authored excerpt/content, then site tagline.</small></span>
                            <input type="checkbox" name="enable_metadata" value="1" <?php checked($settings['enable_metadata']); ?>>
                        </label>
                        <label class="staark-seo-toggle">
                            <span><strong>Canonical URLs</strong><small>Automatic self-canonical with optional per-page override.</small></span>
                            <input type="checkbox" name="enable_canonical" value="1" <?php checked($settings['enable_canonical']); ?>>
                        </label>
                        <label class="staark-seo-toggle">
                            <span><strong>Open Graph + Twitter</strong><small>Social title, description, URL and image metadata.</small></span>
                            <input type="checkbox" name="enable_open_graph" value="1" <?php checked($settings['enable_open_graph']); ?>>
                        </label>
                        <label class="staark-seo-toggle">
                            <span><strong>JSON-LD schema</strong><small>WebSite, Organization/LocalBusiness, Breadcrumb and Article where appropriate.</small></span>
                            <input type="checkbox" name="enable_schema" value="1" <?php checked($settings['enable_schema']); ?>>
                        </label>
                    </div>
                </section>

                <section class="staark-hub-card">
                    <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                        <div>
                            <span class="staark-hub-card-label">Entity</span>
                            <h2>Organization & LocalBusiness schema</h2>
                            <p>Use real client details. Empty fields are simply omitted from the JSON-LD graph.</p>
                        </div>
                    </div>

                    <div class="staark-hub-form-grid">
                        <label class="staark-hub-field">
                            <span>Entity type</span>
                            <select name="entity_type">
                                <option value="LocalBusiness" <?php selected($settings['entity_type'], 'LocalBusiness'); ?>>LocalBusiness</option>
                                <option value="Organization" <?php selected($settings['entity_type'], 'Organization'); ?>>Organization</option>
                            </select>
                        </label>
                        <label class="staark-hub-field">
                            <span>Business / organization name</span>
                            <input type="text" name="organization_name" maxlength="160" value="<?php echo esc_attr($settings['organization_name']); ?>">
                        </label>
                    </div>

                    <label class="staark-hub-field">
                        <span>Business description</span>
                        <textarea name="organization_description" rows="3" maxlength="500"><?php echo esc_textarea($settings['organization_description']); ?></textarea>
                    </label>

                    <div class="staark-hub-form-grid">
                        <label class="staark-hub-field">
                            <span>Phone</span>
                            <input type="text" name="phone" maxlength="60" value="<?php echo esc_attr($settings['phone']); ?>" placeholder="+46 ...">
                        </label>
                        <label class="staark-hub-field">
                            <span>Email</span>
                            <input type="email" name="email" maxlength="190" value="<?php echo esc_attr($settings['email']); ?>">
                        </label>
                    </div>

                    <div class="staark-hub-form-grid">
                        <label class="staark-hub-field">
                            <span>Street address</span>
                            <input type="text" name="street_address" maxlength="190" value="<?php echo esc_attr($settings['street_address']); ?>">
                        </label>
                        <label class="staark-hub-field">
                            <span>City / locality</span>
                            <input type="text" name="locality" maxlength="120" value="<?php echo esc_attr($settings['locality']); ?>">
                        </label>
                    </div>

                    <div class="staark-hub-form-grid staark-seo-address-row">
                        <label class="staark-hub-field">
                            <span>Region</span>
                            <input type="text" name="region" maxlength="120" value="<?php echo esc_attr($settings['region']); ?>">
                        </label>
                        <label class="staark-hub-field">
                            <span>Postal code</span>
                            <input type="text" name="postal_code" maxlength="30" value="<?php echo esc_attr($settings['postal_code']); ?>">
                        </label>
                        <label class="staark-hub-field">
                            <span>Country</span>
                            <input type="text" name="country" maxlength="2" value="<?php echo esc_attr($settings['country']); ?>" placeholder="SE">
                        </label>
                    </div>
                </section>

                <section class="staark-hub-card">
                    <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                        <div>
                            <span class="staark-hub-card-label">Social previews</span>
                            <h2>Default image & Twitter</h2>
                            <p>Featured images win on individual posts/pages. This image is the site-wide fallback.</p>
                        </div>
                    </div>

                    <div class="staark-hub-form-grid">
                        <div class="staark-hub-field">
                            <span>Default social image</span>
                            <input type="hidden" id="staark_seo_social_image_id" name="default_social_image_id" value="<?php echo esc_attr((string) $settings['default_social_image_id']); ?>">
                            <div class="staark-seo-social-preview" id="staark-seo-social-preview">
                                <?php if ($social_image) : ?>
                                    <img src="<?php echo esc_url($social_image); ?>" alt="">
                                <?php else : ?>
                                    <div class="staark-hub-asset-placeholder">No image selected</div>
                                <?php endif; ?>
                            </div>
                            <div class="staark-hub-actions">
                                <button type="button" class="button" data-staark-media-button data-target="#staark_seo_social_image_id" data-preview="#staark-seo-social-preview" data-title="Choose default social image">Choose image</button>
                                <button type="button" class="button-link-delete" data-staark-media-remove data-target="#staark_seo_social_image_id" data-preview="#staark-seo-social-preview">Remove</button>
                            </div>
                            <small>A featured image overrides this fallback on individual content.</small>
                        </div>
                        <label class="staark-hub-field">
                            <span>Twitter / X handle</span>
                            <input type="text" name="twitter_handle" maxlength="50" value="<?php echo esc_attr($settings['twitter_handle']); ?>" placeholder="company">
                        </label>
                    </div>
                </section>

                <div class="staark-seo-savebar">
                    <div><strong>Technical SEO stays reversible</strong><span>No sitemap file, robots.txt file or theme template is rewritten by this module.</span></div>
                    <button type="submit" class="button button-primary">Save SEO settings</button>
                </div>
            </form>

            <aside class="staark-seo-sidebar">
                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">SEO audit</span>
                    <h2>Current site signals</h2>
                    <div class="staark-seo-audit-list">
                        <div class="<?php echo $indexable ? 'is-pass' : 'is-warn'; ?>"><i></i><span><strong>Search visibility</strong><small><?php echo $indexable ? 'Indexing allowed' : 'Discourage search engines is enabled'; ?></small></span></div>
                        <div class="<?php echo $pretty_permalinks ? 'is-pass' : 'is-warn'; ?>"><i></i><span><strong>Permalinks</strong><small><?php echo $pretty_permalinks ? 'Pretty URLs configured' : 'Plain query-string URLs'; ?></small></span></div>
                        <div class="<?php echo $sitemap ? 'is-pass' : 'is-warn'; ?>"><i></i><span><strong>Core sitemap</strong><small><?php echo $sitemap ? '/wp-sitemap.xml enabled' : 'Sitemap disabled'; ?></small></span></div>
                        <div class="<?php echo $alt['missing'] === 0 ? 'is-pass' : 'is-warn'; ?>"><i></i><span><strong>Image alt text</strong><small><?php echo esc_html($alt['missing'] . ' missing across ' . $alt['checked'] . ' recent image(s)'); ?></small></span></div>
                        <div class="<?php echo $local['percent'] === 100 ? 'is-pass' : 'is-info'; ?>"><i></i><span><strong><?php echo esc_html($settings['entity_type']); ?> identity</strong><small><?php echo esc_html($local['percent'] . '% core fields complete'); ?></small></span></div>
                        <div class="<?php echo ! $conflict['active'] ? 'is-pass' : 'is-info'; ?>"><i></i><span><strong>SEO ownership</strong><small><?php echo $conflict['active'] ? esc_html($conflict['name'] . ' owns frontend output') : 'Staark SEO owns frontend output'; ?></small></span></div>
                    </div>
                    <?php if ($alt['truncated'] || $content['truncated']) : ?>
                        <p class="staark-seo-note">Large-site audit is intentionally bounded. Counts are based on the most recent scan sample to keep wp-admin responsive.</p>
                    <?php endif; ?>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">WordPress core sitemap</span>
                    <h2>No duplicate sitemap</h2>
                    <p>Staark uses WordPress' native sitemap instead of generating another XML tree. That keeps ownership simple and avoids competing sitemap URLs.</p>
                    <div class="staark-hub-actions">
                        <a class="button" href="<?php echo esc_url(home_url('/wp-sitemap.xml')); ?>" target="_blank" rel="noopener">Open sitemap ↗</a>
                        <a class="button" href="<?php echo esc_url(admin_url('options-reading.php')); ?>">Search visibility</a>
                    </div>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">Per-page controls</span>
                    <h2>Edit where the content lives</h2>
                    <p>Posts and pages now get a Staark SEO panel for a custom title, description, canonical URL and noindex control.</p>
                    <div class="staark-hub-feature-list staark-hub-feature-list--stacked">
                        <span><i aria-hidden="true">✓</i>SEO title override</span>
                        <span><i aria-hidden="true">✓</i>Meta description</span>
                        <span><i aria-hidden="true">✓</i>Canonical override</span>
                        <span><i aria-hidden="true">✓</i>Noindex directive</span>
                    </div>
                </section>
            </aside>
        </div>
    </div>
    <?php
}
