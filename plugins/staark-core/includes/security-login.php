<?php
/**
 * Staark Security login throttling.
 *
 * This is deliberately conservative and stores only hashed throttle keys in
 * transients. It does not replace edge/WAF rate limiting, 2FA or server-level
 * monitoring, but it gives managed WordPress sites a safe local fallback.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Visitor IP (see staark_hub_client_ip() for proxy/CDN setups).
 */
function staark_hub_security_client_ip(): string
{
    $ip = function_exists('staark_hub_client_ip') ? staark_hub_client_ip() : 'unknown';
    $ip = (string) apply_filters('staark_hub_security_client_ip', $ip);

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

/**
 * The IP bucket is a soft limit: behind a proxy many people can share one
 * address, so it allows several times more failures than the per-account
 * (identity) bucket before it locks.
 */
function staark_hub_security_login_ip_multiplier(): int
{
    return max(1, absint(apply_filters('staark_hub_security_login_ip_multiplier', 4)));
}

/**
 * @return array{window:int,attempts:int,lockout:int}
 */
function staark_hub_security_login_limits(): array
{
    $limits = [
        'window' => 15 * MINUTE_IN_SECONDS,
        'attempts' => 12,
        'lockout' => 10 * MINUTE_IN_SECONDS,
    ];

    $filtered = apply_filters('staark_hub_security_login_limits', $limits);
    if (! is_array($filtered)) {
        return $limits;
    }

    return [
        'window' => max(MINUTE_IN_SECONDS, absint($filtered['window'] ?? $limits['window'])),
        'attempts' => max(3, absint($filtered['attempts'] ?? $limits['attempts'])),
        'lockout' => max(MINUTE_IN_SECONDS, absint($filtered['lockout'] ?? $limits['lockout'])),
    ];
}

function staark_hub_security_login_key(string $type, string $value): string
{
    $value = strtolower(trim($value));
    $hash = hash_hmac('sha256', $type . '|' . $value, wp_salt('auth'));

    return 'staark_login_' . sanitize_key($type) . '_' . substr($hash, 0, 32);
}

/**
 * @return array{count:int,first:int,locked_until:int}
 */
function staark_hub_security_login_state(string $key): array
{
    $state = get_transient($key);
    if (! is_array($state)) {
        return ['count' => 0, 'first' => 0, 'locked_until' => 0];
    }

    return [
        'count' => absint($state['count'] ?? 0),
        'first' => absint($state['first'] ?? 0),
        'locked_until' => absint($state['locked_until'] ?? 0),
    ];
}

function staark_hub_security_login_is_locked(string $login): bool
{
    $now = time();
    $ip = staark_hub_security_client_ip();
    $keys = [staark_hub_security_login_key('identity', $ip . '|' . $login)];
    if ($ip !== 'unknown') {
        array_unshift($keys, staark_hub_security_login_key('ip', $ip));
    }

    foreach ($keys as $key) {
        $state = staark_hub_security_login_state($key);
        if ($state['locked_until'] > $now) {
            return true;
        }
    }

    return false;
}

function staark_hub_security_login_record_failure(string $login): void
{
    if (! staark_hub_module_enabled('security', true)) {
        return;
    }

    $settings = staark_hub_security_settings();
    if (empty($settings['login_protection'])) {
        return;
    }

    $now = time();
    $limits = staark_hub_security_login_limits();
    $ip = staark_hub_security_client_ip();
    $keys = [staark_hub_security_login_key('identity', $ip . '|' . $login)];
    if ($ip !== 'unknown') {
        array_unshift($keys, staark_hub_security_login_key('ip', $ip));
    }

    $ip_key = $ip !== 'unknown' ? staark_hub_security_login_key('ip', $ip) : '';

    foreach ($keys as $key) {
        $state = staark_hub_security_login_state($key);
        if ($state['first'] === 0 || ($now - $state['first']) > $limits['window']) {
            $state = ['count' => 0, 'first' => $now, 'locked_until' => 0];
        }

        $threshold = $key === $ip_key
            ? $limits['attempts'] * staark_hub_security_login_ip_multiplier()
            : $limits['attempts'];

        ++$state['count'];
        if ($state['count'] >= $threshold) {
            $state['locked_until'] = $now + $limits['lockout'];
        }

        $ttl = max($limits['window'], $limits['lockout']) + MINUTE_IN_SECONDS;
        set_transient($key, $state, $ttl);
    }
}

/**
 * A successful login clears only that account's bucket. Clearing the shared
 * IP bucket would let anyone with a valid low-privilege account reset the
 * counter while guessing other accounts' passwords.
 */
function staark_hub_security_login_clear_success(string $login): void
{
    $ip = staark_hub_security_client_ip();
    delete_transient(staark_hub_security_login_key('identity', $ip . '|' . $login));
}

/**
 * Fail before WordPress performs credential validation once a bucket is locked.
 * The message is deliberately generic and does not reveal whether the username
 * exists or whether the limit was reached by the identity or IP bucket.
 *
 * @param WP_User|WP_Error|null $user
 * @return WP_User|WP_Error|null
 */
function staark_hub_security_login_authenticate($user, $username, $password)
{
    $username = is_scalar($username) ? (string) $username : '';
    if ($user instanceof WP_Error || ! staark_hub_module_enabled('security', true)) {
        return $user;
    }

    $settings = staark_hub_security_settings();
    if (empty($settings['login_protection']) || $username === '') {
        return $user;
    }

    if (staark_hub_security_login_is_locked($username)) {
        return new WP_Error(
            'staark_login_throttled',
            __('Too many login attempts. Please wait a few minutes and try again.', 'staark-core')
        );
    }

    return $user;
}

add_filter('authenticate', 'staark_hub_security_login_authenticate', 5, 3);
add_action('wp_login_failed', 'staark_hub_security_login_record_failure', 10, 1);
add_action('wp_login', static function (string $user_login): void {
    staark_hub_security_login_clear_success($user_login);
}, 10, 1);
