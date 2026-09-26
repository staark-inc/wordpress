<?php
/**
 * Managed lifecycle for Staark starter pages.
 *
 * Starter pages owned by Staark carry metadata describing the design pack,
 * preset and exact starter blueprint last written to the page.
 *
 * A managed page is only rewritten, reactivated or deactivated while its
 * content/title still match the last Staark-managed version. Once the user
 * edits it, the page is preserved.
 *
 * @package StaarkCore
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_STARTER_CONTEXT_OPTION = 'staark_hub_starter_managed_context';
const STAARK_HUB_STARTER_LAST_RUN_OPTION = 'staark_hub_starter_managed_last_run';

const STAARK_HUB_STARTER_META_MANAGED = '_staark_starter_managed';
const STAARK_HUB_STARTER_META_PACK = '_staark_starter_pack';
const STAARK_HUB_STARTER_META_PRESET = '_staark_starter_preset';
const STAARK_HUB_STARTER_META_VERSION = '_staark_starter_version';
const STAARK_HUB_STARTER_META_SLUG = '_staark_starter_slug';
const STAARK_HUB_STARTER_META_HASH = '_staark_starter_hash';

/**
 * Stable hash for the starter-controlled portion of a page.
 */
function staark_hub_starter_managed_hash(string $title, string $content): string
{
    $normalize = static function (string $value): string {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value);
    };

    return hash(
        'sha256',
        $normalize($title) . "\0" . $normalize($content)
    );
}

function staark_hub_starter_managed_active_preset(): string
{
    return function_exists('staark_theme_system_active_preset_id')
        ? sanitize_key(staark_theme_system_active_preset_id())
        : 'scandinavian';
}

/**
 * Active page blueprints, including extra routes supplied by child themes.
 *
 * @return array<string,array{title:string,content:string}>
 */
function staark_hub_starter_managed_blueprints(): array
{
    if (! function_exists('staark_hub_first_install_page_blueprints')) {
        return [];
    }

    $preset = staark_hub_starter_managed_active_preset();

    $blueprints = staark_hub_first_install_page_blueprints($preset);

    // Salong / Bygg / Gästfrihet already use this extension point for routes
    // beyond the base First Install set.
    $blueprints = apply_filters(
        'staark_hub_starter_repair_blueprints',
        $blueprints,
        $preset
    );

    if (! is_array($blueprints)) {
        return [];
    }

    $clean = [];

    foreach ($blueprints as $slug => $page) {
        $slug = sanitize_title((string) $slug);

        if (
            $slug === ''
            || ! is_array($page)
            || ! isset($page['title'], $page['content'])
        ) {
            continue;
        }

        $clean[$slug] = [
            'title' => (string) $page['title'],
            'content' => (string) $page['content'],
        ];
    }

    ksort($clean);

    return $clean;
}

/**
 * @param array<string,array{title:string,content:string}> $blueprints
 * @return array{pack:string,preset:string,version:string,blueprints:string,signature:string}
 */
function staark_hub_starter_managed_context(array $blueprints = []): array
{
    if ($blueprints === []) {
        $blueprints = staark_hub_starter_managed_blueprints();
    }

    $theme = wp_get_theme();

    $fingerprint = [];

    foreach ($blueprints as $slug => $page) {
        $fingerprint[$slug] = staark_hub_starter_managed_hash(
            (string) $page['title'],
            (string) $page['content']
        );
    }

    $context = [
        'pack' => sanitize_key((string) $theme->get_stylesheet()),
        'preset' => staark_hub_starter_managed_active_preset(),
        'version' => (string) ($theme->get('Version') ?: '0'),
        'blueprints' => hash('sha256', wp_json_encode($fingerprint)),
    ];

    $context['signature'] = hash(
        'sha256',
        implode(
            '|',
            [
                $context['pack'],
                $context['preset'],
                $context['version'],
                $context['blueprints'],
            ]
        )
    );

    return $context;
}

/**
 * Locate the page corresponding to a starter route.
 */
function staark_hub_starter_managed_page(string $slug): ?WP_Post
{
    if ($slug === 'home') {
        $front_id = (int) get_option('page_on_front');

        if ($front_id > 0) {
            $front = get_post($front_id);

            if ($front instanceof WP_Post && $front->post_type === 'page') {
                return $front;
            }
        }
    }

    $page = get_page_by_path($slug, OBJECT, 'page');

    return $page instanceof WP_Post ? $page : null;
}

function staark_hub_starter_managed_is_managed(int $post_id): bool
{
    return (string) get_post_meta(
        $post_id,
        STAARK_HUB_STARTER_META_MANAGED,
        true
    ) === '1';
}

/**
 * @param array{title:string,content:string} $blueprint
 * @param array<string,string>|null $context
 */
function staark_hub_starter_managed_mark_page(
    int $post_id,
    string $slug,
    array $blueprint,
    ?array $context = null
): void {
    $context = $context ?? staark_hub_starter_managed_context();

    update_post_meta($post_id, STAARK_HUB_STARTER_META_MANAGED, '1');
    update_post_meta($post_id, STAARK_HUB_STARTER_META_PACK, $context['pack']);
    update_post_meta($post_id, STAARK_HUB_STARTER_META_PRESET, $context['preset']);
    update_post_meta($post_id, STAARK_HUB_STARTER_META_VERSION, $context['version']);
    update_post_meta($post_id, STAARK_HUB_STARTER_META_SLUG, sanitize_title($slug));

    update_post_meta(
        $post_id,
        STAARK_HUB_STARTER_META_HASH,
        staark_hub_starter_managed_hash(
            (string) $blueprint['title'],
            (string) $blueprint['content']
        )
    );
}

/**
 * Has the page stayed untouched since Staark last wrote its starter version?
 */
function staark_hub_starter_managed_is_unchanged(WP_Post $page): bool
{
    $saved = (string) get_post_meta(
        $page->ID,
        STAARK_HUB_STARTER_META_HASH,
        true
    );

    if ($saved === '') {
        return false;
    }

    return hash_equals(
        $saved,
        staark_hub_starter_managed_hash(
            (string) $page->post_title,
            (string) $page->post_content
        )
    );
}

/**
 * Adopt old starter pages only when they are byte-for-byte equivalent to the
 * active starter blueprint after harmless newline normalization.
 *
 * @param array<string,array{title:string,content:string}> $blueprints
 * @param array<string,string> $context
 * @return int
 */
function staark_hub_starter_managed_adopt_exact_pages(
    array $blueprints,
    array $context
): int {
    $adopted = 0;

    foreach ($blueprints as $slug => $blueprint) {
        $page = staark_hub_starter_managed_page($slug);

        if (
            ! $page instanceof WP_Post
            || staark_hub_starter_managed_is_managed($page->ID)
        ) {
            continue;
        }

        $current = staark_hub_starter_managed_hash(
            (string) $page->post_title,
            (string) $page->post_content
        );

        $expected = staark_hub_starter_managed_hash(
            (string) $blueprint['title'],
            (string) $blueprint['content']
        );

        if (! hash_equals($expected, $current)) {
            continue;
        }

        staark_hub_starter_managed_mark_page(
            $page->ID,
            $slug,
            $blueprint,
            $context
        );

        ++$adopted;
    }

    return $adopted;
}

/**
 * @return array<int,WP_Post>
 */
function staark_hub_starter_managed_pages(): array
{
    $pages = get_posts(
        [
            'post_type' => 'page',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'meta_key' => STAARK_HUB_STARTER_META_MANAGED,
            'meta_value' => '1',
            'orderby' => 'ID',
            'order' => 'ASC',
            'suppress_filters' => true,
        ]
    );

    return array_values(
        array_filter(
            $pages,
            static fn ($page): bool => $page instanceof WP_Post
        )
    );
}

function staark_hub_starter_managed_enabled(): bool
{
    $state = function_exists('staark_hub_first_install_state')
        ? staark_hub_first_install_state()
        : [];

    if (! empty($state['completed'])) {
        return true;
    }

    return staark_hub_starter_managed_pages() !== [];
}

/**
 * Reconcile managed routes with the currently active theme/preset.
 *
 * @return array<string,mixed>
 */
function staark_hub_starter_managed_reconcile(bool $force = false): array
{
    $blueprints = staark_hub_starter_managed_blueprints();
    $context = staark_hub_starter_managed_context($blueprints);

    $previous = get_option(STAARK_HUB_STARTER_CONTEXT_OPTION, []);
    $previous = is_array($previous) ? $previous : [];

    $adopted = staark_hub_starter_managed_adopt_exact_pages(
        $blueprints,
        $context
    );

    /*
     * First run is migration-only. Establish a safe baseline and do not
     * rewrite/draft anything merely because the feature was installed.
     */
    if ($previous === [] && ! $force) {
        update_option(
            STAARK_HUB_STARTER_CONTEXT_OPTION,
            $context,
            false
        );

        $result = [
            'mode' => 'baseline',
            'context' => $context,
            'adopted' => $adopted,
            'updated' => 0,
            'created' => 0,
            'deactivated' => 0,
            'preserved' => [],
            'conflicts' => [],
        ];

        update_option(
            STAARK_HUB_STARTER_LAST_RUN_OPTION,
            $result,
            false
        );

        return $result;
    }

    $changed = ! isset($previous['signature'])
        || ! hash_equals(
            (string) $previous['signature'],
            (string) $context['signature']
        );

    if (! $force && ! $changed) {
        return [
            'mode' => 'noop',
            'context' => $context,
            'adopted' => $adopted,
            'updated' => 0,
            'created' => 0,
            'deactivated' => 0,
            'preserved' => [],
            'conflicts' => [],
        ];
    }

    if (get_transient('staark_hub_starter_managed_lock')) {
        return [
            'mode' => 'locked',
            'context' => $context,
            'adopted' => $adopted,
        ];
    }

    set_transient('staark_hub_starter_managed_lock', '1', 30);

    $updated = 0;
    $created = 0;
    $deactivated = 0;
    $preserved = [];
    $conflicts = [];

    try {
        /*
         * First update/deactivate pages already owned by Staark.
         */
        foreach (staark_hub_starter_managed_pages() as $page) {
            $slug = sanitize_title(
                (string) get_post_meta(
                    $page->ID,
                    STAARK_HUB_STARTER_META_SLUG,
                    true
                )
            );

            if ($slug === '') {
                $slug = (
                    (int) get_option('page_on_front') === (int) $page->ID
                )
                    ? 'home'
                    : (string) $page->post_name;
            }

            if (! staark_hub_starter_managed_is_unchanged($page)) {
                $preserved[] = [
                    'id' => (int) $page->ID,
                    'slug' => $slug,
                    'title' => (string) $page->post_title,
                ];
                continue;
            }

            /*
             * Route belongs to another pack/preset now.
             */
            if (! isset($blueprints[$slug])) {
                if ($page->post_status === 'publish') {
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

                continue;
            }

            $blueprint = $blueprints[$slug];

            $needs_update =
                $page->post_status !== 'publish'
                || (string) $page->post_title !== (string) $blueprint['title']
                || staark_hub_starter_managed_hash(
                    (string) $page->post_title,
                    (string) $page->post_content
                ) !== staark_hub_starter_managed_hash(
                    (string) $blueprint['title'],
                    (string) $blueprint['content']
                );

            if ($needs_update) {
                if (function_exists('wp_save_post_revision')) {
                    wp_save_post_revision($page->ID);
                }

                $result = wp_update_post(
                    [
                        'ID' => $page->ID,
                        'post_status' => 'publish',
                        'post_title' => (string) $blueprint['title'],
                        'post_content' => (string) $blueprint['content'],
                    ],
                    true
                );

                if (is_wp_error($result)) {
                    $conflicts[] = [
                        'id' => (int) $page->ID,
                        'slug' => $slug,
                        'reason' => $result->get_error_message(),
                    ];
                    continue;
                }

                ++$updated;
            }

            staark_hub_starter_managed_mark_page(
                $page->ID,
                $slug,
                $blueprint,
                $context
            );
        }

        /*
         * Then create/adopt active routes that do not have a managed page yet.
         */
        if (staark_hub_starter_managed_enabled()) {
            foreach ($blueprints as $slug => $blueprint) {
                $page = staark_hub_starter_managed_page($slug);

                if ($page instanceof WP_Post) {
                    if (staark_hub_starter_managed_is_managed($page->ID)) {
                        continue;
                    }

                    $current = staark_hub_starter_managed_hash(
                        (string) $page->post_title,
                        (string) $page->post_content
                    );

                    $expected = staark_hub_starter_managed_hash(
                        (string) $blueprint['title'],
                        (string) $blueprint['content']
                    );

                    if (hash_equals($expected, $current)) {
                        staark_hub_starter_managed_mark_page(
                            $page->ID,
                            $slug,
                            $blueprint,
                            $context
                        );

                        if ($page->post_status !== 'publish') {
                            wp_update_post(
                                [
                                    'ID' => $page->ID,
                                    'post_status' => 'publish',
                                ]
                            );
                        }

                        continue;
                    }

                    $conflicts[] = [
                        'id' => (int) $page->ID,
                        'slug' => $slug,
                        'reason' => 'Existing custom page preserved.',
                    ];
                    continue;
                }

                $result = wp_insert_post(
                    [
                        'post_type' => 'page',
                        'post_status' => 'publish',
                        'post_title' => (string) $blueprint['title'],
                        'post_name' => sanitize_title($slug),
                        'post_content' => (string) $blueprint['content'],
                    ],
                    true
                );

                if (is_wp_error($result)) {
                    $conflicts[] = [
                        'id' => 0,
                        'slug' => $slug,
                        'reason' => $result->get_error_message(),
                    ];
                    continue;
                }

                staark_hub_starter_managed_mark_page(
                    (int) $result,
                    $slug,
                    $blueprint,
                    $context
                );

                if (
                    $slug === 'home'
                    && get_option('show_on_front') === 'page'
                    && (int) get_option('page_on_front') === 0
                ) {
                    update_option('page_on_front', (int) $result);
                }

                ++$created;
            }
        }

        update_option(
            STAARK_HUB_STARTER_CONTEXT_OPTION,
            $context,
            false
        );

        $result = [
            'mode' => $force ? 'forced' : 'reconciled',
            'context' => $context,
            'adopted' => $adopted,
            'updated' => $updated,
            'created' => $created,
            'deactivated' => $deactivated,
            'preserved' => $preserved,
            'conflicts' => $conflicts,
        ];

        update_option(
            STAARK_HUB_STARTER_LAST_RUN_OPTION,
            $result,
            false
        );

        return $result;
    } finally {
        delete_transient('staark_hub_starter_managed_lock');
    }
}

/**
 * Useful for WP-CLI diagnostics.
 *
 * @return array<string,mixed>
 */
function staark_hub_starter_managed_status(): array
{
    $pages = [];

    foreach (staark_hub_starter_managed_pages() as $page) {
        $pages[] = [
            'id' => (int) $page->ID,
            'slug' => (string) get_post_meta(
                $page->ID,
                STAARK_HUB_STARTER_META_SLUG,
                true
            ),
            'status' => (string) $page->post_status,
            'title' => (string) $page->post_title,
            'pack' => (string) get_post_meta(
                $page->ID,
                STAARK_HUB_STARTER_META_PACK,
                true
            ),
            'preset' => (string) get_post_meta(
                $page->ID,
                STAARK_HUB_STARTER_META_PRESET,
                true
            ),
            'unchanged' => staark_hub_starter_managed_is_unchanged($page),
        ];
    }

    return [
        'current' => staark_hub_starter_managed_context(),
        'stored' => get_option(STAARK_HUB_STARTER_CONTEXT_OPTION, []),
        'lastRun' => get_option(STAARK_HUB_STARTER_LAST_RUN_OPTION, []),
        'pages' => $pages,
    ];
}

/*
 * Automatic lifecycle. The context signature changes when the active theme,
 * preset, theme version or actual blueprint set changes.
 */
const STAARK_HUB_STARTER_GATE_OPTION = 'staark_hub_starter_managed_gate';
const STAARK_HUB_STARTER_RECONCILE_EVENT = 'staark_hub_starter_reconcile';

/**
 * Cheap fingerprint of everything that can change the starter blueprints:
 * theme, parent, theme version, preset, front page and Staark Core version.
 * Reading it costs one autoloaded option, no queries.
 */
function staark_hub_starter_managed_gate_key(): string
{
    $theme = wp_get_theme();

    return md5(implode('|', [
        get_stylesheet(),
        get_template(),
        (string) $theme->get('Version'),
        (string) wp_get_theme(get_template())->get('Version'),
        (string) get_theme_mod('staark_theme_preset', ''),
        (string) get_option('page_on_front'),
        STAARK_HUB_VERSION,
    ]));
}

function staark_hub_starter_managed_reconcile_if_changed(): void
{
    $key = staark_hub_starter_managed_gate_key();
    if (get_option(STAARK_HUB_STARTER_GATE_OPTION) === $key) {
        return;
    }

    staark_hub_starter_managed_reconcile(false);
    update_option(STAARK_HUB_STARTER_GATE_OPTION, $key, true);
}

/*
 * Automatic lifecycle. The full reconcile (blueprints, page lookups, hashes)
 * only runs when the gate key changed, and never inside a visitor's request:
 * admin, cron and WP-CLI run it directly, front-end requests schedule it.
 */
add_action(
    'init',
    static function (): void {
        if (get_option(STAARK_HUB_STARTER_GATE_OPTION) === staark_hub_starter_managed_gate_key()) {
            return;
        }

        if (is_admin() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            staark_hub_starter_managed_reconcile_if_changed();
            return;
        }

        if (! wp_next_scheduled(STAARK_HUB_STARTER_RECONCILE_EVENT)) {
            wp_schedule_single_event(time(), STAARK_HUB_STARTER_RECONCILE_EVENT);
        }
    },
    100
);

add_action(STAARK_HUB_STARTER_RECONCILE_EVENT, 'staark_hub_starter_managed_reconcile_if_changed');

/**
 * Force the next request to reconcile (and adopt new starter pages), e.g.
 * after First Install or the starter repair created pages without changing
 * theme, preset or front page.
 */
function staark_hub_starter_managed_invalidate(): void
{
    delete_option(STAARK_HUB_STARTER_GATE_OPTION);
}

add_action('staark_hub_first_install_completed', 'staark_hub_starter_managed_invalidate');
// Starter repair and the themes' "set up pages" actions create or rewrite
// pages; page saves are rare, so any page save re-checks on the next request.
// (The reconcile stores the gate after its own page saves, so it cannot loop.)
add_action('save_post_page', 'staark_hub_starter_managed_invalidate');
