<?php
/**
 * Shared Staark Hub module helpers.
 *
 * Keep cross-module primitives here so Security, SEO and Performance can be
 * developed independently without turning staark-core.php into another
 * monolith.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Return all module switches.
 *
 * @return array<string,bool>
 */
function staark_hub_modules(): array
{
    $saved = get_option('staark_hub_modules', []);
    return is_array($saved) ? $saved : [];
}

function staark_hub_module_enabled(string $module, bool $default = true): bool
{
    $module = sanitize_key($module);
    $modules = staark_hub_modules();

    if (! array_key_exists($module, $modules)) {
        return $default;
    }

    return (bool) $modules[$module];
}

function staark_hub_set_module_enabled(string $module, bool $enabled): void
{
    $module = sanitize_key($module);
    if ($module === '') {
        return;
    }

    $modules = staark_hub_modules();
    $modules[$module] = $enabled;
    update_option('staark_hub_modules', $modules, true);
}

function staark_hub_checkbox_value(string $key): bool
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Generic checkbox reader; calling actions verify their nonce.
    if (! isset($_POST[$key]) || ! is_scalar($_POST[$key])) {
        return false;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Generic checkbox reader; calling actions verify their nonce.
    return sanitize_text_field((string) wp_unslash($_POST[$key])) === '1';
}

/**
 * Normalized WordPress environment name.
 */
function staark_hub_environment_type(): string
{
    if (function_exists('wp_get_environment_type')) {
        return (string) wp_get_environment_type();
    }

    return defined('WP_ENVIRONMENT_TYPE')
        ? sanitize_key((string) WP_ENVIRONMENT_TYPE)
        : 'production';
}

/**
 * Does $ip fall inside one of the ranges ("1.2.3.4", "10.0.0.0/8", "2001:db8::/32")?
 *
 * @param list<string> $ranges
 */
function staark_hub_ip_in_ranges(string $ip, array $ranges): bool
{
    $packed = @inet_pton($ip);
    if ($packed === false) {
        return false;
    }

    foreach ($ranges as $range) {
        $range = trim((string) $range);
        if ($range === '') {
            continue;
        }

        [$subnet, $bits] = array_pad(explode('/', $range, 2), 2, null);
        $subnet_packed = @inet_pton((string) $subnet);
        if ($subnet_packed === false || strlen($subnet_packed) !== strlen($packed)) {
            continue;
        }

        $max = strlen($packed) * 8;
        $bits = $bits === null ? $max : max(0, min($max, (int) $bits));
        $bytes = intdiv($bits, 8);
        $rest = $bits % 8;

        if (substr($packed, 0, $bytes) !== substr($subnet_packed, 0, $bytes)) {
            continue;
        }
        if ($rest === 0) {
            return true;
        }

        $mask = chr((0xff << (8 - $rest)) & 0xff);
        if ((ord($packed[$bytes]) & ord($mask)) === (ord($subnet_packed[$bytes]) & ord($mask))) {
            return true;
        }
    }

    return false;
}

/**
 * Cloudflare edge ranges (https://www.cloudflare.com/ips/), trusted
 * automatically when the header is CF-Connecting-IP.
 *
 * @return list<string>
 */
function staark_hub_cloudflare_ranges(): array
{
    return [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];
}

/**
 * Visitor IP address used for rate limits and login throttling.
 *
 * REMOTE_ADDR is the default because forwarding headers are spoofable. Sites
 * behind a proxy or CDN must opt in, otherwise every visitor shares the
 * proxy's address:
 *
 *   // Cloudflare (its edge ranges are built in):
 *   define('STAARK_HUB_CLIENT_IP_HEADER', 'HTTP_CF_CONNECTING_IP');
 *
 *   // nginx / load balancer in front of WordPress:
 *   define('STAARK_HUB_CLIENT_IP_HEADER', 'HTTP_X_FORWARDED_FOR');
 *   define('STAARK_HUB_TRUSTED_PROXIES', '10.0.0.0/8, 172.16.0.0/12');
 *
 * The header is only read when the connection comes from a trusted proxy
 * (STAARK_HUB_TRUSTED_PROXIES: IPs or CIDR ranges). For list headers such as
 * X-Forwarded-For the right-most address that is not a trusted proxy is the
 * client; entries further left are visitor-controlled and ignored.
 */
function staark_hub_client_ip(): string
{
    $remote = isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR'])
        ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
        : '';
    $ip = $remote;

    $header = defined('STAARK_HUB_CLIENT_IP_HEADER') ? strtoupper((string) STAARK_HUB_CLIENT_IP_HEADER) : '';
    if ($header !== '' && preg_match('/^HTTP_[A-Z0-9_]+$/', $header) && isset($_SERVER[$header]) && is_scalar($_SERVER[$header])) {
        $trusted = defined('STAARK_HUB_TRUSTED_PROXIES')
            ? array_values(array_filter(array_map('trim', explode(',', (string) STAARK_HUB_TRUSTED_PROXIES))))
            : [];
        if ($header === 'HTTP_CF_CONNECTING_IP') {
            $trusted = array_merge($trusted, staark_hub_cloudflare_ranges());
        }

        if ($trusted !== [] && staark_hub_ip_in_ranges($remote, $trusted)) {
            $forwarded = sanitize_text_field(wp_unslash($_SERVER[$header]));
            $chain = array_reverse(array_map('trim', explode(',', $forwarded)));
            foreach ($chain as $candidate) {
                if (! filter_var($candidate, FILTER_VALIDATE_IP)) {
                    break;
                }
                $ip = $candidate;
                if (! staark_hub_ip_in_ranges($candidate, $trusted)) {
                    break;
                }
            }
        }
    }

    $ip = (string) apply_filters('staark_hub_client_ip', $ip, $remote);

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

/**
 * Small settings read on every request are autoloaded (one query for all of
 * them instead of one each). Options saved before 0.6.8 were stored with
 * autoload off; switch them once.
 *
 * @return list<string>
 */
function staark_hub_autoload_options(): array
{
    return [
        'staark_hub_modules',
        'staark_hub_security_settings',
        'staark_hub_performance_settings',
        'staark_hub_seo_settings',
        'staark_hub_branding',
        'staark_hub_managed_mode',
        'staark_hub_update_state',
    ];
}

add_action('admin_init', static function (): void {
    if ((int) get_option('staark_hub_autoload_schema', 0) >= 1 || ! function_exists('wp_set_option_autoload_values')) {
        return;
    }

    $values = [];
    foreach (staark_hub_autoload_options() as $option) {
        $values[$option] = true;
    }
    wp_set_option_autoload_values($values);
    update_option('staark_hub_autoload_schema', 1, true);
});
