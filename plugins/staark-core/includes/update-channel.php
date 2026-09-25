<?php
/**
 * Staark Hub managed update channel.
 *
 * WP-6.1 adds a release manifest client, checksum/signature verification,
 * staged package validation, Staark Core managed deployment, Staark Theme
 * updates, next-request health verification and rollback support.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_UPDATE_STATE_OPTION = 'staark_hub_update_state';
const STAARK_HUB_UPDATE_CRON = 'staark_hub_update_check_twicedaily';
const STAARK_HUB_UPDATE_MANIFEST_TRANSIENT = 'staark_hub_update_manifest';
const STAARK_HUB_UPDATE_MANIFEST_PATH = '/api/hub/wordpress/releases';

function staark_hub_update_channel(): string
{
    $channel = defined('STAARK_HUB_UPDATE_CHANNEL')
        ? sanitize_key((string) STAARK_HUB_UPDATE_CHANNEL)
        : 'stable';

    return in_array($channel, ['stable', 'beta'], true) ? $channel : 'stable';
}

/** @return array<string,array{slug:string,label:string}> */
function staark_hub_update_theme_releases(): array
{
    return [
        'theme' => [
            'slug' => 'staark',
            'label' => 'S-Hub Light',
        ],
        'salong' => [
            'slug' => 'staark-salong',
            'label' => 'S-Hub Salong',
        ],
        'bygg' => [
            'slug' => 'staark-bygg',
            'label' => 'S-Hub Bygg',
        ],
    ];
}

function staark_hub_update_release_slug(string $type): string
{
    if ($type === 'core') {
        return 'staark-core';
    }

    $definitions = staark_hub_update_theme_releases();

    return isset($definitions[$type])
        ? (string) $definitions[$type]['slug']
        : 'staark';
}

function staark_hub_update_release_label(string $type): string
{
    if ($type === 'core') {
        return 'Staark Core';
    }

    $definitions = staark_hub_update_theme_releases();

    return isset($definitions[$type])
        ? (string) $definitions[$type]['label']
        : 'Staark Theme';
}

/** @return array<string,mixed> */
function staark_hub_update_state(): array
{
    $saved = get_option(STAARK_HUB_UPDATE_STATE_OPTION, []);
    $saved = is_array($saved) ? $saved : [];

    return array_merge(
        [
            'channel' => staark_hub_update_channel(),
            'last_checked' => '',
            'last_error' => '',
            'manifest_generated_at' => '',
            'core_latest' => '',
            'theme_latest' => '',
            'salong_latest' => '',
            'bygg_latest' => '',
            'last_action' => '',
            'last_action_at' => '',
            'pending_core' => [],
        ],
        $saved
    );
}

/** @param array<string,mixed> $state */
function staark_hub_update_save_state(array $state): void
{
    update_option(STAARK_HUB_UPDATE_STATE_OPTION, $state, false);
}

function staark_hub_update_manifest_request_path(): string
{
    return add_query_arg(
        [
            'channel' => staark_hub_update_channel(),
            'hub' => STAARK_HUB_VERSION,
        ],
        STAARK_HUB_UPDATE_MANIFEST_PATH
    );
}

function staark_hub_update_manifest_url(): string
{
    if (defined('STAARK_HUB_UPDATE_MANIFEST_URL') && trim((string) STAARK_HUB_UPDATE_MANIFEST_URL) !== '') {
        return esc_url_raw((string) STAARK_HUB_UPDATE_MANIFEST_URL);
    }

    $connection = function_exists('staark_hub_connection_ensure_identity')
        ? staark_hub_connection_ensure_identity()
        : ['hub_url' => 'https://staarkinc.com'];
    $base = untrailingslashit(esc_url_raw((string) ($connection['hub_url'] ?? 'https://staarkinc.com')));

    return $base . staark_hub_update_manifest_request_path();
}

function staark_hub_update_http_url_allowed(string $url): bool
{
    $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
    $host = (string) wp_parse_url($url, PHP_URL_HOST);
    if ($host === '') {
        return false;
    }

    if ($scheme === 'https') {
        return true;
    }

    return $scheme === 'http'
        && function_exists('staark_hub_runtime_environment')
        && in_array(staark_hub_runtime_environment(), ['local', 'development'], true);
}

/** @return array<string,mixed> */
function staark_hub_update_normalize_release(array $release, string $type): array
{
    $version = isset($release['version']) ? sanitize_text_field((string) $release['version']) : '';
    $package = isset($release['package']) ? esc_url_raw((string) $release['package']) : '';
    $sha256 = isset($release['sha256']) ? strtolower(sanitize_text_field((string) $release['sha256'])) : '';
    $signature = isset($release['signature']) ? trim((string) $release['signature']) : '';
    $notes = isset($release['notes']) ? sanitize_textarea_field((string) $release['notes']) : '';
    $requires = isset($release['requires']) && is_array($release['requires']) ? $release['requires'] : [];

    return [
        'type' => $type,
        'slug' => isset($release['slug'])
            ? sanitize_key((string) $release['slug'])
            : staark_hub_update_release_slug($type),
        'version' => $version,
        'package' => $package,
        'sha256' => preg_match('/^[a-f0-9]{64}$/', $sha256) ? $sha256 : '',
        'signature' => $signature,
        'notes' => $notes,
        'requires' => [
            'wordpress' => isset($requires['wordpress']) ? sanitize_text_field((string) $requires['wordpress']) : '',
            'php' => isset($requires['php']) ? sanitize_text_field((string) $requires['php']) : '',
        ],
    ];
}

/** @return array<string,mixed>|WP_Error */
function staark_hub_update_normalize_manifest(array $manifest)
{
    $schema = isset($manifest['schema']) ? (int) $manifest['schema'] : 1;
    if ($schema !== 1) {
        return new WP_Error('staark_updates_schema', 'Unsupported Staark update manifest schema.');
    }

    $releases = isset($manifest['releases']) && is_array($manifest['releases']) ? $manifest['releases'] : [];
    if (isset($manifest['hub']) && is_array($manifest['hub']) && ! isset($releases['core'])) {
        $releases['core'] = $manifest['hub'];
    }
    if (isset($manifest['theme']) && is_array($manifest['theme']) && ! isset($releases['theme'])) {
        $releases['theme'] = $manifest['theme'];
    }

    $normalized = [
        'schema' => 1,
        'channel' => isset($manifest['channel']) ? sanitize_key((string) $manifest['channel']) : staark_hub_update_channel(),
        'generatedAt' => isset($manifest['generatedAt']) ? sanitize_text_field((string) $manifest['generatedAt']) : '',
        'releases' => [],
    ];

    $release_types = array_merge(
        ['core'],
        array_keys(staark_hub_update_theme_releases())
    );

    foreach ($release_types as $type) {
        if (! isset($releases[$type]) || ! is_array($releases[$type])) {
            continue;
        }

        $release = staark_hub_update_normalize_release($releases[$type], $type);
        if ($release['version'] === '' || $release['package'] === '' || $release['sha256'] === '') {
            return new WP_Error('staark_updates_release', 'Update manifest contains an incomplete ' . $type . ' release.');
        }
        if (! staark_hub_update_http_url_allowed((string) $release['package'])) {
            return new WP_Error('staark_updates_package_url', 'Update package URLs must use HTTPS outside local/development environments.');
        }

        $normalized['releases'][$type] = $release;
    }

    if ($normalized['releases'] === []) {
        return new WP_Error('staark_updates_empty', 'The Staark update manifest contains no usable releases.');
    }

    return $normalized;
}

/** @return array<string,mixed>|WP_Error */
function staark_hub_update_fetch_manifest(bool $force = false)
{
    if ($force) {
        delete_transient(STAARK_HUB_UPDATE_MANIFEST_TRANSIENT);
    } else {
        $cached = get_transient(STAARK_HUB_UPDATE_MANIFEST_TRANSIENT);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $path = staark_hub_update_manifest_request_path();
    $result = null;

    if (
        ! defined('STAARK_HUB_UPDATE_MANIFEST_URL')
        && function_exists('staark_hub_connection_is_connected')
        && staark_hub_connection_is_connected()
        && function_exists('staark_hub_connection_request')
    ) {
        $request = staark_hub_connection_request($path, 'GET');
        if (! $request['ok']) {
            $result = new WP_Error('staark_updates_request', $request['error'] !== '' ? $request['error'] : 'Could not fetch the Staark update manifest.');
        } else {
            $result = $request['data'];
        }
    } else {
        $url = staark_hub_update_manifest_url();
        if (! staark_hub_update_http_url_allowed($url)) {
            $result = new WP_Error('staark_updates_manifest_url', 'Staark update manifest must use HTTPS outside local/development environments.');
        } else {
            $response = wp_remote_get(
                $url,
                [
                    'timeout' => 15,
                    'redirection' => 2,
                    'sslverify' => true,
                    'headers' => ['Accept' => 'application/json'],
                    'user-agent' => 'Staark-WordPress/' . STAARK_HUB_VERSION . '; ' . home_url('/'),
                ]
            );

            if (is_wp_error($response)) {
                $result = new WP_Error('staark_updates_request', $response->get_error_message());
            } else {
                $status = (int) wp_remote_retrieve_response_code($response);
                $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
                if ($status < 200 || $status >= 300 || ! is_array($decoded)) {
                    $result = new WP_Error('staark_updates_http', 'Staark update endpoint returned HTTP ' . $status . '.');
                } else {
                    $result = $decoded;
                }
            }
        }
    }

    $state = staark_hub_update_state();
    $state['last_checked'] = current_time('mysql');
    $state['channel'] = staark_hub_update_channel();

    if (is_wp_error($result)) {
        $state['last_error'] = $result->get_error_message();
        staark_hub_update_save_state($state);
        return $result;
    }

    $manifest = staark_hub_update_normalize_manifest($result);
    if (is_wp_error($manifest)) {
        $state['last_error'] = $manifest->get_error_message();
        staark_hub_update_save_state($state);
        return $manifest;
    }

    $state['last_error'] = '';
    $state['manifest_generated_at'] = (string) ($manifest['generatedAt'] ?? '');
    $state['core_latest'] = isset($manifest['releases']['core']['version'])
        ? (string) $manifest['releases']['core']['version']
        : '';

    foreach (staark_hub_update_theme_releases() as $type => $definition) {
        unset($definition);

        $state[$type . '_latest'] = isset($manifest['releases'][$type]['version'])
            ? (string) $manifest['releases'][$type]['version']
            : '';
    }

    staark_hub_update_save_state($state);
    set_transient(STAARK_HUB_UPDATE_MANIFEST_TRANSIENT, $manifest, 6 * HOUR_IN_SECONDS);

    return $manifest;
}

/** @return array<string,mixed> */
function staark_hub_update_cached_manifest(): array
{
    $cached = get_transient(STAARK_HUB_UPDATE_MANIFEST_TRANSIENT);
    return is_array($cached) ? $cached : [];
}

/** @return array<string,mixed>|null */
function staark_hub_update_release(string $type): ?array
{
    $manifest = staark_hub_update_cached_manifest();
    $release = $manifest['releases'][$type] ?? null;

    return is_array($release) ? $release : null;
}

function staark_hub_update_theme_installed_version(string $slug = 'staark'): string
{
    $theme = wp_get_theme($slug);
    return $theme->exists() ? (string) $theme->get('Version') : '';
}

function staark_hub_update_available(string $type, array $release): bool
{
    $installed = $type === 'core'
        ? STAARK_HUB_VERSION
        : staark_hub_update_theme_installed_version((string) ($release['slug'] ?? 'staark'));

    return $installed === '' || version_compare((string) ($release['version'] ?? ''), $installed, '>');
}

/** @return true|WP_Error */
function staark_hub_update_requirements_ok(array $release)
{
    $requires = isset($release['requires']) && is_array($release['requires']) ? $release['requires'] : [];
    $wp = isset($requires['wordpress']) ? trim((string) $requires['wordpress']) : '';
    $php = isset($requires['php']) ? trim((string) $requires['php']) : '';

    if ($wp !== '' && version_compare((string) get_bloginfo('version'), $wp, '<')) {
        return new WP_Error('staark_updates_wp_requirement', 'This release requires WordPress ' . $wp . ' or newer.');
    }
    if ($php !== '' && version_compare(PHP_VERSION, $php, '<')) {
        return new WP_Error('staark_updates_php_requirement', 'This release requires PHP ' . $php . ' or newer.');
    }

    return true;
}

/** @return true|WP_Error */
function staark_hub_update_verify_release_signature(array $release, string $type)
{
    $required = defined('STAARK_HUB_REQUIRE_SIGNED_UPDATES') && STAARK_HUB_REQUIRE_SIGNED_UPDATES === true;
    $public_key = defined('STAARK_HUB_UPDATE_PUBLIC_KEY') ? trim((string) STAARK_HUB_UPDATE_PUBLIC_KEY) : '';
    $signature = trim((string) ($release['signature'] ?? ''));

    if ($public_key === '') {
        return $required
            ? new WP_Error('staark_updates_public_key', 'Signed updates are required but STAARK_HUB_UPDATE_PUBLIC_KEY is not configured.')
            : true;
    }

    if ($signature === '') {
        return new WP_Error('staark_updates_signature_missing', 'The update release is missing its detached signature.');
    }
    if (! function_exists('sodium_crypto_sign_verify_detached')) {
        return new WP_Error('staark_updates_sodium', 'libsodium is required to verify the Staark update signature.');
    }

    $key = base64_decode($public_key, true);
    $sig = base64_decode($signature, true);
    if (! is_string($key) || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || ! is_string($sig) || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
        return new WP_Error('staark_updates_signature_format', 'The configured update key or release signature is invalid.');
    }

    $message = implode('|', [$type, (string) $release['version'], (string) $release['sha256'], (string) $release['package']]);

    return sodium_crypto_sign_verify_detached($sig, $message, $key)
        ? true
        : new WP_Error('staark_updates_signature', 'Staark update signature verification failed.');
}

/** @return true|WP_Error */
function staark_hub_update_lint_php_tree(string $root)
{
    $php_files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $item) {
        if ($item instanceof SplFileInfo && $item->isFile() && strtolower($item->getExtension()) === 'php') {
            $php_files[] = $item->getPathname();
        }
    }

    if ($php_files === []) {
        return true;
    }
    if (! function_exists('proc_open') || ! defined('PHP_BINARY') || PHP_BINARY === '' || ! is_executable(PHP_BINARY)) {
        return new WP_Error('staark_updates_lint_unavailable', 'PHP package lint is unavailable. Use the server/CLI deployment path instead of installing this update from wp-admin.');
    }

    foreach ($php_files as $file) {
        $pipes = [];
        $process = @proc_open([PHP_BINARY, '-l', $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            return new WP_Error('staark_updates_lint_start', 'Could not start PHP syntax validation.');
        }
        $stdout = isset($pipes[1]) && is_resource($pipes[1]) ? stream_get_contents($pipes[1]) : '';
        $stderr = isset($pipes[2]) && is_resource($pipes[2]) ? stream_get_contents($pipes[2]) : '';
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        $exit = proc_close($process);
        if ($exit !== 0) {
            return new WP_Error('staark_updates_lint', 'PHP syntax validation failed for ' . basename($file) . ': ' . trim((string) ($stderr !== '' ? $stderr : $stdout)));
        }
    }

    return true;
}

/** @return array{file:string,workspace:string,root:string}|WP_Error */
function staark_hub_update_prepare_package(array $release, string $type)
{
    $requirements = staark_hub_update_requirements_ok($release);
    if (is_wp_error($requirements)) {
        return $requirements;
    }
    $signature = staark_hub_update_verify_release_signature($release, $type);
    if (is_wp_error($signature)) {
        return $signature;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    if (! function_exists('WP_Filesystem') || ! WP_Filesystem()) {
        return new WP_Error('staark_updates_filesystem', 'WordPress filesystem initialization failed.');
    }

    $file = download_url((string) $release['package'], 60);
    if (is_wp_error($file)) {
        return $file;
    }

    $actual_hash = @hash_file('sha256', $file);
    if (! is_string($actual_hash) || ! hash_equals((string) $release['sha256'], strtolower($actual_hash))) {
        @unlink($file);
        return new WP_Error('staark_updates_checksum', 'Downloaded package SHA256 does not match the release manifest.');
    }

    $workspace = trailingslashit(get_temp_dir()) . 'staark-update-' . $type . '-' . bin2hex(random_bytes(6));
    if (! wp_mkdir_p($workspace)) {
        @unlink($file);
        return new WP_Error('staark_updates_workspace', 'Could not create update staging directory.');
    }

    $unzipped = unzip_file($file, $workspace);
    if (is_wp_error($unzipped)) {
        @unlink($file);
        staark_hub_deployment_remove_tree($workspace);
        return $unzipped;
    }

    $entry = $type === 'core' ? 'staark-core.php' : 'style.css';
    $root = is_readable(trailingslashit($workspace) . $entry) ? $workspace : '';
    if ($root === '') {
        $children = glob(trailingslashit($workspace) . '*', GLOB_ONLYDIR);
        if (is_array($children) && count($children) === 1 && is_readable(trailingslashit($children[0]) . $entry)) {
            $root = $children[0];
        }
    }
    if ($root === '') {
        @unlink($file);
        staark_hub_deployment_remove_tree($workspace);
        return new WP_Error('staark_updates_structure', 'Update package has an unexpected directory structure.');
    }

    $lint = staark_hub_update_lint_php_tree($root);
    if (is_wp_error($lint)) {
        @unlink($file);
        staark_hub_deployment_remove_tree($workspace);
        return $lint;
    }

    return ['file' => $file, 'workspace' => $workspace, 'root' => $root];
}

/** @param array{file?:string,workspace?:string} $prepared */
function staark_hub_update_cleanup_prepared(array $prepared): void
{
    if (! empty($prepared['file']) && is_file($prepared['file'])) {
        @unlink($prepared['file']);
    }
    if (! empty($prepared['workspace'])) {
        staark_hub_deployment_remove_tree((string) $prepared['workspace']);
    }
}

/** @return true|WP_Error */
function staark_hub_update_validate_core_source(string $source, array $release)
{
    $valid = staark_hub_deployment_validate_source($source);
    if (is_wp_error($valid)) {
        return $valid;
    }

    // The request performing the first WP-6.1 update can still be running the
    // WP-6.0 deployment validator. Guard the new bootstrap dependencies here
    // as well so a package can never switch in without its updater UI/runtime.
    foreach (['includes/update-channel.php', 'includes/support-sync.php', 'admin/updates-page.php', 'assets/updates.css'] as $relative) {
        if (! is_readable(trailingslashit($source) . $relative)) {
            return new WP_Error('staark_updates_core_structure', 'Core package is missing: ' . $relative);
        }
    }

    $version = staark_hub_deployment_package_version($source);
    return $version === (string) $release['version']
        ? true
        : new WP_Error('staark_updates_core_version', 'Core package version does not match the update manifest.');
}

/** @return true|WP_Error */
function staark_hub_update_validate_theme_source(string $source, array $release)
{
    $style = trailingslashit($source) . 'style.css';
    if (! is_readable($style)) {
        return new WP_Error('staark_updates_theme_style', 'Theme package is missing style.css.');
    }

    $data = get_file_data(
        $style,
        [
            'Name' => 'Theme Name',
            'Version' => 'Version',
            'Template' => 'Template',
        ],
        'theme'
    );
    $version = isset($data['Version']) ? trim((string) $data['Version']) : '';
    if ($version !== (string) $release['version']) {
        return new WP_Error('staark_updates_theme_version', 'Theme package version does not match the update manifest.');
    }

    $slug = sanitize_key((string) ($release['slug'] ?? 'staark'));
    if ($slug === 'staark' && (! is_readable(trailingslashit($source) . 'theme.json') || ! is_dir(trailingslashit($source) . 'templates'))) {
        return new WP_Error('staark_updates_theme_structure', 'Staark Theme package must contain theme.json and templates/.');
    }

    if ($slug !== 'staark') {
        $template = isset($data['Template'])
            ? sanitize_key(trim((string) $data['Template']))
            : '';

        if ($template !== 'staark') {
            return new WP_Error(
                'staark_updates_child_theme_parent',
                'Staark child theme package must declare Template: staark.'
            );
        }
    }

    return true;
}

function staark_hub_update_can_install(): bool
{
    if (defined('WP_CLI') && WP_CLI) {
        return true;
    }
    if (! current_user_can('manage_options')) {
        return false;
    }

    return ! function_exists('staark_hub_is_managed') || ! staark_hub_is_managed() || staark_hub_current_user_is_operator();
}

/** @return array<string,string>|WP_Error */
function staark_hub_update_install_core(array $release)
{
    if (! staark_hub_update_can_install()) {
        return new WP_Error('staark_updates_authority', 'Only a Staark operator can install managed updates.');
    }
    if (! staark_hub_update_available('core', $release)) {
        return new WP_Error('staark_updates_not_newer', 'The selected Staark Core release is not newer than the installed version.');
    }

    $prepared = staark_hub_update_prepare_package($release, 'core');
    if (is_wp_error($prepared)) {
        return $prepared;
    }

    try {
        $valid = staark_hub_update_validate_core_source($prepared['root'], $release);
        if (is_wp_error($valid)) {
            return $valid;
        }

        $current_dir = dirname(staark_hub_standard_plugin_file());
        if (function_exists('staark_hub_path_is_mountpoint') && staark_hub_path_is_mountpoint($current_dir)) {
            return new WP_Error('staark_updates_core_mounted', 'The Staark Core plugin directory is externally mounted. Update the source mapping outside WordPress.');
        }

        $suffix = gmdate('YmdHis') . '-' . bin2hex(random_bytes(3));
        $stage = trailingslashit(WP_PLUGIN_DIR) . '.staark-core-next-' . $suffix;
        $backup = trailingslashit(WP_PLUGIN_DIR) . '.staark-core-prev-' . $suffix;
        staark_hub_deployment_remove_tree($stage);
        staark_hub_deployment_remove_tree($backup);

        $copy = staark_hub_deployment_copy_tree($prepared['root'], $stage);
        if (is_wp_error($copy)) {
            return $copy;
        }

        $stage_valid = staark_hub_update_validate_core_source($stage, $release);
        if (is_wp_error($stage_valid)) {
            staark_hub_deployment_remove_tree($stage);
            return $stage_valid;
        }

        if (! @rename($current_dir, $backup)) {
            staark_hub_deployment_remove_tree($stage);
            return new WP_Error('staark_updates_core_backup', 'Could not move the current Staark Core package into rollback position.');
        }
        if (! @rename($stage, $current_dir)) {
            @rename($backup, $current_dir);
            staark_hub_deployment_remove_tree($stage);
            return new WP_Error('staark_updates_core_switch', 'Could not atomically activate the new Staark Core package.');
        }

        $deployment_before = staark_hub_deployment_release_id(staark_hub_managed_current_path());
        $deployed = staark_hub_deployment_deploy($current_dir);
        if (is_wp_error($deployed)) {
            staark_hub_deployment_remove_tree($current_dir);
            @rename($backup, $current_dir);
            return $deployed;
        }

        $verified = staark_hub_deployment_verify();
        if (is_wp_error($verified)) {
            $current_release = staark_hub_deployment_release_id(staark_hub_managed_current_path());
            if ($current_release === (string) ($deployed['release'] ?? '') && $deployment_before !== '') {
                staark_hub_deployment_rollback();
            }
            staark_hub_deployment_remove_tree($current_dir);
            @rename($backup, $current_dir);
            return $verified;
        }

        $state = staark_hub_update_state();
        $state['last_action'] = 'Installed Staark Core ' . (string) $release['version'] . '; awaiting next-request health check.';
        $state['last_action_at'] = current_time('mysql');
        $state['pending_core'] = [
            'version' => (string) $release['version'],
            'backup' => $backup,
            'release' => (string) ($deployed['release'] ?? ''),
            'previousRelease' => $deployment_before,
            'startedAt' => gmdate('c'),
        ];
        staark_hub_update_save_state($state);
        delete_transient(STAARK_HUB_UPDATE_MANIFEST_TRANSIENT);

        return [
            'version' => (string) $release['version'],
            'release' => (string) ($deployed['release'] ?? ''),
            'previous' => $deployment_before,
        ];
    } finally {
        staark_hub_update_cleanup_prepared($prepared);
    }
}

/** @return true|WP_Error */
function staark_hub_update_restore_core_backup(array $pending)
{
    $backup = isset($pending['backup']) ? (string) $pending['backup'] : '';
    $current_dir = dirname(staark_hub_standard_plugin_file());
    if ($backup === '' || ! is_dir($backup)) {
        return new WP_Error('staark_updates_backup_missing', 'The previous Staark Core package backup is unavailable.');
    }

    $failed = trailingslashit(WP_PLUGIN_DIR) . '.staark-core-failed-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(2));
    if (is_dir($current_dir) && ! @rename($current_dir, $failed)) {
        return new WP_Error('staark_updates_rollback_switch', 'Could not move the failed Staark Core package aside.');
    }
    if (! @rename($backup, $current_dir)) {
        if (is_dir($failed)) {
            @rename($failed, $current_dir);
        }
        return new WP_Error('staark_updates_rollback_restore', 'Could not restore the previous Staark Core package.');
    }
    staark_hub_deployment_remove_tree($failed);

    return true;
}

/** @return true|WP_Error */
function staark_hub_update_rollback_pending_core()
{
    $state = staark_hub_update_state();
    $pending = isset($state['pending_core']) && is_array($state['pending_core']) ? $state['pending_core'] : [];
    if ($pending === []) {
        return new WP_Error('staark_updates_no_pending_core', 'No pending Staark Core update rollback is available.');
    }

    $release = isset($pending['release']) ? (string) $pending['release'] : '';
    $current_release = staark_hub_deployment_release_id(staark_hub_managed_current_path());
    if ($release !== '' && $current_release === $release) {
        $rollback = staark_hub_deployment_rollback();
        if (is_wp_error($rollback)) {
            return $rollback;
        }
    }

    $restore = staark_hub_update_restore_core_backup($pending);
    if (is_wp_error($restore)) {
        return $restore;
    }

    $state['last_action'] = 'Rolled back Staark Core update after health validation failure.';
    $state['last_action_at'] = current_time('mysql');
    $state['pending_core'] = [];
    staark_hub_update_save_state($state);

    return true;
}

function staark_hub_update_finalize_pending_core(): void
{
    $state = staark_hub_update_state();
    $pending = isset($state['pending_core']) && is_array($state['pending_core']) ? $state['pending_core'] : [];
    if ($pending === []) {
        return;
    }

    $expected = isset($pending['version']) ? (string) $pending['version'] : '';
    if ($expected === '' || STAARK_HUB_VERSION !== $expected) {
        staark_hub_update_rollback_pending_core();
        return;
    }

    $healthy = function_exists('staark_hub_rc_report') ? staark_hub_rc_report() : ['ok' => true];
    if (empty($healthy['ok'])) {
        staark_hub_update_rollback_pending_core();
        return;
    }

    $backup = isset($pending['backup']) ? (string) $pending['backup'] : '';
    if ($backup !== '') {
        staark_hub_deployment_remove_tree($backup);
    }
    $state['last_action'] = 'Staark Core ' . $expected . ' passed the next-request health check.';
    $state['last_action_at'] = current_time('mysql');
    $state['pending_core'] = [];
    staark_hub_update_save_state($state);
}

/** @return array<string,string>|WP_Error */
function staark_hub_update_install_theme(array $release, string $type = 'theme')
{
    if (! staark_hub_update_can_install()) {
        return new WP_Error('staark_updates_authority', 'Only a Staark operator can install managed updates.');
    }

    $definitions = staark_hub_update_theme_releases();
    if (! isset($definitions[$type])) {
        return new WP_Error('staark_updates_theme_type', 'Unsupported Staark theme release type.');
    }

    if (! staark_hub_update_available($type, $release)) {
        return new WP_Error(
            'staark_updates_not_newer',
            'The selected ' . staark_hub_update_release_label($type) . ' release is not newer than the installed version.'
        );
    }

    $prepared = staark_hub_update_prepare_package($release, $type);
    if (is_wp_error($prepared)) {
        return $prepared;
    }

    try {
        $valid = staark_hub_update_validate_theme_source($prepared['root'], $release);
        if (is_wp_error($valid)) {
            return $valid;
        }

        $slug = sanitize_key((string) ($release['slug'] ?? 'staark'));
        $target = trailingslashit(get_theme_root()) . $slug;
        if (function_exists('staark_hub_path_is_mountpoint') && is_dir($target) && staark_hub_path_is_mountpoint($target)) {
            return new WP_Error('staark_updates_theme_mounted', 'The Staark Theme directory is externally mounted. Update the source mapping outside WordPress.');
        }

        $suffix = gmdate('YmdHis') . '-' . bin2hex(random_bytes(3));
        $stage = trailingslashit(get_theme_root()) . '.' . $slug . '-next-' . $suffix;
        $backup = trailingslashit(get_theme_root()) . '.' . $slug . '-prev-' . $suffix;
        staark_hub_deployment_remove_tree($stage);
        staark_hub_deployment_remove_tree($backup);

        $copy = staark_hub_deployment_copy_tree($prepared['root'], $stage);
        if (is_wp_error($copy)) {
            return $copy;
        }
        $stage_valid = staark_hub_update_validate_theme_source($stage, $release);
        if (is_wp_error($stage_valid)) {
            staark_hub_deployment_remove_tree($stage);
            return $stage_valid;
        }

        $had_theme = is_dir($target);
        if ($had_theme && ! @rename($target, $backup)) {
            staark_hub_deployment_remove_tree($stage);
            return new WP_Error('staark_updates_theme_backup', 'Could not move the current Staark Theme into rollback position.');
        }
        if (! @rename($stage, $target)) {
            if ($had_theme) {
                @rename($backup, $target);
            }
            staark_hub_deployment_remove_tree($stage);
            return new WP_Error('staark_updates_theme_switch', 'Could not atomically activate the new Staark Theme package.');
        }

        wp_clean_themes_cache(true);
        $theme = wp_get_theme($slug);
        $installed_version = $theme->exists() ? (string) $theme->get('Version') : '';
        if (! $theme->exists() || $installed_version !== (string) $release['version']) {
            staark_hub_deployment_remove_tree($target);
            if ($had_theme) {
                @rename($backup, $target);
            }
            wp_clean_themes_cache(true);
            return new WP_Error('staark_updates_theme_verify', 'Staark Theme failed its post-install version check and was rolled back.');
        }

        if ($had_theme) {
            staark_hub_deployment_remove_tree($backup);
        }

        $state = staark_hub_update_state();
        $state['last_action'] = 'Installed ' . staark_hub_update_release_label($type) . ' ' . (string) $release['version'] . '.';
        $state['last_action_at'] = current_time('mysql');
        staark_hub_update_save_state($state);
        delete_transient(STAARK_HUB_UPDATE_MANIFEST_TRANSIENT);

        return ['version' => (string) $release['version'], 'slug' => $slug];
    } finally {
        staark_hub_update_cleanup_prepared($prepared);
    }
}

/** @return array<string,mixed> */
function staark_hub_update_summary(): array
{
    $state = staark_hub_update_state();

    $core = staark_hub_update_release('core');
    $core_latest = is_array($core)
        ? (string) $core['version']
        : (string) $state['core_latest'];

    $themes = [];

    foreach (staark_hub_update_theme_releases() as $type => $definition) {
        $release = staark_hub_update_release($type);

        $slug = is_array($release)
            ? (string) ($release['slug'] ?? $definition['slug'])
            : (string) $definition['slug'];

        $installed = staark_hub_update_theme_installed_version($slug);

        $latest = is_array($release)
            ? (string) ($release['version'] ?? '')
            : (string) ($state[$type . '_latest'] ?? '');

        $themes[$type] = [
            'key' => $type,
            'label' => (string) $definition['label'],
            'slug' => $slug,
            'installed' => $installed,
            'latest' => $latest,
            'updateAvailable' => $latest !== ''
                && ($installed === '' || version_compare($latest, $installed, '>')),
            'notes' => is_array($release)
                ? (string) ($release['notes'] ?? '')
                : '',
        ];
    }

    $primary = $themes['theme'];

    return [
        'channel' => staark_hub_update_channel(),
        'lastChecked' => (string) $state['last_checked'],
        'lastError' => (string) $state['last_error'],
        'lastAction' => (string) $state['last_action'],
        'lastActionAt' => (string) $state['last_action_at'],
        'pendingCoreHealth' => ! empty($state['pending_core']),

        'coreInstalled' => STAARK_HUB_VERSION,
        'coreLatest' => $core_latest,
        'coreUpdateAvailable' => $core_latest !== ''
            && version_compare($core_latest, STAARK_HUB_VERSION, '>'),

        // Backwards-compatible primary theme fields.
        'themeSlug' => (string) $primary['slug'],
        'themeInstalled' => (string) $primary['installed'],
        'themeLatest' => (string) $primary['latest'],
        'themeUpdateAvailable' => (bool) $primary['updateAvailable'],

        'themes' => $themes,

        'scheduled' => (int) (wp_next_scheduled(STAARK_HUB_UPDATE_CRON) ?: 0),
    ];
}

function staark_hub_update_schedule(): void
{
    if (! wp_next_scheduled(STAARK_HUB_UPDATE_CRON)) {
        wp_schedule_event(time() + 300, 'twicedaily', STAARK_HUB_UPDATE_CRON);
    }
}

add_action('init', 'staark_hub_update_schedule');
add_action(STAARK_HUB_UPDATE_CRON, static function (): void {
    staark_hub_update_fetch_manifest(true);
});
add_action('plugins_loaded', 'staark_hub_update_finalize_pending_core', 90);

function staark_hub_update_admin_redirect(string $status): void
{
    wp_safe_redirect(admin_url('admin.php?page=staark-hub-updates&staark_updates=' . sanitize_key($status)));
    exit;
}

add_action('admin_post_staark_updates_check', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to check Staark updates.', 'staark-core'));
    }
    check_admin_referer('staark_updates_check');
    $result = staark_hub_update_fetch_manifest(true);
    staark_hub_update_admin_redirect(is_wp_error($result) ? 'check_error' : 'checked');
});

add_action('admin_post_staark_updates_install_core', static function (): void {
    if (! staark_hub_update_can_install()) {
        wp_die(esc_html__('Only a Staark operator can install this managed update.', 'staark-core'));
    }
    check_admin_referer('staark_updates_install_core');
    $manifest = staark_hub_update_fetch_manifest(true);
    if (is_wp_error($manifest) || ! isset($manifest['releases']['core']) || ! is_array($manifest['releases']['core'])) {
        staark_hub_update_admin_redirect('install_error');
    }
    $result = staark_hub_update_install_core($manifest['releases']['core']);
    staark_hub_update_admin_redirect(is_wp_error($result) ? 'install_error' : 'core_installed');
});

add_action('admin_post_staark_updates_install_theme', static function (): void {
    if (! staark_hub_update_can_install()) {
        wp_die(esc_html__('Only a Staark operator can install this managed update.', 'staark-core'));
    }

    $type = isset($_POST['release'])
        ? sanitize_key(wp_unslash($_POST['release']))
        : 'theme';

    $definitions = staark_hub_update_theme_releases();

    if (! isset($definitions[$type])) {
        wp_die(esc_html__('Unsupported Staark theme release.', 'staark-core'));
    }

    check_admin_referer('staark_updates_install_theme_' . $type);

    $manifest = staark_hub_update_fetch_manifest(true);

    if (
        is_wp_error($manifest)
        || ! isset($manifest['releases'][$type])
        || ! is_array($manifest['releases'][$type])
    ) {
        staark_hub_update_admin_redirect('install_error');
    }

    $result = staark_hub_update_install_theme(
        $manifest['releases'][$type],
        $type
    );

    staark_hub_update_admin_redirect(
        is_wp_error($result) ? 'install_error' : 'theme_installed'
    );
});

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    class Staark_Hub_Updates_CLI_Command
    {
        public function status(array $args, array $assoc_args): void
        {
            unset($args, $assoc_args);
            $summary = staark_hub_update_summary();
            \WP_CLI\Utils\format_items(
                'table',
                [
                    ['key' => 'Channel', 'value' => $summary['channel']],
                    ['key' => 'Core installed', 'value' => $summary['coreInstalled']],
                    ['key' => 'Core latest', 'value' => $summary['coreLatest'] !== '' ? $summary['coreLatest'] : 'unknown'],
                    ['key' => 'Core update', 'value' => $summary['coreUpdateAvailable'] ? 'available' : 'none/unknown'],
                    ['key' => 'Theme installed', 'value' => $summary['themeInstalled'] !== '' ? $summary['themeInstalled'] : 'not installed'],
                    ['key' => 'Theme latest', 'value' => $summary['themeLatest'] !== '' ? $summary['themeLatest'] : 'unknown'],
                    ['key' => 'Theme update', 'value' => $summary['themeUpdateAvailable'] ? 'available' : 'none/unknown'],
                    ['key' => 'Last checked', 'value' => $summary['lastChecked'] !== '' ? $summary['lastChecked'] : 'never'],
                    ['key' => 'Last error', 'value' => $summary['lastError'] !== '' ? $summary['lastError'] : 'none'],
                    ['key' => 'Pending core health', 'value' => $summary['pendingCoreHealth'] ? 'yes' : 'no'],
                ],
                ['key', 'value']
            );
        }

        public function check(array $args, array $assoc_args): void
        {
            unset($args, $assoc_args);
            $manifest = staark_hub_update_fetch_manifest(true);
            if (is_wp_error($manifest)) {
                \WP_CLI::error($manifest->get_error_message());
            }
            \WP_CLI::success('Staark update manifest fetched and verified.');
            $this->status([], []);
        }

        public function install(array $args, array $assoc_args): void
        {
            $type = isset($args[0]) ? sanitize_key((string) $args[0]) : '';
            $allowed = array_merge(
                ['core'],
                array_keys(staark_hub_update_theme_releases())
            );

            if (! in_array($type, $allowed, true)) {
                \WP_CLI::error(
                    'Usage: wp staark updates install <core|theme|salong|bygg> [--yes]'
                );
            }
            if (empty($assoc_args['yes'])) {
                \WP_CLI::confirm('Install the available Staark ' . $type . ' update now?');
            }

            $manifest = staark_hub_update_fetch_manifest(true);
            if (is_wp_error($manifest)) {
                \WP_CLI::error($manifest->get_error_message());
            }
            $release = $manifest['releases'][$type] ?? null;
            if (! is_array($release)) {
                \WP_CLI::error('The manifest does not contain a ' . $type . ' release.');
            }

            $result = $type === 'core'
                ? staark_hub_update_install_core($release)
                : staark_hub_update_install_theme($release, $type);
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }

            \WP_CLI::success('Staark ' . $type . ' update installed: ' . (string) ($result['version'] ?? 'unknown') . '.');
        }

        public function rollback_core(array $args, array $assoc_args): void
        {
            unset($args);
            if (empty($assoc_args['yes'])) {
                \WP_CLI::confirm('Rollback the pending Staark Core update and restore the previous managed release?');
            }
            $result = staark_hub_update_rollback_pending_core();
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }
            \WP_CLI::success('Pending Staark Core update rolled back.');
        }
    }

    WP_CLI::add_command('staark updates', 'Staark_Hub_Updates_CLI_Command');
}
