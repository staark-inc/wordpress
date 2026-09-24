<?php
/**
 * Staark Hub release-candidate diagnostics.
 *
 * These checks are intentionally read-only. They support the RC smoke process
 * without introducing another client-facing settings screen.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return array{id:string,label:string,ok:bool,detail:string}
 */
function staark_hub_rc_check(string $id, string $label, bool $ok, string $detail): array
{
    return compact('id', 'label', 'ok', 'detail');
}

/**
 * @return array<int,array{id:string,label:string,ok:bool,detail:string}>
 */
function staark_hub_rc_checks(): array
{
    $required_files = [
        'includes/helpers.php',
        'includes/accessibility.php',
        'includes/security.php',
        'includes/security-login.php',
        'includes/seo.php',
        'includes/performance.php',
        'includes/lifecycle.php',
        'includes/managed.php',
        'includes/managed-protection.php',
        'includes/managed-deployment.php',
        'includes/update-channel.php',
        'includes/support-sync.php',
        'deployment/staark-loader.php',
        'admin/security-page.php',
        'admin/seo-page.php',
        'admin/performance-page.php',
        'admin/managed-page.php',
        'admin/support-detail.php',
        'admin/updates-page.php',
        'assets/admin.css',
        'assets/cleanup.css',
        'assets/accessibility.js',
        'assets/security.css',
        'assets/seo.css',
        'assets/performance.css',
        'assets/managed.css',
        'assets/updates.css',
    ];

    $missing = [];
    foreach ($required_files as $relative) {
        if (! is_readable(STAARK_HUB_PLUGIN_DIR . $relative)) {
            $missing[] = $relative;
        }
    }

    $modules = [
        'security' => function_exists('staark_hub_security_scan') && function_exists('staark_hub_render_security'),
        'seo' => function_exists('staark_hub_seo_settings') && function_exists('staark_hub_render_seo'),
        'performance' => function_exists('staark_hub_performance_run_audit') && function_exists('staark_hub_render_performance'),
        'support' => function_exists('staark_hub_support_tickets') && function_exists('staark_hub_render_support') && function_exists('staark_hub_render_support_ticket_detail') && function_exists('staark_hub_support_apply_remote_tickets'),
        'connect' => function_exists('staark_hub_connection_request') && function_exists('staark_hub_render_connect'),
        'managed' => function_exists('staark_hub_managed_summary') && function_exists('staark_hub_render_managed') && function_exists('staark_hub_managed_protection_summary'),
        'updates' => function_exists('staark_hub_update_summary') && function_exists('staark_hub_render_updates'),
    ];
    $missing_modules = array_keys(array_filter($modules, static fn (bool $loaded): bool => ! $loaded));

    $security_enabled = function_exists('staark_hub_module_enabled')
        ? staark_hub_module_enabled('security', true)
        : true;
    $performance_enabled = function_exists('staark_hub_module_enabled')
        ? staark_hub_module_enabled('performance', true)
        : true;

    $security_cron = wp_next_scheduled('staark_hub_security_daily_scan');
    $performance_cron = wp_next_scheduled('staark_hub_performance_daily_audit');
    $update_cron = wp_next_scheduled('staark_hub_update_check_twicedaily');

    $version_option = (string) get_option('staark_hub_installed_version', '');
    $php_ok = PHP_VERSION_ID >= 80000;
    $connection = function_exists('staark_hub_connection_ensure_identity')
        ? staark_hub_connection_ensure_identity()
        : [];
    $connection_secret = isset($connection['site_secret']) ? (string) $connection['site_secret'] : '';
    $managed = function_exists('staark_hub_managed_summary') ? staark_hub_managed_summary() : ['mode' => 'normal', 'operatorCount' => 0];
    $managed_mode = isset($managed['mode']) ? (string) $managed['mode'] : 'normal';
    $managed_operators = isset($managed['operatorCount']) ? (int) $managed['operatorCount'] : 0;
    $managed_safe = $managed_mode === 'normal' || $managed_operators > 0;
    $managed_protection = function_exists('staark_hub_managed_protection_summary') ? staark_hub_managed_protection_summary() : [];
    $managed_protection_enabled = ! empty($managed_protection['enabled']);
    $managed_protection_safe = $managed_mode === 'normal' || $managed_protection_enabled;
    $managed_loader = isset($managed['loader']) ? (string) $managed['loader'] : 'plugin';
    $managed_loader_installed = ! empty($managed['loaderInstalled']);
    $managed_bootstrapped_by_mu = ! empty($managed['bootstrappedByMu']);
    $locked_runtime_safe = $managed_mode !== 'locked' || ($managed_loader === 'mu' && $managed_bootstrapped_by_mu);
    $deployment = function_exists('staark_hub_managed_deployment_status')
        ? staark_hub_managed_deployment_status()
        : [];
    $deployment_required = $managed_mode !== 'normal';
    $deployment_current_readable = ! empty($deployment['currentReadable']);
    $deployment_manifest_version = isset($deployment['manifestVersion']) ? (string) $deployment['manifestVersion'] : '';
    $deployment_runtime_source = isset($deployment['runtimeSource']) ? (string) $deployment['runtimeSource'] : '';
    $managed_deployment_safe = ! $deployment_required
        || (
            $deployment_current_readable
            && $deployment_manifest_version !== ''
            && ($managed_mode !== 'locked' || $deployment_runtime_source === 'managed')
        );

    $checks = [
        staark_hub_rc_check(
            'version',
            'Version marker',
            $version_option === STAARK_HUB_VERSION,
            $version_option === STAARK_HUB_VERSION
                ? 'Installed version matches ' . STAARK_HUB_VERSION . '.'
                : 'Installed version option is ' . ($version_option !== '' ? $version_option : 'missing') . '.'
        ),
        staark_hub_rc_check(
            'php',
            'PHP runtime',
            $php_ok,
            'Running PHP ' . PHP_VERSION . '; Staark modules require PHP 8+ language helpers.'
        ),
        staark_hub_rc_check(
            'files',
            'Required files',
            $missing === [],
            $missing === [] ? 'All RC module files are readable.' : 'Missing/unreadable: ' . implode(', ', $missing)
        ),
        staark_hub_rc_check(
            'modules',
            'Module bootstrap',
            $missing_modules === [],
            $missing_modules === [] ? 'Security, SEO, Performance, Support, Connect, Managed and Updates functions are loaded.' : 'Missing: ' . implode(', ', $missing_modules)
        ),
        staark_hub_rc_check(
            'security_cron',
            'Security schedule',
            ! $security_enabled || $security_cron !== false,
            ! $security_enabled
                ? 'Security module is disabled; no scheduled scan is required.'
                : ($security_cron !== false ? 'Daily security scan is scheduled.' : 'Security is enabled but its daily scan is not scheduled.')
        ),
        staark_hub_rc_check(
            'performance_cron',
            'Performance schedule',
            ! $performance_enabled || $performance_cron !== false,
            ! $performance_enabled
                ? 'Performance module is disabled; no scheduled audit is required.'
                : ($performance_cron !== false ? 'Daily performance audit is scheduled.' : 'Performance is enabled but its daily audit is not scheduled.')
        ),
        staark_hub_rc_check(
            'update_cron',
            'Update channel schedule',
            $update_cron !== false,
            $update_cron !== false ? 'Twice-daily Staark update checks are scheduled.' : 'Staark update channel is loaded but its scheduled check is missing.'
        ),
        staark_hub_rc_check(
            'connection_secret',
            'Connector secret storage',
            $connection_secret !== '' && strlen($connection_secret) >= 32,
            $connection_secret !== ''
                ? 'Connector identity exists locally and its secret is omitted from RC output.'
                : 'Connector identity could not be initialized.'
        ),
        staark_hub_rc_check(
            'managed_authority',
            'Managed authority',
            $managed_safe,
            $managed_safe
                ? 'Managed Mode is ' . $managed_mode . ' with ' . $managed_operators . ' Staark operator(s).'
                : 'Managed/Locked mode requires at least one Staark operator for recovery.'
        ),
        staark_hub_rc_check(
            'managed_protection',
            'Managed protection',
            $managed_protection_safe,
            $managed_mode === 'normal'
                ? 'Managed protection is not required while the site is in Normal mode.'
                : ($managed_protection_enabled
                    ? 'WordPress-level Staark plugin protection is active; operator and WP-CLI recovery are retained.'
                    : 'Managed/Locked mode is active but plugin protection is disabled or unavailable.')
        ),
        staark_hub_rc_check(
            'managed_deployment',
            'Managed deployment',
            $managed_deployment_safe,
            ! $deployment_required
                ? 'Managed release deployment is not required while the site is in Normal mode.'
                : ($managed_deployment_safe
                    ? 'Managed release is readable at version ' . $deployment_manifest_version . ($managed_mode === 'locked' ? ' and Locked runtime source is managed.' : '.')
                    : 'Managed/Locked mode requires a readable deployed release; Locked mode must run from runtime source managed.')
        ),
        staark_hub_rc_check(
            'locked_runtime',
            'Locked MU runtime',
            $locked_runtime_safe,
            $managed_mode !== 'locked'
                ? ($managed_loader_installed ? 'MU-loader is installed and ready; Locked runtime is not currently required.' : 'Locked runtime is not required in the current mode.')
                : ($locked_runtime_safe
                    ? 'Locked mode bootstrapped Staark Core through the MU-loader.'
                    : 'Locked mode is configured but this request was not bootstrapped by the Staark MU-loader.')
        ),
    ];

    return $checks;
}

/**
 * @return array{ok:bool,passed:int,failed:int,checks:array<int,array<string,mixed>>}
 */
function staark_hub_rc_report(): array
{
    $checks = staark_hub_rc_checks();
    $failed = count(array_filter($checks, static fn (array $check): bool => empty($check['ok'])));

    return [
        'ok' => $failed === 0,
        'passed' => count($checks) - $failed,
        'failed' => $failed,
        'checks' => $checks,
    ];
}

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    WP_CLI::add_command('staark rc-check', static function (array $args, array $assoc_args): void {
        unset($args);
        $report = staark_hub_rc_report();
        $format = isset($assoc_args['format']) ? sanitize_key((string) $assoc_args['format']) : 'table';

        if ($format === 'json') {
            WP_CLI::line((string) wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $rows = array_map(
                static fn (array $check): array => [
                    'status' => ! empty($check['ok']) ? 'PASS' : 'FAIL',
                    'check' => (string) $check['label'],
                    'detail' => (string) $check['detail'],
                ],
                $report['checks']
            );
            WP_CLI\Utils\format_items('table', $rows, ['status', 'check', 'detail']);
        }

        if (! $report['ok']) {
            WP_CLI::error(sprintf('Staark RC check failed: %d check(s) need attention.', $report['failed']), false);
            return;
        }

        WP_CLI::success(sprintf('Staark RC checks passed: %d/%d.', $report['passed'], $report['passed'] + $report['failed']));
    });
}
