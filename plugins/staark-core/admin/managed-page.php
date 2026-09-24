<?php
/**
 * Staark Managed Mode admin screen.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_render_managed(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $summary = staark_hub_managed_summary();
    $mode = $summary['mode'];
    $operator_ids = staark_hub_operator_ids();
    $is_operator = staark_hub_current_user_is_operator();
    $state = isset($_GET['staark_managed']) ? sanitize_key(wp_unslash($_GET['staark_managed'])) : '';
    ?>
    <div class="wrap staark-hub-wrap staark-managed-page">
        <?php staark_hub_header('Managed Mode'); ?>

        <?php if ($state === 'operator_ready') : ?>
            <div class="notice notice-success is-dismissible"><p>This administrator is now a Staark operator.</p></div>
        <?php elseif ($state === 'mode_saved') : ?>
            <div class="notice notice-success is-dismissible"><p>Managed Mode policy saved.</p></div>
        <?php elseif (in_array($state, ['operator_error', 'mode_error', 'operator_exists'], true)) : ?>
            <div class="notice notice-error is-dismissible"><p>The Managed Mode change was not applied. Use WP-CLI for recovery or review the operator state.</p></div>
        <?php endif; ?>

        <section class="staark-managed-hero">
            <div>
                <span class="staark-hub-card-label">Staark 6.0</span>
                <h2>Separate client administration from Staark operations.</h2>
                <p>Managed Mode now protects the Staark plugin from client-side mutation while keeping Staark operators and WP-CLI as recovery paths. Locked mode uses the Staark MU-loader so the managed runtime no longer depends on the normal plugin activation state.</p>
            </div>
            <span class="staark-managed-mode is-<?php echo esc_attr($mode); ?>"><?php echo esc_html($summary['label']); ?></span>
        </section>

        <div class="staark-managed-summary">
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Mode</span>
                <strong><?php echo esc_html($summary['label']); ?></strong>
                <p><?php echo $summary['forced'] ? 'Forced by server configuration.' : 'Stored in WordPress options.'; ?></p>
            </section>
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Operators</span>
                <strong><?php echo esc_html((string) $summary['operatorCount']); ?></strong>
                <p>Explicit Staark-authorized WordPress administrators.</p>
            </section>
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Current account</span>
                <strong><?php echo $is_operator ? 'Staark operator' : 'Administrator'; ?></strong>
                <p><?php echo $is_operator ? 'This account can change the managed-site policy.' : 'No Staark operator flag on this account.'; ?></p>
            </section>
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Loader</span>
                <strong><?php echo esc_html($summary['loader']); ?></strong>
                <p><?php echo $summary['bootstrappedByMu'] ? 'Staark Core was bootstrapped by the must-use loader for this request.' : ($summary['loaderInstalled'] ? 'MU-loader installed and ready for Locked mode.' : 'Regular plugin runtime; install the MU-loader before Locked mode.'); ?></p>
            </section>
        </div>

        <div class="staark-hub-grid staark-hub-grid--split staark-managed-layout">
            <section class="staark-hub-card">
                <span class="staark-hub-card-label">Deployment policy</span>
                <h2>Normal, Managed or Locked</h2>
                <div class="staark-managed-modes">
                    <article class="<?php echo $mode === 'normal' ? 'is-current' : ''; ?>">
                        <strong>Normal</strong>
                        <p>Standard WordPress plugin behavior. No management restrictions are requested.</p>
                    </article>
                    <article class="<?php echo $mode === 'managed' ? 'is-current' : ''; ?>">
                        <strong>Managed</strong>
                        <p>Client administrators cannot deactivate, delete, overwrite or edit Staark Core through WordPress. Staark operators keep control.</p>
                    </article>
                    <article class="<?php echo $mode === 'locked' ? 'is-current' : ''; ?>">
                        <strong>Locked</strong>
                        <p>Uses the same WordPress-level protection as Managed. The MU-loader boots Staark Core before normal plugins, so runtime remains available even if the regular plugin activation flag is removed.</p>
                    </article>
                </div>

                <?php if (! $summary['loaderInstalled']) : ?>
                    <div class="staark-managed-callout"><strong>MU-loader required for Locked</strong><p>Install it with <code>wp staark managed loader install</code> or deploy <code>deployment/staark-loader.php</code> to <code>wp-content/mu-plugins/staark-loader.php</code>.</p></div>
                <?php endif; ?>

                <?php if ($summary['forced']) : ?>
                    <div class="staark-managed-callout"><strong>Server controlled</strong><p><code>STAARK_HUB_MANAGED_MODE</code> is defined, so wp-admin cannot change this value.</p></div>
                <?php elseif ($is_operator) : ?>
                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-managed-mode-form">
                        <input type="hidden" name="action" value="staark_managed_save_mode">
                        <?php wp_nonce_field('staark_managed_save_mode'); ?>
                        <label>
                            <span>Managed Mode</span>
                            <select name="managed_mode">
                                <option value="normal" <?php selected($mode, 'normal'); ?>>Normal</option>
                                <option value="managed" <?php selected($mode, 'managed'); ?>>Managed</option>
                                <option value="locked" <?php selected($mode, 'locked'); ?> <?php disabled(! $summary['loaderInstalled'] && $mode !== 'locked'); ?>>Locked · MU-loader required</option>
                            </select>
                        </label>
                        <button type="submit" class="button button-primary">Save deployment mode</button>
                    </form>
                <?php else : ?>
                    <div class="staark-managed-callout"><strong>Operator required</strong><p>Only an explicit Staark operator may change the deployment mode.</p></div>
                <?php endif; ?>
            </section>

            <aside class="staark-managed-sidebar">
                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">Staark authority</span>
                    <h2>Operators</h2>
                    <?php if ($operator_ids === []) : ?>
                        <p>No Staark operator is configured yet. Bootstrap one administrator before switching away from Normal.</p>
                        <?php if ($mode === 'normal') : ?>
                            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                <input type="hidden" name="action" value="staark_managed_bootstrap_operator">
                                <?php wp_nonce_field('staark_managed_bootstrap_operator'); ?>
                                <button type="submit" class="button button-primary">Make this admin a Staark operator</button>
                            </form>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="staark-managed-operators">
                            <?php foreach ($operator_ids as $operator_id) :
                                $operator = get_userdata($operator_id);
                                if (! $operator instanceof WP_User) {
                                    continue;
                                }
                                ?>
                                <div><strong><?php echo esc_html($operator->display_name ?: $operator->user_login); ?></strong><span>#<?php echo esc_html((string) $operator->ID); ?> · <?php echo esc_html($operator->user_login); ?></span></div>
                            <?php endforeach; ?>
                        </div>
                        <p class="staark-managed-note">Additional operator grant/revoke is intentionally CLI-only in this foundation patch.</p>
                    <?php endif; ?>
                </section>

                <section class="staark-hub-card staark-managed-cli">
                    <span class="staark-hub-card-label">Recovery path</span>
                    <h2>WP-CLI stays authoritative</h2>
                    <code>wp staark managed status</code>
                    <code>wp staark managed grant &lt;user&gt;</code>
                    <code>wp staark managed loader status</code>
                    <code>wp staark managed loader install</code>
                    <code>wp staark managed mode locked</code>
                    <code>wp plugin activate staark-core</code>
                    <code>wp staark managed mode managed</code>
                    <p>When leaving Locked mode after a server-side deactivation, reactivate <code>staark-core</code> first so the regular plugin lifecycle can resume safely.</p>
                </section>

                <section class="staark-hub-card staark-managed-warning">
                    <span class="staark-hub-card-label">6.0C Locked runtime</span>
                    <h2>MU-loader ready</h2>
                    <p>Locked mode now boots Staark Core from <code>mu-plugins/staark-loader.php</code>. Managed mode keeps normal plugin activation behavior, while WP-CLI and explicit Staark operators remain recovery paths.</p>
                </section>
            </aside>
        </div>
    </div>
    <?php
}
