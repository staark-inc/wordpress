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
 * @return array{
 *   switch:array<string,WP_Post>,
 *   missing:array<string,string>,
 *   deactivate:array<string,WP_Post>
 * }
 */
function staark_gast_starter_state(): array
{
    $state = [
        'switch' => [],
        'missing' => [],
        'deactivate' => [],
    ];

    $uses_starter = false;
    $pages = staark_gast_page_sections();

    /*
     * Pages required by the active preset.
     */
    foreach ($pages as $slug => $page_def) {
        $page = staark_gast_starter_page($slug);

        if (! $page) {
            $state['missing'][$slug] = $page_def['title'];
            continue;
        }

        $content = (string) $page->post_content;

        if (str_contains($content, '"slug":"staark/')) {
            $uses_starter = true;
        }

        $expected = staark_gast_pattern_blocks($page_def['sections']);

        /*
         * A route may already exist as a draft:
         *
         * - because another Staark theme left it unpublished; or
         * - because Gästfrihet itself deactivated it when switching presets.
         *
         * If it is still a known starter page, rebuild/reactivate it instead
         * of treating the route as satisfied.
         */
        if ($page->post_status !== 'publish') {
            if (
                trim($content) === trim($expected)
                || staark_gast_is_foreign_starter_content($content)
            ) {
                $state['switch'][$slug] = $page;
            }

            continue;
        }

        /*
         * Published pages whose patterns are completely unavailable are
         * handled by the global Core repair notice.
         */
        if (
            function_exists('staark_hub_starter_missing_patterns')
            && staark_hub_starter_missing_patterns($content) !== []
        ) {
            continue;
        }

        if (staark_gast_is_foreign_starter_content($content)) {
            $state['switch'][$slug] = $page;
        }
    }

    /*
     * Pages exclusive to the other Gästfrihet preset should not remain
     * publicly exposed after switching.
     *
     * Be deliberately conservative: only deactivate a page when its content
     * still exactly matches the starter blueprint from the other preset.
     * A page that the client has edited manually is preserved.
     */
    $other_mode = staark_gast_is_hotel() ? 'restaurang' : 'hotell';
    $other_pages = staark_gast_page_sections($other_mode);

    foreach ($other_pages as $slug => $page_def) {
        if (isset($pages[$slug])) {
            continue;
        }

        $page = staark_gast_starter_page($slug);

        if (! $page || $page->post_status !== 'publish') {
            continue;
        }

        $expected = staark_gast_pattern_blocks($page_def['sections']);

        if (trim((string) $page->post_content) === trim($expected)) {
            $state['deactivate'][$slug] = $page;
        }
    }

    /*
     * Do not offer starter-page creation/deactivation on a site that is not
     * actually using the Staark starter structure.
     */
    if (! $uses_starter || staark_gast_starter_page('home') === null) {
        $state['missing'] = [];
        $state['deactivate'] = [];
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
    if (
        $state['switch'] === []
        && $state['missing'] === []
        && $state['deactivate'] === []
    ) {
        return;
    }

    $titles = array_map(
        static fn (WP_Post $page): string => get_the_title($page),
        $state['switch']
    );

    $deactivate_titles = array_map(
        static fn (WP_Post $page): string => get_the_title($page),
        $state['deactivate']
    );
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

        <?php if ($deactivate_titles !== []) : ?>
            <p><?php echo esc_html(sprintf(__('Starter pages not used by this preset will be set to draft: %s', 'staark-gastfrihet'), implode(', ', $deactivate_titles))); ?></p>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Set up these pages? Rebuilt pages keep their current content as a revision. Starter pages exclusive to the previous preset will be set to draft.', 'staark-gastfrihet')); ?>');">
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
    $deactivated = 0;

    foreach ($state['switch'] as $slug => $page) {
        if (! isset($map[$slug])) {
            continue;
        }

        /*
         * The action itself already requires edit_pages + nonce.
         * Every item in $state['switch'] is a detected Staark starter route,
         * so do not let an old per-post capability state make the route
         * impossible to repair.
         */
        if (function_exists('wp_save_post_revision')) {
            wp_save_post_revision($page->ID);
        }

        $update = [
            'ID' => $page->ID,
            'post_content' => staark_gast_pattern_blocks($map[$slug]),
        ];

        /*
         * A starter route left as draft by another Staark theme must become
         * usable again when the operator explicitly applies this preset.
         */
        if ($page->post_status !== 'publish' && current_user_can('publish_pages')) {
            $update['post_status'] = 'publish';
            $update['post_title'] = $pages[$slug]['title'];
        }

        $result = wp_update_post($update, true);

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

    foreach ($state['deactivate'] as $slug => $page) {
        unset($slug);

        if (! current_user_can('edit_post', $page->ID)) {
            continue;
        }

        if (function_exists('wp_save_post_revision')) {
            wp_save_post_revision($page->ID);
        }

        $result = wp_update_post(
            [
                'ID' => $page->ID,
                'post_status' => 'draft',
            ],
            true
        );

        if (! is_wp_error($result)) {
            ++$deactivated;
        }
    }

    $back = wp_get_referer() ?: admin_url('edit.php?post_type=page');

    wp_safe_redirect(
        add_query_arg(
            [
                'staark_gast_starter' => 'done',
                'updated' => $updated,
                'created' => $created,
                'deactivated' => $deactivated,
            ],
            $back
        )
    );
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
