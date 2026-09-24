<?php
/**
 * Staark Managed Mode foundation.
 *
 * 6.0A establishes two primitives used by later protection layers:
 * - a deployment mode: normal / managed / locked;
 * - explicit Staark operator accounts.
 *
 * This file does not hide or block the plugin yet. Enforcement is added by the
 * following 6.0 protection patches so a bad setting cannot lock Staark out.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_MANAGED_MODE_OPTION = 'staark_hub_managed_mode';
const STAARK_HUB_OPERATOR_META = '_staark_hub_operator';

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

function staark_hub_operator_count(): int
{
    return count(staark_hub_operator_ids());
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

    update_option(STAARK_HUB_MANAGED_MODE_OPTION, $mode, false);

    return true;
}

/**
 * Safe connector/lifecycle summary. No user identifiers are exposed.
 *
 * @return array{mode:string,label:string,source:string,forced:bool,operatorCount:int,loader:string,protection:?array<string,mixed>}
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
        'loader' => 'plugin',
        'protection' => function_exists('staark_hub_managed_protection_summary') ? staark_hub_managed_protection_summary() : null,
    ];
}

add_action('admin_post_staark_managed_bootstrap_operator', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_managed_bootstrap_operator');

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
