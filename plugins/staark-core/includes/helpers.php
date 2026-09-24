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
    update_option('staark_hub_modules', $modules, false);
}

function staark_hub_checkbox_value(string $key): bool
{
    return isset($_POST[$key]) && (string) wp_unslash($_POST[$key]) === '1';
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
