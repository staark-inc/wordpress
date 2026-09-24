<?php
/**
 * Staark 6.0D production deployment and recovery primitives.
 *
 * Managed runtime releases live outside the normal plugin lifecycle:
 *   wp-content/staark-managed/releases/<release-id>/
 *   wp-content/staark-managed/current  -> releases/<release-id>
 *   wp-content/staark-managed/previous -> releases/<release-id>
 *
 * Mutation is intentionally WP-CLI only. wp-admin never deploys, rolls back or
 * rewrites the managed runtime.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_MANAGED_RUNTIME_DIRNAME = 'staark-managed';
const STAARK_HUB_RELEASE_MANIFEST = '.staark-release.json';

function staark_hub_standard_plugin_basename(): string
{
    return 'staark-core/staark-core.php';
}

function staark_hub_standard_plugin_file(): string
{
    return trailingslashit(WP_PLUGIN_DIR) . staark_hub_standard_plugin_basename();
}

function staark_hub_managed_runtime_root(): string
{
    return trailingslashit(WP_CONTENT_DIR) . STAARK_HUB_MANAGED_RUNTIME_DIRNAME;
}

function staark_hub_managed_releases_dir(): string
{
    return trailingslashit(staark_hub_managed_runtime_root()) . 'releases';
}

function staark_hub_managed_current_path(): string
{
    return trailingslashit(staark_hub_managed_runtime_root()) . 'current';
}

function staark_hub_managed_previous_path(): string
{
    return trailingslashit(staark_hub_managed_runtime_root()) . 'previous';
}

function staark_hub_managed_current_core_file(): string
{
    return trailingslashit(staark_hub_managed_current_path()) . 'staark-core.php';
}

function staark_hub_runtime_source(): string
{
    if (defined('STAARK_HUB_RUNTIME_SOURCE')) {
        return sanitize_key((string) STAARK_HUB_RUNTIME_SOURCE);
    }

    return staark_hub_bootstrapped_by_mu() ? 'plugin-fallback' : 'plugin';
}

/**
 * Resolve a managed release symlink to a release id.
 */
function staark_hub_deployment_release_id(string $link): string
{
    $real = realpath($link);
    $releases = realpath(staark_hub_managed_releases_dir());

    if ($real === false || $releases === false) {
        return '';
    }

    $real = wp_normalize_path($real);
    $releases = trailingslashit(wp_normalize_path($releases));
    if (! str_starts_with($real . '/', $releases)) {
        return '';
    }

    return sanitize_file_name(basename($real));
}

/**
 * @return array<string,mixed>
 */
function staark_hub_deployment_manifest(string $release_path = ''): array
{
    $release_path = $release_path !== '' ? $release_path : staark_hub_managed_current_path();
    $file = trailingslashit($release_path) . STAARK_HUB_RELEASE_MANIFEST;

    if (! is_readable($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Read-only status safe for connector diagnostics and RC checks.
 *
 * @return array<string,mixed>
 */
function staark_hub_managed_deployment_status(): array
{
    $current = staark_hub_managed_current_path();
    $previous = staark_hub_managed_previous_path();
    $manifest = staark_hub_deployment_manifest($current);
    $current_file = staark_hub_managed_current_core_file();

    return [
        'root' => staark_hub_managed_runtime_root(),
        'current' => $current,
        'previous' => $previous,
        'currentRelease' => staark_hub_deployment_release_id($current),
        'previousRelease' => staark_hub_deployment_release_id($previous),
        'currentReadable' => is_file($current_file) && is_readable($current_file),
        'runtimeSource' => staark_hub_runtime_source(),
        'runtimeFile' => defined('STAARK_HUB_LOCKED_CORE_FILE_RESOLVED') ? (string) STAARK_HUB_LOCKED_CORE_FILE_RESOLVED : STAARK_HUB_PLUGIN_FILE,
        'manifestVersion' => isset($manifest['version']) ? (string) $manifest['version'] : '',
        'manifestFingerprint' => isset($manifest['fingerprint']) ? (string) $manifest['fingerprint'] : '',
        'deployedAt' => isset($manifest['deployedAt']) ? (string) $manifest['deployedAt'] : '',
    ];
}

/**
 * Parse the plugin version without booting the source package.
 */
function staark_hub_deployment_package_version(string $source): string
{
    $entry = trailingslashit($source) . 'staark-core.php';
    if (! is_readable($entry)) {
        return '';
    }

    $data = get_file_data($entry, ['Version' => 'Version'], 'plugin');

    return isset($data['Version']) ? trim((string) $data['Version']) : '';
}

/**
 * @return true|WP_Error
 */
function staark_hub_deployment_validate_source(string $source)
{
    $source = untrailingslashit(wp_normalize_path($source));
    $required = [
        'staark-core.php',
        'includes/managed.php',
        'includes/managed-deployment.php',
        'includes/managed-protection.php',
        'includes/rc.php',
        'deployment/staark-loader.php',
        'assets/admin.css',
    ];

    foreach ($required as $relative) {
        if (! is_readable($source . '/' . $relative)) {
            return new WP_Error('staark_deploy_source', 'Managed deployment source is missing: ' . $relative);
        }
    }

    if (staark_hub_deployment_package_version($source) === '') {
        return new WP_Error('staark_deploy_version', 'The deployment package has no readable Staark Hub version header.');
    }

    $managed_root = realpath(staark_hub_managed_runtime_root());
    $source_real = realpath($source);
    if ($managed_root !== false && $source_real !== false) {
        $managed_root = trailingslashit(wp_normalize_path($managed_root));
        $source_real = wp_normalize_path($source_real);
        if (str_starts_with($source_real . '/', $managed_root)) {
            return new WP_Error('staark_deploy_recursive', 'Refusing to deploy a package from inside staark-managed.');
        }
    }

    return true;
}

/**
 * Fingerprint all regular package files in stable path order.
 */
function staark_hub_deployment_fingerprint(string $source): string
{
    $source = untrailingslashit(wp_normalize_path($source));
    $rows = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $item) {
        if (! $item instanceof SplFileInfo || ! $item->isFile() || $item->isLink()) {
            continue;
        }

        $path = wp_normalize_path($item->getPathname());
        $relative = ltrim(substr($path, strlen($source)), '/');
        if ($relative === '' || str_starts_with($relative, '.git/')) {
            continue;
        }

        $hash = @hash_file('sha256', $path);
        if (is_string($hash) && $hash !== '') {
            $rows[$relative] = $hash;
        }
    }

    ksort($rows, SORT_STRING);
    return hash('sha256', (string) wp_json_encode($rows, JSON_UNESCAPED_SLASHES));
}

/** @return true|WP_Error */
function staark_hub_deployment_copy_tree(string $source, string $target)
{
    if (! is_dir($target) && ! wp_mkdir_p($target)) {
        return new WP_Error('staark_deploy_mkdir', 'Could not create managed release staging directory.');
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if (! $item instanceof SplFileInfo) {
            continue;
        }

        $path = wp_normalize_path($item->getPathname());
        $relative = ltrim(substr($path, strlen(untrailingslashit(wp_normalize_path($source)))), '/');
        if ($relative === '' || str_starts_with($relative, '.git/')) {
            continue;
        }

        $destination = trailingslashit($target) . $relative;
        if ($item->isLink()) {
            return new WP_Error('staark_deploy_symlink', 'Symlinks inside the Staark package are not accepted: ' . $relative);
        }

        if ($item->isDir()) {
            if (! is_dir($destination) && ! wp_mkdir_p($destination)) {
                return new WP_Error('staark_deploy_mkdir', 'Could not create directory: ' . $relative);
            }
            continue;
        }

        if (! @copy($path, $destination)) {
            return new WP_Error('staark_deploy_copy', 'Could not copy managed release file: ' . $relative);
        }
        @chmod($destination, 0644);
    }

    return true;
}

function staark_hub_deployment_remove_tree(string $path): void
{
    if (! is_dir($path) || is_link($path)) {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
        }
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if (! $item instanceof SplFileInfo) {
            continue;
        }
        $item->isDir() && ! $item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($path);
}

/**
 * Atomically replace a relative symlink within staark-managed.
 *
 * @return true|WP_Error
 */
function staark_hub_deployment_switch_link(string $link, string $relative_target)
{
    if (! function_exists('symlink')) {
        return new WP_Error('staark_deploy_symlink_unavailable', 'PHP symlink() is unavailable; use the server deployment script instead.');
    }

    $tmp = $link . '.next-' . bin2hex(random_bytes(4));
    if (! @symlink($relative_target, $tmp)) {
        return new WP_Error('staark_deploy_symlink_create', 'Could not create the temporary managed runtime symlink.');
    }

    if (! @rename($tmp, $link)) {
        @unlink($tmp);
        return new WP_Error('staark_deploy_symlink_switch', 'Could not atomically switch the managed runtime symlink.');
    }

    clearstatcache(true, $link);
    return true;
}

/**
 * Install the release's loader without changing Managed Mode.
 *
 * @return true|WP_Error
 */
function staark_hub_deployment_install_loader(string $release_dir)
{
    $source = trailingslashit($release_dir) . 'deployment/' . STAARK_HUB_MU_LOADER_FILENAME;
    $target = staark_hub_mu_loader_path();

    if (! is_readable($source)) {
        return new WP_Error('staark_deploy_loader_source', 'The release does not contain a readable Staark MU-loader.');
    }

    if (is_readable($target)) {
        $source_hash = @hash_file('sha256', $source);
        $target_hash = @hash_file('sha256', $target);
        if (is_string($source_hash) && is_string($target_hash) && hash_equals($source_hash, $target_hash)) {
            return true;
        }
        // wp-env and similar deployments can bind-mount the bundled loader
        // directly onto wp-content/mu-plugins. In that case the external
        // mapping, not this release directory, owns the loader bytes. Allow a
        // release switch/rollback when the mounted target still matches the
        // runtime's bundled loader; otherwise keep failing closed.
        if (staark_hub_mu_loader_is_deployment_mapped() && staark_hub_mu_loader_matches_source()) {
            return true;
        }

        if (staark_hub_path_is_mountpoint($target)) {
            return new WP_Error('staark_deploy_loader_mounted', 'The MU-loader target is deployment-mounted and does not match the active Staark package. Update the external mapping first.');
        }
    }

    $directory = dirname($target);
    if (! is_dir($directory) && ! wp_mkdir_p($directory)) {
        return new WP_Error('staark_deploy_loader_dir', 'Could not create the mu-plugins directory.');
    }

    $tmp = $target . '.next-' . bin2hex(random_bytes(4));
    if (! @copy($source, $tmp)) {
        return new WP_Error('staark_deploy_loader_copy', 'Could not stage the release MU-loader.');
    }
    @chmod($tmp, 0644);

    if (! @rename($tmp, $target)) {
        @unlink($tmp);
        return new WP_Error('staark_deploy_loader_switch', 'Could not atomically replace the Staark MU-loader.');
    }

    return true;
}

/**
 * Deploy a package into a new immutable release and atomically move current.
 *
 * @return array<string,string>|WP_Error
 */
function staark_hub_deployment_deploy(string $source = '')
{
    $source = $source !== '' ? $source : dirname(staark_hub_standard_plugin_file());
    $source_real = realpath($source);
    if ($source_real === false || ! is_dir($source_real)) {
        return new WP_Error('staark_deploy_source_missing', 'Staark deployment source directory does not exist.');
    }
    $source = untrailingslashit(wp_normalize_path($source_real));

    $valid = staark_hub_deployment_validate_source($source);
    if (is_wp_error($valid)) {
        return $valid;
    }

    $root = staark_hub_managed_runtime_root();
    $releases = staark_hub_managed_releases_dir();
    if ((! is_dir($root) && ! wp_mkdir_p($root)) || (! is_dir($releases) && ! wp_mkdir_p($releases))) {
        return new WP_Error('staark_deploy_root', 'Could not create the staark-managed release directories.');
    }

    $lock_handle = @fopen(trailingslashit($root) . '.deploy.lock', 'c+');
    if (! is_resource($lock_handle) || ! @flock($lock_handle, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock_handle)) {
            fclose($lock_handle);
        }
        return new WP_Error('staark_deploy_locked', 'Another Staark deployment is already in progress.');
    }

    try {
        $version = staark_hub_deployment_package_version($source);
        $fingerprint = staark_hub_deployment_fingerprint($source);
        $release_id = 'v' . sanitize_file_name($version) . '-' . gmdate('YmdHis') . '-' . substr($fingerprint, 0, 8);
        $release_dir = trailingslashit($releases) . $release_id;
        $stage = trailingslashit($root) . '.stage-' . $release_id;

        if (file_exists($release_dir)) {
            return new WP_Error('staark_deploy_exists', 'The generated release id already exists; wait one second and retry.');
        }

        staark_hub_deployment_remove_tree($stage);
        $copy = staark_hub_deployment_copy_tree($source, $stage);
        if (is_wp_error($copy)) {
            staark_hub_deployment_remove_tree($stage);
            return $copy;
        }

        $manifest = [
            'releaseId' => $release_id,
            'version' => $version,
            'fingerprint' => $fingerprint,
            'deployedAt' => gmdate('c'),
            'source' => 'staark-core package',
        ];
        $manifest_json = wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (! is_string($manifest_json) || @file_put_contents(trailingslashit($stage) . STAARK_HUB_RELEASE_MANIFEST, $manifest_json . "\n", LOCK_EX) === false) {
            staark_hub_deployment_remove_tree($stage);
            return new WP_Error('staark_deploy_manifest', 'Could not write the managed release manifest.');
        }

        if (! @rename($stage, $release_dir)) {
            staark_hub_deployment_remove_tree($stage);
            return new WP_Error('staark_deploy_publish', 'Could not publish the staged managed release.');
        }

        $loader = staark_hub_deployment_install_loader($release_dir);
        if (is_wp_error($loader)) {
            return $loader;
        }

        $old_release = staark_hub_deployment_release_id(staark_hub_managed_current_path());
        if ($old_release !== '' && $old_release !== $release_id) {
            $previous = staark_hub_deployment_switch_link(staark_hub_managed_previous_path(), 'releases/' . $old_release);
            if (is_wp_error($previous)) {
                return $previous;
            }
        }

        $current = staark_hub_deployment_switch_link(staark_hub_managed_current_path(), 'releases/' . $release_id);
        if (is_wp_error($current)) {
            return $current;
        }

        return [
            'release' => $release_id,
            'version' => $version,
            'previous' => $old_release,
        ];
    } finally {
        @flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
    }
}

/**
 * Atomically swap current with previous. The next request boots the rollback.
 *
 * @return array<string,string>|WP_Error
 */
function staark_hub_deployment_rollback()
{
    $current_release = staark_hub_deployment_release_id(staark_hub_managed_current_path());
    $previous_release = staark_hub_deployment_release_id(staark_hub_managed_previous_path());

    if ($previous_release === '') {
        return new WP_Error('staark_rollback_previous', 'No previous managed release is available.');
    }

    $previous_dir = trailingslashit(staark_hub_managed_releases_dir()) . $previous_release;
    $loader = staark_hub_deployment_install_loader($previous_dir);
    if (is_wp_error($loader)) {
        return $loader;
    }

    $current = staark_hub_deployment_switch_link(staark_hub_managed_current_path(), 'releases/' . $previous_release);
    if (is_wp_error($current)) {
        return $current;
    }

    if ($current_release !== '') {
        $previous = staark_hub_deployment_switch_link(staark_hub_managed_previous_path(), 'releases/' . $current_release);
        if (is_wp_error($previous)) {
            return $previous;
        }
    }

    return ['release' => $previous_release, 'previous' => $current_release];
}

/** @return true|WP_Error */
function staark_hub_deployment_verify()
{
    $status = staark_hub_managed_deployment_status();

    if (empty($status['currentReadable']) || (string) $status['currentRelease'] === '') {
        return new WP_Error('staark_deploy_verify_current', 'No readable managed current release is installed.');
    }

    if ((string) $status['manifestVersion'] === '') {
        return new WP_Error('staark_deploy_verify_manifest', 'The current managed release manifest is missing or invalid.');
    }

    if (staark_hub_is_locked()) {
        if (! staark_hub_bootstrapped_by_mu()) {
            return new WP_Error('staark_deploy_verify_mu', 'Locked mode is not currently bootstrapped by the MU-loader.');
        }
        if ((string) $status['runtimeSource'] !== 'managed') {
            return new WP_Error('staark_deploy_verify_source', 'Locked mode is using the plugin fallback instead of wp-content/staark-managed/current.');
        }
    }

    return true;
}

// When the runtime itself lives under staark-managed, lifecycle hooks still
// belong to the normal plugin package used for activation/recovery. Registering
// the same callback twice is harmless when both paths are identical.
if (function_exists('staark_hub_activate') && function_exists('staark_hub_deactivate')) {
    register_activation_hook(staark_hub_standard_plugin_file(), 'staark_hub_activate');
    register_deactivation_hook(staark_hub_standard_plugin_file(), 'staark_hub_deactivate');
}

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    class Staark_Hub_Deployment_CLI_Command
    {
        public function status(array $args, array $assoc_args): void
        {
            unset($args, $assoc_args);
            $status = staark_hub_managed_deployment_status();
            \WP_CLI\Utils\format_items('table', [
                ['key' => 'Mode', 'value' => staark_hub_managed_mode_label()],
                ['key' => 'Runtime source', 'value' => (string) $status['runtimeSource']],
                ['key' => 'Runtime file', 'value' => (string) $status['runtimeFile']],
                ['key' => 'Current release', 'value' => (string) ($status['currentRelease'] ?: 'none')],
                ['key' => 'Previous release', 'value' => (string) ($status['previousRelease'] ?: 'none')],
                ['key' => 'Current readable', 'value' => ! empty($status['currentReadable']) ? 'yes' : 'no'],
                ['key' => 'Manifest version', 'value' => (string) ($status['manifestVersion'] ?: 'none')],
                ['key' => 'Deployed at', 'value' => (string) ($status['deployedAt'] ?: 'n/a')],
            ], ['key', 'value']);
        }

        public function deploy(array $args, array $assoc_args): void
        {
            unset($args);
            $source = isset($assoc_args['source']) ? (string) $assoc_args['source'] : '';
            $result = staark_hub_deployment_deploy($source);
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }

            \WP_CLI::success(sprintf(
                'Managed release %s (%s) deployed. Previous: %s. Mode was not changed.',
                $result['release'],
                $result['version'],
                $result['previous'] !== '' ? $result['previous'] : 'none'
            ));
        }

        public function rollback(array $args, array $assoc_args): void
        {
            unset($args, $assoc_args);
            $result = staark_hub_deployment_rollback();
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }

            \WP_CLI::success(sprintf(
                'Managed runtime rolled back to %s. Previous pointer is now %s.',
                $result['release'],
                $result['previous'] !== '' ? $result['previous'] : 'none'
            ));
        }

        public function verify(array $args, array $assoc_args): void
        {
            unset($args, $assoc_args);
            $result = staark_hub_deployment_verify();
            if (is_wp_error($result)) {
                \WP_CLI::error($result->get_error_message());
            }
            \WP_CLI::success('Staark managed production deployment verified.');
        }
    }

    WP_CLI::add_command('staark deployment', 'Staark_Hub_Deployment_CLI_Command');
}
