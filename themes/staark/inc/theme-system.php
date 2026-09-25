<?php
/**
 * Staark Theme System v2.
 *
 * Preset registry + design tokens + editor parity.
 *
 * A preset is a JSON file in /presets. New presets can be added without
 * changing this engine. WP-6.4 intentionally ships only the existing
 * Scandinavian direction so this patch changes architecture, not design.
 *
 * @package Staark
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_theme_system_default_preset_id(): string
{
    return 'scandinavian';
}

/**
 * @return array<string,array<string,mixed>>
 */
function staark_theme_system_registry(): array
{
    static $registry = null;

    if (is_array($registry)) {
        return $registry;
    }

    $registry = [];
    $directory = get_theme_file_path('presets');

    if (! is_dir($directory)) {
        return $registry;
    }

    try {
        $iterator = new DirectoryIterator($directory);
    } catch (Throwable $error) {
        return $registry;
    }

    foreach ($iterator as $file) {
        if ($file->isDot() || ! $file->isFile() || strtolower($file->getExtension()) !== 'json') {
            continue;
        }

        $raw = file_get_contents($file->getPathname());
        if (! is_string($raw) || trim($raw) === '') {
            continue;
        }

        $preset = json_decode($raw, true);
        if (! is_array($preset)) {
            continue;
        }

        $id = isset($preset['id']) ? sanitize_key((string) $preset['id']) : '';
        if ($id === '' || $id !== pathinfo($file->getFilename(), PATHINFO_FILENAME)) {
            continue;
        }

        $preset['id'] = $id;
        $preset['name'] = sanitize_text_field((string) ($preset['name'] ?? $id));
        $preset['description'] = sanitize_text_field((string) ($preset['description'] ?? ''));
        $preset['version'] = max(1, absint($preset['version'] ?? 1));
        $preset['tokens'] = isset($preset['tokens']) && is_array($preset['tokens']) ? $preset['tokens'] : [];
        $preset['components'] = isset($preset['components']) && is_array($preset['components']) ? $preset['components'] : [];
        $preset['stylesheet'] = isset($preset['stylesheet'])
            ? ltrim(sanitize_text_field((string) $preset['stylesheet']), '/')
            : '';

        $registry[$id] = $preset;
    }

    ksort($registry);

    return $registry;
}

function staark_theme_system_active_preset_id(): string
{
    $registry = staark_theme_system_registry();
    $saved = sanitize_key((string) get_theme_mod('staark_theme_preset', staark_theme_system_default_preset_id()));

    if ($saved !== '' && isset($registry[$saved])) {
        return $saved;
    }

    $default = staark_theme_system_default_preset_id();

    if (isset($registry[$default])) {
        return $default;
    }

    $first = array_key_first($registry);

    return is_string($first) ? $first : '';
}

/**
 * @return array<string,mixed>
 */
function staark_theme_system_active_preset(): array
{
    $registry = staark_theme_system_registry();
    $id = staark_theme_system_active_preset_id();

    return $id !== '' && isset($registry[$id]) ? $registry[$id] : [];
}

/**
 * Read a nested preset value.
 *
 * @param array<string,mixed> $preset
 * @param mixed $fallback
 * @return mixed
 */
function staark_theme_system_value(array $preset, string $path, $fallback = '')
{
    $value = $preset;

    foreach (explode('.', $path) as $segment) {
        if (! is_array($value) || ! array_key_exists($segment, $value)) {
            return $fallback;
        }

        $value = $value[$segment];
    }

    return $value;
}

function staark_theme_system_safe_css_value(string $value, string $fallback = ''): string
{
    $value = trim($value);

    if ($value === '') {
        return $fallback;
    }

    // Presets are trusted theme files, but avoid allowing CSS rule breakout.
    $value = str_replace(["\0", "\r", "\n", '{', '}', ';'], '', $value);

    return trim($value) !== '' ? trim($value) : $fallback;
}

function staark_theme_system_color(array $preset, string $slug, string $fallback): string
{
    $value = (string) staark_theme_system_value($preset, 'tokens.colors.' . $slug, $fallback);
    $color = sanitize_hex_color($value);

    return $color ?: $fallback;
}

/**
 * Runtime variables used by the existing Staark CSS and future preset layers.
 */
function staark_theme_system_css(): string
{
    $preset = staark_theme_system_active_preset();

    if ($preset === []) {
        return '';
    }

    $ink = staark_theme_system_color($preset, 'ink', '#101619');
    $muted = staark_theme_system_color($preset, 'muted', '#65706b');
    $line = staark_theme_system_color($preset, 'line', '#e4e7ec');
    $paper = staark_theme_system_color($preset, 'paper', '#fbfaf7');
    $surface = staark_theme_system_color($preset, 'surface', '#f2eee7');
    $white = staark_theme_system_color($preset, 'white', '#ffffff');
    $primary = staark_theme_system_color($preset, 'primary', '#2457f5');
    $primary_dark = staark_theme_system_color($preset, 'primaryDark', '#173bb7');
    $primary_soft = staark_theme_system_color($preset, 'primarySoft', '#eef3ff');
    $success = staark_theme_system_color($preset, 'success', '#0f9f6e');
    $forest = staark_theme_system_color($preset, 'forest', '#142720');
    $forest_deep = staark_theme_system_color($preset, 'forestDeep', '#0d1916');
    $gold = staark_theme_system_color($preset, 'gold', '#d59531');

    $radius_sm = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.radius.sm', '12px'),
        '12px'
    );
    $radius_md = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.radius.md', '18px'),
        '18px'
    );
    $radius_lg = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.radius.lg', '28px'),
        '28px'
    );
    $radius_button = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.radius.button', '8px'),
        '8px'
    );
    $wrap = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.layout.wrap', '1440px'),
        '1440px'
    );

    return ':root{'
        . '--staark-system-ink:' . $ink . ';'
        . '--staark-system-muted:' . $muted . ';'
        . '--staark-system-line:' . $line . ';'
        . '--staark-system-paper:' . $paper . ';'
        . '--staark-system-surface:' . $surface . ';'
        . '--staark-system-white:' . $white . ';'
        . '--staark-system-primary:' . $primary . ';'
        . '--staark-system-primary-dark:' . $primary_dark . ';'
        . '--staark-system-primary-soft:' . $primary_soft . ';'
        . '--staark-system-success:' . $success . ';'
        . '--staark-system-radius-sm:' . $radius_sm . ';'
        . '--staark-system-radius-md:' . $radius_md . ';'
        . '--staark-system-radius-lg:' . $radius_lg . ';'
        . '--staark-system-radius-button:' . $radius_button . ';'
        . '--staark-system-wrap:' . $wrap . ';'
        . '--wp--preset--color--ink:' . $ink . ';'
        . '--wp--preset--color--muted:' . $muted . ';'
        . '--wp--preset--color--line:' . $line . ';'
        . '--wp--preset--color--paper:' . $paper . ';'
        . '--wp--preset--color--surface:' . $surface . ';'
        . '--wp--preset--color--white:' . $white . ';'
        . '--wp--preset--color--primary:' . $primary . ';'
        . '--wp--preset--color--primary-dark:' . $primary_dark . ';'
        . '--wp--preset--color--primary-soft:' . $primary_soft . ';'
        . '--wp--preset--color--success:' . $success . ';'
        . '--staark-radius-sm:' . $radius_sm . ';'
        . '--staark-radius-md:' . $radius_md . ';'
        . '--staark-radius-lg:' . $radius_lg . ';'
        . '--showcase-ink:' . $ink . ';'
        . '--showcase-muted:' . $muted . ';'
        . '--showcase-line:' . $line . ';'
        . '--showcase-paper:' . $paper . ';'
        . '--showcase-cream:' . $surface . ';'
        . '--showcase-white:' . $white . ';'
        . '--showcase-forest:' . $forest . ';'
        . '--showcase-forest-deep:' . $forest_deep . ';'
        . '--showcase-gold:' . $gold . ';'
        . '--showcase-radius:' . $radius_md . ';'
        . '--showcase-radius-lg:' . $radius_lg . ';'
        . '}'
        . '.staark-showcase-wrap{max-width:' . $wrap . ';}';
}

/**
 * Keep WordPress theme.json/editor tokens aligned with the active Staark preset.
 *
 * Branding can still override the public primary color later through Staark Hub.
 */
add_filter('wp_theme_json_data_theme', static function ($theme_json) {
    if (! $theme_json instanceof WP_Theme_JSON_Data || ! method_exists($theme_json, 'update_with')) {
        return $theme_json;
    }

    $preset = staark_theme_system_active_preset();
    if ($preset === []) {
        return $theme_json;
    }

    $colors = [
        ['slug' => 'ink', 'name' => 'Ink', 'color' => staark_theme_system_color($preset, 'ink', '#101619')],
        ['slug' => 'muted', 'name' => 'Muted', 'color' => staark_theme_system_color($preset, 'muted', '#65706b')],
        ['slug' => 'line', 'name' => 'Line', 'color' => staark_theme_system_color($preset, 'line', '#e4e7ec')],
        ['slug' => 'paper', 'name' => 'Paper', 'color' => staark_theme_system_color($preset, 'paper', '#fbfaf7')],
        ['slug' => 'surface', 'name' => 'Surface', 'color' => staark_theme_system_color($preset, 'surface', '#f2eee7')],
        ['slug' => 'white', 'name' => 'White', 'color' => staark_theme_system_color($preset, 'white', '#ffffff')],
        ['slug' => 'primary', 'name' => 'Primary', 'color' => staark_theme_system_color($preset, 'primary', '#2457f5')],
        ['slug' => 'primary-dark', 'name' => 'Primary Dark', 'color' => staark_theme_system_color($preset, 'primaryDark', '#173bb7')],
        ['slug' => 'primary-soft', 'name' => 'Primary Soft', 'color' => staark_theme_system_color($preset, 'primarySoft', '#eef3ff')],
        ['slug' => 'success', 'name' => 'Success', 'color' => staark_theme_system_color($preset, 'success', '#0f9f6e')],
    ];

    $body_font = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value(
            $preset,
            'tokens.typography.body',
            'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
        )
    );
    $display_font = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.typography.display', $body_font),
        $body_font
    );
    $display_weight = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.typography.displayWeight', '760'),
        '760'
    );

    $content_size = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.layout.content', '760px'),
        '760px'
    );
    $wide_size = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.layout.wide', '1320px'),
        '1320px'
    );
    $button_radius = staark_theme_system_safe_css_value(
        (string) staark_theme_system_value($preset, 'tokens.radius.button', '8px'),
        '8px'
    );

    $theme_json->update_with(
        [
            'version' => 3,
            'settings' => [
                'layout' => [
                    'contentSize' => $content_size,
                    'wideSize' => $wide_size,
                ],
                'color' => [
                    'defaultPalette' => false,
                    'palette' => $colors,
                ],
                'typography' => [
                    'fontFamilies' => [
                        [
                            'slug' => 'sans',
                            'name' => 'Body',
                            'fontFamily' => $body_font,
                        ],
                        [
                            'slug' => 'display',
                            'name' => 'Display',
                            'fontFamily' => $display_font,
                        ],
                    ],
                ],
            ],
            'styles' => [
                'typography' => [
                    'fontFamily' => 'var:preset|font-family|sans',
                ],
                'elements' => [
                    'heading' => [
                        'typography' => [
                            'fontFamily' => 'var:preset|font-family|display',
                            'fontWeight' => $display_weight,
                        ],
                    ],
                    'button' => [
                        'border' => [
                            'radius' => $button_radius,
                        ],
                    ],
                ],
            ],
        ]
    );

    return $theme_json;
}, 20);

add_action('wp_enqueue_scripts', static function (): void {
    $css = staark_theme_system_css();

    if ($css !== '') {
        wp_add_inline_style('staark-theme', $css);
    }

    $preset = staark_theme_system_active_preset();
    $stylesheet = isset($preset['stylesheet']) ? (string) $preset['stylesheet'] : '';

    if ($stylesheet === '') {
        return;
    }

    $file = get_theme_file_path($stylesheet);
    if (! is_file($file) || ! is_readable($file)) {
        return;
    }

    wp_enqueue_style(
        'staark-preset-' . staark_theme_system_active_preset_id(),
        get_theme_file_uri($stylesheet),
        ['staark-theme'],
        (string) filemtime($file)
    );
}, 20);

add_action('enqueue_block_editor_assets', static function (): void {
    $css = staark_theme_system_css();

    if ($css !== '') {
        wp_add_inline_style('wp-edit-blocks', $css);
    }
}, 20);

add_filter('body_class', static function (array $classes): array {
    $preset = staark_theme_system_active_preset();
    $id = staark_theme_system_active_preset_id();

    if ($id !== '') {
        $classes[] = 'staark-preset-' . sanitize_html_class($id);
    }

    $components = isset($preset['components']) && is_array($preset['components'])
        ? $preset['components']
        : [];

    foreach (['header', 'buttons', 'cards', 'hero', 'footer'] as $component) {
        if (! isset($components[$component])) {
            continue;
        }

        $variant = sanitize_key((string) $components[$component]);
        if ($variant !== '') {
            $classes[] = 'staark-' . $component . '-' . sanitize_html_class($variant);
        }
    }

    return array_values(array_unique($classes));
});

function staark_theme_system_admin_slug(): string
{
    return defined('STAARK_HUB_SLUG') ? 'staark-hub-theme' : 'staark-theme-system';
}

add_action('admin_menu', static function (): void {
    if (defined('STAARK_HUB_SLUG')) {
        add_submenu_page(
            STAARK_HUB_SLUG,
            __('Theme System', 'staark'),
            __('Theme', 'staark'),
            'manage_options',
            'staark-hub-theme',
            'staark_theme_system_render_admin_page'
        );

        return;
    }

    add_theme_page(
        __('Staark Theme System', 'staark'),
        __('Staark Theme', 'staark'),
        'manage_options',
        'staark-theme-system',
        'staark_theme_system_render_admin_page'
    );
}, 50);

add_action('admin_post_staark_theme_apply_preset', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark'));
    }

    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
        wp_die(esc_html__('This action requires POST.', 'staark'), '', ['response' => 405]);
    }

    $preset_id = isset($_POST['preset_id'])
        ? sanitize_key(wp_unslash($_POST['preset_id']))
        : '';

    $registry = staark_theme_system_registry();

    if ($preset_id === '' || ! isset($registry[$preset_id])) {
        wp_die(esc_html__('Unknown Staark theme preset.', 'staark'), '', ['response' => 400]);
    }

    check_admin_referer('staark_theme_apply_preset_' . $preset_id);

    set_theme_mod('staark_theme_preset', $preset_id);

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => staark_theme_system_admin_slug(),
                'staark_theme' => 'applied',
                'preset' => $preset_id,
            ],
            defined('STAARK_HUB_SLUG') ? admin_url('admin.php') : admin_url('themes.php')
        )
    );
    exit;
});

function staark_theme_system_render_admin_page(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $registry = staark_theme_system_registry();
    $active_id = staark_theme_system_active_preset_id();

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Staark Theme System', 'staark'); ?></h1>
        <p style="max-width:760px">
            <?php
            echo esc_html__(
                'Design presets control the visual foundation of the Staark theme: palette, typography, spacing, radii and component direction. Branding may override the primary brand color without replacing the active preset.',
                'staark'
            );
            ?>
        </p>

        <?php if (isset($_GET['staark_theme']) && sanitize_key(wp_unslash($_GET['staark_theme'])) === 'applied') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Theme preset applied.', 'staark'); ?></p>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:18px;max-width:980px;margin-top:24px">
            <?php foreach ($registry as $id => $preset) : ?>
                <?php
                $is_active = $id === $active_id;
                $colors = isset($preset['tokens']['colors']) && is_array($preset['tokens']['colors'])
                    ? $preset['tokens']['colors']
                    : [];
                ?>
                <div style="background:#fff;border:1px solid <?php echo $is_active ? '#2271b1' : '#dcdcde'; ?>;border-radius:10px;padding:22px">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px">
                        <div>
                            <h2 style="margin:0 0 6px"><?php echo esc_html((string) $preset['name']); ?></h2>
                            <p style="margin:0;color:#646970"><?php echo esc_html((string) $preset['description']); ?></p>
                        </div>
                        <?php if ($is_active) : ?>
                            <span style="background:#edfaef;color:#116329;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:700">
                                <?php echo esc_html__('Active', 'staark'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex;gap:7px;margin:20px 0">
                        <?php foreach (['ink', 'paper', 'surface', 'primary', 'forest', 'gold'] as $color_slug) : ?>
                            <?php
                            $color = isset($colors[$color_slug])
                                ? sanitize_hex_color((string) $colors[$color_slug])
                                : '';
                            if (! $color) {
                                continue;
                            }
                            ?>
                            <span
                                title="<?php echo esc_attr($color_slug . ': ' . $color); ?>"
                                style="display:block;width:30px;height:30px;border-radius:50%;background:<?php echo esc_attr($color); ?>;border:1px solid rgba(0,0,0,.10)"
                            ></span>
                        <?php endforeach; ?>
                    </div>

                    <?php if (! $is_active) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="staark_theme_apply_preset">
                            <input type="hidden" name="preset_id" value="<?php echo esc_attr($id); ?>">
                            <?php wp_nonce_field('staark_theme_apply_preset_' . $id); ?>
                            <?php submit_button(__('Apply preset', 'staark'), 'primary', 'submit', false); ?>
                        </form>
                    <?php else : ?>
                        <p style="margin:18px 0 0">
                            <strong><?php echo esc_html__('Component direction:', 'staark'); ?></strong><br>
                            <?php
                            $components = isset($preset['components']) && is_array($preset['components'])
                                ? $preset['components']
                                : [];
                            echo esc_html(
                                implode(
                                    ' · ',
                                    array_map(
                                        static fn (string $key, $value): string => ucfirst($key) . ': ' . sanitize_text_field((string) $value),
                                        array_keys($components),
                                        array_values($components)
                                    )
                                )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="margin-top:24px;color:#646970">
            <?php echo esc_html__('WP-6.4 ships the theme engine and the current Scandinavian preset. Additional design packs are added as independent presets.', 'staark'); ?>
        </p>
    </div>
    <?php
}
