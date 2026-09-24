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
        'admin/security-page.php',
        'admin/seo-page.php',
        'admin/performance-page.php',
        'assets/admin.css',
        'assets/cleanup.css',
        'assets/accessibility.js',
        'assets/security.css',
        'assets/seo.css',
        'assets/performance.css',
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
        'support' => function_exists('staark_hub_support_tickets') && function_exists('staark_hub_render_support'),
        'connect' => function_exists('staark_hub_connection_request') && function_exists('staark_hub_render_connect'),
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

    $version_option = (string) get_option('staark_hub_installed_version', '');
    $php_ok = PHP_VERSION_ID >= 80000;
    $connection = function_exists('staark_hub_connection_ensure_identity')
        ? staark_hub_connection_ensure_identity()
        : [];
    $connection_secret = isset($connection['site_secret']) ? (string) $connection['site_secret'] : '';

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
            $missing_modules === [] ? 'Security, SEO, Performance, Support and Connect functions are loaded.' : 'Missing: ' . implode(', ', $missing_modules)
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
            'connection_secret',
            'Connector secret storage',
            $connection_secret !== '' && strlen($connection_secret) >= 32,
            $connection_secret !== ''
                ? 'Connector identity exists locally and its secret is omitted from RC output.'
                : 'Connector identity could not be initialized.'
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
