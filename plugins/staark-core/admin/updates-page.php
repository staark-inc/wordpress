<?php
/**
 * Staark Hub update-channel screen.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_render_updates(): void
{
    $summary = staark_hub_update_summary();
    $core = staark_hub_update_release('core');
    $theme = staark_hub_update_release('theme');
    $status = isset($_GET['staark_updates']) ? sanitize_key(wp_unslash($_GET['staark_updates'])) : '';
    $can_install = staark_hub_update_can_install();
    $operator_required = function_exists('staark_hub_is_managed') && staark_hub_is_managed() && ! $can_install;
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Updates'); ?>

        <?php if ($status === 'checked') : ?>
            <div class="notice notice-success is-dismissible"><p>Staark update channel checked successfully.</p></div>
        <?php elseif ($status === 'check_error') : ?>
            <div class="notice notice-error"><p>The update channel could not be checked. Review the channel status below.</p></div>
        <?php elseif ($status === 'core_installed') : ?>
            <div class="notice notice-success is-dismissible"><p>Staark Core update installed and staged for next-request health verification.</p></div>
        <?php elseif ($status === 'theme_installed') : ?>
            <div class="notice notice-success is-dismissible"><p>Staark Theme update installed and verified.</p></div>
        <?php elseif ($status === 'install_error') : ?>
            <div class="notice notice-error"><p>The managed update was not installed. No successful release switch was recorded.</p></div>
        <?php endif; ?>

        <section class="staark-hub-updates-hero">
            <div>
                <span class="staark-hub-card-label">WP-6.1 Update Channel</span>
                <h2>Managed releases without blind updates</h2>
                <p>Staark checks a release manifest, verifies package integrity, stages the package and uses the managed deployment runtime for Staark Core. Theme updates use the same checksum and preflight rules.</p>
            </div>
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="staark_updates_check">
                <?php wp_nonce_field('staark_updates_check'); ?>
                <button type="submit" class="button button-primary">Check for updates</button>
            </form>
        </section>

        <div class="staark-hub-grid staark-hub-grid--split staark-hub-updates-grid">
            <section class="staark-hub-card staark-hub-update-card">
                <div class="staark-hub-update-card-head">
                    <div>
                        <span class="staark-hub-card-label">Staark Core</span>
                        <h2><?php echo esc_html($summary['coreInstalled']); ?></h2>
                        <p>Managed runtime + normal plugin package.</p>
                    </div>
                    <span class="staark-hub-mini-status <?php echo $summary['coreUpdateAvailable'] ? 'staark-hub-mini-status--warn' : 'staark-hub-mini-status--ok'; ?>">
                        <?php echo $summary['coreUpdateAvailable'] ? 'Update available' : 'Current'; ?>
                    </span>
                </div>

                <dl class="staark-hub-details staark-hub-update-details">
                    <div><dt>Installed</dt><dd><?php echo esc_html($summary['coreInstalled']); ?></dd></div>
                    <div><dt>Latest</dt><dd><?php echo esc_html($summary['coreLatest'] !== '' ? $summary['coreLatest'] : 'Unknown'); ?></dd></div>
                    <div><dt>Channel</dt><dd><?php echo esc_html(ucfirst((string) $summary['channel'])); ?></dd></div>
                    <div><dt>Health check</dt><dd><?php echo $summary['pendingCoreHealth'] ? 'Pending next request' : 'Ready'; ?></dd></div>
                </dl>

                <?php if (is_array($core) && (string) ($core['notes'] ?? '') !== '') : ?>
                    <div class="staark-hub-update-notes"><?php echo nl2br(esc_html((string) $core['notes'])); ?></div>
                <?php endif; ?>

                <?php if ($summary['coreUpdateAvailable']) : ?>
                    <?php if ($can_install) : ?>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" onsubmit="return confirm('Install the verified Staark Core update now?');">
                            <input type="hidden" name="action" value="staark_updates_install_core">
                            <?php wp_nonce_field('staark_updates_install_core'); ?>
                            <button type="submit" class="button button-primary">Install Core <?php echo esc_html((string) $summary['coreLatest']); ?></button>
                        </form>
                    <?php else : ?>
                        <p class="staark-hub-update-managed-note">This site is managed. A Staark operator must install Core updates.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="staark-hub-card staark-hub-update-card">
                <div class="staark-hub-update-card-head">
                    <div>
                        <span class="staark-hub-card-label">Staark Theme</span>
                        <h2><?php echo esc_html($summary['themeInstalled'] !== '' ? $summary['themeInstalled'] : 'Not installed'); ?></h2>
                        <p>Verified theme package with preflight + rollback.</p>
                    </div>
                    <span class="staark-hub-mini-status <?php echo $summary['themeUpdateAvailable'] ? 'staark-hub-mini-status--warn' : 'staark-hub-mini-status--ok'; ?>">
                        <?php echo $summary['themeUpdateAvailable'] ? 'Update available' : 'Current'; ?>
                    </span>
                </div>

                <dl class="staark-hub-details staark-hub-update-details">
                    <div><dt>Slug</dt><dd><?php echo esc_html((string) $summary['themeSlug']); ?></dd></div>
                    <div><dt>Installed</dt><dd><?php echo esc_html($summary['themeInstalled'] !== '' ? $summary['themeInstalled'] : '—'); ?></dd></div>
                    <div><dt>Latest</dt><dd><?php echo esc_html($summary['themeLatest'] !== '' ? $summary['themeLatest'] : 'Unknown'); ?></dd></div>
                    <div><dt>Verification</dt><dd>SHA256 + PHP lint<?php echo defined('STAARK_HUB_UPDATE_PUBLIC_KEY') ? ' + signature' : ''; ?></dd></div>
                </dl>

                <?php if (is_array($theme) && (string) ($theme['notes'] ?? '') !== '') : ?>
                    <div class="staark-hub-update-notes"><?php echo nl2br(esc_html((string) $theme['notes'])); ?></div>
                <?php endif; ?>

                <?php if ($summary['themeUpdateAvailable']) : ?>
                    <?php if ($can_install) : ?>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" onsubmit="return confirm('Install the verified Staark Theme update now?');">
                            <input type="hidden" name="action" value="staark_updates_install_theme">
                            <?php wp_nonce_field('staark_updates_install_theme'); ?>
                            <button type="submit" class="button button-primary">Install Theme <?php echo esc_html((string) $summary['themeLatest']); ?></button>
                        </form>
                    <?php else : ?>
                        <p class="staark-hub-update-managed-note">This site is managed. A Staark operator must install Theme updates.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>

        <div class="staark-hub-grid staark-hub-grid--split staark-hub-updates-grid">
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Channel status</span>
                <h2><?php echo esc_html(ucfirst((string) $summary['channel'])); ?> channel</h2>
                <dl class="staark-hub-details staark-hub-update-details">
                    <div><dt>Last checked</dt><dd><?php echo esc_html($summary['lastChecked'] !== '' ? $summary['lastChecked'] : 'Never'); ?></dd></div>
                    <div><dt>Next scheduled</dt><dd><?php echo $summary['scheduled'] > 0 ? esc_html(wp_date('Y-m-d H:i', (int) $summary['scheduled'])) : 'Not scheduled'; ?></dd></div>
                    <div><dt>Manifest endpoint</dt><dd><code><?php echo esc_html(staark_hub_update_manifest_url()); ?></code></dd></div>
                    <div><dt>Signed updates</dt><dd><?php echo defined('STAARK_HUB_REQUIRE_SIGNED_UPDATES') && STAARK_HUB_REQUIRE_SIGNED_UPDATES === true ? 'Required' : 'Optional'; ?></dd></div>
                </dl>

                <?php if ($summary['lastError'] !== '') : ?>
                    <div class="staark-hub-update-error"><strong>Last channel error</strong><span><?php echo esc_html((string) $summary['lastError']); ?></span></div>
                <?php endif; ?>
            </section>

            <section class="staark-hub-card staark-hub-card--dark">
                <span class="staark-hub-card-label">Safety model</span>
                <h2>Verify → stage → switch → health-check</h2>
                <div class="staark-hub-feature-list staark-hub-feature-list--stacked">
                    <span><i aria-hidden="true">✓</i>HTTPS package URLs outside local/dev</span>
                    <span><i aria-hidden="true">✓</i>SHA256 integrity verification</span>
                    <span><i aria-hidden="true">✓</i>Optional Ed25519 detached signatures</span>
                    <span><i aria-hidden="true">✓</i>PHP syntax preflight before switch</span>
                    <span><i aria-hidden="true">✓</i>Managed Core release + previous pointer</span>
                    <span><i aria-hidden="true">✓</i>Next-request Core health verification</span>
                </div>
                <?php if ($operator_required) : ?>
                    <span class="staark-hub-pill">Client view · updates managed by Staark</span>
                <?php else : ?>
                    <span class="staark-hub-pill">Operator controls available</span>
                <?php endif; ?>
            </section>
        </div>

        <?php if ($summary['lastAction'] !== '') : ?>
            <section class="staark-hub-card staark-hub-update-last-action">
                <span class="staark-hub-card-label">Last update action</span>
                <h2><?php echo esc_html((string) $summary['lastAction']); ?></h2>
                <p><?php echo esc_html($summary['lastActionAt'] !== '' ? (string) $summary['lastActionAt'] : ''); ?></p>
            </section>
        <?php endif; ?>
    </div>
    <?php
}
