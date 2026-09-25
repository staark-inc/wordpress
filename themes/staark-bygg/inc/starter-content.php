<?php
/**
 * Switch existing starter pages to the Bygg layout.
 *
 * Pages created by Staark First Install for another design pack (Showcase,
 * Local Business …) reference that pack's patterns. Their styles only load
 * while that pack's preset is active, so after switching to S-Hub Bygg the
 * homepage renders almost unstyled. This offers a one-click, explicit switch:
 * only pages built from another pack's patterns are touched, and the previous
 * content is stored as a revision first.
 *
 * @package StaarkBygg
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Bygg sections per starter page slug.
 *
 * @return array<string,list<string>>
 */
function staark_bygg_starter_page_map(): array
{
    $map = array_map(static fn (array $page): array => $page['sections'], staark_bygg_page_sections());
    $map['kontakt'] = ['quote', 'area'];

    return $map;
}

/**
 * True when content is built from Staark patterns of another design pack.
 */
function staark_bygg_is_foreign_starter_content(string $content): bool
{
    if (! str_contains($content, '"slug":"staark/')) {
        return false;
    }

    return ! str_contains($content, '"slug":"' . STAARK_BYGG_PATTERN_PREFIX);
}

/**
 * Starter pages that would be switched: slug => page.
 *
 * @return array<string,WP_Post>
 */
function staark_bygg_foreign_starter_pages(): array
{
    $pages = [];
    $front_id = (int) get_option('page_on_front');

    foreach (array_keys(staark_bygg_starter_page_map()) as $slug) {
        $page = $slug === 'home' && $front_id > 0 ? get_post($front_id) : get_page_by_path($slug, OBJECT, 'page');

        if (! $page instanceof WP_Post || $page->post_type !== 'page') {
            continue;
        }

        // Pages with patterns the active theme does not register at all are
        // handled by Staark Hub's starter page repair (one notice, not two).
        if (function_exists('staark_hub_starter_missing_patterns')
            && staark_hub_starter_missing_patterns((string) $page->post_content) !== []) {
            continue;
        }

        if (staark_bygg_is_foreign_starter_content((string) $page->post_content)) {
            $pages[$slug] = $page;
        }
    }

    return $pages;
}

function staark_bygg_starter_notice_screens(): bool
{
    if (! function_exists('get_current_screen')) {
        return false;
    }

    $screen = get_current_screen();
    if (! $screen) {
        return false;
    }

    return in_array($screen->id, ['dashboard', 'themes', 'edit-page'], true)
        || (function_exists('staark_hub_is_admin_page') && staark_hub_is_admin_page());
}

add_action('admin_notices', static function (): void {
    if (! current_user_can('edit_pages') || ! staark_bygg_starter_notice_screens()) {
        return;
    }

    $state = isset($_GET['staark_bygg_starter']) ? sanitize_key(wp_unslash($_GET['staark_bygg_starter'])) : '';

    if ($state === 'done') {
        $count = isset($_GET['updated']) ? absint($_GET['updated']) : 0;
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %d: number of pages. */
                    _n(
                        '%d page now uses the Bygg layout. The previous content is saved as a revision.',
                        '%d pages now use the Bygg layout. The previous content is saved as a revision.',
                        $count,
                        'staark-bygg'
                    ),
                    $count
                )
            )
        );
        return;
    }

    $pages = staark_bygg_foreign_starter_pages();
    if ($pages === []) {
        return;
    }

    $titles = array_map(static fn (WP_Post $page): string => get_the_title($page), $pages);
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php echo esc_html__('S-Hub Bygg: some starter pages still use another design pack.', 'staark-bygg'); ?></strong>
            <?php echo esc_html__('They were created for a different preset, so their sections are not styled by the Bygg theme.', 'staark-bygg'); ?>
        </p>
        <p><?php echo esc_html(sprintf(__('Pages: %s', 'staark-bygg'), implode(', ', $titles))); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Replace these pages with the Bygg sections? The current content is saved as a revision first.', 'staark-bygg')); ?>');">
            <input type="hidden" name="action" value="staark_bygg_apply_starter">
            <?php wp_nonce_field('staark_bygg_apply_starter'); ?>
            <p><button type="submit" class="button button-primary"><?php echo esc_html__('Use the Bygg layout for these pages', 'staark-bygg'); ?></button></p>
        </form>
    </div>
    <?php
});

add_action('admin_post_staark_bygg_apply_starter', static function (): void {
    if (! current_user_can('edit_pages')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-bygg'));
    }

    check_admin_referer('staark_bygg_apply_starter');

    $map = staark_bygg_starter_page_map();
    $updated = 0;

    foreach (staark_bygg_foreign_starter_pages() as $slug => $page) {
        if (! current_user_can('edit_post', $page->ID) || ! isset($map[$slug])) {
            continue;
        }

        // Keep the current content restorable from the page's revisions.
        if (function_exists('wp_save_post_revision')) {
            wp_save_post_revision($page->ID);
        }

        $result = wp_update_post(
            [
                'ID' => $page->ID,
                'post_content' => staark_bygg_pattern_blocks($map[$slug]),
            ],
            true
        );

        if (! is_wp_error($result)) {
            ++$updated;
        }
    }

    $back = wp_get_referer() ?: admin_url('edit.php?post_type=page');
    wp_safe_redirect(add_query_arg(['staark_bygg_starter' => 'done', 'updated' => $updated], $back));
    exit;
});

/*
 * Let Staark Hub's starter page repair rebuild every page this theme knows,
 * not only the First Install ones (for example "tjanster" or "kontakt").
 */
add_filter('staark_hub_starter_repair_blueprints', static function ($blueprints, $preset) {
    if ($preset !== 'bygg' || ! is_array($blueprints)) {
        return $blueprints;
    }

    foreach (staark_bygg_starter_page_map() as $slug => $sections) {
        if (! isset($blueprints[$slug])) {
            $blueprints[$slug] = [
                'title' => ucfirst(str_replace('-', ' ', $slug)),
                'content' => staark_bygg_pattern_blocks($sections),
            ];
        }
    }

    return $blueprints;
}, 10, 2);
