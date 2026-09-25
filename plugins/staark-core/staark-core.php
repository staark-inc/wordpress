<?php
/**
 * Plugin Name: Staark Hub
 * Plugin URI: https://staarkinc.com
 * Description: Website management layer for sites built and maintained by Staark Inc.
 * Version: 0.6.5.0
 * Author: Staark Inc.
 * Author URI: https://staarkinc.com
 * Text Domain: staark-core
 */

if (! defined('ABSPATH')) {
    exit;
}

// Managed releases and the regular plugin package can both be present in
// Locked mode. The MU runtime wins; the later normal-plugin include becomes a
// no-op instead of redeclaring every Staark function.
if (defined('STAARK_HUB_RUNTIME_LOADED')) {
    return;
}
define('STAARK_HUB_RUNTIME_LOADED', true);

const STAARK_HUB_VERSION = '0.6.5.0';
const STAARK_HUB_SLUG = 'staark-hub';
define('STAARK_HUB_PLUGIN_FILE', __FILE__);
define('STAARK_HUB_PLUGIN_DIR', __DIR__ . '/');

/**
 * Build an asset URL for both regular-plugin and wp-content/staark-managed
 * runtimes. plugin_dir_url() cannot safely map a runtime outside WP_PLUGIN_DIR.
 */
function staark_hub_runtime_url(string $relative = ''): string
{
    if (defined('STAARK_HUB_RUNTIME_URL') && trim((string) STAARK_HUB_RUNTIME_URL) !== '') {
        $base = trailingslashit((string) STAARK_HUB_RUNTIME_URL);
    } else {
        $runtime_dir = untrailingslashit(wp_normalize_path(STAARK_HUB_PLUGIN_DIR));
        $content_dir = untrailingslashit(wp_normalize_path(WP_CONTENT_DIR));

        if ($runtime_dir === $content_dir || str_starts_with($runtime_dir . '/', $content_dir . '/')) {
            $content_relative = ltrim(substr($runtime_dir, strlen($content_dir)), '/');
            $base = trailingslashit(content_url('/' . $content_relative));
        } else {
            $base = plugin_dir_url(STAARK_HUB_PLUGIN_FILE);
        }
    }

    return $base . ltrim($relative, '/');
}

require_once STAARK_HUB_PLUGIN_DIR . 'includes/helpers.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/accessibility.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/managed.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/managed-protection.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/security.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/seo.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/performance.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/performance-media.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/forms.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/first-install.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/starter-repair.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/lifecycle.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/managed-deployment.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/update-channel.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/support-sync.php';
require_once STAARK_HUB_PLUGIN_DIR . 'includes/rc.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/security-page.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/seo-page.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/performance-page.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/forms-page.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/first-install-page.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/managed-page.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/updates-page.php';
require_once STAARK_HUB_PLUGIN_DIR . 'admin/support-detail.php';

/**
 * Return the current Staark Hub admin page slug.
 */
function staark_hub_current_page(): string
{
    return isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
}

/**
 * Check whether the current admin screen belongs to Staark Hub.
 */
function staark_hub_is_admin_page(): bool
{
    $page = staark_hub_current_page();

    return $page === STAARK_HUB_SLUG || strpos($page, 'staark-hub-') === 0;
}

/**
 * Inline Code2-style icon used by the WordPress admin menu.
 */
function staark_hub_menu_icon(): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Pages that make up the first Staark Business Starter.
 * Existing pages are never overwritten by the installer.
 *
 * @return array<string, array{title:string, content:string}>
 */
function staark_hub_starter_pages(): array
{
    return [
        'tjanster' => [
            'title' => 'Tjänster',
            'content' => <<<'BLOCKS'
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted","fontSize":"lg"} -->
<p class="staark-page-lead has-muted-color has-text-color has-lg-font-size">Från första idé till en snabb, tydlig och lättskött webbplats. Välj det stöd som passar företaget idag och bygg vidare när behovet växer.</p>
<!-- /wp:paragraph -->
<!-- wp:pattern {"slug":"staark/services-three"} /-->
<!-- wp:pattern {"slug":"staark/process-three"} /-->
<!-- wp:pattern {"slug":"staark/faq-business"} /-->
<!-- wp:pattern {"slug":"staark/cta-light"} /-->
BLOCKS,
        ],
        'om-oss' => [
            'title' => 'Om oss',
            'content' => <<<'BLOCKS'
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted","fontSize":"lg"} -->
<p class="staark-page-lead has-muted-color has-text-color has-lg-font-size">Vi bygger webbplatser med samma fokus som vi själva vill ha från en leverantör: tydlig kommunikation, stabil teknik och lösningar som faktiskt går att använda efter lansering.</p>
<!-- /wp:paragraph -->
<!-- wp:columns {"align":"wide","className":"staark-page-grid","style":{"spacing":{"blockGap":{"left":"24px"},"margin":{"top":"48px","bottom":"72px"}}}} -->
<div class="wp-block-columns alignwide staark-page-grid" style="margin-top:48px;margin-bottom:72px">
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"staark-page-card"} --><div class="wp-block-group staark-page-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Tydligt</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">Du ska förstå vad som byggs, varför det behövs och vad nästa steg är.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"staark-page-card"} --><div class="wp-block-group staark-page-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Praktiskt</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">Vi prioriterar sådant som gör webbplatsen snabbare, enklare och bättre för kunden.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:group {"className":"staark-page-card"} --><div class="wp-block-group staark-page-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Långsiktigt</h3><!-- /wp:heading --><!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">En bra grund ska kunna växa utan att hela webbplatsen måste byggas om från början.</p><!-- /wp:paragraph --></div><!-- /wp:group --></div><!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:pattern {"slug":"staark/why-us-business"} /-->
<!-- wp:pattern {"slug":"staark/testimonials-business"} /-->
<!-- wp:pattern {"slug":"staark/cta-light"} /-->
BLOCKS,
        ],
        'kontakt' => [
            'title' => 'Kontakt',
            'content' => <<<'BLOCKS'
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted","fontSize":"lg"} -->
<p class="staark-page-lead has-muted-color has-text-color has-lg-font-size">Berätta kort vad du behöver hjälp med. Vi börjar med behovet och tar tekniken därefter.</p>
<!-- /wp:paragraph -->
<!-- wp:pattern {"slug":"staark/contact-business"} /-->
<!-- wp:pattern {"slug":"staark/faq-business"} /-->
BLOCKS,
        ],
        'integritetspolicy' => [
            'title' => 'Integritetspolicy',
            'content' => <<<'BLOCKS'
<!-- wp:group {"className":"staark-privacy","layout":{"type":"constrained"}} -->
<div class="wp-block-group staark-privacy">
<!-- wp:paragraph {"className":"staark-page-lead","textColor":"muted"} --><p class="staark-page-lead has-muted-color has-text-color">Den här sidan är en startmall. Anpassa uppgifterna om personuppgiftsansvarig, kontaktvägar, formulär, analysverktyg och andra tjänster innan webbplatsen publiceras för kund.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Vilka uppgifter samlas in?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>När du kontaktar oss kan vi behandla uppgifter som namn, e-postadress, telefonnummer, företag och information som du själv lämnar i ett formulär eller meddelande.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Varför behandlas uppgifterna?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Uppgifterna används för att besvara förfrågningar, hantera kundrelationer och leverera de tjänster som efterfrågas.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Hur länge sparas uppgifterna?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Personuppgifter sparas inte längre än vad som behövs för ändamålet eller vad som krävs enligt tillämpliga regler och bokföringskrav.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Dina rättigheter</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Du kan kontakta företaget för frågor om dina personuppgifter eller för att begära tillgång, rättelse eller radering när det är tillämpligt.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Kontakt</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Ange företagets aktuella kontaktuppgifter här innan lansering.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
BLOCKS,
        ],
    ];
}

function staark_hub_installed_page_count(): int
{
    $count = 0;

    foreach (array_keys(staark_hub_starter_pages()) as $slug) {
        if (get_page_by_path($slug, OBJECT, 'page') instanceof WP_Post) {
            ++$count;
        }
    }

    return $count;
}

/**
 * Count pending WordPress, plugin and theme updates without forcing a remote check.
 *
 * @return array{core:int,plugins:int,themes:int,total:int}
 */
function staark_hub_pending_updates(): array
{
    $core_updates = get_site_transient('update_core');
    $plugin_updates = get_site_transient('update_plugins');
    $theme_updates = get_site_transient('update_themes');

    $core = 0;
    if (is_object($core_updates) && ! empty($core_updates->updates) && is_array($core_updates->updates)) {
        foreach ($core_updates->updates as $update) {
            if (is_object($update) && isset($update->response) && $update->response === 'upgrade') {
                ++$core;
            }
        }
    }

    $plugins = is_object($plugin_updates) && isset($plugin_updates->response) && is_array($plugin_updates->response)
        ? count($plugin_updates->response)
        : 0;
    $themes = is_object($theme_updates) && isset($theme_updates->response) && is_array($theme_updates->response)
        ? count($theme_updates->response)
        : 0;

    return [
        'core' => $core,
        'plugins' => $plugins,
        'themes' => $themes,
        'total' => $core + $plugins + $themes,
    ];
}

/**
 * Return an edit URL for the homepage. Block themes without a static front page
 * open the front-page template in the Site Editor.
 */
function staark_hub_homepage_edit_url(): string
{
    $front_page_id = (int) get_option('page_on_front');

    if ($front_page_id > 0) {
        $url = get_edit_post_link($front_page_id, 'raw');
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $stylesheet = wp_get_theme()->get_stylesheet();
    $template_id = $stylesheet . '//front-page';

    return admin_url('site-editor.php?path=/wp_template/' . rawurlencode($template_id));
}

function staark_hub_published_page_count(): int
{
    $counts = wp_count_posts('page');

    return isset($counts->publish) ? (int) $counts->publish : 0;
}

/**
 * Support ticket categories exposed in the Staark Hub form.
 *
 * @return array<string,string>
 */
function staark_hub_support_categories(): array
{
    return [
        'technical' => 'Technical issue',
        'content' => 'Content change',
        'design' => 'Design change',
        'maintenance' => 'Maintenance',
        'other' => 'Other',
    ];
}

/**
 * Support ticket priorities exposed in the Staark Hub form.
 *
 * @return array<string,string>
 */
function staark_hub_support_priorities(): array
{
    return [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];
}

/**
 * Snapshot attached to every support request so Staark gets useful context
 * without asking the client for WordPress or server details first.
 *
 * @return array<string,string|int>
 */
function staark_hub_support_environment(): array
{
    $theme = wp_get_theme();
    $updates = staark_hub_pending_updates();

    return [
        'site_url' => home_url('/'),
        'wordpress' => (string) get_bloginfo('version'),
        'php' => PHP_VERSION,
        'theme' => trim((string) $theme->get('Name') . ' ' . (string) $theme->get('Version')),
        'hub' => STAARK_HUB_VERSION,
        'updates' => $updates['total'],
        'locale' => get_locale(),
        'timezone' => wp_timezone_string(),
    ];
}

/**
 * Return recent locally stored support tickets.
 *
 * @return WP_Post[]
 */
function staark_hub_support_tickets(int $limit = 8): array
{
    return get_posts(
        [
            'post_type' => 'staark_ticket',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => true,
        ]
    );
}

function staark_hub_support_ticket_label(int $ticket_id): string
{
    return 'STK-' . str_pad((string) $ticket_id, 5, '0', STR_PAD_LEFT);
}



/**
 * Public Staark Inc. contact address used across the Hub (support, pairing
 * code requests, direct contact).
 */
function staark_hub_contact_email(): string
{
    $email = sanitize_email((string) apply_filters('staark_hub_contact_email', 'contact@staarkinc.com'));

    return is_email($email) ? $email : 'contact@staarkinc.com';
}

/**
 * Pre-filled email asking Staark Inc. for a pairing code (the connector key).
 *
 * Only public identifiers are included: site URL, site ID and versions. The
 * per-site secret never leaves WordPress this way.
 *
 * @param array<string,string> $connection
 */
function staark_hub_pairing_request_url(array $connection): string
{
    $user = wp_get_current_user();
    $host = (string) (wp_parse_url(home_url('/'), PHP_URL_HOST) ?: home_url('/'));
    $environment = ($connection['environment'] ?? 'production') === 'development' ? 'Development' : 'Production';

    $subject = sprintf('Pairing code request · %s', $host);
    $body = implode(
        "\r\n",
        [
            'Hi Staark,',
            '',
            'Please send a pairing code so this website can connect to Staark Hub.',
            '',
            'Website: ' . home_url('/'),
            'Site name: ' . wp_strip_all_tags((string) get_bloginfo('name')),
            'Site ID: ' . (string) ($connection['site_id'] ?? ''),
            'Environment: ' . $environment . ' (' . (string) ($connection['hub_url'] ?? '') . ')',
            'WordPress: ' . (string) get_bloginfo('version'),
            'Staark Hub: ' . STAARK_HUB_VERSION,
            'Requested by: ' . ($user instanceof WP_User && $user->exists() ? $user->display_name . ' <' . $user->user_email . '>' : ''),
            '',
            'Thanks!',
        ]
    );

    return 'mailto:' . staark_hub_contact_email()
        . '?subject=' . rawurlencode($subject)
        . '&body=' . rawurlencode($body);
}

/**
 * Connection state for the WordPress -> Staark Hub bridge.
 *
 * The site secret is a revocable per-site integration secret, not a WordPress
 * password. It is never rendered in the admin UI and is only sent during the
 * one-time pairing request over HTTPS. Subsequent requests use HMAC signing.
 *
 * @return array{environment:string,hub_url:string,site_id:string,site_secret:string,status:string,remote_site_id:string,last_checked:string,last_error:string}
 */
function staark_hub_connection(): array
{
    $saved = get_option('staark_hub_connection', []);
    if (! is_array($saved)) {
        $saved = [];
    }

    $defaults = [
        'environment' => 'production',
        'hub_url' => 'https://staarkinc.com',
        'site_id' => '',
        'site_secret' => '',
        'status' => 'disconnected',
        'remote_site_id' => '',
        'last_checked' => '',
        'last_error' => '',
    ];

    $connection = array_merge($defaults, $saved);
    $connection['environment'] = in_array((string) $connection['environment'], ['production', 'development'], true)
        ? (string) $connection['environment']
        : 'production';
    $connection['hub_url'] = untrailingslashit(esc_url_raw((string) $connection['hub_url']));
    $connection['site_id'] = sanitize_text_field((string) $connection['site_id']);
    $connection['site_secret'] = sanitize_text_field((string) $connection['site_secret']);
    $connection['status'] = in_array((string) $connection['status'], ['disconnected', 'connected', 'error'], true)
        ? (string) $connection['status']
        : 'disconnected';
    $connection['remote_site_id'] = sanitize_text_field((string) $connection['remote_site_id']);
    $connection['last_checked'] = sanitize_text_field((string) $connection['last_checked']);
    $connection['last_error'] = sanitize_text_field((string) $connection['last_error']);

    return $connection;
}

/**
 * WordPress runtime environment used to protect insecure connector URLs.
 */
function staark_hub_runtime_environment(): string
{
    if (function_exists('wp_get_environment_type')) {
        return (string) wp_get_environment_type();
    }

    return defined('WP_ENVIRONMENT_TYPE') ? sanitize_key((string) WP_ENVIRONMENT_TYPE) : 'production';
}

/**
 * Plain HTTP is only permitted when both the connector and WordPress itself are
 * explicitly running in a local/development environment. Production remains
 * HTTPS-only even if the option is modified outside the Staark Hub UI.
 */
function staark_hub_connection_allows_http(array $connection): bool
{
    return ($connection['environment'] ?? 'production') === 'development'
        && in_array(staark_hub_runtime_environment(), ['local', 'development'], true);
}

/**
 * Make sure every WordPress installation has a stable integration identity.
 *
 * @return array{environment:string,hub_url:string,site_id:string,site_secret:string,status:string,remote_site_id:string,last_checked:string,last_error:string}
 */
function staark_hub_connection_ensure_identity(): array
{
    $connection = staark_hub_connection();
    $changed = false;

    if ($connection['site_id'] === '') {
        $connection['site_id'] = wp_generate_uuid4();
        $changed = true;
    }

    if ($connection['site_secret'] === '') {
        try {
            $connection['site_secret'] = bin2hex(random_bytes(32));
        } catch (Throwable $error) {
            $connection['site_secret'] = wp_generate_password(64, false, false);
        }
        $changed = true;
    }

    if ($connection['environment'] === 'production' && $connection['hub_url'] !== 'https://staarkinc.com') {
        $connection['hub_url'] = 'https://staarkinc.com';
        $changed = true;
    } elseif ($connection['hub_url'] === '') {
        $connection['hub_url'] = 'https://staarkinc.com';
        $changed = true;
    }

    if ($changed) {
        update_option('staark_hub_connection', $connection, false);
    }

    return $connection;
}

function staark_hub_connection_is_connected(): bool
{
    $connection = staark_hub_connection_ensure_identity();

    return $connection['remote_site_id'] !== '' && in_array($connection['status'], ['connected', 'error'], true);
}

/**
 * Return support requests waiting to be pushed to the main Staark Hub.
 *
 * Staark Core is a managed-site support agent, not a client CRM. Lead capture
 * therefore stays outside the core connector and only support tickets are
 * queued here.
 *
 * @return array{tickets:int,total:int,ticket_ids:int[]}
 */
function staark_hub_sync_queue(): array
{
    $ticket_ids = get_posts(
        [
            'post_type' => 'staark_ticket',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'ASC',
            'suppress_filters' => true,
        ]
    );

    $ticket_ids = array_values(
        array_filter(
            array_map('intval', $ticket_ids),
            static fn (int $id): bool => (string) get_post_meta($id, '_staark_ticket_sync_state', true) !== 'synced'
        )
    );

    return [
        'tickets' => count($ticket_ids),
        'total' => count($ticket_ids),
        'ticket_ids' => $ticket_ids,
    ];
}

/**
 * Public site metadata sent to Staark Hub during pairing and health checks.
 *
 * @return array<string,mixed>
 */
function staark_hub_connection_site_payload(): array
{
    $connection = staark_hub_connection_ensure_identity();
    $environment = staark_hub_support_environment();

    return [
        'siteId' => $connection['site_id'],
        'siteUrl' => home_url('/'),
        'siteName' => (string) get_bloginfo('name'),
        'adminUrl' => admin_url('/'),
        'wordpressVersion' => (string) get_bloginfo('version'),
        'phpVersion' => PHP_VERSION,
        'hubVersion' => STAARK_HUB_VERSION,
        'theme' => (string) $environment['theme'],
        'locale' => get_locale(),
        'timezone' => wp_timezone_string(),
        'capabilities' => [
            'support' => true,
            'security' => staark_hub_module_enabled('security', true),
            'seo' => staark_hub_module_enabled('seo', true),
            'performance' => staark_hub_module_enabled('performance', true),
            'forms' => true,
            'managed' => true,
            'updates' => true,
        ],
        'security' => function_exists('staark_hub_security_summary') ? staark_hub_security_summary() : null,
        'seo' => function_exists('staark_hub_seo_summary') ? staark_hub_seo_summary() : null,
        'performance' => function_exists('staark_hub_performance_summary') ? staark_hub_performance_summary() : null,
        'forms' => function_exists('staark_hub_forms_summary') ? staark_hub_forms_summary() : null,
        'managed' => function_exists('staark_hub_managed_summary') ? staark_hub_managed_summary() : null,
        'updates' => function_exists('staark_hub_update_summary') ? staark_hub_update_summary() : null,
    ];
}

/**
 * Signed request client for the Staark WordPress connector.
 *
 * Expected API namespace on Staark Hub:
 *   POST /api/hub/wordpress/connect  (pairing code, unsigned)
 *   GET  /api/hub/wordpress/ping     (signed)
 *   POST /api/hub/wordpress/sync     (signed)
 *
 * @return array{ok:bool,status:int,data:array<string,mixed>,error:string}
 */
function staark_hub_connection_request(string $path, string $method = 'GET', array $payload = [], bool $signed = true): array
{
    $connection = staark_hub_connection_ensure_identity();
    $base_url = (string) apply_filters('staark_hub_api_base_url', $connection['hub_url']);
    $base_url = untrailingslashit(esc_url_raw($base_url));
    $path = '/' . ltrim($path, '/');

    $scheme = strtolower((string) wp_parse_url($base_url, PHP_URL_SCHEME));
    $host = (string) wp_parse_url($base_url, PHP_URL_HOST);
    $http_allowed = $scheme === 'http' && staark_hub_connection_allows_http($connection);

    if ($base_url === '' || $host === '' || ($scheme !== 'https' && ! $http_allowed)) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => [],
            'error' => 'Staark Hub API must use HTTPS. Plain HTTP is allowed only for an explicitly configured local/development connector.',
        ];
    }

    $url = $base_url . $path;
    $method = strtoupper($method);
    $body = $payload === [] && $method === 'GET' ? '' : wp_json_encode($payload);
    if (! is_string($body)) {
        $body = '';
    }

    $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'X-Staark-Site-ID' => $connection['site_id'],
        'X-Staark-Hub-Version' => STAARK_HUB_VERSION,
    ];

    if ($signed) {
        $timestamp = (string) time();
        $body_hash = hash('sha256', $body);
        $signature_payload = implode("\n", [$method, $path, $timestamp, $body_hash]);
        $headers['X-Staark-Timestamp'] = $timestamp;
        $headers['X-Staark-Signature'] = hash_hmac('sha256', $signature_payload, $connection['site_secret']);
    }

    $args = [
        'method' => $method,
        'timeout' => 12,
        'redirection' => 2,
        'sslverify' => true,
        'headers' => $headers,
        'user-agent' => 'Staark-WordPress/' . STAARK_HUB_VERSION . '; ' . home_url('/'),
    ];

    if ($body !== '') {
        $args['body'] = $body;
    }

    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
        return ['ok' => false, 'status' => 0, 'data' => [], 'error' => $response->get_error_message()];
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $raw = (string) wp_remote_retrieve_body($response);
    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : [];
    $ok = $status >= 200 && $status < 300 && (! isset($data['ok']) || $data['ok'] === true);

    $error = '';
    if (! $ok) {
        if (isset($data['error']) && is_string($data['error'])) {
            $error = sanitize_text_field($data['error']);
        } elseif ($status === 404) {
            $error = 'The Staark WordPress connector endpoint is not deployed on the Hub yet.';
        } elseif ($status > 0) {
            $error = 'Staark Hub returned HTTP ' . $status . '.';
        } else {
            $error = 'Could not reach Staark Hub.';
        }
    }

    return ['ok' => $ok, 'status' => $status, 'data' => $data, 'error' => $error];
}

/**
 * Serialize a local support request for the Hub sync endpoint.
 *
 * @return array<string,mixed>
 */
function staark_hub_sync_ticket_payload(int $ticket_id): array
{
    $ticket = get_post($ticket_id);
    if (! $ticket instanceof WP_Post || $ticket->post_type !== 'staark_ticket') {
        return [];
    }

    return [
        'localId' => $ticket_id,
        'label' => staark_hub_support_ticket_label($ticket_id),
        'subject' => $ticket->post_title,
        'message' => $ticket->post_content,
        'category' => (string) get_post_meta($ticket_id, '_staark_ticket_category', true),
        'priority' => (string) get_post_meta($ticket_id, '_staark_ticket_priority', true),
        'status' => (string) get_post_meta($ticket_id, '_staark_ticket_status', true),
        'contactName' => (string) get_post_meta($ticket_id, '_staark_ticket_contact_name', true),
        'contactEmail' => (string) get_post_meta($ticket_id, '_staark_ticket_contact_email', true),
        'environment' => get_post_meta($ticket_id, '_staark_ticket_environment', true),
        'createdAt' => get_post_time(DATE_ATOM, true, $ticket),
    ];
}

/**
 * Exchange support state with the main Staark Hub.
 *
 * Pending local requests are pushed and acknowledged, while status changes and
 * public Staark replies are pulled back into the local ticket copy. A request is
 * still made when there is no local queue so remote updates can be received.
 *
 * @return array{ok:bool,synced:int,received:int,error:string}
 */
function staark_hub_sync_pending_records(): array
{
    if (! staark_hub_connection_is_connected()) {
        return [
            'ok' => false,
            'synced' => 0,
            'received' => 0,
            'error' => 'Connect this website before syncing.',
        ];
    }

    $queue = staark_hub_sync_queue();
    $tickets = array_values(array_filter(array_map('staark_hub_sync_ticket_payload', $queue['ticket_ids'])));

    $result = staark_hub_connection_request(
        '/api/hub/wordpress/sync',
        'POST',
        [
            'site' => staark_hub_connection_site_payload(),
            'tickets' => $tickets,
        ]
    );

    if (! $result['ok']) {
        return [
            'ok' => false,
            'synced' => 0,
            'received' => 0,
            'error' => $result['error'],
        ];
    }

    $synced = 0;
    $ack = isset($result['data']['synced']) && is_array($result['data']['synced']) ? $result['data']['synced'] : [];
    $ticket_ids = isset($ack['tickets']) && is_array($ack['tickets']) ? array_map('intval', $ack['tickets']) : [];

    foreach ($ticket_ids as $ticket_id) {
        if (get_post_type($ticket_id) === 'staark_ticket') {
            update_post_meta($ticket_id, '_staark_ticket_sync_state', 'synced');
            update_post_meta($ticket_id, '_staark_ticket_synced_at', current_time('mysql'));
            ++$synced;
        }
    }

    $remote = isset($result['data']['remote']) && is_array($result['data']['remote'])
        ? $result['data']['remote']
        : [];
    $received = function_exists('staark_hub_support_apply_remote_tickets')
        ? staark_hub_support_apply_remote_tickets($remote)
        : 0;

    return [
        'ok' => true,
        'synced' => $synced,
        'received' => $received,
        'error' => '',
    ];
}

/**
 * Branding defaults used before a client saves their own identity.
 *
 * @return array{brand_name:string,tagline:string,primary_color:string,ink_color:string,logo_id:int,site_icon_id:int}
 */
function staark_hub_branding_defaults(): array
{
    $brand_name = trim((string) get_bloginfo('name'));
    $tagline = trim((string) get_bloginfo('description'));

    if ($brand_name === '' || in_array(strtolower($brand_name), ['test blog', 'my wordpress', 'wordpress'], true)) {
        $brand_name = 'Staark Inc';
    }

    if ($tagline === '' || strtolower($tagline) === 'just another wordpress site') {
        $tagline = 'Moderna, snabba och genomtänkta webbplatser.';
    }

    return [
        'brand_name' => $brand_name,
        'tagline' => $tagline,
        'primary_color' => '#2563eb',
        'ink_color' => '#0f172a',
        'logo_id' => (int) get_theme_mod('custom_logo', 0),
        'site_icon_id' => (int) get_option('site_icon', 0),
    ];
}

/**
 * Return saved branding merged with safe defaults.
 *
 * @return array{brand_name:string,tagline:string,primary_color:string,ink_color:string,logo_id:int,site_icon_id:int}
 */
function staark_hub_branding(): array
{
    $defaults = staark_hub_branding_defaults();
    $saved = get_option('staark_hub_branding', []);

    if (! is_array($saved)) {
        $saved = [];
    }

    $branding = array_merge($defaults, $saved);
    $branding['brand_name'] = sanitize_text_field((string) $branding['brand_name']);
    $branding['tagline'] = sanitize_text_field((string) $branding['tagline']);
    $branding['primary_color'] = sanitize_hex_color((string) $branding['primary_color']) ?: $defaults['primary_color'];
    $branding['ink_color'] = sanitize_hex_color((string) $branding['ink_color']) ?: $defaults['ink_color'];
    $branding['logo_id'] = absint($branding['logo_id']);
    $branding['site_icon_id'] = absint($branding['site_icon_id']);

    return $branding;
}

function staark_hub_darken_hex(string $hex, int $amount = 28): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
        return '#1d4ed8';
    }

    $rgb = [];
    for ($i = 0; $i < 6; $i += 2) {
        $rgb[] = max(0, hexdec(substr($hex, $i, 2)) - $amount);
    }

    return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
}

function staark_hub_branding_css(): string
{
    $branding = staark_hub_branding();
    $primary_dark = staark_hub_darken_hex($branding['primary_color']);

    return sprintf(
        ':root{--wp--preset--color--primary:%1$s;--wp--preset--color--primary-dark:%2$s;--wp--preset--color--dark:%3$s;}',
        $branding['primary_color'],
        $primary_dark,
        $branding['ink_color']
    );
}

/**
 * Frontend brand lockup used by the Staark block theme header and footer.
 */
function staark_hub_frontend_brand_shortcode(): string
{
    $branding = staark_hub_branding();
    $logo = '';

    if ($branding['logo_id'] > 0 && wp_attachment_is_image($branding['logo_id'])) {
        $logo = wp_get_attachment_image(
            $branding['logo_id'],
            'medium',
            false,
            [
                'class' => 'staark-brand__logo',
                'alt' => '',
                'loading' => 'eager',
                'decoding' => 'async',
            ]
        );
    }

    if ($logo === '') {
        $logo = '<svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>';
    }

    return sprintf(
        '<a class="staark-brand" aria-label="%1$s" href="%2$s">%3$s<strong>%4$s</strong><span aria-hidden="true"></span></a>',
        esc_attr(sprintf(__('%s home', 'staark-core'), $branding['brand_name'])),
        esc_url(home_url('/')),
        $logo,
        esc_html($branding['brand_name'])
    );
}

function staark_hub_frontend_tagline_shortcode(): string
{
    $branding = staark_hub_branding();

    return '<p class="staark-brand-tagline has-muted-color has-text-color">' . esc_html($branding['tagline']) . '</p>';
}

function staark_hub_frontend_copyright_shortcode(): string
{
    $branding = staark_hub_branding();

    return sprintf(
        '<p class="staark-brand-copyright has-text-align-center has-muted-color has-text-color has-sm-font-size">© %1$s %2$s. %3$s</p>',
        esc_html(wp_date('Y')),
        esc_html($branding['brand_name']),
        esc_html__('All rights reserved.', 'staark-core')
    );
}

add_shortcode('staark_brand', 'staark_hub_frontend_brand_shortcode');
add_shortcode('staark_brand_tagline', 'staark_hub_frontend_tagline_shortcode');
add_shortcode('staark_brand_copyright', 'staark_hub_frontend_copyright_shortcode');

add_action('wp_enqueue_scripts', static function (): void {
    wp_add_inline_style('staark-theme', staark_hub_branding_css());
}, 30);

add_action('enqueue_block_editor_assets', static function (): void {
    wp_add_inline_style('wp-edit-blocks', staark_hub_branding_css());
});

add_action('init', static function (): void {
    register_post_type(
        'staark_ticket',
        [
            'labels' => [
                'name' => __('Staark Support Tickets', 'staark-core'),
                'singular_name' => __('Staark Support Ticket', 'staark-core'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => false,
            'supports' => ['title', 'editor', 'author'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]
    );
});

add_action('admin_menu', static function (): void {
    add_menu_page(
        __('Staark Website Hub', 'staark-core'),
        __('Staark Hub', 'staark-core'),
        'manage_options',
        STAARK_HUB_SLUG,
        'staark_hub_render_overview',
        staark_hub_menu_icon(),
        3
    );

    add_submenu_page(STAARK_HUB_SLUG, __('Overview', 'staark-core'), __('Overview', 'staark-core'), 'manage_options', STAARK_HUB_SLUG, 'staark_hub_render_overview');
    add_submenu_page(STAARK_HUB_SLUG, __('Website', 'staark-core'), __('Website', 'staark-core'), 'manage_options', 'staark-hub-website', 'staark_hub_render_website');
    add_submenu_page(STAARK_HUB_SLUG, __('Security', 'staark-core'), __('Security', 'staark-core'), 'manage_options', 'staark-hub-security', 'staark_hub_render_security');
    add_submenu_page(STAARK_HUB_SLUG, __('SEO', 'staark-core'), __('SEO', 'staark-core'), 'manage_options', 'staark-hub-seo', 'staark_hub_render_seo');
    add_submenu_page(STAARK_HUB_SLUG, __('Performance', 'staark-core'), __('Performance', 'staark-core'), 'manage_options', 'staark-hub-performance', 'staark_hub_render_performance');
    add_submenu_page(STAARK_HUB_SLUG, __('Forms & Submissions', 'staark-core'), __('Forms', 'staark-core'), 'manage_options', 'staark-hub-forms', 'staark_hub_render_forms');
    add_submenu_page(STAARK_HUB_SLUG, __('Support', 'staark-core'), __('Support', 'staark-core'), 'manage_options', 'staark-hub-support', 'staark_hub_render_support');
    add_submenu_page(STAARK_HUB_SLUG, __('Branding', 'staark-core'), __('Branding', 'staark-core'), 'manage_options', 'staark-hub-branding', 'staark_hub_render_branding');
    add_submenu_page(STAARK_HUB_SLUG, __('Updates', 'staark-core'), __('Updates', 'staark-core'), 'manage_options', 'staark-hub-updates', 'staark_hub_render_updates');
    add_submenu_page(STAARK_HUB_SLUG, __('Managed Mode', 'staark-core'), __('Managed', 'staark-core'), 'manage_options', 'staark-hub-managed', 'staark_hub_render_managed');
    add_submenu_page(STAARK_HUB_SLUG, __('Connect', 'staark-core'), __('Connect to Staark', 'staark-core'), 'manage_options', 'staark-hub-connect', 'staark_hub_render_connect');
});

add_action('admin_init', static function (): void {
    if (staark_hub_current_page() !== 'staark-hub-leads' || ! current_user_can('manage_options')) {
        return;
    }

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-support&staark_support=leads_removed'));
    exit;
});

add_action('admin_enqueue_scripts', static function (string $hook_suffix): void {
    if (strpos($hook_suffix, 'staark-hub') === false) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-admin',
        staark_hub_runtime_url('assets/admin.css'),
        [],
        is_file(STAARK_HUB_PLUGIN_DIR . 'assets/admin.css')
            ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/admin.css')
            : STAARK_HUB_VERSION
    );

    /*
     * Hub UI 2 — shared shell (header, navigation, cards) and the redesigned
     * Overview / Connect screens. Loaded after admin.css on every Hub page.
     */
    wp_enqueue_style(
        'staark-hub-ui',
        staark_hub_runtime_url('assets/hub-ui.css'),
        ['staark-hub-admin'],
        is_file(STAARK_HUB_PLUGIN_DIR . 'assets/hub-ui.css')
            ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/hub-ui.css')
            : STAARK_HUB_VERSION
    );

    wp_enqueue_script(
        'staark-hub-ui',
        staark_hub_runtime_url('assets/hub-ui.js'),
        [],
        is_file(STAARK_HUB_PLUGIN_DIR . 'assets/hub-ui.js')
            ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/hub-ui.js')
            : STAARK_HUB_VERSION,
        true
    );

    // The Forms screen styles live in forms.css, which was only loaded on the frontend.
    if (staark_hub_current_page() === 'staark-hub-forms') {
        wp_enqueue_style(
            'staark-hub-forms-admin',
            staark_hub_runtime_url('assets/forms.css'),
            ['staark-hub-admin'],
            is_file(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
                ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
                : STAARK_HUB_VERSION
        );
    }

    if (staark_hub_current_page() === 'staark-hub-security') {
        wp_enqueue_style(
            'staark-hub-security',
            staark_hub_runtime_url('assets/security.css'),
            ['staark-hub-admin'],
            STAARK_HUB_VERSION
        );
    }

    if (staark_hub_current_page() === 'staark-hub-seo') {
        wp_enqueue_style(
            'staark-hub-seo',
            staark_hub_runtime_url('assets/seo.css'),
            ['staark-hub-admin'],
            STAARK_HUB_VERSION
        );
    }

    if (staark_hub_current_page() === 'staark-hub-performance') {
        wp_enqueue_style(
            'staark-hub-performance',
            staark_hub_runtime_url('assets/performance.css'),
            ['staark-hub-admin'],
            STAARK_HUB_VERSION
        );
    }


    if (staark_hub_current_page() === 'staark-hub-forms') {
        wp_enqueue_style(
            'staark-hub-forms',
            staark_hub_runtime_url('assets/forms.css'),
            ['staark-hub-admin'],
            is_file(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
                ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
                : STAARK_HUB_VERSION
        );
    }

    if (staark_hub_current_page() === 'staark-hub-managed') {
        wp_enqueue_style(
            'staark-hub-managed',
            staark_hub_runtime_url('assets/managed.css'),
            ['staark-hub-admin'],
            STAARK_HUB_VERSION
        );
    }

    if (staark_hub_current_page() === 'staark-hub-updates') {
        wp_enqueue_style(
            'staark-hub-updates',
            staark_hub_runtime_url('assets/updates.css'),
            ['staark-hub-admin'],
            STAARK_HUB_VERSION
        );
    }

    if (in_array(staark_hub_current_page(), ['staark-hub-branding', 'staark-hub-seo'], true)) {
        wp_enqueue_media();
        wp_enqueue_script(
            'staark-hub-admin',
            staark_hub_runtime_url('assets/admin.js'),
            [],
            STAARK_HUB_VERSION,
            true
        );
    }
});

add_filter('admin_body_class', static function (string $classes): string {
    if (staark_hub_is_admin_page()) {
        $classes .= ' staark-hub-admin-page';
    }

    return $classes;
});

add_action('admin_post_staark_install_starter_pages', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_install_starter_pages');

    $created = 0;

    foreach (staark_hub_starter_pages() as $slug => $page) {
        if (get_page_by_path($slug, OBJECT, 'page') instanceof WP_Post) {
            continue;
        }

        $result = wp_insert_post(
            [
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_content' => $page['content'],
            ],
            true
        );

        if (! is_wp_error($result)) {
            ++$created;
        }
    }

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'staark-hub-website',
                'staark_installed' => $created,
            ],
            admin_url('admin.php')
        )
    );
    exit;
});

add_action('admin_post_staark_create_starter_page', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    $slug = isset($_POST['starter_slug']) ? sanitize_key(wp_unslash($_POST['starter_slug'])) : '';
    $pages = staark_hub_starter_pages();

    if ($slug === '' || ! isset($pages[$slug])) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-website&staark_page=invalid'));
        exit;
    }

    check_admin_referer('staark_create_starter_page_' . $slug);

    if (get_page_by_path($slug, OBJECT, 'page') instanceof WP_Post) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-website&staark_page=exists'));
        exit;
    }

    $page = $pages[$slug];
    $result = wp_insert_post(
        [
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $page['title'],
            'post_name' => $slug,
            'post_content' => $page['content'],
        ],
        true
    );

    $status = is_wp_error($result) ? 'error' : 'created';
    wp_safe_redirect(admin_url('admin.php?page=staark-hub-website&staark_page=' . $status));
    exit;
});


add_action('admin_post_staark_submit_support_ticket', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_submit_support_ticket');

    $categories = staark_hub_support_categories();
    $priorities = staark_hub_support_priorities();
    $subject = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
    $category = isset($_POST['category']) ? sanitize_key(wp_unslash($_POST['category'])) : 'technical';
    $priority = isset($_POST['priority']) ? sanitize_key(wp_unslash($_POST['priority'])) : 'normal';
    $contact_name = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
    $contact_email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';

    if (! isset($categories[$category])) {
        $category = 'technical';
    }

    if (! isset($priorities[$priority])) {
        $priority = 'normal';
    }

    if ($subject === '' || $message === '' || ! is_email($contact_email)) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-support&staark_support=invalid'));
        exit;
    }

    $environment = staark_hub_support_environment();
    $ticket_id = wp_insert_post(
        [
            'post_type' => 'staark_ticket',
            'post_status' => 'private',
            'post_title' => $subject,
            'post_content' => $message,
            'post_author' => get_current_user_id(),
        ],
        true
    );

    if (is_wp_error($ticket_id)) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-support&staark_support=error'));
        exit;
    }

    update_post_meta($ticket_id, '_staark_ticket_category', $category);
    update_post_meta($ticket_id, '_staark_ticket_priority', $priority);
    update_post_meta($ticket_id, '_staark_ticket_status', 'open');
    update_post_meta($ticket_id, '_staark_ticket_contact_name', $contact_name);
    update_post_meta($ticket_id, '_staark_ticket_contact_email', $contact_email);
    update_post_meta($ticket_id, '_staark_ticket_environment', $environment);
    update_post_meta($ticket_id, '_staark_ticket_channel', 'local');
    update_post_meta($ticket_id, '_staark_ticket_sync_state', 'pending');

    $recipient = (string) apply_filters('staark_hub_support_email', staark_hub_contact_email());
    $ticket_label = staark_hub_support_ticket_label((int) $ticket_id);
    $mail_subject = sprintf('[%s] %s · %s', $ticket_label, $priorities[$priority], $subject);
    $mail_body = implode(
        "\n",
        [
            'New support request from Staark Website Hub',
            '',
            'Ticket: ' . $ticket_label,
            'Category: ' . $categories[$category],
            'Priority: ' . $priorities[$priority],
            'Contact: ' . ($contact_name !== '' ? $contact_name : 'Not provided'),
            'Email: ' . $contact_email,
            'Website: ' . $environment['site_url'],
            '',
            $message,
            '',
            'Environment',
            'WordPress: ' . $environment['wordpress'],
            'PHP: ' . $environment['php'],
            'Theme: ' . $environment['theme'],
            'Staark Hub: ' . $environment['hub'],
            'Pending updates: ' . $environment['updates'],
        ]
    );

    $headers = ['Reply-To: ' . ($contact_name !== '' ? $contact_name . ' <' . $contact_email . '>' : $contact_email)];
    $notified = is_email($recipient) ? wp_mail($recipient, $mail_subject, $mail_body, $headers) : false;
    update_post_meta($ticket_id, '_staark_ticket_email_notified', $notified ? 'yes' : 'no');

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'staark-hub-support',
                'staark_support' => 'created',
                'ticket' => (int) $ticket_id,
            ],
            admin_url('admin.php')
        )
    );
    exit;
});


add_action('admin_post_staark_save_connection_settings', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_save_connection_settings');

    $connection = staark_hub_connection_ensure_identity();
    if (staark_hub_connection_is_connected()) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=disconnect_first'));
        exit;
    }

    $environment = isset($_POST['connector_environment'])
        ? sanitize_key(wp_unslash($_POST['connector_environment']))
        : 'production';
    $environment = $environment === 'development' ? 'development' : 'production';

    if ($environment === 'production') {
        $hub_url = 'https://staarkinc.com';
    } else {
        $hub_url = isset($_POST['hub_url'])
            ? untrailingslashit(esc_url_raw(wp_unslash($_POST['hub_url'])))
            : '';
        $scheme = strtolower((string) wp_parse_url($hub_url, PHP_URL_SCHEME));
        $host = (string) wp_parse_url($hub_url, PHP_URL_HOST);

        if ($hub_url === '' || $host === '' || ! in_array($scheme, ['http', 'https'], true)) {
            wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=invalid_url'));
            exit;
        }

        if ($scheme === 'http' && ! in_array(staark_hub_runtime_environment(), ['local', 'development'], true)) {
            wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=insecure_url'));
            exit;
        }
    }

    $connection['environment'] = $environment;
    $connection['hub_url'] = $hub_url;
    $connection['status'] = 'disconnected';
    $connection['remote_site_id'] = '';
    $connection['last_checked'] = '';
    $connection['last_error'] = '';
    update_option('staark_hub_connection', $connection, false);

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=settings_saved'));
    exit;
});

add_action('admin_post_staark_connect_site', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_connect_site');
    $pairing_code = isset($_POST['pairing_code']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['pairing_code']))) : '';
    $pairing_code = preg_replace('/[^A-Z0-9-]/', '', $pairing_code) ?: '';

    if ($pairing_code === '' || strlen($pairing_code) < 6) {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=invalid'));
        exit;
    }

    $connection = staark_hub_connection_ensure_identity();
    $result = staark_hub_connection_request(
        '/api/hub/wordpress/connect',
        'POST',
        [
            'pairingCode' => $pairing_code,
            'site' => staark_hub_connection_site_payload(),
            'siteSecret' => $connection['site_secret'],
        ],
        false
    );

    $connection['last_checked'] = current_time('mysql');
    $connection['last_error'] = $result['ok'] ? '' : $result['error'];

    if ($result['ok']) {
        $connection['status'] = 'connected';
        $connection['remote_site_id'] = isset($result['data']['siteId'])
            ? sanitize_text_field((string) $result['data']['siteId'])
            : $connection['site_id'];
    } else {
        $connection['status'] = 'error';
    }

    update_option('staark_hub_connection', $connection, false);
    wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=' . ($result['ok'] ? 'connected' : 'error')));
    exit;
});

add_action('admin_post_staark_test_connection', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_test_connection');
    $connection = staark_hub_connection_ensure_identity();
    $result = staark_hub_connection_request('/api/hub/wordpress/ping', 'GET');
    $connection['last_checked'] = current_time('mysql');
    $connection['last_error'] = $result['ok'] ? '' : $result['error'];
    $connection['status'] = $result['ok'] ? 'connected' : 'error';
    update_option('staark_hub_connection', $connection, false);

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=' . ($result['ok'] ? 'healthy' : 'error')));
    exit;
});

add_action('admin_post_staark_sync_now', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_sync_now');
    $result = staark_hub_sync_pending_records();
    $connection = staark_hub_connection_ensure_identity();
    $connection['last_checked'] = current_time('mysql');
    $connection['last_error'] = $result['ok'] ? '' : $result['error'];
    if (! $result['ok'] && $connection['remote_site_id'] !== '') {
        $connection['status'] = 'error';
    } elseif ($result['ok'] && $connection['remote_site_id'] !== '') {
        $connection['status'] = 'connected';
    }
    update_option('staark_hub_connection', $connection, false);

    $return_to = isset($_POST['return_to']) ? sanitize_key(wp_unslash($_POST['return_to'])) : '';
    $return_ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;

    if (
        $return_to === 'support_ticket'
        && $return_ticket_id > 0
        && get_post_type($return_ticket_id) === 'staark_ticket'
        && function_exists('staark_hub_support_ticket_detail_url')
    ) {
        wp_safe_redirect(
            add_query_arg(
                [
                    'staark_support' => $result['ok'] ? 'synced' : 'sync_error',
                    'synced' => (int) $result['synced'],
                    'received' => (int) $result['received'],
                ],
                staark_hub_support_ticket_detail_url($return_ticket_id)
            )
        );
        exit;
    }

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'staark-hub-connect',
                'staark_connect' => $result['ok'] ? 'synced' : 'sync_error',
                'synced' => (int) $result['synced'],
                'received' => (int) $result['received'],
            ],
            admin_url('admin.php')
        )
    );
    exit;
});

add_action('admin_post_staark_disconnect_site', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_disconnect_site');
    $connection = staark_hub_connection_ensure_identity();
    $connection['status'] = 'disconnected';
    $connection['remote_site_id'] = '';
    $connection['last_checked'] = current_time('mysql');
    $connection['last_error'] = '';
    update_option('staark_hub_connection', $connection, false);

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=disconnected'));
    exit;
});

add_action('admin_post_staark_regenerate_site_identity', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_regenerate_site_identity');
    $connection = staark_hub_connection_ensure_identity();

    if ($connection['status'] === 'connected') {
        wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=disconnect_first'));
        exit;
    }

    $connection['site_id'] = wp_generate_uuid4();
    try {
        $connection['site_secret'] = bin2hex(random_bytes(32));
    } catch (Throwable $error) {
        $connection['site_secret'] = wp_generate_password(64, false, false);
    }
    $connection['status'] = 'disconnected';
    $connection['remote_site_id'] = '';
    $connection['last_checked'] = '';
    $connection['last_error'] = '';
    update_option('staark_hub_connection', $connection, false);

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-connect&staark_connect=identity_regenerated'));
    exit;
});

add_action('admin_post_staark_save_branding', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_save_branding');

    $defaults = staark_hub_branding_defaults();
    $brand_name = isset($_POST['brand_name']) ? sanitize_text_field(wp_unslash($_POST['brand_name'])) : '';
    $tagline = isset($_POST['tagline']) ? sanitize_text_field(wp_unslash($_POST['tagline'])) : '';
    $primary_color = isset($_POST['primary_color']) ? sanitize_hex_color(wp_unslash($_POST['primary_color'])) : '';
    $ink_color = isset($_POST['ink_color']) ? sanitize_hex_color(wp_unslash($_POST['ink_color'])) : '';
    $logo_id = isset($_POST['logo_id']) ? absint($_POST['logo_id']) : 0;
    $site_icon_id = isset($_POST['site_icon_id']) ? absint($_POST['site_icon_id']) : 0;

    if ($brand_name === '') {
        $brand_name = $defaults['brand_name'];
    }

    if ($tagline === '') {
        $tagline = $defaults['tagline'];
    }

    if (! $primary_color) {
        $primary_color = $defaults['primary_color'];
    }

    if (! $ink_color) {
        $ink_color = $defaults['ink_color'];
    }

    if ($logo_id > 0 && ! wp_attachment_is_image($logo_id)) {
        $logo_id = 0;
    }

    if ($site_icon_id > 0 && ! wp_attachment_is_image($site_icon_id)) {
        $site_icon_id = 0;
    }

    $branding = [
        'brand_name' => $brand_name,
        'tagline' => $tagline,
        'primary_color' => $primary_color,
        'ink_color' => $ink_color,
        'logo_id' => $logo_id,
        'site_icon_id' => $site_icon_id,
    ];

    update_option('staark_hub_branding', $branding, false);
    update_option('blogname', $brand_name);
    update_option('blogdescription', $tagline);

    if ($logo_id > 0) {
        set_theme_mod('custom_logo', $logo_id);
    } else {
        remove_theme_mod('custom_logo');
    }

    if ($site_icon_id > 0) {
        update_option('site_icon', $site_icon_id);
    } else {
        delete_option('site_icon');
    }

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-branding&staark_branding=saved'));
    exit;
});

function staark_hub_brand_markup(): void
{
    ?>
    <div class="staark-hub-brand" aria-label="Staark Inc.">
        <span class="staark-hub-brand-mark" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m18 16 4-4-4-4"></path>
                <path d="m6 8-4 4 4 4"></path>
                <path d="m14.5 4-5 16"></path>
            </svg>
        </span>
        <strong>Staark Hub</strong>
    </div>
    <?php
}

/**
 * Hub sections shown in the in-page navigation.
 *
 * @return array<string,string>
 */
function staark_hub_nav_items(): array
{
    $items = [
        STAARK_HUB_SLUG => __('Overview', 'staark-core'),
        'staark-hub-website' => __('Website', 'staark-core'),
        'staark-hub-forms' => __('Forms', 'staark-core'),
        'staark-hub-security' => __('Security', 'staark-core'),
        'staark-hub-seo' => __('SEO', 'staark-core'),
        'staark-hub-performance' => __('Performance', 'staark-core'),
        'staark-hub-support' => __('Support', 'staark-core'),
        'staark-hub-branding' => __('Branding', 'staark-core'),
        'staark-hub-updates' => __('Updates', 'staark-core'),
        'staark-hub-managed' => __('Managed', 'staark-core'),
        'staark-hub-first-install' => __('First Install', 'staark-core'),
        'staark-hub-connect' => __('Connect', 'staark-core'),
    ];

    if (! function_exists('staark_hub_render_forms')) {
        unset($items['staark-hub-forms']);
    }

    if (! function_exists('staark_hub_render_first_install')) {
        unset($items['staark-hub-first-install']);
    }

    if (function_exists('staark_hub_managed_client_restrictions_apply') && staark_hub_managed_client_restrictions_apply()) {
        unset($items['staark-hub-managed']);
    }

    return (array) apply_filters('staark_hub_nav_items', $items);
}

/**
 * One-line description under each section title.
 */
function staark_hub_section_intro(string $section): string
{
    $intros = [
        'Overview' => __('Setup status, shortcuts and everything Staark manages for this website.', 'staark-core'),
        'Website' => __('Starter pages, homepage and the building blocks of the site.', 'staark-core'),
        'Forms' => __('Contact and booking requests stored safely in WordPress.', 'staark-core'),
        'Security' => __('Health checks and reversible hardening for this installation.', 'staark-core'),
        'SEO' => __('Metadata, schema, sitemap and local business details.', 'staark-core'),
        'Performance' => __('Cache, assets, images and Core Web Vitals readiness.', 'staark-core'),
        'Support' => __('Send a request to Staark with the technical context attached.', 'staark-core'),
        'Branding' => __('Name, logo, icon and colors used on the website.', 'staark-core'),
        'Updates' => __('Staark releases and WordPress updates in one place.', 'staark-core'),
        'Managed Mode' => __('Operator controls, restrictions and managed releases.', 'staark-core'),
        'First Install' => __('Turn a fresh WordPress install into a ready starter site.', 'staark-core'),
        'Connect' => __('Link this website to Staark Hub with a one-time pairing code.', 'staark-core'),
    ];

    return $intros[$section] ?? __('Managed by Staark Inc.', 'staark-core');
}

function staark_hub_nav(): void
{
    $current = staark_hub_current_page();
    $items = staark_hub_nav_items();
    ?>
    <nav class="staark-hub-nav" aria-label="<?php echo esc_attr__('Staark Hub sections', 'staark-core'); ?>">
        <?php foreach ($items as $slug => $label) : ?>
            <a class="staark-hub-nav-link<?php echo $current === $slug ? ' is-active' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>"<?php echo $current === $slug ? ' aria-current="page"' : ''; ?>>
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function staark_hub_header(string $section, string $title = ''): void
{
    $host = (string) (wp_parse_url(home_url('/'), PHP_URL_HOST) ?: home_url('/'));
    $site_name = trim(wp_strip_all_tags((string) get_bloginfo('name')));
    $paired = function_exists('staark_hub_connection_is_connected') && staark_hub_connection_is_connected();
    ?>
    <header class="staark-hub-header">
        <div class="staark-hub-topbar">
            <div class="staark-hub-topbar-start">
                <?php staark_hub_brand_markup(); ?>
                <span class="staark-hub-topbar-divider" aria-hidden="true"></span>
                <a class="staark-hub-site-chip" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener">
                    <i aria-hidden="true"></i>
                    <span><?php echo esc_html($site_name !== '' ? $site_name : $host); ?></span>
                    <small><?php echo esc_html($host); ?></small>
                </a>
            </div>
            <div class="staark-hub-header-actions">
                <a class="staark-hub-connection-chip<?php echo $paired ? ' is-linked' : ''; ?>" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-connect')); ?>">
                    <i aria-hidden="true"></i><?php echo $paired ? esc_html__('Linked to Staark', 'staark-core') : esc_html__('Not linked', 'staark-core'); ?>
                </a>
                <a class="button" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener"><?php echo esc_html__('View site', 'staark-core'); ?> ↗</a>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('site-editor.php')); ?>"><?php echo esc_html__('Site Editor', 'staark-core'); ?></a>
            </div>
        </div>
        <div class="staark-hub-title-row">
            <div>
                <p class="staark-hub-kicker"><?php echo esc_html__('Staark Hub', 'staark-core'); ?> · <?php echo esc_html($section); ?></p>
                <h1><?php echo esc_html($title !== '' ? $title : $section); ?></h1>
                <p class="staark-hub-subtitle"><?php echo esc_html(staark_hub_section_intro($section)); ?></p>
            </div>
        </div>
        <?php staark_hub_nav(); ?>
    </header>
    <?php
}

/**
 * Setup checklist shown on the Overview.
 *
 * @return list<array{label:string,ok:bool,detail:string,url:string,action:string}>
 */
function staark_hub_overview_checklist(): array
{
    $using_https = function_exists('wp_is_using_https') ? wp_is_using_https() : str_starts_with(home_url('/'), 'https://');
    $pretty_permalinks = (string) get_option('permalink_structure') !== '';
    $front_page_id = (int) get_option('page_on_front');
    $static_front = get_option('show_on_front') === 'page' && $front_page_id > 0;
    $theme_front = is_file(get_stylesheet_directory() . '/templates/front-page.html')
        || is_file(get_template_directory() . '/templates/front-page.html');
    $forms_email = function_exists('staark_hub_forms_settings') ? (string) (staark_hub_forms_settings()['recipient_email'] ?? '') : '';
    $seo = function_exists('staark_hub_seo_settings') ? staark_hub_seo_settings() : [];
    $seo_ready = trim((string) ($seo['organization_name'] ?? '')) !== ''
        && (trim((string) ($seo['phone'] ?? '')) !== '' || trim((string) ($seo['street_address'] ?? '')) !== '');
    $paired = staark_hub_connection_is_connected();

    return [
        [
            'label' => __('HTTPS', 'staark-core'),
            'ok' => $using_https,
            'detail' => $using_https ? __('Secure connection active', 'staark-core') : __('Site is served over HTTP', 'staark-core'),
            'url' => admin_url('options-general.php'),
            'action' => __('Settings', 'staark-core'),
        ],
        [
            'label' => __('Homepage', 'staark-core'),
            'ok' => $static_front || $theme_front,
            'detail' => $static_front
                ? sprintf(__('Static page: %s', 'staark-core'), get_the_title($front_page_id))
                : ($theme_front ? __('Theme front-page template', 'staark-core') : __('Latest posts', 'staark-core')),
            'url' => staark_hub_homepage_edit_url(),
            'action' => __('Edit', 'staark-core'),
        ],
        [
            'label' => __('Permalinks', 'staark-core'),
            'ok' => $pretty_permalinks,
            'detail' => $pretty_permalinks ? (string) get_option('permalink_structure') : __('Plain URLs (?p=123)', 'staark-core'),
            'url' => admin_url('options-permalink.php'),
            'action' => __('Fix', 'staark-core'),
        ],
        [
            'label' => __('Form notifications', 'staark-core'),
            'ok' => is_email($forms_email) !== false,
            'detail' => $forms_email !== '' ? $forms_email : __('No recipient set', 'staark-core'),
            'url' => admin_url('admin.php?page=staark-hub-forms'),
            'action' => __('Set up', 'staark-core'),
        ],
        [
            'label' => __('Local business SEO', 'staark-core'),
            'ok' => $seo_ready,
            'detail' => $seo_ready ? (string) $seo['organization_name'] : __('Add name, phone and address', 'staark-core'),
            'url' => admin_url('admin.php?page=staark-hub-seo'),
            'action' => __('Complete', 'staark-core'),
        ],
        [
            'label' => __('Staark connection', 'staark-core'),
            'ok' => $paired,
            'detail' => $paired ? __('Linked with a signed connection', 'staark-core') : __('Needs a pairing code', 'staark-core'),
            'url' => admin_url('admin.php?page=staark-hub-connect'),
            'action' => __('Connect', 'staark-core'),
        ],
    ];
}

/**
 * Module cards for the Overview, derived from the Hub navigation.
 *
 * @return array<string,string>
 */
function staark_hub_module_descriptions(): array
{
    return [
        'staark-hub-website' => __('Starter pages and homepage', 'staark-core'),
        'staark-hub-forms' => __('Contact and booking requests', 'staark-core'),
        'staark-hub-security' => __('Scanner and safe hardening', 'staark-core'),
        'staark-hub-seo' => __('Metadata, schema and sitemap', 'staark-core'),
        'staark-hub-performance' => __('Cache, images and fonts', 'staark-core'),
        'staark-hub-support' => __('Requests to the Staark team', 'staark-core'),
        'staark-hub-branding' => __('Logo, icon and colors', 'staark-core'),
        'staark-hub-updates' => __('Staark and WordPress updates', 'staark-core'),
        'staark-hub-managed' => __('Operator controls and releases', 'staark-core'),
        'staark-hub-first-install' => __('Bootstrap a starter site', 'staark-core'),
        'staark-hub-connect' => __('Link to Staark Hub', 'staark-core'),
    ];
}

function staark_hub_render_overview(): void
{
    $theme = wp_get_theme();
    $published_pages = staark_hub_published_page_count();
    $updates = staark_hub_pending_updates();
    $connection = staark_hub_connection_ensure_identity();
    $sync_queue = staark_hub_sync_queue();
    $paired = staark_hub_connection_is_connected();
    $connection_ok = $paired && $connection['status'] === 'connected';
    $checklist = staark_hub_overview_checklist();
    $done = count(array_filter($checklist, static fn (array $item): bool => $item['ok']));
    $total = count($checklist);
    $progress = $total > 0 ? (int) round($done / $total * 100) : 0;
    $forms = function_exists('staark_hub_forms_summary') ? staark_hub_forms_summary() : null;
    $user = wp_get_current_user();
    $first_name = $user instanceof WP_User && $user->exists()
        ? ($user->first_name !== '' ? $user->first_name : $user->display_name)
        : '';
    $nav = staark_hub_nav_items();
    $descriptions = staark_hub_module_descriptions();
    ?>
    <div class="wrap staark-hub-wrap staark-hub-overview">
        <?php staark_hub_header('Overview', $first_name !== '' ? sprintf(__('Welcome back, %s', 'staark-core'), $first_name) : __('Overview', 'staark-core')); ?>

        <div class="staark-hub-overview-hero">
            <section class="staark-hub-card staark-hub-setup">
                <div class="staark-hub-setup-head">
                    <div>
                        <span class="staark-hub-card-label"><?php echo esc_html__('Website setup', 'staark-core'); ?></span>
                        <h2>
                            <?php
                            echo $done === $total
                                ? esc_html__('Everything essential is in place', 'staark-core')
                                : esc_html(sprintf(__('%1$d of %2$d essentials ready', 'staark-core'), $done, $total));
                            ?>
                        </h2>
                    </div>
                    <span class="staark-hub-setup-percent"><?php echo esc_html($progress . '%'); ?></span>
                </div>
                <div class="staark-hub-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string) $progress); ?>" aria-label="<?php echo esc_attr__('Setup progress', 'staark-core'); ?>">
                    <span style="width:<?php echo esc_attr((string) $progress); ?>%"></span>
                </div>
                <ul class="staark-hub-checklist">
                    <?php foreach ($checklist as $item) : ?>
                        <li class="<?php echo $item['ok'] ? 'is-ok' : 'is-todo'; ?>">
                            <span class="staark-hub-check-icon" aria-hidden="true"><?php echo $item['ok'] ? '✓' : '!'; ?></span>
                            <span class="staark-hub-check-copy">
                                <strong><?php echo esc_html($item['label']); ?></strong>
                                <small><?php echo esc_html($item['detail']); ?></small>
                            </span>
                            <?php if (! $item['ok']) : ?>
                                <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['action']); ?> →</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="staark-hub-card staark-hub-card--dark staark-hub-cloud-card">
                <span class="staark-hub-card-label"><?php echo esc_html__('Staark connection', 'staark-core'); ?></span>
                <?php if ($paired) : ?>
                    <h2><?php echo $connection_ok ? esc_html__('Linked to Staark Hub', 'staark-core') : esc_html__('Connection needs attention', 'staark-core'); ?></h2>
                    <p><?php echo esc_html__('Support requests and website health sync over a signed connection. No WordPress passwords are shared.', 'staark-core'); ?></p>
                    <dl class="staark-hub-cloud-meta">
                        <div><dt><?php echo esc_html__('Waiting to sync', 'staark-core'); ?></dt><dd><?php echo esc_html((string) $sync_queue['total']); ?></dd></div>
                        <div><dt><?php echo esc_html__('Last check', 'staark-core'); ?></dt><dd><?php echo esc_html($connection['last_checked'] !== '' ? $connection['last_checked'] : '—'); ?></dd></div>
                    </dl>
                    <div class="staark-hub-actions">
                        <a class="button staark-hub-dark-button" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-connect')); ?>"><?php echo esc_html__('Manage connection', 'staark-core'); ?></a>
                    </div>
                <?php else : ?>
                    <h2><?php echo esc_html__('Connect this website', 'staark-core'); ?></h2>
                    <p><?php echo esc_html__('Pair once with a single-use code from Staark to enable managed support sync and health checks.', 'staark-core'); ?></p>
                    <div class="staark-hub-actions">
                        <a class="button staark-hub-dark-button" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-connect')); ?>"><?php echo esc_html__('Enter pairing code', 'staark-core'); ?></a>
                    </div>
                    <div class="staark-hub-cloud-request">
                        <strong><?php echo esc_html__('No pairing code yet?', 'staark-core'); ?></strong>
                        <a href="<?php echo esc_url(staark_hub_pairing_request_url($connection), ['mailto']); ?>"><?php echo esc_html(sprintf(__('Request one from %s', 'staark-core'), staark_hub_contact_email())); ?> →</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="staark-hub-grid staark-hub-grid--four staark-hub-stats">
            <a class="staark-hub-card staark-hub-stat-card" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-website')); ?>">
                <span class="staark-hub-card-label"><?php echo esc_html__('Theme', 'staark-core'); ?></span>
                <strong class="staark-hub-metric"><?php echo esc_html($theme->get('Name') ?: 'WordPress'); ?></strong>
                <p><?php echo esc_html(sprintf(__('Version %1$s · WordPress %2$s', 'staark-core'), $theme->get('Version') ?: '—', get_bloginfo('version'))); ?></p>
            </a>
            <a class="staark-hub-card staark-hub-stat-card" href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>">
                <span class="staark-hub-card-label"><?php echo esc_html__('Pages', 'staark-core'); ?></span>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $published_pages); ?></strong>
                <p><?php echo esc_html__('Published pages', 'staark-core'); ?></p>
            </a>
            <?php if (is_array($forms)) : ?>
                <a class="staark-hub-card staark-hub-stat-card" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-forms')); ?>">
                    <span class="staark-hub-card-label"><?php echo esc_html__('Form requests', 'staark-core'); ?></span>
                    <strong class="staark-hub-metric"><?php echo esc_html((string) $forms['new']); ?> <small><?php echo esc_html__('new', 'staark-core'); ?></small></strong>
                    <p><?php echo esc_html(sprintf(_n('%d request in total', '%d requests in total', $forms['total'], 'staark-core'), $forms['total'])); ?></p>
                </a>
            <?php endif; ?>
            <a class="staark-hub-card staark-hub-stat-card" href="<?php echo esc_url(admin_url('update-core.php')); ?>">
                <span class="staark-hub-card-label"><?php echo esc_html__('Updates', 'staark-core'); ?></span>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $updates['total']); ?> <small><?php echo esc_html__('pending', 'staark-core'); ?></small></strong>
                <p><?php echo esc_html(sprintf(__('Core %1$d · Plugins %2$d · Themes %3$d', 'staark-core'), $updates['core'], $updates['plugins'], $updates['themes'])); ?></p>
            </a>
        </div>

        <section class="staark-hub-section">
            <div class="staark-hub-section-heading">
                <div>
                    <span class="staark-hub-card-label"><?php echo esc_html__('Quick actions', 'staark-core'); ?></span>
                    <h2><?php echo esc_html__('Common tasks', 'staark-core'); ?></h2>
                </div>
            </div>

            <div class="staark-hub-quick-grid">
                <?php
                $quick = [
                    [staark_hub_homepage_edit_url(), '⌂', __('Edit homepage', 'staark-core'), __('Content or template', 'staark-core')],
                    [admin_url('post-new.php?post_type=page'), '＋', __('New page', 'staark-core'), __('Add another page', 'staark-core')],
                    [admin_url('edit.php?post_type=page'), '▤', __('All pages', 'staark-core'), __('Edit and publish', 'staark-core')],
                    [admin_url('upload.php'), '▧', __('Media', 'staark-core'), __('Images and files', 'staark-core')],
                    [admin_url('site-editor.php'), '✦', __('Site Editor', 'staark-core'), __('Header, footer, styles', 'staark-core')],
                    [admin_url('admin.php?page=staark-hub-support'), '?', __('Ask Staark', 'staark-core'), __('Create a support request', 'staark-core')],
                ];
                foreach ($quick as $action) :
                    ?>
                    <a class="staark-hub-quick-action" href="<?php echo esc_url($action[0]); ?>">
                        <span class="staark-hub-quick-icon" aria-hidden="true"><?php echo esc_html($action[1]); ?></span>
                        <span><strong><?php echo esc_html($action[2]); ?></strong><small><?php echo esc_html($action[3]); ?></small></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="staark-hub-section">
            <div class="staark-hub-section-heading">
                <div>
                    <span class="staark-hub-card-label"><?php echo esc_html__('Modules', 'staark-core'); ?></span>
                    <h2><?php echo esc_html__('Everything Staark manages', 'staark-core'); ?></h2>
                </div>
            </div>

            <div class="staark-hub-tool-grid">
                <?php foreach ($nav as $slug => $label) :
                    if ($slug === STAARK_HUB_SLUG) {
                        continue;
                    }
                    ?>
                    <a class="staark-hub-tool-card" href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>">
                        <span><strong><?php echo esc_html($label); ?></strong><small><?php echo esc_html($descriptions[$slug] ?? ''); ?></small></span>
                        <b aria-hidden="true">→</b>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <?php staark_hub_help_strip(); ?>
    </div>
    <?php
}

/**
 * "Need a hand?" footer shared by Hub pages.
 */
function staark_hub_help_strip(): void
{
    $email = staark_hub_contact_email();
    ?>
    <aside class="staark-hub-help-strip">
        <div>
            <strong><?php echo esc_html__('Need a hand?', 'staark-core'); ?></strong>
            <span><?php echo esc_html__('The Staark team maintains this website. Send a request from the Hub, or email us directly.', 'staark-core'); ?></span>
        </div>
        <div class="staark-hub-actions">
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-support')); ?>"><?php echo esc_html__('Create support request', 'staark-core'); ?></a>
            <a class="button" href="<?php echo esc_url('mailto:' . $email, ['mailto']); ?>"><?php echo esc_html($email); ?></a>
        </div>
    </aside>
    <?php
}

function staark_hub_render_website(): void
{
    $pages = staark_hub_starter_pages();
    $installed = staark_hub_installed_page_count();
    $published_pages = staark_hub_published_page_count();
    $created = isset($_GET['staark_installed']) ? absint($_GET['staark_installed']) : null;
    $single_status = isset($_GET['staark_page']) ? sanitize_key(wp_unslash($_GET['staark_page'])) : '';
    $theme = wp_get_theme();
    $is_block_theme = function_exists('wp_is_block_theme') ? wp_is_block_theme() : false;
    $front_page_id = (int) get_option('page_on_front');
    $front_mode = get_option('show_on_front') === 'page' && $front_page_id > 0 ? 'Static page' : 'Theme front page';
    $permalink = (string) get_option('permalink_structure');
    $timezone = (string) get_option('timezone_string');
    if ($timezone === '') {
        $timezone = 'UTC' . get_option('gmt_offset');
    }
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Website'); ?>

        <?php if ($created !== null) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf('%d starter page(s) created. Existing pages were left untouched.', $created)); ?></p></div>
        <?php endif; ?>
        <?php if ($single_status === 'created') : ?>
            <div class="notice notice-success is-dismissible"><p>Starter page created successfully.</p></div>
        <?php elseif ($single_status === 'exists') : ?>
            <div class="notice notice-info is-dismissible"><p>That starter page already exists, so nothing was overwritten.</p></div>
        <?php elseif ($single_status === 'error' || $single_status === 'invalid') : ?>
            <div class="notice notice-error is-dismissible"><p>The starter page could not be created. Review the request and try again.</p></div>
        <?php endif; ?>

        <div class="staark-hub-grid staark-hub-grid--four staark-hub-website-summary">
            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Homepage</span><span class="staark-hub-mini-status staark-hub-mini-status--ok">Ready</span></div>
                <strong class="staark-hub-metric">Front page</strong>
                <p><?php echo esc_html($front_mode); ?> · <a href="<?php echo esc_url(staark_hub_homepage_edit_url()); ?>">Edit homepage</a></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Pages</span><span class="staark-hub-mini-status <?php echo $installed === count($pages) ? 'staark-hub-mini-status--ok' : 'staark-hub-mini-status--warn'; ?>"><?php echo $installed === count($pages) ? 'Starter ready' : 'Needs setup'; ?></span></div>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $published_pages); ?> published</strong>
                <p><?php echo esc_html($installed . '/' . count($pages)); ?> Staark starter pages installed.</p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Theme</span><span class="staark-hub-mini-status staark-hub-mini-status--ok">Active</span></div>
                <strong class="staark-hub-metric"><?php echo esc_html($theme->get('Name') ?: 'WordPress'); ?></strong>
                <p>Version <?php echo esc_html($theme->get('Version') ?: '—'); ?> · <?php echo $is_block_theme ? 'Block theme' : 'Classic theme'; ?></p>
            </section>

            <section class="staark-hub-card staark-hub-stat-card">
                <div class="staark-hub-metric-top"><span class="staark-hub-card-label">Site</span><span class="staark-hub-mini-status staark-hub-mini-status--ok">Online</span></div>
                <strong class="staark-hub-metric"><?php echo esc_html(get_bloginfo('language')); ?></strong>
                <p><?php echo esc_html($timezone); ?> · <?php echo $permalink !== '' ? 'Pretty URLs' : 'Plain URLs'; ?></p>
            </section>
        </div>

        <section class="staark-hub-card staark-hub-website-pages">
            <div class="staark-hub-card-heading">
                <div>
                    <span class="staark-hub-card-label">Business Starter</span>
                    <h2>Website pages</h2>
                    <p>See what is installed, jump straight into editing, or create only the page that is missing. Existing content is never overwritten.</p>
                </div>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="staark_install_starter_pages">
                    <?php wp_nonce_field('staark_install_starter_pages'); ?>
                    <?php submit_button($installed === count($pages) ? 'Check / repair pages' : 'Install missing pages', 'primary', 'submit', false); ?>
                </form>
            </div>

            <div class="staark-hub-page-table" role="table" aria-label="Staark starter pages">
                <div class="staark-hub-page-table-head" role="row">
                    <span role="columnheader">Page</span>
                    <span role="columnheader">Status</span>
                    <span role="columnheader">Updated</span>
                    <span role="columnheader">Actions</span>
                </div>

                <?php foreach ($pages as $slug => $page) : ?>
                    <?php $existing = get_page_by_path($slug, OBJECT, 'page'); ?>
                    <div class="staark-hub-page-table-row" role="row">
                        <div class="staark-hub-page-name" role="cell">
                            <strong><?php echo esc_html($page['title']); ?></strong>
                            <code>/<?php echo esc_html($slug); ?></code>
                        </div>

                        <div role="cell">
                            <?php if ($existing instanceof WP_Post) : ?>
                                <span class="staark-hub-badge staark-hub-badge--ok"><?php echo esc_html(ucfirst($existing->post_status)); ?></span>
                            <?php else : ?>
                                <span class="staark-hub-badge staark-hub-badge--warn">Missing</span>
                            <?php endif; ?>
                        </div>

                        <div class="staark-hub-page-updated" role="cell">
                            <?php echo $existing instanceof WP_Post ? esc_html(get_the_modified_date('Y-m-d H:i', $existing)) : '—'; ?>
                        </div>

                        <div class="staark-hub-page-actions" role="cell">
                            <?php if ($existing instanceof WP_Post) : ?>
                                <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($existing->ID, 'raw')); ?>">Edit</a>
                                <a class="button button-small" href="<?php echo esc_url(get_permalink($existing)); ?>" target="_blank" rel="noopener">View ↗</a>
                            <?php else : ?>
                                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                    <input type="hidden" name="action" value="staark_create_starter_page">
                                    <input type="hidden" name="starter_slug" value="<?php echo esc_attr($slug); ?>">
                                    <?php wp_nonce_field('staark_create_starter_page_' . $slug); ?>
                                    <button type="submit" class="button button-small button-primary">Create page</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="staark-hub-grid staark-hub-grid--split">
            <section class="staark-hub-card">
                <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                    <div>
                        <span class="staark-hub-card-label">Site settings</span>
                        <h2>Identity & routing</h2>
                    </div>
                    <a class="button" href="<?php echo esc_url(admin_url('options-general.php')); ?>">WordPress settings</a>
                </div>
                <dl class="staark-hub-details staark-hub-details--website">
                    <div><dt>Site title</dt><dd><?php echo esc_html(get_bloginfo('name') ?: '—'); ?></dd></div>
                    <div><dt>Tagline</dt><dd><?php echo esc_html(get_bloginfo('description') ?: 'Not set'); ?></dd></div>
                    <div><dt>Homepage</dt><dd><?php echo esc_html($front_mode); ?></dd></div>
                    <div><dt>Permalinks</dt><dd><?php echo esc_html($permalink !== '' ? $permalink : 'Plain'); ?></dd></div>
                    <div><dt>Language</dt><dd><?php echo esc_html(get_bloginfo('language')); ?></dd></div>
                    <div><dt>Timezone</dt><dd><?php echo esc_html($timezone); ?></dd></div>
                </dl>
            </section>

            <section class="staark-hub-card">
                <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                    <div>
                        <span class="staark-hub-card-label">Theme & structure</span>
                        <h2>Staark editor</h2>
                    </div>
                    <span class="staark-hub-mini-status staark-hub-mini-status--ok"><?php echo $is_block_theme ? 'Block editor' : 'Classic'; ?></span>
                </div>
                <dl class="staark-hub-details staark-hub-details--website">
                    <div><dt>Theme</dt><dd><?php echo esc_html($theme->get('Name') ?: '—'); ?></dd></div>
                    <div><dt>Version</dt><dd><?php echo esc_html($theme->get('Version') ?: '—'); ?></dd></div>
                    <div><dt>Templates</dt><dd><?php echo $is_block_theme ? 'Site Editor' : 'Theme files'; ?></dd></div>
                    <div><dt>Patterns</dt><dd>Staark Pattern Library</dd></div>
                </dl>
                <div class="staark-hub-actions staark-hub-website-tools">
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('site-editor.php')); ?>">Open Site Editor</a>
                    <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>">All pages</a>
                    <a class="button" href="<?php echo esc_url(admin_url('themes.php')); ?>">Themes</a>
                </div>
            </section>
        </div>

        <p class="staark-hub-note"><strong>Privacy page:</strong> the included copy is a starter template. Review company details, forms, analytics, cookies and third-party services before a client site goes live.</p>
    </div>
    <?php
}

function staark_hub_render_placeholder(string $section, string $title, string $copy, array $items = []): void
{
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header($section); ?>
        <section class="staark-hub-card staark-hub-empty-state">
            <div class="staark-hub-empty-visual" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3v18"></path><path d="M3 12h18"></path>
                </svg>
            </div>
            <div class="staark-hub-empty-copy">
                <span class="staark-hub-card-label">Coming next</span>
                <h2><?php echo esc_html($title); ?></h2>
                <p><?php echo esc_html($copy); ?></p>
                <?php if ($items !== []) : ?>
                    <div class="staark-hub-feature-list">
                        <?php foreach ($items as $item) : ?>
                            <span><i aria-hidden="true">✓</i><?php echo esc_html($item); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <span class="staark-hub-pill">Planned</span>
            </div>
        </section>
    </div>
    <?php
}

function staark_hub_render_branding(): void
{
    $branding = staark_hub_branding();
    $logo_url = $branding['logo_id'] > 0 ? wp_get_attachment_image_url($branding['logo_id'], 'medium') : false;
    $icon_url = $branding['site_icon_id'] > 0 ? wp_get_attachment_image_url($branding['site_icon_id'], 'thumbnail') : false;
    $saved = isset($_GET['staark_branding']) && sanitize_key(wp_unslash($_GET['staark_branding'])) === 'saved';
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Branding'); ?>

        <?php if ($saved) : ?>
            <div class="notice notice-success is-dismissible"><p>Branding saved. The frontend identity and theme colors are now updated.</p></div>
        <?php endif; ?>

        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-brand-form">
            <input type="hidden" name="action" value="staark_save_branding">
            <?php wp_nonce_field('staark_save_branding'); ?>

            <div class="staark-hub-grid staark-hub-grid--split staark-hub-brand-layout">
                <div class="staark-hub-brand-editor">
                    <section class="staark-hub-card">
                        <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                            <div>
                                <span class="staark-hub-card-label">Brand identity</span>
                                <h2>Client-facing identity</h2>
                                <p>Change the website brand without touching theme files. Staark Hub stays Staark-branded in wp-admin.</p>
                            </div>
                            <span class="staark-hub-mini-status staark-hub-mini-status--ok">Live</span>
                        </div>

                        <div class="staark-hub-form-grid">
                            <label class="staark-hub-field">
                                <span>Brand name</span>
                                <input type="text" name="brand_name" value="<?php echo esc_attr($branding['brand_name']); ?>" maxlength="80" data-staark-brand-name>
                                <small>Used in the frontend brand lockup, WordPress site title and footer copyright.</small>
                            </label>

                            <label class="staark-hub-field">
                                <span>Tagline</span>
                                <input type="text" name="tagline" value="<?php echo esc_attr($branding['tagline']); ?>" maxlength="160" data-staark-brand-tagline>
                                <small>Short positioning line shown in the footer and saved as the WordPress tagline.</small>
                            </label>
                        </div>
                    </section>

                    <section class="staark-hub-card">
                        <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                            <div>
                                <span class="staark-hub-card-label">Visual system</span>
                                <h2>Colors</h2>
                                <p>The primary color drives buttons, links and accents. Ink controls the main dark text color.</p>
                            </div>
                        </div>

                        <div class="staark-hub-color-grid">
                            <label class="staark-hub-color-field">
                                <span>Primary</span>
                                <span class="staark-hub-color-control">
                                    <input type="color" name="primary_color" value="<?php echo esc_attr($branding['primary_color']); ?>" data-staark-brand-primary>
                                    <code data-staark-color-value="primary"><?php echo esc_html($branding['primary_color']); ?></code>
                                </span>
                            </label>

                            <label class="staark-hub-color-field">
                                <span>Ink</span>
                                <span class="staark-hub-color-control">
                                    <input type="color" name="ink_color" value="<?php echo esc_attr($branding['ink_color']); ?>" data-staark-brand-ink>
                                    <code data-staark-color-value="ink"><?php echo esc_html($branding['ink_color']); ?></code>
                                </span>
                            </label>
                        </div>
                    </section>

                    <section class="staark-hub-card">
                        <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                            <div>
                                <span class="staark-hub-card-label">Assets</span>
                                <h2>Logo & site icon</h2>
                                <p>If no logo is selected, the Staark-style Code2 mark is used as a clean fallback on the frontend.</p>
                            </div>
                        </div>

                        <div class="staark-hub-asset-grid">
                            <div class="staark-hub-asset-field">
                                <div class="staark-hub-asset-label"><strong>Logo mark</strong><span>Header & footer</span></div>
                                <div class="staark-hub-asset-preview" id="staark-logo-preview">
                                    <?php if ($logo_url) : ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="">
                                    <?php else : ?>
                                        <div class="staark-hub-asset-fallback" aria-hidden="true">
                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="staark_brand_logo_id" name="logo_id" value="<?php echo esc_attr((string) $branding['logo_id']); ?>">
                                <div class="staark-hub-asset-actions">
                                    <button type="button" class="button" data-staark-media-button data-target="#staark_brand_logo_id" data-preview="#staark-logo-preview" data-title="Choose brand logo">Choose logo</button>
                                    <button type="button" class="button-link-delete" data-staark-media-remove data-target="#staark_brand_logo_id" data-preview="#staark-logo-preview">Remove</button>
                                </div>
                            </div>

                            <div class="staark-hub-asset-field">
                                <div class="staark-hub-asset-label"><strong>Site icon</strong><span>Browser tab & app icon</span></div>
                                <div class="staark-hub-asset-preview staark-hub-asset-preview--icon" id="staark-icon-preview">
                                    <?php if ($icon_url) : ?>
                                        <img src="<?php echo esc_url($icon_url); ?>" alt="">
                                    <?php else : ?>
                                        <div class="staark-hub-asset-placeholder">512 × 512</div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="staark_brand_site_icon_id" name="site_icon_id" value="<?php echo esc_attr((string) $branding['site_icon_id']); ?>">
                                <div class="staark-hub-asset-actions">
                                    <button type="button" class="button" data-staark-media-button data-target="#staark_brand_site_icon_id" data-preview="#staark-icon-preview" data-title="Choose site icon">Choose icon</button>
                                    <button type="button" class="button-link-delete" data-staark-media-remove data-target="#staark_brand_site_icon_id" data-preview="#staark-icon-preview">Remove</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="staark-hub-brand-savebar">
                        <div>
                            <strong>Ready to apply?</strong>
                            <span>These settings affect the public website, not the Staark Hub admin branding.</span>
                        </div>
                        <?php submit_button('Save branding', 'primary', 'submit', false); ?>
                    </div>
                </div>

                <aside class="staark-hub-brand-sidebar">
                    <section class="staark-hub-card staark-hub-brand-preview-card">
                        <span class="staark-hub-card-label">Live preview</span>
                        <h2>Brand snapshot</h2>
                        <div class="staark-hub-brand-preview" data-staark-brand-preview style="--brand-primary:<?php echo esc_attr($branding['primary_color']); ?>;--brand-ink:<?php echo esc_attr($branding['ink_color']); ?>;">
                            <div class="staark-hub-brand-preview-browser"><i></i><i></i><i></i><span><?php echo esc_html(wp_parse_url(home_url('/'), PHP_URL_HOST) ?: 'website'); ?></span></div>
                            <div class="staark-hub-brand-preview-nav">
                                <div class="staark-hub-brand-preview-lockup">
                                    <span class="staark-hub-brand-preview-mark" data-staark-preview-mark>
                                        <?php if ($logo_url) : ?>
                                            <img src="<?php echo esc_url($logo_url); ?>" alt="">
                                        <?php else : ?>
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>
                                        <?php endif; ?>
                                    </span>
                                    <strong data-staark-preview-name><?php echo esc_html($branding['brand_name']); ?></strong><i></i>
                                </div>
                                <span class="staark-hub-brand-preview-button">Kontakt</span>
                            </div>
                            <div class="staark-hub-brand-preview-body">
                                <span class="staark-hub-brand-preview-kicker">MODERN WEBB</span>
                                <h3 data-staark-preview-name><?php echo esc_html($branding['brand_name']); ?></h3>
                                <p data-staark-preview-tagline><?php echo esc_html($branding['tagline']); ?></p>
                                <span class="staark-hub-brand-preview-cta">Få offert</span>
                            </div>
                        </div>
                    </section>

                    <section class="staark-hub-card">
                        <span class="staark-hub-card-label">Applied automatically</span>
                        <h2>One save, multiple surfaces</h2>
                        <div class="staark-hub-feature-list staark-hub-feature-list--stacked">
                            <span><i aria-hidden="true">✓</i>Header and footer brand lockup</span>
                            <span><i aria-hidden="true">✓</i>WordPress site title and tagline</span>
                            <span><i aria-hidden="true">✓</i>Theme primary and ink colors</span>
                            <span><i aria-hidden="true">✓</i>Browser site icon</span>
                        </div>
                        <p class="staark-hub-brand-note">Staark Hub itself keeps the Staark Inc. identity so clients always know who manages the website.</p>
                    </section>
                </aside>
            </div>
        </form>
    </div>
    <?php
}

function staark_hub_render_connect(): void
{
    $connection = staark_hub_connection_ensure_identity();
    $queue = staark_hub_sync_queue();
    $state = isset($_GET['staark_connect']) ? sanitize_key(wp_unslash($_GET['staark_connect'])) : '';
    $synced = isset($_GET['synced']) ? absint($_GET['synced']) : 0;
    $paired = staark_hub_connection_is_connected();
    $healthy = $paired && $connection['status'] === 'connected';
    $runtime_environment = staark_hub_runtime_environment();
    $development_http_allowed = in_array($runtime_environment, ['local', 'development'], true);
    $connector_environment_label = $connection['environment'] === 'development' ? 'Development' : 'Production';
    $status_label = $healthy ? __('Connected', 'staark-core') : ($paired ? __('Needs attention', 'staark-core') : __('Not connected', 'staark-core'));
    $status_class = $healthy ? 'staark-hub-mini-status--ok' : ($paired ? 'staark-hub-mini-status--warn' : 'staark-hub-mini-status--muted');
    $request_url = staark_hub_pairing_request_url($connection);
    $contact_email = staark_hub_contact_email();
    ?>
    <div class="wrap staark-hub-wrap staark-hub-connect">
        <?php staark_hub_header('Connect', $paired ? __('Staark connection', 'staark-core') : __('Connect to Staark', 'staark-core')); ?>

        <?php if ($state === 'connected') : ?>
            <div class="notice notice-success is-dismissible"><p>This website is connected to Staark Hub. Signed API requests are now enabled.</p></div>
        <?php elseif ($state === 'healthy') : ?>
            <div class="notice notice-success is-dismissible"><p>Connection check passed. Staark Hub accepted the signed request.</p></div>
        <?php elseif ($state === 'synced') : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf('Sync completed. %d local record(s) were acknowledged by Staark Hub.', $synced)); ?></p></div>
        <?php elseif ($state === 'disconnected') : ?>
            <div class="notice notice-info is-dismissible"><p>The local WordPress connection was disabled. Site identity is preserved for reconnecting later.</p></div>
        <?php elseif ($state === 'identity_regenerated') : ?>
            <div class="notice notice-success is-dismissible"><p>A new site identity was generated. Use a fresh pairing code before connecting again.</p></div>
        <?php elseif ($state === 'disconnect_first') : ?>
            <div class="notice notice-warning"><p>Disconnect the website before changing connector settings or regenerating its integration identity.</p></div>
        <?php elseif ($state === 'settings_saved') : ?>
            <div class="notice notice-success is-dismissible"><p>Connector environment saved. Use a pairing code from the selected Staark Hub environment.</p></div>
        <?php elseif ($state === 'invalid_url') : ?>
            <div class="notice notice-error"><p>Add a valid Staark Hub URL including http:// or https://.</p></div>
        <?php elseif ($state === 'insecure_url') : ?>
            <div class="notice notice-error"><p>Plain HTTP is only allowed when WordPress itself runs with WP_ENVIRONMENT_TYPE set to local or development.</p></div>
        <?php elseif ($state === 'invalid') : ?>
            <div class="notice notice-error">
                <p>
                    <?php echo esc_html__('Add a valid pairing code from Staark.', 'staark-core'); ?>
                    <a href="<?php echo esc_url($request_url, ['mailto']); ?>"><?php echo esc_html__('Don’t have one? Request a pairing code.', 'staark-core'); ?></a>
                </p>
            </div>
        <?php elseif (in_array($state, ['error', 'sync_error'], true)) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($connection['last_error'] !== '' ? $connection['last_error'] : 'Staark Hub could not complete the request.'); ?></p></div>
        <?php endif; ?>

        <div class="staark-hub-grid staark-hub-grid--split staark-hub-connect-layout">
            <div class="staark-hub-connect-main">
                <?php if (! $paired) : ?>
                    <section class="staark-hub-card staark-hub-key-request" aria-labelledby="staark-hub-key-request-title">
                        <span class="staark-hub-key-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"></circle><path d="m21 2-9.6 9.6"></path><path d="m15.5 7.5 3 3L22 7l-3-3"></path></svg>
                        </span>
                        <div class="staark-hub-key-request-copy">
                            <h2 id="staark-hub-key-request-title"><?php echo esc_html__('Don’t have a pairing code yet?', 'staark-core'); ?></h2>
                            <p>
                                <?php
                                echo esc_html__('Pairing codes are issued by Staark Inc. for each website. Request one by email and we will reply with a single-use code for this site. The email already includes your website address and Site ID, never passwords or the connection secret.', 'staark-core');
                                ?>
                            </p>
                            <div class="staark-hub-actions">
                                <a class="button button-primary" href="<?php echo esc_url($request_url, ['mailto']); ?>"><?php echo esc_html__('Request a pairing code', 'staark-core'); ?></a>
                                <button type="button" class="button staark-hub-copy" data-staark-copy="<?php echo esc_attr($connection['site_id']); ?>" data-staark-copied="<?php echo esc_attr__('Copied', 'staark-core'); ?>"><?php echo esc_html__('Copy Site ID', 'staark-core'); ?></button>
                            </div>
                            <small>
                                <?php
                                printf(
                                    /* translators: %s: Staark contact email address. */
                                    esc_html__('No email app on this computer? Write to %s and include the Site ID below.', 'staark-core'),
                                    '<a href="' . esc_url('mailto:' . $contact_email, ['mailto']) . '">' . esc_html($contact_email) . '</a>'
                                );
                                ?>
                            </small>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="staark-hub-card">
                    <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                        <div>
                            <span class="staark-hub-card-label"><?php echo esc_html__('Connection', 'staark-core'); ?></span>
                            <h2><?php echo $paired ? esc_html__('Staark Hub is linked', 'staark-core') : esc_html__('Pair this website', 'staark-core'); ?></h2>
                            <p><?php echo $paired ? esc_html__('The connector uses a per-site secret and HMAC signatures for every request to the Staark Hub API.', 'staark-core') : esc_html__('Paste the pairing code from Staark. WordPress exchanges it once for this site identity.', 'staark-core'); ?></p>
                        </div>
                        <span class="staark-hub-mini-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                    </div>

                    <?php if (! $paired) : ?>
                        <ol class="staark-hub-steps">
                            <li class="is-current"><strong><?php echo esc_html__('Get a code', 'staark-core'); ?></strong><span><?php echo esc_html__('Requested from Staark', 'staark-core'); ?></span></li>
                            <li><strong><?php echo esc_html__('Paste it', 'staark-core'); ?></strong><span><?php echo esc_html__('Single use, short-lived', 'staark-core'); ?></span></li>
                            <li><strong><?php echo esc_html__('Connected', 'staark-core'); ?></strong><span><?php echo esc_html__('Signed sync enabled', 'staark-core'); ?></span></li>
                        </ol>

                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-connect-form">
                            <input type="hidden" name="action" value="staark_connect_site">
                            <?php wp_nonce_field('staark_connect_site'); ?>
                            <label class="staark-hub-field">
                                <span><?php echo esc_html__('Pairing code', 'staark-core'); ?></span>
                                <input type="text" name="pairing_code" maxlength="80" placeholder="STAARK-7F4K-92QX" autocomplete="off" spellcheck="false" required>
                            </label>
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Connect to Staark', 'staark-core'); ?></button>
                        </form>
                    <?php else : ?>
                        <div class="staark-hub-connect-actions">
                            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                <input type="hidden" name="action" value="staark_test_connection">
                                <?php wp_nonce_field('staark_test_connection'); ?>
                                <button type="submit" class="button button-primary"><?php echo esc_html__('Test connection', 'staark-core'); ?></button>
                            </form>
                            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                <input type="hidden" name="action" value="staark_sync_now">
                                <?php wp_nonce_field('staark_sync_now'); ?>
                                <button type="submit" class="button"><?php echo esc_html__('Sync now', 'staark-core'); ?><?php echo $queue['total'] > 0 ? ' · ' . esc_html((string) $queue['total']) . ' ' . esc_html__('pending', 'staark-core') : ''; ?></button>
                            </form>
                            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" onsubmit="return confirm('Disconnect this website from Staark Hub? Local data will remain in WordPress.');">
                                <input type="hidden" name="action" value="staark_disconnect_site">
                                <?php wp_nonce_field('staark_disconnect_site'); ?>
                                <button type="submit" class="button staark-hub-button-danger"><?php echo esc_html__('Disconnect', 'staark-core'); ?></button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <dl class="staark-hub-details staark-hub-connect-details">
                        <div><dt><?php echo esc_html__('Site ID', 'staark-core'); ?></dt><dd><code><?php echo esc_html($connection['site_id']); ?></code></dd></div>
                        <div><dt><?php echo esc_html__('Environment', 'staark-core'); ?></dt><dd><?php echo esc_html($connector_environment_label); ?></dd></div>
                        <div><dt><?php echo esc_html__('Hub', 'staark-core'); ?></dt><dd><?php echo esc_html($connection['hub_url']); ?></dd></div>
                        <div><dt><?php echo esc_html__('Remote site', 'staark-core'); ?></dt><dd><?php echo esc_html($connection['remote_site_id'] !== '' ? $connection['remote_site_id'] : '—'); ?></dd></div>
                        <div><dt><?php echo esc_html__('Last check', 'staark-core'); ?></dt><dd><?php echo esc_html($connection['last_checked'] !== '' ? $connection['last_checked'] : '—'); ?></dd></div>
                        <div><dt><?php echo esc_html__('Authentication', 'staark-core'); ?></dt><dd>HMAC-SHA256</dd></div>
                    </dl>
                </section>

                <?php if (! $paired) : ?>
                    <details class="staark-hub-card staark-hub-disclosure"<?php echo in_array($state, ['settings_saved', 'invalid_url', 'insecure_url'], true) ? ' open' : ''; ?>>
                        <summary>
                            <span>
                                <strong><?php echo esc_html__('Advanced: connector environment', 'staark-core'); ?></strong>
                                <small><?php echo esc_html(sprintf(__('Currently %1$s · %2$s', 'staark-core'), $connector_environment_label, $connection['hub_url'])); ?></small>
                            </span>
                        </summary>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-connect-environment">
                            <input type="hidden" name="action" value="staark_save_connection_settings">
                            <?php wp_nonce_field('staark_save_connection_settings'); ?>
                            <p><?php echo esc_html__('Production is locked to staarkinc.com. Development can point to a separate HTTPS endpoint or, on local/development WordPress installs only, a plain HTTP URL.', 'staark-core'); ?> <span class="staark-hub-pill">WP: <?php echo esc_html($runtime_environment); ?></span></p>
                            <div class="staark-hub-connect-environment-options">
                                <label>
                                    <input type="radio" name="connector_environment" value="production" <?php checked($connection['environment'], 'production'); ?>>
                                    <span><strong>Production</strong><small>https://staarkinc.com</small></span>
                                </label>
                                <label>
                                    <input type="radio" name="connector_environment" value="development" <?php checked($connection['environment'], 'development'); ?>>
                                    <span><strong>Development</strong><small><?php echo esc_html__('Separate Hub / local testing', 'staark-core'); ?></small></span>
                                </label>
                            </div>
                            <label class="staark-hub-field">
                                <span><?php echo esc_html__('Development Hub URL', 'staark-core'); ?></span>
                                <input type="url" name="hub_url" value="<?php echo esc_attr($connection['hub_url']); ?>" placeholder="https://dev.example.com or http://192.168.0.10:3002">
                                <small><?php echo $development_http_allowed ? esc_html__('This WordPress runtime may use HTTP while the connector is set to Development.', 'staark-core') : esc_html__('This WordPress runtime requires HTTPS. Set WP_ENVIRONMENT_TYPE to local/development before using a plain HTTP endpoint.', 'staark-core'); ?></small>
                            </label>
                            <div class="staark-hub-actions">
                                <button type="submit" class="button"><?php echo esc_html__('Save environment', 'staark-core'); ?></button>
                            </div>
                        </form>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-connect-secondary" onsubmit="return confirm('Generate a new site identity? Any old pairing details for this site will stop working.');">
                            <input type="hidden" name="action" value="staark_regenerate_site_identity">
                            <?php wp_nonce_field('staark_regenerate_site_identity'); ?>
                            <button type="submit" class="button button-link-delete"><?php echo esc_html__('Regenerate site identity', 'staark-core'); ?></button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>

            <aside class="staark-hub-connect-sidebar">
                <section class="staark-hub-card staark-hub-card--dark">
                    <span class="staark-hub-card-label"><?php echo esc_html__('Local sync queue', 'staark-core'); ?></span>
                    <h2><?php echo esc_html(sprintf(__('%d waiting', 'staark-core'), $queue['total'])); ?></h2>
                    <div class="staark-hub-connect-queue">
                        <div><span><?php echo esc_html__('Support tickets', 'staark-core'); ?></span><strong><?php echo esc_html((string) $queue['tickets']); ?></strong></div>
                    </div>
                    <p><?php echo esc_html__('Support tickets stay stored in WordPress. After pairing, Staark Hub receives a managed-support copy and WordPress records the acknowledgement.', 'staark-core'); ?></p>
                    <span class="staark-hub-pill"><?php echo $paired ? ($healthy ? esc_html__('Cloud sync enabled', 'staark-core') : esc_html__('Connection needs attention', 'staark-core')) : esc_html__('Stored locally', 'staark-core'); ?></span>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label"><?php echo esc_html__('Security model', 'staark-core'); ?></span>
                    <h2><?php echo esc_html__('No admin password sharing', 'staark-core'); ?></h2>
                    <ul class="staark-hub-feature-list staark-hub-feature-list--stacked">
                        <li><?php echo esc_html__('Unique site ID and 256-bit integration secret', 'staark-core'); ?></li>
                        <li><?php echo esc_html__('Timestamped HMAC-SHA256 request signatures', 'staark-core'); ?></li>
                        <li><?php echo $development_http_allowed ? esc_html__('HTTPS by default; HTTP only for explicit local/development testing', 'staark-core') : esc_html__('HTTPS-only Staark Hub API', 'staark-core'); ?></li>
                        <li><?php echo esc_html__('Revocable connection without deleting local data', 'staark-core'); ?></li>
                    </ul>
                </section>

                <details class="staark-hub-card staark-hub-disclosure staark-hub-connect-contract">
                    <summary><span><strong><?php echo esc_html__('Connector endpoints', 'staark-core'); ?></strong><small><?php echo esc_html__('For Staark developers', 'staark-core'); ?></small></span></summary>
                    <div class="staark-hub-connect-endpoints">
                        <code>POST /api/hub/wordpress/connect</code>
                        <code>GET /api/hub/wordpress/ping</code>
                        <code>POST /api/hub/wordpress/sync</code>
                    </div>
                </details>
            </aside>
        </div>

        <?php staark_hub_help_strip(); ?>
    </div>
    <?php
}

function staark_hub_render_support(): void
{
    if (function_exists('staark_hub_support_maybe_refresh')) {
        staark_hub_support_maybe_refresh();
    }

    $ticket_id = isset($_GET['ticket']) ? absint($_GET['ticket']) : 0;
    if ($ticket_id > 0) {
        staark_hub_render_support_ticket_detail($ticket_id);
        return;
    }
    $user = wp_get_current_user();
    $environment = staark_hub_support_environment();
    $categories = staark_hub_support_categories();
    $priorities = staark_hub_support_priorities();
    $tickets = staark_hub_support_tickets();
    $support_state = isset($_GET['staark_support']) ? sanitize_key(wp_unslash($_GET['staark_support'])) : '';
    $created_ticket_id = isset($_GET['ticket']) ? absint($_GET['ticket']) : 0;
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Support'); ?>

        <?php if ($support_state === 'leads_removed') : ?>
            <div class="notice notice-info is-dismissible"><p>Leads were removed from Staark Core. This client plugin is focused on website management and managed support.</p></div>
        <?php elseif ($support_state === 'created' && $created_ticket_id > 0) : ?>
            <div class="notice notice-success is-dismissible"><p><strong><?php echo esc_html(staark_hub_support_ticket_label($created_ticket_id)); ?></strong> created. The request is stored locally and is ready for Staark Hub sync.</p></div>
        <?php elseif ($support_state === 'invalid') : ?>
            <div class="notice notice-error"><p>Please add a subject, message and valid contact email before sending the request.</p></div>
        <?php elseif ($support_state === 'error') : ?>
            <div class="notice notice-error"><p>The support request could not be saved. Please try again or contact Staark Inc. directly.</p></div>
        <?php endif; ?>

        <div class="staark-hub-support-hero">
            <div>
                <span class="staark-hub-card-label">Managed support</span>
                <h2>Send a request without leaving WordPress</h2>
                <p>Describe what you need and Staark Hub automatically attaches the technical context that usually takes another round of messages to collect.</p>
            </div>
            <div class="staark-hub-support-hero-meta">
                <span class="staark-hub-mini-status staark-hub-mini-status--ok">Local queue active</span>
                <small>Cloud sync arrives with Connect to Staark.</small>
            </div>
        </div>

        <div class="staark-hub-grid staark-hub-grid--split staark-hub-support-layout">
            <section class="staark-hub-card">
                <div class="staark-hub-card-heading staark-hub-card-heading--compact">
                    <div>
                        <span class="staark-hub-card-label">New request</span>
                        <h2>How can we help?</h2>
                        <p>The request is stored in this WordPress installation first. Email notification is best-effort until the main Staark Hub connection is enabled.</p>
                    </div>
                </div>

                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="staark-hub-support-form">
                    <input type="hidden" name="action" value="staark_submit_support_ticket">
                    <?php wp_nonce_field('staark_submit_support_ticket'); ?>

                    <div class="staark-hub-form-grid">
                        <label class="staark-hub-field">
                            <span>Contact name</span>
                            <input type="text" name="contact_name" maxlength="100" value="<?php echo esc_attr($user->display_name); ?>" autocomplete="name">
                        </label>
                        <label class="staark-hub-field">
                            <span>Contact email</span>
                            <input type="email" name="contact_email" maxlength="190" value="<?php echo esc_attr($user->user_email); ?>" autocomplete="email" required>
                        </label>
                    </div>

                    <div class="staark-hub-form-grid">
                        <label class="staark-hub-field">
                            <span>Category</span>
                            <select name="category">
                                <?php foreach ($categories as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="staark-hub-field">
                            <span>Priority</span>
                            <select name="priority">
                                <?php foreach ($priorities as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>"<?php selected($value, 'normal'); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <label class="staark-hub-field">
                        <span>Subject</span>
                        <input type="text" name="subject" maxlength="160" placeholder="e.g. Update the contact section" required>
                    </label>

                    <label class="staark-hub-field">
                        <span>Message</span>
                        <textarea name="message" rows="7" maxlength="5000" placeholder="Tell us what is happening, what you would like changed and anything we should know before we start." required></textarea>
                        <small>Never include passwords, API keys or other secrets in a support request.</small>
                    </label>

                    <div class="staark-hub-support-submit">
                        <div>
                            <strong>Technical context attached automatically</strong>
                            <span>Site URL, WordPress, PHP, theme, Hub version and pending update count.</span>
                        </div>
                        <button type="submit" class="button button-primary">Create support request</button>
                    </div>
                </form>
            </section>

            <aside class="staark-hub-support-sidebar">
                <section class="staark-hub-card staark-hub-card--dark">
                    <span class="staark-hub-card-label">Attached context</span>
                    <h2>Environment snapshot</h2>
                    <dl class="staark-hub-details staark-hub-details--dark">
                        <div><dt>Site</dt><dd><?php echo esc_html((string) (wp_parse_url($environment['site_url'], PHP_URL_HOST) ?: $environment['site_url'])); ?></dd></div>
                        <div><dt>WordPress</dt><dd><?php echo esc_html((string) $environment['wordpress']); ?></dd></div>
                        <div><dt>PHP</dt><dd><?php echo esc_html((string) $environment['php']); ?></dd></div>
                        <div><dt>Theme</dt><dd><?php echo esc_html((string) $environment['theme']); ?></dd></div>
                        <div><dt>Pending updates</dt><dd><?php echo esc_html((string) $environment['updates']); ?></dd></div>
                        <div><dt>Staark Hub</dt><dd><?php echo esc_html((string) $environment['hub']); ?></dd></div>
                    </dl>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label">Direct contact</span>
                    <h2>Prefer email?</h2>
                    <p>You can still contact Staark Inc. directly. The in-Hub form is preferred because it attaches site context automatically.</p>
                    <div class="staark-hub-actions">
                        <a class="button" href="<?php echo esc_url('mailto:' . staark_hub_contact_email(), ['mailto']); ?>"><?php echo esc_html(staark_hub_contact_email()); ?></a>
                        <a class="button" href="https://staarkinc.com/kontakt" target="_blank" rel="noopener">Contact page ↗</a>
                    </div>
                </section>
            </aside>
        </div>

        <section class="staark-hub-section staark-hub-support-history">
            <div class="staark-hub-section-heading">
                <div>
                    <span class="staark-hub-card-label">Request history</span>
                    <h2>Recent support requests</h2>
                    <p>Stored locally for now. When this site is connected, the same queue can sync to the main Staark Hub.</p>
                </div>
                <span class="staark-hub-mini-status staark-hub-mini-status--muted"><?php echo esc_html((string) count($tickets)); ?> shown</span>
            </div>

            <?php if ($tickets === []) : ?>
                <div class="staark-hub-support-empty">
                    <span>No support requests yet.</span>
                    <small>Your first request will appear here with its ticket number and status.</small>
                </div>
            <?php else : ?>
                <div class="staark-hub-support-table-wrap">
                    <table class="staark-hub-support-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Request</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket) :
                                $category = (string) get_post_meta($ticket->ID, '_staark_ticket_category', true);
                                $priority = (string) get_post_meta($ticket->ID, '_staark_ticket_priority', true);
                                $status = (string) get_post_meta($ticket->ID, '_staark_ticket_status', true);
                                $email_notified = (string) get_post_meta($ticket->ID, '_staark_ticket_email_notified', true);
                                $category_label = $categories[$category] ?? ucfirst($category ?: 'Other');
                                $priority_label = $priorities[$priority] ?? ucfirst($priority ?: 'Normal');
                                $status = $status !== '' ? $status : 'open';
                                $detail_url = staark_hub_support_ticket_detail_url($ticket->ID);
                                ?>
                                <tr>
                                    <td><strong><a class="staark-hub-ticket-link" href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html(staark_hub_support_ticket_label($ticket->ID)); ?></a></strong></td>
                                    <td>
                                        <strong><a class="staark-hub-ticket-link" href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($ticket->post_title); ?></a></strong>
                                        <small><?php echo esc_html(wp_trim_words(wp_strip_all_tags($ticket->post_content), 13)); ?></small>
                                    </td>
                                    <td><?php echo esc_html($category_label); ?></td>
                                    <td><span class="staark-hub-priority staark-hub-priority--<?php echo esc_attr($priority ?: 'normal'); ?>"><?php echo esc_html($priority_label); ?></span></td>
                                    <td>
                                        <span class="staark-hub-ticket-status staark-hub-ticket-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(ucwords(str_replace('_', ' ', $status))); ?></span>
                                        <small><?php echo $email_notified === 'yes' ? 'Email sent' : 'Stored locally'; ?></small>
                                    </td>
                                    <td><?php echo esc_html(get_the_date('Y-m-d H:i', $ticket)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php
}
