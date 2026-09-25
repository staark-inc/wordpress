<?php
/**
 * Starter page repair after a theme / design pack switch.
 *
 * Starter pages are built from block patterns ("staark/salong-hero",
 * "staark/bygg-quote", "staark/local-business-home" …). Patterns belong to a
 * theme, so after switching theme a page can reference patterns that are no
 * longer registered — WordPress then renders those sections as nothing and
 * the page looks empty.
 *
 * This detects such pages for any Staark theme and offers an explicit,
 * one-click rebuild from the active preset's First Install blueprint. The
 * current content is stored as a revision before anything is replaced.
 *
 * @package StaarkCore
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pattern slugs referenced by block content.
 *
 * @return list<string>
 */
function staark_hub_starter_referenced_patterns(string $content): array
{
    if (! str_contains($content, 'wp:pattern')) {
        return [];
    }

    preg_match_all('/<!--\s+wp:pattern\s+\{[^}]*"slug"\s*:\s*"([^"]+)"/', $content, $matches);

    return array_values(array_unique($matches[1] ?? []));
}

/**
 * Referenced Staark patterns that the active theme does not register.
 *
 * @return list<string>
 */
function staark_hub_starter_missing_patterns(string $content): array
{
    if (! class_exists('WP_Block_Patterns_Registry')) {
        return [];
    }

    $registry = WP_Block_Patterns_Registry::get_instance();
    $missing = [];

    foreach (staark_hub_starter_referenced_patterns($content) as $slug) {
        if (str_starts_with($slug, 'staark/') && ! $registry->is_registered($slug)) {
            $missing[] = $slug;
        }
    }

    return $missing;
}

function staark_hub_starter_active_preset(): string
{
    return function_exists('staark_theme_system_active_preset_id')
        ? staark_theme_system_active_preset_id()
        : 'scandinavian';
}

/**
 * Page blueprints used for the repair: the First Install blueprints of the
 * active preset, extendable by themes for extra slugs (e.g. "kontakt").
 *
 * @return array<string,array{title:string,content:string}>
 */
function staark_hub_starter_repair_blueprints(): array
{
    $preset = staark_hub_starter_active_preset();
    $blueprints = (array) apply_filters(
        'staark_hub_starter_repair_blueprints',
        staark_hub_first_install_page_blueprints($preset),
        $preset
    );

    return array_filter(
        $blueprints,
        static fn ($page): bool => is_array($page) && isset($page['content'])
    );
}

/**
 * Published pages whose Staark patterns are missing in the active theme.
 *
 * @return array{fixable:array<string,WP_Post>,orphaned:array<int,WP_Post>}
 */
function staark_hub_starter_broken_pages(): array
{
    $blueprints = staark_hub_starter_repair_blueprints();
    $front_id = (int) get_option('page_on_front');
    $result = ['fixable' => [], 'orphaned' => []];

    $pages = get_posts(
        [
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            's' => 'wp:pattern',
            'suppress_filters' => true,
        ]
    );

    foreach ($pages as $page) {
        if (! $page instanceof WP_Post || staark_hub_starter_missing_patterns((string) $page->post_content) === []) {
            continue;
        }

        $slug = (int) $page->ID === $front_id ? 'home' : (string) $page->post_name;

        if (isset($blueprints[$slug])) {
            $result['fixable'][$slug] = $page;
        } else {
            $result['orphaned'][(int) $page->ID] = $page;
        }
    }

    return $result;
}

function staark_hub_starter_notice_screen(): bool
{
    if (! function_exists('get_current_screen')) {
        return false;
    }

    $screen = get_current_screen();

    return $screen && (
        in_array($screen->id, ['dashboard', 'themes', 'edit-page'], true)
        || (function_exists('staark_hub_is_admin_page') && staark_hub_is_admin_page())
    );
}

add_action('admin_notices', static function (): void {
    if (! current_user_can('edit_pages') || ! staark_hub_starter_notice_screen()) {
        return;
    }

    $state = isset($_GET['staark_starter_repair']) ? sanitize_key(wp_unslash($_GET['staark_starter_repair'])) : '';
    if ($state === 'done') {
        $count = isset($_GET['updated']) ? absint($_GET['updated']) : 0;
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %d: number of pages. */
                    _n(
                        '%d page was rebuilt for the active design. The previous content is saved as a revision.',
                        '%d pages were rebuilt for the active design. The previous content is saved as a revision.',
                        $count,
                        'staark-core'
                    ),
                    $count
                )
            )
        );
        return;
    }

    $broken = staark_hub_starter_broken_pages();
    if ($broken['fixable'] === [] && $broken['orphaned'] === []) {
        return;
    }

    $theme = wp_get_theme();
    $title = static fn (WP_Post $page): string => get_the_title($page) !== '' ? get_the_title($page) : $page->post_name;
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php echo esc_html__('Some pages use sections from another Staark theme.', 'staark-core'); ?></strong>
            <?php
            echo esc_html(
                sprintf(
                    /* translators: %s: active theme name. */
                    __('Those sections are not available in %s, so they show up empty on the website.', 'staark-core'),
                    $theme->get('Name')
                )
            );
            ?>
        </p>
        <?php if ($broken['fixable'] !== []) : ?>
            <p><?php echo esc_html(sprintf(__('Can be rebuilt: %s', 'staark-core'), implode(', ', array_map($title, $broken['fixable'])))); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Rebuild these pages with the starter sections of the active design? The current content is saved as a revision first.', 'staark-core')); ?>');">
                <input type="hidden" name="action" value="staark_starter_repair">
                <?php wp_nonce_field('staark_starter_repair'); ?>
                <p><button type="submit" class="button button-primary"><?php echo esc_html__('Rebuild pages for the active design', 'staark-core'); ?></button></p>
            </form>
        <?php endif; ?>
        <?php if ($broken['orphaned'] !== []) : ?>
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %s: comma-separated page titles. */
                        __('No starter version in this design: %s. Switch back to the theme they were made for, edit them, or set them to draft.', 'staark-core'),
                        implode(', ', array_map($title, $broken['orphaned']))
                    )
                );
                ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
});

add_action('admin_post_staark_starter_repair', static function (): void {
    if (! current_user_can('edit_pages')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_starter_repair');

    $blueprints = staark_hub_starter_repair_blueprints();
    $updated = 0;

    foreach (staark_hub_starter_broken_pages()['fixable'] as $slug => $page) {
        if (! isset($blueprints[$slug]) || ! current_user_can('edit_post', $page->ID)) {
            continue;
        }

        if (function_exists('wp_save_post_revision')) {
            wp_save_post_revision($page->ID);
        }

        $result = wp_update_post(
            [
                'ID' => $page->ID,
                'post_content' => (string) $blueprints[$slug]['content'],
            ],
            true
        );

        if (! is_wp_error($result)) {
            ++$updated;
        }
    }

    $back = wp_get_referer() ?: admin_url('edit.php?post_type=page');
    wp_safe_redirect(add_query_arg(['staark_starter_repair' => 'done', 'updated' => $updated], $back));
    exit;
});
