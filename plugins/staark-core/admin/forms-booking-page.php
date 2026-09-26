<?php
/**
 * Forms & Booking admin screens: Inbox, request detail, Bookings, Forms,
 * Notifications and Settings.
 *
 * @package StaarkCore
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', static function (): void {
    $cap = staark_hub_fb_cap();
    $count = staark_hub_fb_notify_settings()['admin_badge'] ? staark_hub_fb_counts()['attention'] : 0;
    $badge = $count > 0
        ? sprintf(' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>', $count)
        : '';

    add_menu_page(
        staark_hub_fb_label(),
        staark_hub_fb_label() . $badge,
        $cap,
        'staark-hub-inbox',
        'staark_hub_render_inbox',
        'dashicons-calendar-alt',
        4
    );

    add_submenu_page('staark-hub-inbox', __('Inbox', 'staark-core'), __('Inbox', 'staark-core') . $badge, $cap, 'staark-hub-inbox', 'staark_hub_render_inbox');
    add_submenu_page('staark-hub-inbox', __('Bookings', 'staark-core'), __('Bookings', 'staark-core'), $cap, 'staark-hub-bookings', 'staark_hub_render_bookings');
    add_submenu_page('staark-hub-inbox', __('Forms', 'staark-core'), __('Forms', 'staark-core'), 'manage_options', 'staark-hub-form-list', 'staark_hub_render_form_list');
    add_submenu_page('staark-hub-inbox', __('Notifications', 'staark-core'), __('Notifications', 'staark-core'), 'manage_options', 'staark-hub-notifications', 'staark_hub_render_notifications');
    add_submenu_page('staark-hub-inbox', __('Form settings', 'staark-core'), __('Settings', 'staark-core'), 'manage_options', 'staark-hub-form-settings', 'staark_hub_render_form_settings');
}, 20);

/* ------------------------------------------------------------------ */
/* Shared pieces                                                       */
/* ------------------------------------------------------------------ */

function staark_hub_fb_header(string $title): void
{
    $pages = staark_hub_fb_pages();
    if (! current_user_can('manage_options')) {
        $pages = array_intersect_key($pages, array_flip(['staark-hub-inbox', 'staark-hub-bookings']));
    }

    $counts = staark_hub_fb_counts();
    staark_hub_header(
        staark_hub_fb_label(),
        $title,
        $pages,
        [
            'staark-hub-inbox' => $counts['attention'],
            'staark-hub-bookings' => $counts['pending'],
        ]
    );
}

function staark_hub_fb_notices(): void
{
    $notice = isset($_GET['fb_notice']) ? sanitize_key(wp_unslash($_GET['fb_notice'])) : '';
    $mail = isset($_GET['fb_mail']) ? sanitize_key(wp_unslash($_GET['fb_mail'])) : '';

    if ($notice === '') {
        return;
    }

    $messages = [
        'saved' => [__('Saved.', 'staark-core'), 'success'],
        'status' => [__('Status updated.', 'staark-core'), 'success'],
        'reply' => [__('Reply sent to the customer.', 'staark-core'), 'success'],
        'deleted' => [__('Request deleted permanently.', 'staark-core'), 'success'],
        'bulk' => [sprintf(__('%d requests updated.', 'staark-core'), isset($_GET['fb_done']) ? absint($_GET['fb_done']) : 0), 'success'],
        'preview_sent' => [__('Test email sent to your address.', 'staark-core'), 'success'],
        'preview_failed' => [__('Test email could not be sent. Check Settings → Mail transport.', 'staark-core'), 'error'],
        'mail_retry' => [$mail === 'sent' ? __('Notification sent.', 'staark-core') : __('Notification failed. See the activity log.', 'staark-core'), $mail === 'sent' ? 'success' : 'error'],
        'error' => [__('Something went wrong. Nothing was changed.', 'staark-core'), 'error'],
    ];

    if ($notice === 'reply' && $mail !== 'sent') {
        $messages['reply'] = [__('The reply could not be sent. Check Settings → Mail transport.', 'staark-core'), 'error'];
    }

    if ($notice === 'mail_test') {
        $result = get_transient('staark_forms_mail_test_' . get_current_user_id());
        delete_transient('staark_forms_mail_test_' . get_current_user_id());
        if (is_array($result)) {
            $messages['mail_test'] = ! empty($result['sent'])
                ? [sprintf(__('Test email sent to %s.', 'staark-core'), (string) ($result['recipient'] ?? '')), 'success']
                : [__('Test email failed: ', 'staark-core') . (string) ($result['error'] ?? ''), 'error'];
        }
    }

    if (! isset($messages[$notice])) {
        return;
    }

    [$text, $type] = $messages[$notice];

    if ($notice === 'status' && $mail !== '') {
        $text .= ' ' . ($mail === 'sent' ? __('The customer was emailed.', 'staark-core') : __('The email to the customer failed — see the activity log.', 'staark-core'));
        $type = $mail === 'sent' ? 'success' : 'warning';
    }

    printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr($type), esc_html($text));
}

function staark_hub_fb_current_url(): string
{
    $page = staark_hub_current_page();
    $keep = array_intersect_key(
        wp_unslash($_GET),
        array_flip(['view', 'form', 's', 'paged', 'submission', 'month', 'day', 'type'])
    );

    return staark_hub_fb_url($page, array_map('sanitize_text_field', array_filter($keep, 'is_scalar')));
}

function staark_hub_fb_hidden_return(): void
{
    printf('<input type="hidden" name="return_to" value="%s">', esc_attr(staark_hub_fb_current_url()));
}

function staark_hub_fb_status_pill(int $id): string
{
    if (staark_hub_fb_is_booking($id)) {
        $status = staark_hub_fb_booking($id)['status'];
        $label = staark_hub_booking_statuses()[$status];
        $class = 'booking-' . $status;
    } else {
        $status = (string) get_post_meta($id, '_staark_submission_status', true);
        $statuses = staark_hub_form_statuses();
        $status = isset($statuses[$status]) ? $status : 'new';
        $label = $statuses[$status];
        $class = 'message-' . $status;
    }

    if ((string) get_post_meta($id, '_staark_submission_status', true) === 'spam') {
        $label = staark_hub_form_statuses()['spam'];
        $class = 'message-spam';
    }

    return '<span class="staark-fb-pill staark-fb-pill--' . esc_attr($class) . '">' . esc_html($label) . '</span>';
}

function staark_hub_fb_relative_time(WP_Post $post): string
{
    $time = (int) get_post_time('U', true, $post);
    $diff = time() - $time;

    if ($diff < DAY_IN_SECONDS) {
        /* translators: %s: human time difference. */
        return sprintf(__('%s ago', 'staark-core'), human_time_diff($time, time()));
    }

    return wp_date('j M Y, H:i', $time);
}

/**
 * Short booking description: "Fri 2 Oct · 19:00 · 4 guests · Dubbelrum".
 */
function staark_hub_fb_booking_brief(int $id): string
{
    $booking = staark_hub_fb_booking($id);
    $parts = [];

    if ($booking['date'] !== '') {
        $date = wp_date('D j M', (int) strtotime($booking['date'] . ' 12:00:00'));
        if ($booking['end_date'] !== '') {
            $date .= ' → ' . wp_date('D j M', (int) strtotime($booking['end_date'] . ' 12:00:00'));
        }
        $parts[] = $date;
    } else {
        $parts[] = __('No date', 'staark-core');
    }
    if ($booking['time'] !== '') {
        $parts[] = $booking['time'];
    }
    if ($booking['guests'] > 0) {
        $parts[] = sprintf(_n('%d guest', '%d guests', $booking['guests'], 'staark-core'), $booking['guests']);
    }
    if ($booking['item'] !== '') {
        $parts[] = $booking['item'];
    }

    return implode(' · ', $parts);
}

/* ------------------------------------------------------------------ */
/* Inbox                                                               */
/* ------------------------------------------------------------------ */

function staark_hub_render_inbox(): void
{
    if (! current_user_can(staark_hub_fb_cap())) {
        return;
    }

    $selected = isset($_GET['submission']) ? absint($_GET['submission']) : 0;
    if ($selected > 0 && get_post_type($selected) === 'staark_submission') {
        staark_hub_render_fb_detail($selected);
        return;
    }

    $counts = staark_hub_fb_counts();
    $registry = staark_hub_fb_registry();
    $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'all';
    $form = isset($_GET['form']) ? sanitize_key(wp_unslash($_GET['form'])) : '';
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);

    $views = [
        'all' => [__('All', 'staark-core'), $counts['total'] - $counts['spam']],
        'attention' => [__('Needs attention', 'staark-core'), $counts['attention']],
        'messages' => [__('Messages', 'staark-core'), null],
        'bookings' => [__('Bookings', 'staark-core'), $counts['bookings']],
        'spam' => [__('Spam', 'staark-core'), $counts['spam']],
    ];
    if (! isset($views[$view])) {
        $view = 'all';
    }

    $meta_query = [];
    switch ($view) {
        case 'attention':
            $meta_query[] = staark_hub_fb_attention_meta_query();
            break;
        case 'messages':
            $meta_query[] = [
                'relation' => 'OR',
                ['key' => '_staark_submission_kind', 'value' => 'message'],
                ['key' => '_staark_submission_kind', 'compare' => 'NOT EXISTS'],
            ];
            $meta_query[] = ['key' => '_staark_submission_status', 'value' => 'spam', 'compare' => '!='];
            break;
        case 'bookings':
            $meta_query[] = ['key' => '_staark_submission_kind', 'value' => 'booking'];
            $meta_query[] = ['key' => '_staark_submission_status', 'value' => 'spam', 'compare' => '!='];
            break;
        case 'spam':
            $meta_query[] = ['key' => '_staark_submission_status', 'value' => 'spam'];
            break;
        default:
            $meta_query[] = ['key' => '_staark_submission_status', 'value' => 'spam', 'compare' => '!='];
    }
    if ($form !== '') {
        $meta_query[] = ['key' => '_staark_submission_form_id', 'value' => $form];
    }

    $query = new WP_Query(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => 25,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
            's' => $search,
            'meta_query' => $meta_query,
            'suppress_filters' => true,
        ]
    );
    ?>
    <div class="wrap staark-hub-wrap staark-fb">
        <?php staark_hub_fb_header(__('Inbox', 'staark-core')); ?>
        <?php staark_hub_fb_notices(); ?>

        <div class="staark-hub-grid staark-hub-grid--four staark-hub-stats">
            <a class="staark-hub-card staark-hub-stat-card<?php echo $counts['attention'] > 0 ? ' staark-fb-stat--hot' : ''; ?>" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-inbox', ['view' => 'attention'])); ?>">
                <span class="staark-hub-card-label"><?php esc_html_e('Needs attention', 'staark-core'); ?></span>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $counts['attention']); ?></strong>
                <p><?php esc_html_e('Unread messages and pending bookings', 'staark-core'); ?></p>
            </a>
            <a class="staark-hub-card staark-hub-stat-card" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['view' => 'pending'])); ?>">
                <span class="staark-hub-card-label"><?php esc_html_e('Pending bookings', 'staark-core'); ?></span>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $counts['pending']); ?></strong>
                <p><?php esc_html_e('Waiting for confirmation', 'staark-core'); ?></p>
            </a>
            <a class="staark-hub-card staark-hub-stat-card" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['day' => wp_date('Y-m-d')])); ?>">
                <span class="staark-hub-card-label"><?php esc_html_e('Today', 'staark-core'); ?></span>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $counts['today']); ?></strong>
                <p><?php esc_html_e('Bookings for today', 'staark-core'); ?></p>
            </a>
            <a class="staark-hub-card staark-hub-stat-card" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-inbox', ['view' => 'attention'])); ?>">
                <span class="staark-hub-card-label"><?php esc_html_e('Unread', 'staark-core'); ?></span>
                <strong class="staark-hub-metric"><?php echo esc_html((string) $counts['new']); ?></strong>
                <p><?php echo esc_html(sprintf(__('%d requests in total', 'staark-core'), $counts['total'])); ?></p>
            </a>
        </div>

        <section class="staark-hub-section staark-fb-panel">
            <div class="staark-fb-toolbar">
                <nav class="staark-fb-tabs" aria-label="<?php esc_attr_e('Filter requests', 'staark-core'); ?>">
                    <?php foreach ($views as $key => [$label, $count]) : ?>
                        <a class="<?php echo $view === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-inbox', array_filter(['view' => $key, 'form' => $form]))); ?>">
                            <?php echo esc_html($label); ?>
                            <?php if ($count !== null) : ?><span><?php echo esc_html((string) $count); ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <form class="staark-fb-search" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                    <input type="hidden" name="page" value="staark-hub-inbox">
                    <input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
                    <label class="screen-reader-text" for="staark-fb-form-filter"><?php esc_html_e('Form', 'staark-core'); ?></label>
                    <select id="staark-fb-form-filter" name="form" onchange="this.form.submit()">
                        <option value=""><?php esc_html_e('All forms', 'staark-core'); ?></option>
                        <?php foreach ($registry as $form_id => $entry) : ?>
                            <?php if ($entry['count'] > 0) : ?>
                                <option value="<?php echo esc_attr($form_id); ?>" <?php selected($form, $form_id); ?>><?php echo esc_html($entry['label'] . ' (' . $entry['count'] . ')'); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <label class="screen-reader-text" for="staark-fb-search"><?php esc_html_e('Search requests', 'staark-core'); ?></label>
                    <input id="staark-fb-search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search name, email or message', 'staark-core'); ?>">
                    <button class="button" type="submit"><?php esc_html_e('Search', 'staark-core'); ?></button>
                </form>
            </div>

            <?php if (! $query->have_posts()) : ?>
                <div class="staark-fb-empty">
                    <strong><?php echo esc_html($counts['total'] === 0 ? __('No requests yet', 'staark-core') : __('Nothing here', 'staark-core')); ?></strong>
                    <p><?php echo esc_html($counts['total'] === 0 ? __('Contact and booking requests from the website will show up here.', 'staark-core') : __('No requests match this filter.', 'staark-core')); ?></p>
                </div>
            <?php else : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="staark-fb-bulk">
                    <?php wp_nonce_field('staark_fb_bulk'); ?>
                    <input type="hidden" name="action" value="staark_fb_bulk">
                    <?php staark_hub_fb_hidden_return(); ?>
                    <div class="staark-fb-bulkbar">
                        <label class="screen-reader-text" for="staark-fb-bulk-action"><?php esc_html_e('Bulk action', 'staark-core'); ?></label>
                        <select id="staark-fb-bulk-action" name="bulk_action">
                            <option value=""><?php esc_html_e('Bulk actions', 'staark-core'); ?></option>
                            <option value="read"><?php esc_html_e('Mark as read', 'staark-core'); ?></option>
                            <option value="new"><?php esc_html_e('Mark as unread', 'staark-core'); ?></option>
                            <option value="confirmed"><?php esc_html_e('Confirm bookings (no email)', 'staark-core'); ?></option>
                            <option value="completed"><?php esc_html_e('Mark bookings completed', 'staark-core'); ?></option>
                            <option value="spam"><?php esc_html_e('Mark as spam', 'staark-core'); ?></option>
                            <option value="delete"><?php esc_html_e('Delete permanently', 'staark-core'); ?></option>
                        </select>
                        <button class="button" type="submit" onclick="var a=document.getElementById('staark-fb-bulk-action').value;return a!=='' && (a!=='delete' || confirm('<?php echo esc_js(__('Delete the selected requests permanently?', 'staark-core')); ?>'));"><?php esc_html_e('Apply', 'staark-core'); ?></button>
                    </div>

                    <div class="staark-fb-table-wrap">
                        <table class="staark-fb-table">
                            <thead>
                                <tr>
                                    <td class="check"><input type="checkbox" aria-label="<?php esc_attr_e('Select all', 'staark-core'); ?>" onclick="document.querySelectorAll('#staark-fb-bulk tbody input[type=checkbox]').forEach(function(c){c.checked=this.checked}.bind(this))"></td>
                                    <th><?php esc_html_e('From', 'staark-core'); ?></th>
                                    <th><?php esc_html_e('Request', 'staark-core'); ?></th>
                                    <th><?php esc_html_e('Status', 'staark-core'); ?></th>
                                    <th><?php esc_html_e('Received', 'staark-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($query->posts as $post) :
                                    $id = (int) $post->ID;
                                    $unread = get_post_meta($id, '_staark_submission_status', true) === 'new';
                                    $is_booking = staark_hub_fb_is_booking($id);
                                    $url = staark_hub_fb_url('staark-hub-inbox', ['submission' => $id]);
                                    $entry = staark_hub_fb_form((string) get_post_meta($id, '_staark_submission_form_id', true));
                                    $excerpt = wp_html_excerpt((string) $post->post_content, 110, '…');
                                    ?>
                                    <tr class="<?php echo $unread ? 'is-unread' : ''; ?>">
                                        <td class="check"><input type="checkbox" name="ids[]" value="<?php echo esc_attr((string) $id); ?>" aria-label="<?php esc_attr_e('Select', 'staark-core'); ?>"></td>
                                        <td class="from">
                                            <a href="<?php echo esc_url($url); ?>"><strong><?php echo esc_html((string) get_post_meta($id, '_staark_submission_name', true)); ?></strong></a>
                                            <span><?php echo esc_html((string) get_post_meta($id, '_staark_submission_email', true)); ?></span>
                                        </td>
                                        <td class="request">
                                            <span class="staark-fb-kind staark-fb-kind--<?php echo $is_booking ? 'booking' : 'message'; ?>"><?php echo esc_html($entry['label']); ?></span>
                                            <?php if ($is_booking) : ?>
                                                <a class="staark-fb-brief" href="<?php echo esc_url($url); ?>"><?php echo esc_html(staark_hub_fb_booking_brief($id)); ?></a>
                                            <?php else : ?>
                                                <a class="staark-fb-excerpt" href="<?php echo esc_url($url); ?>"><?php echo esc_html($excerpt); ?></a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo wp_kses_post(staark_hub_fb_status_pill($id)); ?></td>
                                        <td class="when"><?php echo esc_html(staark_hub_fb_relative_time($post)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <?php if ($query->max_num_pages > 1) : ?>
                    <div class="staark-fb-pagination">
                        <?php
                        echo wp_kses_post(
                            paginate_links(
                                [
                                    'base' => add_query_arg('paged', '%#%', staark_hub_fb_current_url()),
                                    'format' => '',
                                    'current' => $paged,
                                    'total' => (int) $query->max_num_pages,
                                ]
                            )
                        );
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
    <?php
}

/* ------------------------------------------------------------------ */
/* Request detail                                                      */
/* ------------------------------------------------------------------ */

function staark_hub_render_fb_detail(int $id): void
{
    $post = get_post($id);
    if (! $post instanceof WP_Post) {
        return;
    }

    if (get_post_meta($id, '_staark_submission_status', true) === 'new') {
        update_post_meta($id, '_staark_submission_status', 'read');
        staark_hub_fb_flush_counts();
    }

    $is_booking = staark_hub_fb_is_booking($id);
    $booking = $is_booking ? staark_hub_fb_booking($id) : null;
    $name = (string) get_post_meta($id, '_staark_submission_name', true);
    $email = (string) get_post_meta($id, '_staark_submission_email', true);
    $phone = (string) get_post_meta($id, '_staark_submission_phone', true);
    $company = (string) get_post_meta($id, '_staark_submission_company', true);
    $source = (string) get_post_meta($id, '_staark_submission_source_url', true);
    $form_id = (string) get_post_meta($id, '_staark_submission_form_id', true);
    $consent = (string) get_post_meta($id, '_staark_submission_consent_at', true);
    $message_status = (string) get_post_meta($id, '_staark_submission_status', true);
    $mail_state = (string) get_post_meta($id, '_staark_submission_mail_state', true);
    $mail_error = (string) get_post_meta($id, '_staark_submission_mail_error', true);
    $entry = staark_hub_fb_form($form_id);
    $notify = staark_hub_fb_notify_settings();
    $reply_values = staark_hub_fb_template_values($id);
    $reply_subject = staark_hub_fb_render_template($notify['templates']['reply']['subject'], $reply_values);
    $back = $is_booking ? staark_hub_fb_url('staark-hub-bookings') : staark_hub_fb_url();
    $can_email = is_email($email) !== false;
    ?>
    <div class="wrap staark-hub-wrap staark-fb staark-fb-detail-page">
        <?php staark_hub_fb_header(staark_hub_form_submission_label($id)); ?>
        <?php staark_hub_fb_notices(); ?>
        <p class="staark-hub-backlink">
            <a href="<?php echo esc_url(staark_hub_fb_url()); ?>">← <?php esc_html_e('Inbox', 'staark-core'); ?></a>
            <?php if ($is_booking) : ?>
                · <a href="<?php echo esc_url($back); ?>"><?php esc_html_e('Bookings', 'staark-core'); ?></a>
            <?php endif; ?>
        </p>

        <div class="staark-fb-detail">
            <div class="staark-fb-detail-main">
                <section class="staark-hub-section staark-fb-request">
                    <div class="staark-fb-request-head">
                        <div>
                            <span class="staark-hub-card-label"><?php echo esc_html($entry['label']); ?> · <?php echo esc_html(staark_hub_fb_relative_time($post)); ?></span>
                            <h2><?php echo esc_html($name); ?></h2>
                        </div>
                        <?php echo wp_kses_post(staark_hub_fb_status_pill($id)); ?>
                    </div>

                    <?php if ($booking) : ?>
                        <div class="staark-fb-booking">
                            <dl class="staark-fb-booking-facts">
                                <?php foreach (staark_hub_fb_booking_lines($id) as [$label, $value]) : ?>
                                    <?php if ($label === 'Referens') {
                                        continue;
                                    } ?>
                                    <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div>
                                <?php endforeach; ?>
                                <?php if ($booking['date'] === '') : ?>
                                    <div><dt><?php esc_html_e('Date', 'staark-core'); ?></dt><dd><?php esc_html_e('Not given — see the message', 'staark-core'); ?></dd></div>
                                <?php endif; ?>
                            </dl>

                            <form class="staark-fb-booking-actions" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <?php wp_nonce_field('staark_fb_booking_' . $id); ?>
                                <input type="hidden" name="action" value="staark_fb_booking_status">
                                <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">
                                <?php staark_hub_fb_hidden_return(); ?>
                                <label for="staark-fb-note"><strong><?php esc_html_e('Note to the customer (optional)', 'staark-core'); ?></strong></label>
                                <textarea id="staark-fb-note" name="note" rows="3" placeholder="<?php esc_attr_e('E.g. “We have reserved a table by the window.” Added to the email as {note}.', 'staark-core'); ?>"></textarea>
                                <label class="staark-fb-check">
                                    <input type="checkbox" name="notify_customer" value="1" <?php checked($notify['customer_status'] && $can_email); ?> <?php disabled(! $can_email); ?>>
                                    <?php echo esc_html($can_email ? sprintf(__('Email %s about the change', 'staark-core'), $email) : __('No valid email address for this customer', 'staark-core')); ?>
                                </label>
                                <div class="staark-fb-buttons">
                                    <?php if (in_array($booking['status'], ['pending', 'declined', 'cancelled'], true)) : ?>
                                        <button class="button button-primary staark-fb-confirm" type="submit" name="status" value="confirmed"><?php esc_html_e('Confirm booking', 'staark-core'); ?></button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'pending') : ?>
                                        <button class="button staark-fb-decline" type="submit" name="status" value="declined"><?php esc_html_e('Decline', 'staark-core'); ?></button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'confirmed') : ?>
                                        <button class="button" type="submit" name="status" value="completed"><?php esc_html_e('Mark completed', 'staark-core'); ?></button>
                                        <button class="button staark-fb-decline" type="submit" name="status" value="cancelled" onclick="return confirm('<?php echo esc_js(__('Cancel this booking?', 'staark-core')); ?>');"><?php esc_html_e('Cancel booking', 'staark-core'); ?></button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'completed') : ?>
                                        <button class="button" type="submit" name="status" value="confirmed"><?php esc_html_e('Reopen as confirmed', 'staark-core'); ?></button>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <details class="staark-fb-edit">
                                <summary><?php esc_html_e('Edit booking details', 'staark-core'); ?></summary>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <?php wp_nonce_field('staark_fb_booking_edit_' . $id); ?>
                                    <input type="hidden" name="action" value="staark_fb_booking_edit">
                                    <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">
                                    <?php staark_hub_fb_hidden_return(); ?>
                                    <div class="staark-fb-edit-grid">
                                        <label><span><?php echo esc_html($booking['type'] === 'room' ? __('Check-in', 'staark-core') : __('Date', 'staark-core')); ?></span><input type="date" name="booking_date" value="<?php echo esc_attr($booking['date']); ?>"></label>
                                        <?php if ($booking['type'] === 'room' || $booking['end_date'] !== '') : ?>
                                            <label><span><?php esc_html_e('Check-out', 'staark-core'); ?></span><input type="date" name="booking_end_date" value="<?php echo esc_attr($booking['end_date']); ?>"></label>
                                        <?php endif; ?>
                                        <label><span><?php esc_html_e('Time', 'staark-core'); ?></span><input type="text" name="booking_time" value="<?php echo esc_attr($booking['time']); ?>" maxlength="40"></label>
                                        <label><span><?php esc_html_e('Guests', 'staark-core'); ?></span><input type="number" min="0" max="999" name="booking_guests" value="<?php echo esc_attr((string) $booking['guests']); ?>"></label>
                                        <label><span><?php echo esc_html($booking['type'] === 'room' ? __('Room', 'staark-core') : __('Service / note', 'staark-core')); ?></span><input type="text" name="booking_item" value="<?php echo esc_attr($booking['item']); ?>" maxlength="120"></label>
                                    </div>
                                    <button class="button" type="submit"><?php esc_html_e('Save details', 'staark-core'); ?></button>
                                </form>
                            </details>
                        </div>
                    <?php endif; ?>

                    <h3 class="staark-fb-subhead"><?php esc_html_e('Message', 'staark-core'); ?></h3>
                    <div class="staark-fb-message"><?php echo nl2br(esc_html((string) $post->post_content)); ?></div>
                </section>

                <section class="staark-hub-section staark-fb-reply" id="reply">
                    <span class="staark-hub-card-label"><?php esc_html_e('Reply', 'staark-core'); ?></span>
                    <h2><?php echo esc_html(sprintf(__('Reply to %s', 'staark-core'), $name !== '' ? $name : $email)); ?></h2>
                    <?php if ($can_email) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('staark_fb_reply_' . $id); ?>
                            <input type="hidden" name="action" value="staark_fb_reply">
                            <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">
                            <?php staark_hub_fb_hidden_return(); ?>
                            <label class="staark-hub-field"><span><?php esc_html_e('Subject', 'staark-core'); ?></span>
                                <input type="text" name="reply_subject" value="<?php echo esc_attr($reply_subject); ?>">
                            </label>
                            <label class="staark-hub-field"><span><?php esc_html_e('Message', 'staark-core'); ?></span>
                                <textarea name="reply_body" rows="6" required placeholder="<?php esc_attr_e('Write your reply. The greeting and signature from the Reply template are added automatically.', 'staark-core'); ?>"></textarea>
                            </label>
                            <div class="staark-fb-buttons">
                                <button class="button button-primary" type="submit"><?php esc_html_e('Send reply', 'staark-core'); ?></button>
                                <a class="button" href="mailto:<?php echo esc_attr($email); ?>?subject=<?php echo rawurlencode($reply_subject); ?>"><?php esc_html_e('Open in mail app', 'staark-core'); ?></a>
                            </div>
                        </form>
                    <?php else : ?>
                        <p><?php esc_html_e('This request has no valid email address.', 'staark-core'); ?></p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="staark-fb-detail-side">
                <section class="staark-hub-card">
                    <span class="staark-hub-card-label"><?php esc_html_e('Contact', 'staark-core'); ?></span>
                    <dl class="staark-fb-dl">
                        <dt><?php esc_html_e('Email', 'staark-core'); ?></dt>
                        <dd><?php if ($can_email) : ?><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php else : ?>—<?php endif; ?></dd>
                        <dt><?php esc_html_e('Phone', 'staark-core'); ?></dt>
                        <dd><?php if ($phone !== '') : ?><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a><?php else : ?>—<?php endif; ?></dd>
                        <?php if ($company !== '') : ?>
                            <dt><?php esc_html_e('Company', 'staark-core'); ?></dt>
                            <dd><?php echo esc_html($company); ?></dd>
                        <?php endif; ?>
                        <dt><?php esc_html_e('Page', 'staark-core'); ?></dt>
                        <dd><?php if ($source !== '') : ?><a href="<?php echo esc_url($source); ?>" target="_blank" rel="noopener"><?php echo esc_html((string) (wp_parse_url($source, PHP_URL_PATH) ?: $source)); ?></a><?php else : ?>—<?php endif; ?></dd>
                        <dt><?php esc_html_e('Form', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($entry['label']); ?> <code><?php echo esc_html($form_id); ?></code></dd>
                        <dt><?php esc_html_e('Consent', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($consent !== '' ? get_date_from_gmt($consent, 'j M Y, H:i') : __('No record', 'staark-core')); ?></dd>
                        <dt><?php esc_html_e('Reference', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html(staark_hub_form_submission_label($id)); ?></dd>
                    </dl>

                    <form class="staark-fb-inline" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('staark_fb_message_' . $id); ?>
                        <input type="hidden" name="action" value="staark_fb_message_status">
                        <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">
                        <?php staark_hub_fb_hidden_return(); ?>
                        <label for="staark-fb-message-status"><?php esc_html_e('Message status', 'staark-core'); ?></label>
                        <select id="staark-fb-message-status" name="status">
                            <?php foreach (staark_hub_form_statuses() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($message_status === 'new' ? 'read' : $message_status, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button" type="submit"><?php esc_html_e('Update', 'staark-core'); ?></button>
                    </form>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label"><?php esc_html_e('Activity', 'staark-core'); ?></span>
                    <ol class="staark-fb-log">
                        <?php
                        $log = array_reverse(staark_hub_fb_get_log($id));
                        if ($log === []) :
                            ?>
                            <li><span><?php echo esc_html(get_post_time('j M Y, H:i', false, $post)); ?></span><?php esc_html_e('Received', 'staark-core'); ?></li>
                        <?php endif; ?>
                        <?php foreach ($log as $event) :
                            $user = ! empty($event['user']) ? get_userdata((int) $event['user']) : null;
                            ?>
                            <li class="<?php echo empty($event['ok']) ? 'is-error' : ''; ?>">
                                <span><?php echo esc_html(wp_date('j M, H:i', (int) $event['t'])); ?><?php echo $user ? ' · ' . esc_html($user->display_name) : ''; ?></span>
                                <?php echo esc_html((string) $event['text']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <p class="staark-fb-muted">
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Admin notification: %s', 'staark-core'),
                                $mail_state === 'sent' ? __('sent', 'staark-core') : ($mail_state === 'failed' ? __('failed', 'staark-core') : ($mail_state === 'disabled' ? __('turned off', 'staark-core') : __('not sent', 'staark-core')))
                            )
                        );
                        ?>
                        <?php if ($mail_error !== '') : ?><br><code><?php echo esc_html($mail_error); ?></code><?php endif; ?>
                    </p>
                    <?php if (in_array($mail_state, ['failed', 'not_sent'], true) && current_user_can('manage_options')) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('staark_submission_retry_mail_' . $id); ?>
                            <input type="hidden" name="action" value="staark_submission_retry_mail">
                            <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">
                            <button class="button" type="submit"><?php esc_html_e('Retry admin notification', 'staark-core'); ?></button>
                        </form>
                    <?php endif; ?>
                </section>

                <form class="staark-fb-delete" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Delete this request permanently? This cannot be undone.', 'staark-core')); ?>');">
                    <?php wp_nonce_field('staark_fb_delete_' . $id); ?>
                    <input type="hidden" name="action" value="staark_fb_delete">
                    <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">
                    <button class="button-link staark-hub-button-danger" type="submit"><?php esc_html_e('Delete request permanently', 'staark-core'); ?></button>
                </form>
            </aside>
        </div>
    </div>
    <?php
}

/* ------------------------------------------------------------------ */
/* Bookings                                                            */
/* ------------------------------------------------------------------ */

/**
 * Bookings per day for a month: date => [pending, confirmed, guests].
 *
 * @return array<string,array{pending:int,confirmed:int,guests:int}>
 */
function staark_hub_fb_month_summary(string $month): array
{
    $posts = get_posts(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => 500,
            'fields' => 'ids',
            'meta_query' => [
                ['key' => '_staark_booking_date', 'value' => [$month . '-01', $month . '-31'], 'compare' => 'BETWEEN', 'type' => 'CHAR'],
                ['key' => '_staark_booking_status', 'value' => ['pending', 'confirmed', 'completed'], 'compare' => 'IN'],
                ['key' => '_staark_submission_status', 'value' => 'spam', 'compare' => '!='],
            ],
            'suppress_filters' => true,
        ]
    );

    $days = [];
    foreach ($posts as $id) {
        $booking = staark_hub_fb_booking((int) $id);
        $day = $booking['date'];
        $days[$day] = $days[$day] ?? ['pending' => 0, 'confirmed' => 0, 'guests' => 0];
        if ($booking['status'] === 'pending') {
            ++$days[$day]['pending'];
        } else {
            ++$days[$day]['confirmed'];
        }
        $days[$day]['guests'] += $booking['guests'];
    }

    return $days;
}

function staark_hub_fb_render_calendar(string $month, string $selected_day, string $view): void
{
    $first = strtotime($month . '-01 12:00:00');
    $days_in_month = (int) wp_date('t', $first);
    $start_of_week = (int) get_option('start_of_week', 1);
    $offset = ((int) wp_date('w', $first) - $start_of_week + 7) % 7;
    $summary = staark_hub_fb_month_summary($month);
    $today = wp_date('Y-m-d');
    $prev = wp_date('Y-m', strtotime('-1 month', $first));
    $next = wp_date('Y-m', strtotime('+1 month', $first));
    global $wp_locale;
    ?>
    <div class="staark-fb-calendar">
        <div class="staark-fb-calendar-head">
            <a class="button" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['month' => $prev, 'view' => $view])); ?>" aria-label="<?php esc_attr_e('Previous month', 'staark-core'); ?>">‹</a>
            <strong><?php echo esc_html(wp_date('F Y', $first)); ?></strong>
            <a class="button" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['month' => $next, 'view' => $view])); ?>" aria-label="<?php esc_attr_e('Next month', 'staark-core'); ?>">›</a>
        </div>
        <div class="staark-fb-calendar-grid">
            <?php for ($i = 0; $i < 7; $i++) : ?>
                <span class="dow"><?php echo esc_html($wp_locale->get_weekday_initial($wp_locale->get_weekday(($i + $start_of_week) % 7))); ?></span>
            <?php endfor; ?>
            <?php for ($i = 0; $i < $offset; $i++) : ?>
                <span class="pad"></span>
            <?php endfor; ?>
            <?php for ($d = 1; $d <= $days_in_month; $d++) :
                $date = $month . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
                $info = $summary[$date] ?? null;
                $classes = ['day'];
                if ($date === $today) {
                    $classes[] = 'is-today';
                }
                if ($date === $selected_day) {
                    $classes[] = 'is-selected';
                }
                if ($date < $today) {
                    $classes[] = 'is-past';
                }
                $title = $info ? sprintf(__('%1$d confirmed, %2$d pending, %3$d guests', 'staark-core'), $info['confirmed'], $info['pending'], $info['guests']) : '';
                ?>
                <a class="<?php echo esc_attr(implode(' ', $classes)); ?>" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['day' => $date])); ?>"<?php echo $title !== '' ? ' title="' . esc_attr($title) . '"' : ''; ?>>
                    <span class="num"><?php echo esc_html((string) $d); ?></span>
                    <?php if ($info) : ?>
                        <span class="dots">
                            <?php if ($info['confirmed'] > 0) : ?><i class="ok"><?php echo esc_html((string) $info['confirmed']); ?></i><?php endif; ?>
                            <?php if ($info['pending'] > 0) : ?><i class="warn"><?php echo esc_html((string) $info['pending']); ?></i><?php endif; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endfor; ?>
        </div>
        <p class="staark-fb-legend"><i class="ok"></i><?php esc_html_e('Confirmed', 'staark-core'); ?> <i class="warn"></i><?php esc_html_e('Pending', 'staark-core'); ?></p>
    </div>
    <?php
}

function staark_hub_render_bookings(): void
{
    if (! current_user_can(staark_hub_fb_cap())) {
        return;
    }

    $counts = staark_hub_fb_counts();
    $notify = staark_hub_fb_notify_settings();
    $today = wp_date('Y-m-d');
    $day = isset($_GET['day']) ? staark_hub_fb_valid_date(sanitize_text_field(wp_unslash($_GET['day']))) : '';
    $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'upcoming';
    $month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', (string) $_GET['month']) ? sanitize_text_field(wp_unslash($_GET['month'])) : wp_date('Y-m');
    if ($day !== '') {
        $month = substr($day, 0, 7);
    }

    $views = [
        'upcoming' => __('Upcoming', 'staark-core'),
        'pending' => __('Pending', 'staark-core'),
        'past' => __('Past', 'staark-core'),
        'all' => __('All', 'staark-core'),
    ];
    if (! isset($views[$view])) {
        $view = 'upcoming';
    }

    $meta_query = [
        ['key' => '_staark_submission_kind', 'value' => 'booking'],
        ['key' => '_staark_submission_status', 'value' => 'spam', 'compare' => '!='],
    ];
    $order = 'ASC';

    if ($day !== '') {
        $meta_query[] = ['key' => '_staark_booking_date', 'value' => $day];
    } elseif ($view === 'upcoming') {
        $meta_query[] = ['key' => '_staark_booking_status', 'value' => ['pending', 'confirmed'], 'compare' => 'IN'];
        $meta_query[] = [
            'relation' => 'OR',
            ['key' => '_staark_booking_date', 'value' => $today, 'compare' => '>='],
            ['key' => '_staark_booking_date', 'value' => ''],
        ];
    } elseif ($view === 'pending') {
        $meta_query[] = ['key' => '_staark_booking_status', 'value' => 'pending'];
    } elseif ($view === 'past') {
        $meta_query[] = ['key' => '_staark_booking_date', 'value' => $today, 'compare' => '<'];
        $meta_query[] = ['key' => '_staark_booking_date', 'value' => '', 'compare' => '!='];
        $order = 'DESC';
    }

    $posts = get_posts(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => 200,
            'meta_key' => '_staark_booking_sort',
            'orderby' => 'meta_value',
            'order' => $order,
            'meta_query' => $meta_query,
            'suppress_filters' => true,
        ]
    );

    $groups = [];
    foreach ($posts as $post) {
        $date = (string) get_post_meta((int) $post->ID, '_staark_booking_date', true);
        $groups[$date !== '' ? $date : 'none'][] = $post;
    }
    ?>
    <div class="wrap staark-hub-wrap staark-fb">
        <?php staark_hub_fb_header(__('Bookings', 'staark-core')); ?>
        <?php staark_hub_fb_notices(); ?>

        <div class="staark-fb-bookings">
            <aside class="staark-fb-bookings-side">
                <section class="staark-hub-card">
                    <?php staark_hub_fb_render_calendar($month, $day, $view); ?>
                </section>
                <section class="staark-hub-card staark-fb-mini-stats">
                    <a href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['view' => 'pending'])); ?>"><strong><?php echo esc_html((string) $counts['pending']); ?></strong><span><?php esc_html_e('Pending', 'staark-core'); ?></span></a>
                    <a href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['day' => $today])); ?>"><strong><?php echo esc_html((string) $counts['today']); ?></strong><span><?php esc_html_e('Today', 'staark-core'); ?></span></a>
                    <a href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings')); ?>"><strong><?php echo esc_html((string) $counts['upcoming']); ?></strong><span><?php esc_html_e('Upcoming', 'staark-core'); ?></span></a>
                </section>
            </aside>

            <section class="staark-hub-section staark-fb-panel staark-fb-bookings-main">
                <div class="staark-fb-toolbar">
                    <nav class="staark-fb-tabs" aria-label="<?php esc_attr_e('Filter bookings', 'staark-core'); ?>">
                        <?php foreach ($views as $key => $label) : ?>
                            <a class="<?php echo $day === '' && $view === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-bookings', ['view' => $key])); ?>"><?php echo esc_html($label); ?></a>
                        <?php endforeach; ?>
                        <?php if ($day !== '') : ?>
                            <a class="is-active" href="<?php echo esc_url(staark_hub_fb_current_url()); ?>"><?php echo esc_html(wp_date('D j M', (int) strtotime($day . ' 12:00:00'))); ?></a>
                        <?php endif; ?>
                    </nav>
                    <a class="button" href="<?php echo esc_url(staark_hub_fb_url('staark-hub-inbox', ['view' => 'bookings'])); ?>"><?php esc_html_e('Show in Inbox', 'staark-core'); ?></a>
                </div>

                <?php if ($groups === []) : ?>
                    <div class="staark-fb-empty">
                        <strong><?php esc_html_e('No bookings here', 'staark-core'); ?></strong>
                        <p>
                            <?php
                            echo esc_html(
                                $counts['bookings'] === 0
                                    ? __('Booking requests from the website (table, room or appointment forms) will show up here.', 'staark-core')
                                    : __('Try another day or filter.', 'staark-core')
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php foreach ($groups as $date => $items) :
                    $guests = array_sum(array_map(static fn (WP_Post $p): int => (int) get_post_meta((int) $p->ID, '_staark_booking_guests', true), $items));
                    ?>
                    <div class="staark-fb-day<?php echo $date === $today ? ' is-today' : ''; ?>">
                        <h3>
                            <?php echo esc_html($date === 'none' ? __('No date given', 'staark-core') : wp_date('l j F', (int) strtotime($date . ' 12:00:00'))); ?>
                            <?php if ($date === $today) : ?><span class="staark-fb-today"><?php esc_html_e('Today', 'staark-core'); ?></span><?php endif; ?>
                            <small>
                                <?php echo esc_html(sprintf(_n('%d booking', '%d bookings', count($items), 'staark-core'), count($items))); ?>
                                <?php if ($guests > 0) : ?> · <?php echo esc_html(sprintf(_n('%d guest', '%d guests', $guests, 'staark-core'), $guests)); ?><?php endif; ?>
                            </small>
                        </h3>
                        <ul class="staark-fb-booking-list">
                            <?php foreach ($items as $post) :
                                $id = (int) $post->ID;
                                $booking = staark_hub_fb_booking($id);
                                $url = staark_hub_fb_url('staark-hub-inbox', ['submission' => $id]);
                                $can_email = is_email((string) get_post_meta($id, '_staark_submission_email', true)) !== false;
                                $phone = (string) get_post_meta($id, '_staark_submission_phone', true);
                                ?>
                                <li class="staark-fb-booking-row staark-fb-booking-row--<?php echo esc_attr($booking['status']); ?>">
                                    <span class="time">
                                        <?php
                                        if ($booking['time'] !== '') {
                                            echo esc_html($booking['time']);
                                        } elseif ($booking['end_date'] !== '') {
                                            echo '<small>' . esc_html__('until', 'staark-core') . '</small> ' . esc_html(wp_date('j M', (int) strtotime($booking['end_date'] . ' 12:00:00')));
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </span>
                                    <span class="who">
                                        <a href="<?php echo esc_url($url); ?>"><strong><?php echo esc_html((string) get_post_meta($id, '_staark_submission_name', true)); ?></strong></a>
                                        <small>
                                            <?php
                                            $bits = [];
                                            if ($booking['guests'] > 0) {
                                                $bits[] = sprintf(_n('%d guest', '%d guests', $booking['guests'], 'staark-core'), $booking['guests']);
                                            }
                                            if ($booking['end_date'] !== '') {
                                                $nights = (int) round((strtotime($booking['end_date']) - strtotime($booking['date'])) / DAY_IN_SECONDS);
                                                $bits[] = sprintf(_n('%d night', '%d nights', $nights, 'staark-core'), $nights);
                                            }
                                            if ($booking['item'] !== '') {
                                                $bits[] = $booking['item'];
                                            }
                                            if ($phone !== '') {
                                                $bits[] = $phone;
                                            }
                                            echo esc_html(implode(' · ', $bits));
                                            ?>
                                        </small>
                                    </span>
                                    <?php echo wp_kses_post(staark_hub_fb_status_pill($id)); ?>
                                    <span class="actions">
                                        <?php if ($booking['status'] === 'pending') : ?>
                                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                                <?php wp_nonce_field('staark_fb_booking_' . $id); ?>
                                                <input type="hidden" name="action" value="staark_fb_booking_status">
                                                <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $id); ?>">
                                                <input type="hidden" name="notify_customer" value="<?php echo $notify['customer_status'] && $can_email ? '1' : '0'; ?>">
                                                <?php staark_hub_fb_hidden_return(); ?>
                                                <button class="button button-primary button-small" type="submit" name="status" value="confirmed" title="<?php echo esc_attr($notify['customer_status'] && $can_email ? __('Confirm and email the customer', 'staark-core') : __('Confirm', 'staark-core')); ?>"><?php esc_html_e('Confirm', 'staark-core'); ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <a class="button button-small" href="<?php echo esc_url($url); ?>"><?php esc_html_e('Open', 'staark-core'); ?></a>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </div>
    <?php
}

/* ------------------------------------------------------------------ */
/* Forms                                                               */
/* ------------------------------------------------------------------ */

function staark_hub_render_form_list(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $registry = staark_hub_fb_registry();
    $default = staark_hub_forms_settings()['recipient_email'];
    ?>
    <div class="wrap staark-hub-wrap staark-fb">
        <?php staark_hub_fb_header(__('Forms', 'staark-core')); ?>
        <?php staark_hub_fb_notices(); ?>

        <section class="staark-hub-section">
            <div class="staark-hub-section-heading">
                <div>
                    <span class="staark-hub-card-label"><?php esc_html_e('Forms on this website', 'staark-core'); ?></span>
                    <h2><?php esc_html_e('Who gets what', 'staark-core'); ?></h2>
                    <p class="staark-fb-muted"><?php echo esc_html(sprintf(__('Each form can notify its own people, for example bookings to the restaurant and quote requests to sales. Empty means the default recipient (%s).', 'staark-core'), $default)); ?></p>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('staark_fb_save_forms'); ?>
                <input type="hidden" name="action" value="staark_fb_save_forms">
                <div class="staark-fb-table-wrap">
                    <table class="staark-fb-table staark-fb-forms-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Form', 'staark-core'); ?></th>
                                <th><?php esc_html_e('Type', 'staark-core'); ?></th>
                                <th><?php esc_html_e('Notify', 'staark-core'); ?></th>
                                <th><?php esc_html_e('Auto-reply', 'staark-core'); ?></th>
                                <th><?php esc_html_e('Requests', 'staark-core'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registry as $form_id => $entry) :
                                $field = 'forms[' . $form_id . ']';
                                ?>
                                <tr>
                                    <td>
                                        <input type="text" name="<?php echo esc_attr($field); ?>[label]" value="<?php echo esc_attr($entry['label']); ?>" aria-label="<?php esc_attr_e('Form name', 'staark-core'); ?>">
                                        <code class="staark-fb-code">[staark_contact_form form_id="<?php echo esc_html($form_id); ?>"]</code>
                                    </td>
                                    <td>
                                        <select name="<?php echo esc_attr($field); ?>[kind]" aria-label="<?php esc_attr_e('Type', 'staark-core'); ?>">
                                            <option value="message" <?php selected($entry['kind'], 'message'); ?>><?php esc_html_e('Message', 'staark-core'); ?></option>
                                            <option value="booking" <?php selected($entry['kind'], 'booking'); ?>><?php esc_html_e('Booking', 'staark-core'); ?></option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="<?php echo esc_attr($field); ?>[recipients]" value="<?php echo esc_attr($entry['recipients']); ?>" placeholder="<?php echo esc_attr($default); ?>" aria-label="<?php esc_attr_e('Recipients', 'staark-core'); ?>"></td>
                                    <td class="center"><input type="checkbox" name="<?php echo esc_attr($field); ?>[autoreply]" value="1" <?php checked($entry['autoreply']); ?> aria-label="<?php esc_attr_e('Send auto-reply', 'staark-core'); ?>"></td>
                                    <td>
                                        <?php if ($entry['count'] > 0) : ?>
                                            <a href="<?php echo esc_url(staark_hub_fb_url('staark-hub-inbox', ['form' => $form_id])); ?>"><?php echo esc_html((string) $entry['count']); ?></a>
                                        <?php else : ?>
                                            <span class="staark-fb-muted"><?php esc_html_e('None yet', 'staark-core'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="staark-fb-muted"><?php esc_html_e('Booking forms get booking statuses (pending, confirmed …) and appear under Bookings. Several recipients: separate with commas. Auto-reply sends the “received” email to the customer (Notifications).', 'staark-core'); ?></p>
                <?php submit_button(__('Save forms', 'staark-core')); ?>
            </form>
        </section>
    </div>
    <?php
}

/* ------------------------------------------------------------------ */
/* Notifications                                                       */
/* ------------------------------------------------------------------ */

function staark_hub_render_notifications(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $forms = staark_hub_forms_settings();
    $notify = staark_hub_fb_notify_settings();
    $defaults = staark_hub_fb_default_templates();
    $me = (string) wp_get_current_user()->user_email;
    ?>
    <div class="wrap staark-hub-wrap staark-fb">
        <?php staark_hub_fb_header(__('Notifications', 'staark-core')); ?>
        <?php staark_hub_fb_notices(); ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="staark-fb-notifications">
            <?php wp_nonce_field('staark_fb_save_notifications'); ?>
            <input type="hidden" name="action" value="staark_fb_save_notifications">

            <div class="staark-fb-cards">
                <section class="staark-hub-card">
                    <span class="staark-hub-card-label"><?php esc_html_e('To you', 'staark-core'); ?></span>
                    <h2><?php esc_html_e('Email for new requests', 'staark-core'); ?></h2>
                    <label class="staark-fb-check"><input type="checkbox" name="notify" value="1" <?php checked($forms['notify']); ?>> <?php esc_html_e('Email me when a request or booking arrives', 'staark-core'); ?></label>
                    <label class="staark-hub-field"><span><?php esc_html_e('Default recipient', 'staark-core'); ?></span>
                        <input type="email" name="recipient_email" value="<?php echo esc_attr($forms['recipient_email']); ?>">
                    </label>
                    <label class="staark-hub-field"><span><?php esc_html_e('Subject prefix', 'staark-core'); ?></span>
                        <input type="text" name="subject_prefix" value="<?php echo esc_attr($forms['subject_prefix']); ?>">
                    </label>
                    <p class="staark-fb-muted">
                        <?php esc_html_e('Different people per form (bookings, quotes …):', 'staark-core'); ?>
                        <a href="<?php echo esc_url(staark_hub_fb_url('staark-hub-form-list')); ?>"><?php esc_html_e('Forms', 'staark-core'); ?></a>
                    </p>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label"><?php esc_html_e('In WordPress', 'staark-core'); ?></span>
                    <h2><?php esc_html_e('Dashboard alerts', 'staark-core'); ?></h2>
                    <label class="staark-fb-check"><input type="checkbox" name="admin_badge" value="1" <?php checked($notify['admin_badge']); ?>> <?php echo esc_html(sprintf(__('Count badge on the %s menu', 'staark-core'), staark_hub_fb_label())); ?></label>
                    <label class="staark-fb-check"><input type="checkbox" name="admin_bar" value="1" <?php checked($notify['admin_bar']); ?>> <?php esc_html_e('Bell with the latest requests in the admin bar (also on the website when logged in)', 'staark-core'); ?></label>
                    <p class="staark-fb-muted"><?php esc_html_e('Counts unread messages and bookings waiting for confirmation.', 'staark-core'); ?></p>
                </section>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label"><?php esc_html_e('To the customer', 'staark-core'); ?></span>
                    <h2><?php esc_html_e('Customer emails', 'staark-core'); ?></h2>
                    <label class="staark-fb-check"><input type="checkbox" name="customer_receipt" value="1" <?php checked($notify['customer_receipt']); ?>> <?php esc_html_e('Send a “we received your request” email', 'staark-core'); ?></label>
                    <label class="staark-fb-check"><input type="checkbox" name="customer_status" value="1" <?php checked($notify['customer_status']); ?>> <?php esc_html_e('Email the customer when a booking is confirmed, declined or cancelled (pre-selected, can be turned off per booking)', 'staark-core'); ?></label>
                    <p class="staark-fb-muted"><?php esc_html_e('Auto-replies can be turned off per form. Emails are sent as plain text with your address as Reply-To.', 'staark-core'); ?></p>
                </section>
            </div>

            <section class="staark-hub-section">
                <div class="staark-hub-section-heading">
                    <div>
                        <span class="staark-hub-card-label"><?php esc_html_e('Templates', 'staark-core'); ?></span>
                        <h2><?php esc_html_e('What the customer receives', 'staark-core'); ?></h2>
                    </div>
                </div>

                <div class="staark-fb-templates">
                    <div class="staark-fb-template-list">
                        <?php foreach ($defaults as $key => $template) : ?>
                            <details class="staark-fb-template"<?php echo $key === 'receipt_booking' ? ' open' : ''; ?>>
                                <summary>
                                    <strong><?php echo esc_html($template['label']); ?></strong>
                                    <span><?php echo esc_html($template['description']); ?></span>
                                </summary>
                                <label class="staark-hub-field"><span><?php esc_html_e('Subject', 'staark-core'); ?></span>
                                    <input type="text" name="templates[<?php echo esc_attr($key); ?>][subject]" value="<?php echo esc_attr($notify['templates'][$key]['subject']); ?>">
                                </label>
                                <label class="staark-hub-field"><span><?php esc_html_e('Message', 'staark-core'); ?></span>
                                    <textarea name="templates[<?php echo esc_attr($key); ?>][body]" rows="10"><?php echo esc_textarea($notify['templates'][$key]['body']); ?></textarea>
                                </label>
                                <div class="staark-fb-buttons">
                                    <button class="button" type="submit" form="staark-fb-preview-<?php echo esc_attr($key); ?>"><?php echo esc_html(sprintf(__('Send a test to %s', 'staark-core'), $me)); ?></button>
                                    <span class="staark-fb-muted"><?php esc_html_e('Clear both fields and save to restore the default text.', 'staark-core'); ?></span>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                    <aside class="staark-fb-placeholders">
                        <strong><?php esc_html_e('Placeholders', 'staark-core'); ?></strong>
                        <dl>
                            <?php foreach (staark_hub_fb_placeholders() as $tag => $label) : ?>
                                <dt><code><?php echo esc_html($tag); ?></code></dt>
                                <dd><?php echo esc_html($label); ?></dd>
                            <?php endforeach; ?>
                        </dl>
                        <p class="staark-fb-muted"><?php esc_html_e('The test email is sent with example data and uses the saved text — save first.', 'staark-core'); ?></p>
                    </aside>
                </div>
            </section>

            <?php submit_button(__('Save notifications', 'staark-core')); ?>
        </form>

        <?php foreach (array_keys($defaults) as $key) : ?>
            <form id="staark-fb-preview-<?php echo esc_attr($key); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" hidden>
                <?php wp_nonce_field('staark_fb_preview_mail', '_wpnonce', true, true); ?>
                <input type="hidden" name="action" value="staark_fb_preview_mail">
                <input type="hidden" name="template" value="<?php echo esc_attr($key); ?>">
            </form>
        <?php endforeach; ?>
    </div>
    <?php
}

/* ------------------------------------------------------------------ */
/* Settings                                                            */
/* ------------------------------------------------------------------ */

function staark_hub_render_form_settings(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $settings = staark_hub_forms_settings();
    ?>
    <div class="wrap staark-hub-wrap staark-fb">
        <?php staark_hub_fb_header(__('Settings', 'staark-core')); ?>
        <?php staark_hub_fb_notices(); ?>

        <div class="staark-fb-cards staark-fb-cards--two">
            <form class="staark-hub-card" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('staark_fb_save_settings'); ?>
                <input type="hidden" name="action" value="staark_fb_save_settings">

                <span class="staark-hub-card-label"><?php esc_html_e('Forms', 'staark-core'); ?></span>
                <h2><?php esc_html_e('On the website', 'staark-core'); ?></h2>
                <label class="staark-hub-field"><span><?php esc_html_e('Success message', 'staark-core'); ?></span>
                    <input type="text" name="success_message" value="<?php echo esc_attr($settings['success_message']); ?>">
                </label>
                <label class="staark-hub-field"><span><?php esc_html_e('Privacy policy URL', 'staark-core'); ?></span>
                    <input type="url" name="privacy_url" value="<?php echo esc_attr($settings['privacy_url']); ?>">
                </label>

                <h2 class="staark-fb-gap"><?php esc_html_e('Mail transport', 'staark-core'); ?></h2>
                <p class="staark-fb-muted"><?php esc_html_e('SMTP here is used only for Staark form emails (to you and to customers). Other WordPress email is not affected.', 'staark-core'); ?></p>
                <label class="staark-hub-field"><span><?php esc_html_e('Transport', 'staark-core'); ?></span>
                    <select name="mail_transport">
                        <option value="wordpress" <?php selected($settings['mail_transport'], 'wordpress'); ?>><?php esc_html_e('WordPress default', 'staark-core'); ?></option>
                        <option value="smtp" <?php selected($settings['mail_transport'], 'smtp'); ?>>SMTP</option>
                    </select>
                </label>
                <div class="staark-fb-field-row">
                    <label class="staark-hub-field"><span><?php esc_html_e('SMTP host', 'staark-core'); ?></span>
                        <input type="text" name="smtp_host" value="<?php echo esc_attr($settings['smtp_host']); ?>" placeholder="smtp.example.com">
                    </label>
                    <label class="staark-hub-field staark-fb-field-small"><span><?php esc_html_e('Port', 'staark-core'); ?></span>
                        <input type="number" min="1" max="65535" name="smtp_port" value="<?php echo esc_attr((string) $settings['smtp_port']); ?>">
                    </label>
                    <label class="staark-hub-field staark-fb-field-small"><span><?php esc_html_e('Encryption', 'staark-core'); ?></span>
                        <select name="smtp_encryption">
                            <option value="tls" <?php selected($settings['smtp_encryption'], 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected($settings['smtp_encryption'], 'ssl'); ?>>SSL</option>
                            <option value="none" <?php selected($settings['smtp_encryption'], 'none'); ?>><?php esc_html_e('None', 'staark-core'); ?></option>
                        </select>
                    </label>
                </div>
                <label class="staark-fb-check"><input type="checkbox" name="smtp_auth" value="1" <?php checked($settings['smtp_auth']); ?>> <?php esc_html_e('SMTP authentication', 'staark-core'); ?></label>
                <div class="staark-fb-field-row">
                    <label class="staark-hub-field"><span><?php esc_html_e('Username', 'staark-core'); ?></span>
                        <input type="text" name="smtp_username" value="<?php echo esc_attr($settings['smtp_username']); ?>" autocomplete="off">
                    </label>
                    <label class="staark-hub-field"><span><?php esc_html_e('Password', 'staark-core'); ?></span>
                        <input type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="<?php echo esc_attr($settings['smtp_password'] !== '' || defined('STAARK_FORMS_SMTP_PASSWORD') ? __('Saved — leave blank to keep it', 'staark-core') : __('Enter SMTP password', 'staark-core')); ?>">
                    </label>
                </div>
                <div class="staark-fb-field-row">
                    <label class="staark-hub-field"><span><?php esc_html_e('From email', 'staark-core'); ?></span>
                        <input type="email" name="smtp_from_email" value="<?php echo esc_attr($settings['smtp_from_email']); ?>">
                    </label>
                    <label class="staark-hub-field"><span><?php esc_html_e('From name', 'staark-core'); ?></span>
                        <input type="text" name="smtp_from_name" value="<?php echo esc_attr($settings['smtp_from_name']); ?>">
                    </label>
                </div>
                <p class="staark-fb-muted">
                    <?php
                    echo esc_html(
                        defined('STAARK_FORMS_SMTP_PASSWORD')
                            ? __('The SMTP password is supplied by STAARK_FORMS_SMTP_PASSWORD.', 'staark-core')
                            : __('The password is stored in a non-autoloaded option and never shown again. Managed sites can set STAARK_FORMS_SMTP_PASSWORD instead.', 'staark-core')
                    );
                    ?>
                </p>
                <?php submit_button(__('Save settings', 'staark-core')); ?>
            </form>

            <div>
                <form class="staark-hub-card" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('staark_fb_test_mail'); ?>
                    <input type="hidden" name="action" value="staark_fb_test_mail">
                    <span class="staark-hub-card-label"><?php esc_html_e('Check delivery', 'staark-core'); ?></span>
                    <h2><?php esc_html_e('Send a test email', 'staark-core'); ?></h2>
                    <label class="staark-hub-field"><span><?php esc_html_e('Recipient', 'staark-core'); ?></span>
                        <input type="email" name="test_email" value="<?php echo esc_attr($settings['recipient_email']); ?>">
                    </label>
                    <button class="button" type="submit"><?php esc_html_e('Send test email', 'staark-core'); ?></button>
                </form>

                <section class="staark-hub-card">
                    <span class="staark-hub-card-label"><?php esc_html_e('Protection', 'staark-core'); ?></span>
                    <h2><?php esc_html_e('Form security', 'staark-core'); ?></h2>
                    <dl class="staark-fb-dl">
                        <?php foreach (staark_hub_forms_security_summary() as $key => $value) : ?>
                            <dt><?php echo esc_html(strtoupper($key)); ?></dt>
                            <dd><?php echo esc_html($value); ?></dd>
                        <?php endforeach; ?>
                    </dl>
                </section>
            </div>
        </div>
    </div>
    <?php
}
