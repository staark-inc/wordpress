<?php
/**
 * Staark Security Core.
 *
 * The module deliberately separates auditing from hardening:
 * - audits are read-only;
 * - hardening switches are explicit and reversible;
 * - no core files, .htaccess or wp-config.php are edited automatically.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_SECURITY_SCAN_HOOK = 'staark_hub_security_daily_scan';

/**
 * @return array{security_headers:bool,block_rest_users:bool,disable_xmlrpc:bool,generic_login_errors:bool,login_protection:bool}
 */
function staark_hub_security_settings(): array
{
    $saved = get_option('staark_hub_security_settings', []);
    if (! is_array($saved)) {
        $saved = [];
    }

    return array_merge(
        [
            'security_headers' => true,
            'block_rest_users' => false,
            'disable_xmlrpc' => false,
            'generic_login_errors' => true,
            'login_protection' => false,
        ],
        array_map('boolval', $saved)
    );
}

/**
 * @param array<string,bool> $settings
 */
function staark_hub_security_save_settings(array $settings): void
{
    update_option(
        'staark_hub_security_settings',
        [
            'security_headers' => ! empty($settings['security_headers']),
            'block_rest_users' => ! empty($settings['block_rest_users']),
            'disable_xmlrpc' => ! empty($settings['disable_xmlrpc']),
            'generic_login_errors' => ! empty($settings['generic_login_errors']),
            'login_protection' => ! empty($settings['login_protection']),
        ],
        true
    );
}

/**
 * @return array{id:string,label:string,ok:bool,severity:string,points:int,detail:string,recommendation:string}
 */
function staark_hub_security_check(
    string $id,
    string $label,
    bool $ok,
    string $severity,
    int $points,
    string $detail,
    string $recommendation = ''
): array {
    return compact('id', 'label', 'ok', 'severity', 'points', 'detail', 'recommendation');
}

function staark_hub_security_has_strong_salts(): bool
{
    $constants = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT',
    ];

    foreach ($constants as $constant) {
        if (! defined($constant)) {
            return false;
        }

        $value = trim((string) constant($constant));
        if ($value === '' || stripos($value, 'put your unique phrase here') !== false || strlen($value) < 32) {
            return false;
        }
    }

    return true;
}

function staark_hub_security_config_path(): string
{
    $inside_root = ABSPATH . 'wp-config.php';
    if (is_file($inside_root)) {
        return $inside_root;
    }

    $above_root = dirname(untrailingslashit(ABSPATH)) . '/wp-config.php';
    return is_file($above_root) ? $above_root : '';
}

/**
 * @return array{ok:bool,detail:string}
 */
function staark_hub_security_config_permissions(): array
{
    $path = staark_hub_security_config_path();
    if ($path === '') {
        return ['ok' => false, 'detail' => 'wp-config.php could not be located for a permission check.'];
    }

    $perms = @fileperms($path);
    if ($perms === false) {
        return ['ok' => false, 'detail' => 'WordPress could not read wp-config.php permissions.'];
    }

    $mode = $perms & 0777;
    $world_writable = (bool) ($mode & 0002);
    $group_writable = (bool) ($mode & 0020);
    $display = substr(sprintf('%o', $mode), -3);

    return [
        'ok' => ! $world_writable && ! $group_writable,
        'detail' => 'wp-config.php mode is ' . $display . '.',
    ];
}

/**
 * Verify WordPress core files against official checksums when the checksum API
 * is available. The result is informational when remote verification cannot be
 * completed, so temporary network/API failures do not lower the site score.
 *
 * @return array{available:bool,ok:bool,checked:int,mismatches:string[],missing:string[],detail:string}
 */
function staark_hub_security_core_integrity(): array
{
    if (! function_exists('get_core_checksums')) {
        require_once ABSPATH . 'wp-admin/includes/update.php';
    }

    if (! function_exists('get_core_checksums')) {
        return [
            'available' => false,
            'ok' => true,
            'checked' => 0,
            'mismatches' => [],
            'missing' => [],
            'detail' => 'WordPress core checksum support is unavailable in this runtime.',
        ];
    }

    $version = (string) get_bloginfo('version');
    $locale = function_exists('get_locale') ? (string) get_locale() : 'en_US';
    $checksums = get_core_checksums($version, $locale);

    if (! is_array($checksums) || $checksums === []) {
        return [
            'available' => false,
            'ok' => true,
            'checked' => 0,
            'mismatches' => [],
            'missing' => [],
            'detail' => 'Official WordPress core checksums could not be retrieved. No score penalty was applied.',
        ];
    }

    $mismatches = [];
    $missing = [];
    $checked = 0;

    foreach ($checksums as $relative => $expected) {
        $relative = ltrim((string) $relative, '/\\');
        if ($relative === '' || str_starts_with($relative, 'wp-content/')) {
            continue;
        }

        $path = ABSPATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        ++$checked;

        if (! is_file($path)) {
            $missing[] = $relative;
            continue;
        }

        $actual = @md5_file($path);
        if (! is_string($actual) || ! hash_equals(strtolower((string) $expected), strtolower($actual))) {
            $mismatches[] = $relative;
        }
    }

    $problem_count = count($mismatches) + count($missing);
    $examples = array_slice(array_merge($mismatches, $missing), 0, 3);
    $detail = $problem_count === 0
        ? sprintf('%d WordPress core file(s) matched the official checksum set.', $checked)
        : sprintf(
            '%d core file issue(s) detected across %d checked file(s)%s.',
            $problem_count,
            $checked,
            $examples !== [] ? ': ' . implode(', ', $examples) : ''
        );

    return [
        'available' => true,
        'ok' => $problem_count === 0,
        'checked' => $checked,
        'mismatches' => $mismatches,
        'missing' => $missing,
        'detail' => $detail,
    ];
}

/**
 * Scan the uploads tree for PHP-like executable files. Uploads normally contain
 * media/documents; executable PHP in that tree deserves a manual review. The
 * scan is bounded to avoid turning a security check into an expensive crawl.
 *
 * @return array{available:bool,ok:bool,scanned:int,truncated:bool,files:string[],detail:string}
 */
function staark_hub_security_uploads_executables(int $limit = 10000): array
{
    $uploads = wp_get_upload_dir();
    $base = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';

    if ($base === '' || ! is_dir($base) || ! is_readable($base)) {
        return [
            'available' => false,
            'ok' => true,
            'scanned' => 0,
            'truncated' => false,
            'files' => [],
            'detail' => 'The uploads directory is not available for a local executable-file scan.',
        ];
    }

    $extensions = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar'];
    $found = [];
    $scanned = 0;
    $truncated = false;

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            ++$scanned;
            if ($scanned > $limit) {
                $truncated = true;
                break;
            }

            $extension = strtolower((string) pathinfo($file->getFilename(), PATHINFO_EXTENSION));
            if (! in_array($extension, $extensions, true)) {
                continue;
            }

            $path = wp_normalize_path($file->getPathname());
            $normalized_base = trailingslashit(wp_normalize_path($base));
            $found[] = str_starts_with($path, $normalized_base)
                ? substr($path, strlen($normalized_base))
                : $file->getFilename();

            if (count($found) >= 25) {
                break;
            }
        }
    } catch (UnexpectedValueException $error) {
        return [
            'available' => false,
            'ok' => true,
            'scanned' => $scanned,
            'truncated' => $truncated,
            'files' => [],
            'detail' => 'The uploads tree could not be traversed completely. No score penalty was applied.',
        ];
    }

    $detail = $found === []
        ? sprintf(
            'No PHP-like executable files were found in %d scanned upload file(s)%s.',
            min($scanned, $limit),
            $truncated ? ' before the safety limit was reached' : ''
        )
        : sprintf(
            '%d PHP-like executable file(s) found in uploads%s: %s',
            count($found),
            $truncated ? ' before the safety limit was reached' : '',
            implode(', ', array_slice($found, 0, 3))
        );

    return [
        'available' => true,
        'ok' => $found === [],
        'scanned' => min($scanned, $limit),
        'truncated' => $truncated,
        'files' => $found,
        'detail' => $detail,
    ];
}

/**
 * Run the local configuration scanner.
 *
 * This is intentionally a configuration/hygiene scanner, not malware
 * detection. The score is useful for maintenance prioritization but is not a
 * guarantee that a site is secure.
 *
 * @return array{score:int,earned:int,possible:int,passed:int,warnings:int,checked_at:string,checks:array<int,array<string,mixed>>}
 */
function staark_hub_security_scan(): array
{
    $settings = staark_hub_security_settings();
    $environment = staark_hub_environment_type();
    $updates = function_exists('staark_hub_pending_updates')
        ? staark_hub_pending_updates()
        : ['total' => 0, 'core' => 0, 'plugins' => 0, 'themes' => 0];

    $using_https = function_exists('wp_is_using_https')
        ? wp_is_using_https()
        : strtolower((string) wp_parse_url(home_url('/'), PHP_URL_SCHEME)) === 'https';

    $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
    $production = $environment === 'production';
    $config_permissions = staark_hub_security_config_permissions();
    $admin_user = get_user_by('login', 'admin');

    if (! function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = function_exists('get_plugins') ? get_plugins() : [];
    $active_plugins = (array) get_option('active_plugins', []);
    $inactive_plugins = max(0, count($plugins) - count($active_plugins));
    $debug_log = WP_CONTENT_DIR . '/debug.log';
    $debug_log_exposed = is_file($debug_log) && @filesize($debug_log) > 0;
    $core_integrity = staark_hub_security_core_integrity();
    $uploads_scan = staark_hub_security_uploads_executables();

    $checks = [];
    $checks[] = staark_hub_security_check(
        'https',
        'HTTPS',
        $using_https,
        'critical',
        14,
        $using_https ? 'The public site uses HTTPS.' : 'The public site is not using HTTPS.',
        'Serve the production website over HTTPS and redirect HTTP requests.'
    );
    $checks[] = staark_hub_security_check(
        'updates',
        'Pending updates',
        (int) $updates['total'] === 0,
        'high',
        14,
        (int) $updates['total'] === 0
            ? 'WordPress core, plugins and themes report no pending updates.'
            : sprintf(
                '%d update(s) pending: core %d, plugins %d, themes %d.',
                (int) $updates['total'],
                (int) $updates['core'],
                (int) $updates['plugins'],
                (int) $updates['themes']
            ),
        'Review updates, test them, and deploy security fixes promptly.'
    );
    $checks[] = staark_hub_security_check(
        'file_editor',
        'Dashboard file editor',
        defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
        'high',
        10,
        defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT
            ? 'Theme and plugin file editing is disabled in wp-admin.'
            : 'The wp-admin theme/plugin file editor is available.',
        "Add define('DISALLOW_FILE_EDIT', true); to wp-config.php after testing. Staark does not edit wp-config.php automatically."
    );
    $checks[] = staark_hub_security_check(
        'salts',
        'Authentication salts',
        staark_hub_security_has_strong_salts(),
        'high',
        10,
        staark_hub_security_has_strong_salts()
            ? 'All WordPress authentication keys and salts are present.'
            : 'One or more WordPress authentication keys/salts are missing, short or use placeholder text.',
        'Generate unique WordPress authentication keys and salts.'
    );
    $checks[] = staark_hub_security_check(
        'core_integrity',
        'WordPress core integrity',
        ! $core_integrity['available'] || $core_integrity['ok'],
        $core_integrity['available'] ? 'critical' : 'info',
        $core_integrity['available'] ? 8 : 0,
        $core_integrity['detail'],
        $core_integrity['available'] && ! $core_integrity['ok']
            ? 'Review the changed or missing core files, compare them with a clean WordPress package, and reinstall core after taking a backup if appropriate.'
            : ''
    );
    $checks[] = staark_hub_security_check(
        'uploads_executables',
        'Executable files in uploads',
        ! $uploads_scan['available'] || $uploads_scan['ok'],
        $uploads_scan['available'] ? 'high' : 'info',
        $uploads_scan['available'] ? 6 : 0,
        $uploads_scan['detail'],
        $uploads_scan['available'] && ! $uploads_scan['ok']
            ? 'Review every executable file in wp-content/uploads. Do not delete blindly: confirm whether a plugin intentionally created it before removal.'
            : ''
    );

    $checks[] = staark_hub_security_check(
        'config_permissions',
        'wp-config.php permissions',
        $config_permissions['ok'],
        'high',
        8,
        $config_permissions['detail'],
        'Avoid group/world writable permissions on wp-config.php.'
    );

    if ($production) {
        $checks[] = staark_hub_security_check(
            'debug',
            'Production debugging',
            ! $debug_enabled,
            'medium',
            6,
            $debug_enabled ? 'WP_DEBUG is enabled on a production environment.' : 'WP_DEBUG is disabled for production.',
            'Disable WP_DEBUG on production after troubleshooting.'
        );
    } else {
        $checks[] = staark_hub_security_check(
            'debug',
            'Development debugging',
            true,
            'info',
            0,
            'This is a ' . $environment . ' environment, so WP_DEBUG does not reduce the security score.',
            ''
        );
    }

    $checks[] = staark_hub_security_check(
        'security_headers',
        'Baseline security headers',
        ! empty($settings['security_headers']),
        'medium',
        5,
        ! empty($settings['security_headers'])
            ? 'Staark sends X-Content-Type-Options and a strict-origin referrer policy.'
            : 'Staark baseline security headers are disabled.',
        'Enable the baseline headers unless your reverse proxy already manages an equivalent policy.'
    );
    $checks[] = staark_hub_security_check(
        'rest_users',
        'Public REST user listing',
        ! empty($settings['block_rest_users']),
        'medium',
        4,
        ! empty($settings['block_rest_users'])
            ? 'Unauthenticated REST user endpoints are removed by Staark.'
            : 'Staark is not blocking unauthenticated REST user endpoints.',
        'Enable this hardening if the site does not intentionally expose public author data through the REST API.'
    );

    $external_login_protection = (bool) apply_filters('staark_hub_security_external_login_protection', false);
    $login_protection_active = ! empty($settings['login_protection']) || $external_login_protection;
    $checks[] = staark_hub_security_check(
        'login_protection',
        'Login brute-force protection',
        $login_protection_active,
        'high',
        8,
        ! empty($settings['login_protection'])
            ? 'Staark throttles repeated failed authentication attempts per login and client address.'
            : ($external_login_protection
                ? 'An external login-protection layer was declared through the Staark security filter.'
                : 'No Staark login throttling or declared external login protection is active.'),
        'Enable Staark login protection unless a trusted reverse proxy, WAF or hosting layer already enforces equivalent throttling.'
    );

    $xmlrpc_enabled = (bool) apply_filters('xmlrpc_enabled', true);
    $checks[] = staark_hub_security_check(
        'xmlrpc',
        'XML-RPC',
        ! $xmlrpc_enabled,
        'low',
        3,
        $xmlrpc_enabled ? 'XML-RPC is enabled.' : 'XML-RPC is disabled.',
        'Disable XML-RPC if the site does not depend on Jetpack, remote publishing or another XML-RPC integration.'
    );
    $checks[] = staark_hub_security_check(
        'admin_username',
        'Default admin username',
        ! $admin_user,
        'medium',
        2,
        $admin_user ? 'A user with the login "admin" exists.' : 'No user is using the default "admin" login.',
        'Use a non-default administrator login and remove the old account after assigning its content.'
    );
    $checks[] = staark_hub_security_check(
        'inactive_plugins',
        'Inactive plugins',
        $inactive_plugins === 0,
        'low',
        1,
        $inactive_plugins === 0
            ? 'No inactive plugins are installed.'
            : sprintf('%d inactive plugin(s) remain installed.', $inactive_plugins),
        'Remove unused plugins after confirming they are no longer needed.'
    );
    $checks[] = staark_hub_security_check(
        'debug_log',
        'Debug log exposure',
        ! ($production && $debug_log_exposed),
        'medium',
        1,
        $production && $debug_log_exposed
            ? 'wp-content/debug.log exists and contains data on production.'
            : 'No populated production debug.log was detected.',
        'Remove stale debug logs from production and ensure they are not publicly readable.'
    );

    $possible = 0;
    $earned = 0;
    $passed = 0;
    $warnings = 0;

    foreach ($checks as $check) {
        $possible += (int) $check['points'];
        if ($check['ok']) {
            $earned += (int) $check['points'];
            ++$passed;
        } else {
            ++$warnings;
        }
    }

    $score = $possible > 0 ? (int) round(($earned / $possible) * 100) : 100;

    return [
        'score' => max(0, min(100, $score)),
        'earned' => $earned,
        'possible' => $possible,
        'passed' => $passed,
        'warnings' => $warnings,
        'checked_at' => current_time('mysql'),
        'checks' => $checks,
    ];
}

/**
 * @param array<string,mixed>|null $report
 */
function staark_hub_security_store_report(?array $report = null): array
{
    $report = $report ?? staark_hub_security_scan();
    update_option('staark_hub_security_last_scan', $report, false);
    return $report;
}

/**
 * @return array<string,mixed>
 */
function staark_hub_security_last_report(): array
{
    $report = get_option('staark_hub_security_last_scan', []);
    return is_array($report) ? $report : [];
}

/**
 * Compact, non-secret status used by the connector payload.
 *
 * @return array{enabled:bool,score:int,warnings:int,lastScan:string}
 */
function staark_hub_security_summary(): array
{
    if (! staark_hub_module_enabled('security', true)) {
        return [
            'enabled' => false,
            'score' => 0,
            'warnings' => 0,
            'lastScan' => '',
        ];
    }

    $report = staark_hub_security_last_report();
    if ($report === []) {
        $report = staark_hub_security_store_report();
    }

    return [
        'enabled' => true,
        'score' => isset($report['score']) ? (int) $report['score'] : 0,
        'warnings' => isset($report['warnings']) ? (int) $report['warnings'] : 0,
        'lastScan' => isset($report['checked_at']) ? sanitize_text_field((string) $report['checked_at']) : '',
    ];
}

function staark_hub_security_send_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * @param array<string,callable> $methods
 * @return array<string,callable>
 */
function staark_hub_security_xmlrpc_methods(array $methods): array
{
    unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
    return $methods;
}

/**
 * @param array<string,mixed> $endpoints
 * @return array<string,mixed>
 */
function staark_hub_security_rest_endpoints(array $endpoints): array
{
    // Logged-in authors and editors need /users/me and ?who=authors in the
    // block editor; core already limits what they can see there.
    if (is_user_logged_in() && (current_user_can('list_users') || current_user_can('edit_posts'))) {
        return $endpoints;
    }

    foreach (array_keys($endpoints) as $route) {
        if ($route === '/wp/v2/users' || str_starts_with((string) $route, '/wp/v2/users/')) {
            unset($endpoints[$route]);
        }
    }

    return $endpoints;
}

function staark_hub_security_login_error(): string
{
    return __('Login failed. Check your credentials and try again.', 'staark-core');
}

function staark_hub_security_register_hardening(): void
{
    if (! staark_hub_module_enabled('security', true)) {
        return;
    }

    $settings = staark_hub_security_settings();

    if ($settings['security_headers']) {
        add_action('send_headers', 'staark_hub_security_send_headers');
    }

    if ($settings['disable_xmlrpc']) {
        add_filter('xmlrpc_enabled', '__return_false', 99);
        add_filter('xmlrpc_methods', 'staark_hub_security_xmlrpc_methods', 99);
    }

    if ($settings['block_rest_users']) {
        add_filter('rest_endpoints', 'staark_hub_security_rest_endpoints', 99);
    }

    if ($settings['generic_login_errors']) {
        add_filter('login_errors', 'staark_hub_security_login_error', 99);
    }
}

staark_hub_security_register_hardening();

function staark_hub_security_schedule_scan(): void
{
    $scheduled = wp_next_scheduled(STAARK_HUB_SECURITY_SCAN_HOOK);

    if (! staark_hub_module_enabled('security', true)) {
        if ($scheduled) {
            wp_unschedule_event($scheduled, STAARK_HUB_SECURITY_SCAN_HOOK);
        }
        return;
    }

    if (! $scheduled) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', STAARK_HUB_SECURITY_SCAN_HOOK);
    }
}

add_action('init', 'staark_hub_security_schedule_scan');
add_action(STAARK_HUB_SECURITY_SCAN_HOOK, static function (): void {
    staark_hub_security_store_report();
});

function staark_hub_security_deactivate(): void
{
    $timestamp = wp_next_scheduled(STAARK_HUB_SECURITY_SCAN_HOOK);
    while ($timestamp) {
        wp_unschedule_event($timestamp, STAARK_HUB_SECURITY_SCAN_HOOK);
        $timestamp = wp_next_scheduled(STAARK_HUB_SECURITY_SCAN_HOOK);
    }
}

// Scheduled events are cleared by staark_hub_deactivate() (includes/lifecycle.php).

add_action('admin_post_staark_security_scan', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_security_scan');
    staark_hub_security_store_report();

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-security&staark_security=scanned'));
    exit;
});

add_action('admin_post_staark_security_save', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_security_save');

    staark_hub_set_module_enabled('security', staark_hub_checkbox_value('module_enabled'));
    staark_hub_security_save_settings(
        [
            'security_headers' => staark_hub_checkbox_value('security_headers'),
            'block_rest_users' => staark_hub_checkbox_value('block_rest_users'),
            'disable_xmlrpc' => staark_hub_checkbox_value('disable_xmlrpc'),
            'generic_login_errors' => staark_hub_checkbox_value('generic_login_errors'),
            'login_protection' => staark_hub_checkbox_value('login_protection'),
        ]
    );

    staark_hub_security_store_report();

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-security&staark_security=saved'));
    exit;
});

require_once STAARK_HUB_PLUGIN_DIR . 'includes/security-login.php';
