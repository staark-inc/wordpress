<?php
/**
 * Keep starter pages in line with the active preset.
 *
 * - Pages created by First Install for another design pack (Showcase, Local
 *   Business …) reference that pack's patterns and render unstyled here.
 * - Switching between the Restaurang and Hotell presets leaves pages built
 *   for the other mode (a menu on a hotel homepage) and misses pages the new
 *   header links to (/rum/, /erbjudanden/ …).
 *
 * One explicit button fixes both: affected pages are rebuilt (the previous
 * content is stored as a revision first) and missing pages are created.
 *
 * @package StaarkGastfrihet
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Sections per starter page slug for the active mode.
 *
 * @return array<string,list<string>>
 */
function staark_gast_starter_page_map(): array
{
    return array_map(static fn (array $page): array => $page['sections'], staark_gast_page_sections());
}

/**
 * True when content is built from Staark patterns that do not fit the active
 * mode: another design pack, or sections that only exist in the other preset.
 */
function staark_gast_is_foreign_starter_content(string $content): bool
{
    if (! str_contains($content, '"slug":"staark/')) {
        return false;
    }

    if (! str_contains($content, '"slug":"' . STAARK_GAST_PATTERN_PREFIX)) {
        return true;
    }

    $other = staark_gast_is_hotel() ? 'restaurang' : 'hotell';

    foreach (staark_gast_mode_only_sections()[$other] as $section) {
        if (str_contains($content, '"slug":"' . STAARK_GAST_PATTERN_PREFIX . $section . '"')) {
            return true;
        }
    }

    return false;
}

function staark_gast_starter_page(string $slug): ?WP_Post
{
    $front_id = (int) get_option('page_on_front');
    $page = $slug === 'home' && $front_id > 0 ? get_post($front_id) : get_page_by_path($slug, OBJECT, 'page');

    return $page instanceof WP_Post && $page->post_type === 'page' && $page->post_status !== 'trash' ? $page : null;
}

/**
 * @return array{switch:array<string,WP_Post>,missing:array<string,string>}
 */
function staark_gast_starter_state(): array
{
    $state = ['switch' => [], 'missing' => []];
    $uses_starter = false;
    $titles = staark_gast_page_sections();

    foreach (array_keys(staark_gast_starter_page_map()) as $slug) {
        $page = staark_gast_starter_page($slug);

        if (! $page) {
            $state['missing'][$slug] = $titles[$slug]['title'];
            continue;
        }

        $content = (string) $page->post_content;
        if (str_contains($content, '"slug":"staark/')) {
            $uses_starter = true;
        }

        // Pages with patterns the active theme does not register at all are
        // handled by Staark Hub's starter page repair (one notice, not two).
        if (function_exists('staark_hub_starter_missing_patterns')
            && staark_hub_starter_missing_patterns($content) !== []) {
            continue;
        }

        if (staark_gast_is_foreign_starter_content($content)) {
            $state['switch'][$slug] = $page;
        }
    }

    // Only offer new pages on sites that use the starter pages.
    if (! $uses_starter || staark_gast_starter_page('home') === null) {
        $state['missing'] = [];
    }

    return $state;
}

function staark_gast_starter_notice_screens(): bool
{
    if (! function_exists('get_current_screen')) {
        return false;
    }

    $screen = get_current_screen();
    if (! $screen) {
        return false;
    }

    return in_array($screen->id, ['dashboard', 'themes', 'edit-page', 'appearance_page_staark-gast-booking'], true)
        || (function_exists('staark_hub_is_admin_page') && staark_hub_is_admin_page());
}

add_action('admin_notices', static function (): void {
    if (! current_user_can('edit_pages') || ! staark_gast_starter_notice_screens()) {
        return;
    }

    $label = staark_gast_is_hotel() ? __('Hotell', 'staark-gastfrihet') : __('Restaurang', 'staark-gastfrihet');
    $done = isset($_GET['staark_gast_starter']) ? sanitize_key(wp_unslash($_GET['staark_gast_starter'])) : '';

    if ($done === 'done') {
        $updated = isset($_GET['updated']) ? absint($_GET['updated']) : 0;
        $created = isset($_GET['created']) ? absint($_GET['created']) : 0;
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: 1: preset name, 2: rebuilt pages, 3: created pages. */
                    __('Pages set up for %1$s: %2$d rebuilt (previous content saved as a revision), %3$d created.', 'staark-gastfrihet'),
                    $label,
                    $updated,
                    $created
                )
            )
        );
        return;
    }

    $state = staark_gast_starter_state();
    if ($state['switch'] === [] && $state['missing'] === []) {
        return;
    }

    $titles = array_map(static fn (WP_Post $page): string => get_the_title($page), $state['switch']);
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php echo esc_html(sprintf(__('S-Hub Gästfrihet: some pages do not match the %s preset yet.', 'staark-gastfrihet'), $label)); ?></strong>
        </p>
        <?php if ($titles !== []) : ?>
            <p><?php echo esc_html(sprintf(__('Built for another design or preset: %s', 'staark-gastfrihet'), implode(', ', $titles))); ?></p>
        <?php endif; ?>
        <?php if ($state['missing'] !== []) : ?>
            <p><?php echo esc_html(sprintf(__('Linked from the menu but missing: %s', 'staark-gastfrihet'), implode(', ', $state['missing']))); ?></p>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Set up these pages? Rebuilt pages keep their current content as a revision.', 'staark-gastfrihet')); ?>');">
            <input type="hidden" name="action" value="staark_gast_apply_starter">
            <?php wp_nonce_field('staark_gast_apply_starter'); ?>
            <p><button type="submit" class="button button-primary"><?php echo esc_html(sprintf(__('Set up pages for %s', 'staark-gastfrihet'), $label)); ?></button></p>
        </form>
    </div>
    <?php
});

add_action('admin_post_staark_gast_apply_starter', static function (): void {
    if (! current_user_can('edit_pages')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-gastfrihet'));
    }

    check_admin_referer('staark_gast_apply_starter');

    $map = staark_gast_starter_page_map();
    $pages = staark_gast_page_sections();
    $state = staark_gast_starter_state();
    $updated = 0;
    $created = 0;

    foreach ($state['switch'] as $slug => $page) {
        if (! current_user_can('edit_post', $page->ID) || ! isset($map[$slug])) {
            continue;
        }

        if (function_exists('wp_save_post_revision')) {
            wp_save_post_revision($page->ID);
        }

        $result = wp_update_post(
            [
                'ID' => $page->ID,
                'post_content' => staark_gast_pattern_blocks($map[$slug]),
            ],
            true
        );

        if (! is_wp_error($result)) {
            ++$updated;
        }
    }

    // Page titles that still carry the other preset's default ("Boka bord" → "Boka rum").
    $other = staark_gast_page_sections(staark_gast_is_hotel() ? 'restaurang' : 'hotell');
    foreach ($pages as $slug => $page_def) {
        $page = staark_gast_starter_page($slug);
        if ($page && isset($other[$slug]) && $slug !== 'home'
            && $other[$slug]['title'] !== $page_def['title']
            && get_the_title($page) === $other[$slug]['title']
            && current_user_can('edit_post', $page->ID)) {
            wp_update_post(['ID' => $page->ID, 'post_title' => $page_def['title']]);
        }
    }

    if (current_user_can('publish_pages')) {
        foreach (array_keys($state['missing']) as $slug) {
            if ($slug === 'home' || ! isset($map[$slug])) {
                continue;
            }

            $result = wp_insert_post(
                [
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_name' => $slug,
                    'post_title' => $pages[$slug]['title'],
                    'post_content' => staark_gast_pattern_blocks($map[$slug]),
                ],
                true
            );

            if (! is_wp_error($result)) {
                ++$created;
            }
        }
    }

    $back = wp_get_referer() ?: admin_url('edit.php?post_type=page');
    wp_safe_redirect(add_query_arg(['staark_gast_starter' => 'done', 'updated' => $updated, 'created' => $created], $back));
    exit;
});

/*
 * Let Staark Hub's starter page repair rebuild every page this theme knows.
 */
add_filter('staark_hub_starter_repair_blueprints', static function ($blueprints, $preset) {
    if (! in_array($preset, ['restaurang', 'hotell'], true) || ! is_array($blueprints)) {
        return $blueprints;
    }

    foreach (staark_gast_page_blueprints((string) $preset) as $slug => $page) {
        if (! isset($blueprints[$slug])) {
            $blueprints[$slug] = $page;
        }
    }

    return $blueprints;
}, 10, 2);
