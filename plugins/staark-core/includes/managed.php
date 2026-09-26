<?php
/**
 * Staark Managed Mode authority and deployment runtime.
 *
 * 6.0A established deployment policy and operator authority. 6.0B added
 * WordPress-level mutation protection. 6.0C adds a guarded MU-loader for
 * Locked mode while keeping server/WP-CLI recovery explicit.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_MANAGED_MODE_OPTION = 'staark_hub_managed_mode';
const STAARK_HUB_OPERATOR_META = '_staark_hub_operator';
const STAARK_HUB_MU_LOADER_FILENAME = 'staark-loader.php';

/**
 * @return string[]
 */
function staark_hub_managed_modes(): array
{
    return ['normal', 'managed', 'locked'];
}

function staark_hub_managed_mode(): string
{
    $value = defined('STAARK_HUB_MANAGED_MODE')
        ? (string) STAARK_HUB_MANAGED_MODE
        : (string) get_option(STAARK_HUB_MANAGED_MODE_OPTION, 'normal');

    $value = sanitize_key($value);

    return in_array($value, staark_hub_managed_modes(), true) ? $value : 'normal';
}

function staark_hub_managed_mode_source(): string
{
    return defined('STAARK_HUB_MANAGED_MODE') ? 'constant' : 'database';
}

function staark_hub_managed_mode_forced(): bool
{
    return defined('STAARK_HUB_MANAGED_MODE');
}

function staark_hub_is_managed(): bool
{
    return in_array(staark_hub_managed_mode(), ['managed', 'locked'], true);
}

function staark_hub_is_locked(): bool
{
    return staark_hub_managed_mode() === 'locked';
}

function staark_hub_mu_loader_path(): string
{
    $directory = defined('WPMU_PLUGIN_DIR') ? (string) WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';

    return trailingslashit($directory) . STAARK_HUB_MU_LOADER_FILENAME;
}

function staark_hub_mu_loader_source(): string
{
    return STAARK_HUB_PLUGIN_DIR . 'deployment/' . STAARK_HUB_MU_LOADER_FILENAME;
}

function staark_hub_mu_loader_installed(): bool
{
    return is_file(staark_hub_mu_loader_path()) && is_readable(staark_hub_mu_loader_path());
}

/**
 * Decode the escape sequences used by Linux /proc/self/mountinfo paths.
 */
function staark_hub_mountinfo_decode_path(string $path): string
{
    return strtr(
        $path,
        [
            '\\040' => ' ',
            '\\011' => "\t",
            '\\012' => "\n",
            '\\134' => '\\',
        ]
    );
}

/**
 * Detect an exact file/directory mount point inside Linux containers.
 *
 * wp-env file mappings are bind mounts. A bind-mounted file can be readable and
 * writable but unlink() still fails with EBUSY, so writability is not a useful
 * signal. /proc/self/mountinfo exposes the exact mounted target safely without
 * mutating it.
 */
function staark_hub_path_is_mountpoint(string $path): bool
{
    $real = realpath($path);
    if ($real === false || ! is_readable('/proc/self/mountinfo')) {
        return false;
    }

    $target = untrailingslashit(wp_normalize_path($real));
    $lines = @file('/proc/self/mountinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (! is_array($lines)) {
        return false;
    }

    foreach ($lines as $line) {
        $sections = explode(' - ', (string) $line, 2);
        if ($sections === []) {
            continue;
        }

        $fields = preg_split('/\s+/', trim($sections[0]));
        if (! is_array($fields) || count($fields) < 5) {
            continue;
        }

        $mounted = staark_hub_mountinfo_decode_path((string) $fields[4]);
        $mounted = untrailingslashit(wp_normalize_path($mounted));
        if ($mounted !== '' && $mounted === $target) {
            return true;
        }
    }

    return false;
}

/**
 * wp-env can mount the bundled loader directly at the MU target. Depending on
 * the Docker/storage driver, source and target may report different device/inode
 * pairs, so use the Linux mount table first and retain inode equality as a
 * portable fast-path.
 */
function staark_hub_mu_loader_is_deployment_mapped(): bool
{
    if (staark_hub_path_is_mountpoint(staark_hub_mu_loader_path())) {
        return true;
    }

    $source_stat = @stat(staark_hub_mu_loader_source());
    $target_stat = @stat(staark_hub_mu_loader_path());

    if (! is_array($source_stat) || ! is_array($target_stat)) {
        return false;
    }

    return isset($source_stat['dev'], $source_stat['ino'], $target_stat['dev'], $target_stat['ino'])
        && (string) $source_stat['dev'] === (string) $target_stat['dev']
        && (string) $source_stat['ino'] === (string) $target_stat['ino'];
}

/**
 * Treat an already deployed, byte-identical loader as installed. This makes
 * `loader install` idempotent for wp-env mappings and normal production copies.
 */
function staark_hub_mu_loader_matches_source(): bool
{
    $source = staark_hub_mu_loader_source();
    $target = staark_hub_mu_loader_path();

    if (! is_readable($source) || ! is_readable($target)) {
        return false;
    }

    $source_hash = @hash_file('sha256', $source);
    $target_hash = @hash_file('sha256', $target);

    return is_string($source_hash)
        && $source_hash !== ''
        && is_string($target_hash)
        && hash_equals($source_hash, $target_hash);
}

function staark_hub_bootstrapped_by_mu(): bool
{
    return defined('STAARK_HUB_BOOTSTRAPPED_BY_MU') && STAARK_HUB_BOOTSTRAPPED_BY_MU === true;
}

function staark_hub_runtime_loader(): string
{
    if (staark_hub_bootstrapped_by_mu()) {
        return 'mu';
    }

    return staark_hub_mu_loader_installed() ? 'mu-ready' : 'plugin';
}

/**
 * Whether WordPress still has Staark Core in its regular active plugin list.
 * Locked mode may run without this flag because the MU-loader bootstraps core.
 */
function staark_hub_standard_plugin_active(): bool
{
    $plugin = staark_hub_standard_plugin_basename();
    $active = get_option('active_plugins', []);
    $active = is_array($active) ? $active : [];

    if (in_array($plugin, $active, true)) {
        return true;
    }

    if (is_multisite()) {
        $network = get_site_option('active_sitewide_plugins', []);
        if (is_array($network) && array_key_exists($plugin, $network)) {
            return true;
        }
    }

    return false;
}

/**
 * Restore the regular plugin activation flag without loading the plugin file.
 *
 * In Locked mode the managed runtime is already compiled for the current
 * request. Calling WordPress's normal `activate_plugin()` path would sandbox-
 * include staark-core.php a second time and can redeclare functions. Updating
 * the activation option is safe: the regular package is picked up on the next
 * request, after Locked mode has been left.
 *
 * @return true|WP_Error
 */
function staark_hub_restore_standard_plugin_activation()
{
    $plugin = staark_hub_standard_plugin_basename();
    $plugin_file = function_exists('staark_hub_standard_plugin_file')
        ? staark_hub_standard_plugin_file()
        : trailingslashit(WP_PLUGIN_DIR) . $plugin;

    if (! is_file($plugin_file) || ! is_readable($plugin_file)) {
        return new WP_Error(
            'staark_plugin_package_missing',
            'The regular Staark Core plugin package is missing or unreadable; Locked mode cannot hand control back safely.'
        );
    }

    $active = get_option('active_plugins', []);
    $active = is_array($active) ? array_values($active) : [];

    if (! in_array($plugin, $active, true)) {
        $active[] = $plugin;
        $active = array_values(array_unique(array_map('strval', $active)));
        update_option('active_plugins', $active);
    }

    if (! in_array($plugin, (array) get_option('active_plugins', []), true)) {
        return new WP_Error(
            'staark_plugin_activation_flag',
            'WordPress could not restore the Staark Core activation flag.'
        );
    }

    return true;
}

/** @return true|WP_Error */
function staark_hub_install_mu_loader()
{
    $source = staark_hub_mu_loader_source();
    $target = staark_hub_mu_loader_path();

    if (! is_readable($source)) {
        return new WP_Error('staark_loader_source_missing', 'The bundled Staark MU-loader source is missing or unreadable.');
    }

    $source_real = realpath($source);
    $target_real = realpath($target);
    if (
        ($source_real !== false && $target_real !== false && $source_real === $target_real)
        || staark_hub_mu_loader_is_deployment_mapped()
        || staark_hub_mu_loader_matches_source()
    ) {
        return true;
    }

    $directory = dirname($target);
    if (! is_dir($directory) && ! wp_mkdir_p($directory)) {
        return new WP_Error('staark_loader_directory', 'WordPress could not create the mu-plugins directory.');
    }

    if (! @copy($source, $target)) {
        return new WP_Error('staark_loader_copy', 'WordPress could not install the Staark MU-loader. Use server deployment or copy it manually.');
    }

    @chmod($target, 0644);
    clearstatcache(true, $target);

    return staark_hub_mu_loader_installed()
        ? true
        : new WP_Error('staark_loader_verify', 'The MU-loader copy completed but could not be verified.');
}

/** @return true|WP_Error */
function staark_hub_remove_mu_loader()
{
    if (staark_hub_is_locked()) {
        return new WP_Error('staark_loader_locked', 'Return Managed Mode to Managed or Normal before removing the MU-loader.');
    }

    $target = staark_hub_mu_loader_path();
    if (! file_exists($target) && ! is_link($target)) {
        return true;
    }

    if (staark_hub_mu_loader_is_deployment_mapped()) {
        return new WP_Error(
            'staark_loader_deployment_mapped',
            'The Staark MU-loader is deployment-mounted (for example by wp-env). Remove the deployment mapping outside WordPress instead of unlinking the mounted file.'
        );
    }

    if (! @unlink($target)) {
        return new WP_Error('staark_loader_remove', 'The Staark MU-loader could not be removed. Use server recovery.');
    }

    clearstatcache(true, $target);
    return true;
}

function staark_hub_managed_mode_label(?string $mode = null): string
{
    $mode = $mode ?? staark_hub_managed_mode();

    return match ($mode) {
        'managed' => 'Managed',
        'locked' => 'Locked',
        default => 'Normal',
    };
}

/**
 * @return int[]
 */
function staark_hub_operator_ids(): array
{
    $ids = get_users(
        [
            'meta_key' => STAARK_HUB_OPERATOR_META,
            'meta_value' => '1',
            'fields' => 'ID',
        ]
    );

    return array_values(array_unique(array_map('intval', is_array($ids) ? $ids : [])));
}

/**
 * Operators that can still act: flagged users who remain administrators.
 * A demoted operator (e.g. changed to Editor) no longer counts, so the site
 * is never left in Managed mode with a phantom operator.
 */
function staark_hub_operator_count(): int
{
    return count(array_filter(staark_hub_operator_ids(), 'staark_hub_user_has_manage_options'));
}

/**
 * Check the administrator capability without calling user_can()/current_user_can().
 *
 * Managed protection hooks into map_meta_cap. Calling user_can() while that
 * filter is executing re-enters map_meta_cap and can recurse until PHP exhausts
 * memory. WP_User::allcaps is already resolved from roles/capabilities and is
 * safe to inspect directly here.
 */
function staark_hub_user_has_manage_options(int $user_id): bool
{
    if ($user_id <= 0) {
        return false;
    }

    $user = get_userdata($user_id);
    if (! $user instanceof WP_User) {
        return false;
    }

    return ! empty($user->allcaps['manage_options']);
}

function staark_hub_user_is_operator(int $user_id = 0): bool
{
    if ($user_id <= 0) {
        $user_id = get_current_user_id();
    }

    if (! staark_hub_user_has_manage_options($user_id)) {
        return false;
    }

    return (string) get_user_meta($user_id, STAARK_HUB_OPERATOR_META, true) === '1';
}

function staark_hub_current_user_is_operator(): bool
{
    return staark_hub_user_is_operator(get_current_user_id());
}

/**
 * @return true|WP_Error
 */
function staark_hub_set_operator(int $user_id, bool $enabled)
{
    $user = get_userdata($user_id);
    if (! $user instanceof WP_User) {
        return new WP_Error('staark_operator_missing', 'The requested WordPress user does not exist.');
    }

    if ($enabled && ! staark_hub_user_has_manage_options($user_id)) {
        return new WP_Error('staark_operator_capability', 'A Staark operator must be a WordPress administrator.');
    }

    if (! $enabled && staark_hub_user_is_operator($user_id)) {
        $operators = staark_hub_operator_ids();
        if (staark_hub_managed_mode() !== 'normal' && count($operators) <= 1) {
            return new WP_Error('staark_operator_last', 'Return Managed Mode to Normal before removing the last Staark operator.');
        }
    }

    if ($enabled) {
        update_user_meta($user_id, STAARK_HUB_OPERATOR_META, '1');
    } else {
        delete_user_meta($user_id, STAARK_HUB_OPERATOR_META);
    }

    return true;
}

/**
 * @return true|WP_Error
 */
function staark_hub_set_managed_mode(string $mode)
{
    $mode = sanitize_key($mode);

    if (! in_array($mode, staark_hub_managed_modes(), true)) {
        return new WP_Error('staark_mode_invalid', 'Unknown Staark Managed Mode.');
    }

    if (staark_hub_managed_mode_forced()) {
        return new WP_Error('staark_mode_forced', 'Managed Mode is forced by STAARK_HUB_MANAGED_MODE in server configuration.');
    }

    if ($mode !== 'normal' && staark_hub_operator_count() < 1) {
        return new WP_Error('staark_mode_no_operator', 'Create at least one Staark operator before enabling Managed or Locked mode.');
    }

    if ($mode === 'locked' && ! staark_hub_mu_loader_installed()) {
        return new WP_Error('staark_mode_loader_missing', 'Install the Staark MU-loader before enabling Locked mode.');
    }

    $current_mode = staark_hub_managed_mode();
    if ($current_mode === 'locked' && $mode !== 'locked' && ! staark_hub_standard_plugin_active()) {
        $restored = staark_hub_restore_standard_plugin_activation();
        if (is_wp_error($restored)) {
            return $restored;
        }
    }

    update_option(STAARK_HUB_MANAGED_MODE_OPTION, $mode, false);

    // A normal WordPress activation hook cannot be run through activate_plugin()
    // while the managed runtime is already loaded. Re-establish Staark-owned
    // schedules/state directly after the safe handoff instead.
    if ($current_mode === 'locked' && $mode !== 'locked' && function_exists('staark_hub_activate')) {
        staark_hub_activate();
    }

    return true;
}

/**
 * Safe connector/lifecycle summary. No user identifiers are exposed.
 *
 * @return array{mode:string,label:string,source:string,forced:bool,operatorCount:int,loader:string,loaderInstalled:bool,bootstrappedByMu:bool,protection:?array<string,mixed>}
 */
function staark_hub_managed_summary(): array
{
    $mode = staark_hub_managed_mode();

    return [
        'mode' => $mode,
        'label' => staark_hub_managed_mode_label($mode),
        'source' => staark_hub_managed_mode_source(),
        'forced' => staark_hub_managed_mode_forced(),
        'operatorCount' => staark_hub_operator_count(),
        'loader' => staark_hub_runtime_loader(),
        'loaderInstalled' => staark_hub_mu_loader_installed(),
        'bootstrappedByMu' => staark_hub_bootstrapped_by_mu(),
        'protection' => function_exists('staark_hub_managed_protection_summary') ? staark_hub_managed_protection_summary() : null,
    ];
}

add_action('admin_post_staark_managed_bootstrap_operator', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_managed_bootstrap_operator');

    // Self-service bootstrap is only for sites in Normal mode. A managed site
    // without a usable operator is recovered by Staark over WP-CLI
    // (`wp staark managed grant <user>`), not by whoever clicks first.
    if (staark_hub_managed_mode() !== 'normal' && ! staark_hub_current_user_is_operator()) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-managed&staark_managed=operator_exists'));
        exit;
    }

    if (staark_hub_operator_count() > 0 && ! staark_hub_current_user_is_operator()) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-managed&staark_managed=operator_exists'));
        exit;
    }

    $result = staark_hub_set_operator(get_current_user_id(), true);
    $status = is_wp_error($result) ? 'operator_error' : 'operator_ready';

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-managed&staark_managed=' . $status));
    exit;
});

add_action('admin_post_staark_managed_save_mode', static function (): void {
    if (! current_user_can('manage_options') || ! staark_hub_current_user_is_operator()) {
        wp_die(esc_html__('Only a Staark operator can change Managed Mode.', 'staark-core'));
    }

    check_admin_referer('staark_managed_save_mode');

    $mode = isset($_POST['managed_mode']) ? sanitize_key(wp_unslash($_POST['managed_mode'])) : 'normal';
    $result = staark_hub_set_managed_mode($mode);
    $status = is_wp_error($result) ? 'mode_error' : 'mode_saved';

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-managed&staark_managed=' . $status));
    exit;
});

/**
 * Resolve a CLI user argument by ID, login or email.
 */
function staark_hub_managed_cli_user(string $value): ?WP_User
{
    if (ctype_digit($value)) {
        $user = get_userdata((int) $value);
        return $user instanceof WP_User ? $user : null;
    }

    $user = get_user_by('login', $value);
    if (! $user instanceof WP_User && is_email($value)) {
        $user = get_user_by('email', $value);
    }

    return $user instanceof WP_User ? $user : null;
}

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    class Staark_Hub_Managed_CLI_Command
    {
        public function status(array $args, array $assoc_args): void
        {
            unset($args, $assoc_args);
            $summary = staark_hub_managed_summary();
            \WP_CLI\Utils\format_items(
                'table',
                [
                    ['key' => 'Mode', 'value' => $summary['label']],
                    ['key' => 'Source', 'value' => $summary['source']],
                    ['key' => 'Forced by config', 'value' => $summary['forced'] ? 'yes' : 'no'],
                    ['key' => 'Staark operators', 'value' => (string) $summary['operatorCount']],
                    ['key' => 'Loader', 'value' => $summary['loader']],
                    ['key' => 'MU loader installed', 'value' => $summary['loaderInstalled'] ? 'yes' : 'no'],
                    ['key' => 'Bootstrapped by MU', 'value' => $summary['bootstrappedByMu'] ? 'yes' : 'no'],
                ],
                ['key', 'value']
            );
        }

        public function mode(array $args, array $assoc_args): void
        {
            unset($assoc_args);
            $mode = isset($args[0]) ? sanitize_key((string) $args[0]) : '';
            if ($mode === '') {
                \WP_CLI::error('Usage: wp staark managed mode <normal|managed|locked>');
            }

            $result = staark_hub_set_managed_mode($mode);
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }

            \WP_CLI::success('Staark Managed Mode is now ' . staark_hub_managed_mode_label($mode) . '.');
        }

        public function grant(array $args, array $assoc_args): void
        {
            unset($assoc_args);
            $value = isset($args[0]) ? trim((string) $args[0]) : '';
            $user = $value !== '' ? staark_hub_managed_cli_user($value) : null;
            if (! $user instanceof WP_User) {
                \WP_CLI::error('User not found. Pass a WordPress user ID, login or email.');
            }

            $result = staark_hub_set_operator((int) $user->ID, true);
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }

            \WP_CLI::success('Staark operator granted to user #' . (int) $user->ID . '.');
        }

        public function revoke(array $args, array $assoc_args): void
        {
            unset($assoc_args);
            $value = isset($args[0]) ? trim((string) $args[0]) : '';
            $user = $value !== '' ? staark_hub_managed_cli_user($value) : null;
            if (! $user instanceof WP_User) {
                \WP_CLI::error('User not found. Pass a WordPress user ID, login or email.');
            }

            $result = staark_hub_set_operator((int) $user->ID, false);
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }

            \WP_CLI::success('Staark operator revoked from user #' . (int) $user->ID . '.');
        }

        public function inspect(array $args, array $assoc_args): void
        {
            unset($assoc_args);
            $value = isset($args[0]) ? trim((string) $args[0]) : '';
            $user = $value !== '' ? staark_hub_managed_cli_user($value) : null;
            if (! $user instanceof WP_User) {
                \WP_CLI::error('User not found. Pass a WordPress user ID, login or email.');
            }

            $restricted = function_exists('staark_hub_managed_user_is_restricted')
                ? staark_hub_managed_user_is_restricted((int) $user->ID)
                : false;

            \WP_CLI\Utils\format_items(
                'table',
                [
                    ['key' => 'Mode', 'value' => staark_hub_managed_mode_label()],
                    ['key' => 'User', 'value' => '#' . (int) $user->ID . ' ' . $user->user_login],
                    ['key' => 'Staark operator', 'value' => staark_hub_user_is_operator((int) $user->ID) ? 'yes' : 'no'],
                    ['key' => 'Client restrictions', 'value' => $restricted ? 'active' : 'bypassed'],
                    ['key' => 'Plugin row', 'value' => $restricted ? 'hidden' : 'visible'],
                    ['key' => 'Mutation controls', 'value' => $restricted ? 'blocked' : 'available'],
                ],
                ['key', 'value']
            );
        }

        public function loader(array $args, array $assoc_args): void
        {
            unset($assoc_args);
            $action = isset($args[0]) ? sanitize_key((string) $args[0]) : 'status';

            if ($action === 'install') {
                $result = staark_hub_install_mu_loader();
                if (is_wp_error($result)) {
                    \WP_CLI::error($result->get_error_message());
                }
                \WP_CLI::success('Staark MU-loader is installed at ' . staark_hub_mu_loader_path() . '.');
                return;
            }

            if ($action === 'remove') {
                $result = staark_hub_remove_mu_loader();
                if (is_wp_error($result)) {
                    if ($result->get_error_code() === 'staark_loader_deployment_mapped') {
                        \WP_CLI::warning($result->get_error_message());
                        return;
                    }
                    \WP_CLI::error($result->get_error_message());
                }
                \WP_CLI::success('Staark MU-loader removed.');
                return;
            }

            if ($action !== 'status') {
                \WP_CLI::error('Usage: wp staark managed loader <status|install|remove>');
            }

            \WP_CLI\Utils\format_items(
                'table',
                [
                    ['key' => 'Mode', 'value' => staark_hub_managed_mode_label()],
                    ['key' => 'Bundled source', 'value' => staark_hub_mu_loader_source()],
                    ['key' => 'Installed target', 'value' => staark_hub_mu_loader_path()],
                    ['key' => 'Installed', 'value' => staark_hub_mu_loader_installed() ? 'yes' : 'no'],
                    ['key' => 'Deployment mapped', 'value' => staark_hub_mu_loader_is_deployment_mapped() ? 'yes' : 'no'],
                    ['key' => 'Runtime loader', 'value' => staark_hub_runtime_loader()],
                    ['key' => 'Standard plugin active', 'value' => staark_hub_standard_plugin_active() ? 'yes' : 'no'],
                ],
                ['key', 'value']
            );
        }

        public function operators(array $args, array $assoc_args): void
        {
            unset($args, $assoc_args);
            $rows = [];
            foreach (staark_hub_operator_ids() as $user_id) {
                $user = get_userdata($user_id);
                if (! $user instanceof WP_User) {
                    continue;
                }
                $rows[] = [
                    'id' => (int) $user->ID,
                    'login' => $user->user_login,
                    'name' => $user->display_name,
                ];
            }

            if ($rows === []) {
                \WP_CLI::line('No Staark operators configured.');
                return;
            }

            \WP_CLI\Utils\format_items('table', $rows, ['id', 'login', 'name']);
        }
    }

    WP_CLI::add_command('staark managed', 'Staark_Hub_Managed_CLI_Command');
}
