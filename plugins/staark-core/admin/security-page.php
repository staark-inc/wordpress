<?php
/**
 * Staark Security admin screen.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_security_score_label(int $score): string
{
    if ($score >= 90) {
        return 'Strong';
    }
    if ($score >= 75) {
        return 'Good';
    }
    if ($score >= 55) {
        return 'Needs attention';
    }

    return 'At risk';
}

function staark_hub_security_score_class(int $score): string
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

function staark_hub_render_security(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $enabled = staark_hub_module_enabled('security', true);
    $settings = staark_hub_security_settings();
    $report = staark_hub_security_last_report();
    if ($report === []) {
        $report = staark_hub_security_store_report();
    }

    $score = isset($report['score']) ? (int) $report['score'] : 0;
    $passed = isset($report['passed']) ? (int) $report['passed'] : 0;
    $warnings = isset($report['warnings']) ? (int) $report['warnings'] : 0;
    $checked_at = isset($report['checked_at']) ? (string) $report['checked_at'] : '';
    $checks = isset($report['checks']) && is_array($report['checks']) ? $report['checks'] : [];
    $state = isset($_GET['staark_security']) ? sanitize_key(wp_unslash($_GET['staark_security'])) : '';
    $active_hardening = array_sum(
        [
            (int) $settings['security_headers'],
            (int) $settings['block_rest_users'],
            (int) $settings['disable_xmlrpc'],
            (int) $settings['generic_login_errors'],
            (int) $settings['login_protection'],
        ]
    );
    ?>
    <div class="wrap staark-hub-wrap staark-security-page">
        <?php staark_hub_header('Security'); ?>

        <?php if ($state === 'scanned') : ?>
            <div class="notice notice-success is-dismissible"><p>Security scan completed and the local snapshot was updated.</p></div>
        <?php elseif ($state === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p>Security settings saved. Reversible hardening changes apply on the next request.</p></div>
        <?php endif; ?>

        <section class="staark-security-hero">
            <div>
                <span class="staark-hub-card-label">Security Core</span>
                <h2>Configuration scanner + reversible hardening</h2>
                <p>Staark checks practical WordPress security hygiene without pretending that a score replaces patching, backups, monitoring or server security.</p>
            </div>
            <div class="staark-security-hero-actions">
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="staark_security_scan">
                    <?php wp_nonce_field('staark_security_scan'); ?>
                    <button type="submit" class="button button-primary">Run security scan</button>
                </form>
                <span class="staark-hub-mini-status <?php echo $enabled ? 'staark-hub-mini-status--ok' : 'staark-hub-mini-status--muted'; ?>">
                    <?php echo $enabled ? 'Module active' : 'Module disabled'; ?>
                </span>
            </div>
        </section>

        <div class="staark-security-summary">
            <section class="staark-hub-card staark-security-score-card">
                <div class="staark-security-score <?php echo esc_attr(staark_hub_security_score_class($score)); ?>" style="--staark-security-score:<?php echo esc_attr((string) $score); ?>">
                    <div><strong><?php echo esc_html((string) $score); ?></strong><span>/100</span></div>
                </div>
                <div>
                    <span class="staark-hub-card-label">Security score</span>
                    <h2><?php echo esc_html(staark_hub_security_score_label($score)); ?></h2>
                    <p>Weighted configuration hygiene. It is not a vulnerability or malware certification.</p>
                </div>
            </section>

            <section class="staark-hub-card staark-security-stat">
                <span class="staark-hub-card-label">Checks passing</span>
                <strong><?php echo esc_html((string) $passed); ?></strong>
                <p><?php echo esc_html((string) count($checks)); ?> checks in the current scanner.</p>
            </section>

            <section class="staark-hub-card staark-security-stat">
                <span class="staark-hub-card-label">Needs attention</span>
                <strong><?php echo esc_html((string) $warnings); ?></strong>
                <p>Items to review rather than blindly auto-fix.</p>
            </section>

            <section class="staark-hub-card staark-security-stat">
                <span class="staark-hub-card-label">Hardening</span>
                <strong><?php echo esc_html($active_hardening . '/5'); ?></strong>
                <p><?php echo $checked_at !== '' ? 'Last scan ' . esc_html($checked_at) : 'No stored scan yet'; ?></p>
            </section>
        </div>

        <div class="staark-hub-grid staark-hub-grid--split staark-security-layout">
            <section class="staark-hub-card">
                <div class="staark-hub-card-heading">
                    <div>
                        <span class="staark-hub-card-label">Scanner</span>
                        <h2>Security checks</h2>
                        <p>Read-only checks including official core checksums and a bounded uploads scan. Staark does not rewrite core files or server configuration during a scan.</p>
                    </div>
                </div>

                <div class="staark-security-checks">
                    <?php foreach ($checks as $check) :
                        $ok = ! empty($check['ok']);
                        $severity = isset($check['severity']) ? sanitize_key((string) $check['severity']) : 'info';
                        ?>
                        <article class="staark-security-check <?php echo $ok ? 'is-pass' : 'is-warning'; ?>">
                            <span class="staark-security-check-icon" aria-hidden="true"><?php echo $ok ? '✓' : '!'; ?></span>
                            <div>
                                <div class="staark-security-check-title">
                                    <strong><?php echo esc_html((string) $check['label']); ?></strong>
                                    <span class="staark-security-severity is-<?php echo esc_attr($severity); ?>"><?php echo esc_html(ucfirst($severity)); ?></span>
                                </div>
                                <p><?php echo esc_html((string) $check['detail']); ?></p>
                                <?php if (! $ok && ! empty($check['recommendation'])) : ?>
                                    <small><?php echo esc_html((string) $check['recommendation']); ?></small>
                                <?php endif; ?>
                            </div>
                            <b><?php echo esc_html((string) $check['points']); ?> pt</b>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="staark-security-sidebar">
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-card staark-security-settings">
                    <input type="hidden" name="action" value="staark_security_save">
                    <?php wp_nonce_field('staark_security_save'); ?>

                    <span class="staark-hub-card-label">Module control</span>
                    <h2>Safe hardening</h2>
                    <p>Every switch is reversible. Compatibility-sensitive changes are opt-in.</p>

                    <label class="staark-security-toggle staark-security-toggle-master">
                        <span>
                            <strong>Security module</strong>
                            <small>Disable all Staark Security runtime hardening while keeping stored scan history.</small>
                        </span>
                        <input type="checkbox" name="module_enabled" value="1" <?php checked($enabled); ?>>
                    </label>

                    <div class="staark-security-toggle-list">
                        <label class="staark-security-toggle">
                            <span>
                                <strong>Baseline security headers</strong>
                                <small>Add nosniff and strict-origin referrer policy. Does not set HSTS or CSP automatically.</small>
                            </span>
                            <input type="checkbox" name="security_headers" value="1" <?php checked($settings['security_headers']); ?>>
                        </label>

                        <label class="staark-security-toggle">
                            <span>
                                <strong>Generic login errors</strong>
                                <small>Avoid revealing whether a username or email exists during a failed login.</small>
                            </span>
                            <input type="checkbox" name="generic_login_errors" value="1" <?php checked($settings['generic_login_errors']); ?>>
                        </label>

                        <label class="staark-security-toggle">
                            <span>
                                <strong>Login brute-force protection</strong>
                                <small>Throttle repeated failed logins with conservative per-IP and per-identity buckets. Leave off when your host/WAF already provides equivalent protection.</small>
                            </span>
                            <input type="checkbox" name="login_protection" value="1" <?php checked($settings['login_protection']); ?>>
                        </label>

                        <label class="staark-security-toggle">
                            <span>
                                <strong>Protect REST user listing</strong>
                                <small>Remove wp/v2/users routes for visitors. Leave off if a public integration needs author data.</small>
                            </span>
                            <input type="checkbox" name="block_rest_users" value="1" <?php checked($settings['block_rest_users']); ?>>
                        </label>

                        <label class="staark-security-toggle">
                            <span>
                                <strong>Disable XML-RPC</strong>
                                <small>Enable only when Jetpack, remote publishing and other XML-RPC integrations are not used.</small>
                            </span>
                            <input type="checkbox" name="disable_xmlrpc" value="1" <?php checked($settings['disable_xmlrpc']); ?>>
                        </label>
                    </div>

                    <button type="submit" class="button button-primary">Save security settings</button>
                </form>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">Deliberately manual</span>
                    <h2>No risky auto-edits</h2>
                    <div class="staark-hub-feature-list staark-hub-feature-list--stacked">
                        <span><i aria-hidden="true">✓</i>No wp-config.php rewriting</span>
                        <span><i aria-hidden="true">✓</i>No hidden wp-admin URL tricks</span>
                        <span><i aria-hidden="true">✓</i>No automatic HSTS/CSP policy</span>
                        <span><i aria-hidden="true">✓</i>No deletion of plugins or users</span>
                    </div>
                    <p class="staark-security-note">High-impact remediation stays a conscious maintenance action so the security module can be disabled without leaving the website in a broken state.</p>
                </section>
            </aside>
        </div>
    </div>
    <?php
}
