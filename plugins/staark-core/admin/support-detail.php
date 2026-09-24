<?php
/**
 * Staark Hub support ticket detail screen.
 * Tickets stay private and are rendered through Staark Hub instead of the
 * standard WordPress editor UI.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_support_ticket_detail_url(int $ticket_id): string
{
    return add_query_arg(
        [
            'page' => 'staark-hub-support',
            'ticket' => $ticket_id,
        ],
        admin_url('admin.php')
    );
}

function staark_hub_support_ticket_for_detail(int $ticket_id): ?WP_Post
{
    if ($ticket_id <= 0) {
        return null;
    }

    $ticket = get_post($ticket_id);

    return $ticket instanceof WP_Post && $ticket->post_type === 'staark_ticket'
        ? $ticket
        : null;
}

function staark_hub_support_status_key(string $status): string
{
    $status = sanitize_key($status !== '' ? $status : 'open');

    return in_array($status, ['open', 'in_progress', 'waiting_client', 'resolved'], true) ? $status : 'open';
}

function staark_hub_support_status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', staark_hub_support_status_key($status)));
}

function staark_hub_render_support_ticket_detail(int $ticket_id): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to view this support request.', 'staark-core'));
    }

    $ticket = staark_hub_support_ticket_for_detail($ticket_id);
    $support_url = admin_url('admin.php?page=staark-hub-support');
    $support_state = isset($_GET['staark_support']) ? sanitize_key(wp_unslash($_GET['staark_support'])) : '';
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Support'); ?>

        <?php if ($ticket === null) : ?>
            <div class="notice notice-error"><p>The requested support ticket does not exist or is no longer available.</p></div>
            <section class="staark-hub-card staark-hub-ticket-detail-missing">
                <span class="staark-hub-card-label">Support request</span>
                <h2>Ticket not found</h2>
                <p>Return to Support and choose one of the locally stored requests.</p>
                <a class="button button-primary" href="<?php echo esc_url($support_url); ?>">← Back to Support</a>
            </section>
        <?php else : ?>
            <?php
            $categories = staark_hub_support_categories();
            $priorities = staark_hub_support_priorities();
            $category = (string) get_post_meta($ticket_id, '_staark_ticket_category', true);
            $priority = (string) get_post_meta($ticket_id, '_staark_ticket_priority', true);
            $status = staark_hub_support_status_key((string) get_post_meta($ticket_id, '_staark_ticket_status', true));
            $contact_name = (string) get_post_meta($ticket_id, '_staark_ticket_contact_name', true);
            $contact_email = (string) get_post_meta($ticket_id, '_staark_ticket_contact_email', true);
            $environment = get_post_meta($ticket_id, '_staark_ticket_environment', true);
            $environment = is_array($environment) ? $environment : [];
            $email_notified = (string) get_post_meta($ticket_id, '_staark_ticket_email_notified', true);
            $sync_state = sanitize_key((string) get_post_meta($ticket_id, '_staark_ticket_sync_state', true));
            $synced_at = (string) get_post_meta($ticket_id, '_staark_ticket_synced_at', true);
            $channel = sanitize_key((string) get_post_meta($ticket_id, '_staark_ticket_channel', true));
            $category_label = $categories[$category] ?? ucfirst($category ?: 'Other');
            $priority_label = $priorities[$priority] ?? ucfirst($priority ?: 'Normal');
            $site_url = isset($environment['site_url']) ? esc_url_raw((string) $environment['site_url']) : '';
            $sync_label = $sync_state === 'synced' ? 'Synced with Staark Hub' : 'Waiting for sync';
            $replies = function_exists('staark_hub_support_replies')
                ? staark_hub_support_replies($ticket_id)
                : [];
            ?>

            <?php if ($support_state === 'created') : ?>
                <div class="notice notice-success is-dismissible"><p><strong><?php echo esc_html(staark_hub_support_ticket_label($ticket_id)); ?></strong> created. The complete request is shown below.</p></div>
            <?php elseif ($support_state === 'synced') : ?>
                <div class="notice notice-success is-dismissible"><p>Support sync completed. <?php echo esc_html((string) absint($_GET['received'] ?? 0)); ?> remote ticket update(s) received.</p></div>
            <?php elseif ($support_state === 'sync_error') : ?>
                <div class="notice notice-error"><p>Could not refresh this ticket from Staark Hub. Check the connection status and try again.</p></div>
            <?php endif; ?>

            <div class="staark-hub-ticket-detail-topbar">
                <a class="button" href="<?php echo esc_url($support_url); ?>">← Back to Support</a>
                <?php if (function_exists('staark_hub_connection_is_connected') && staark_hub_connection_is_connected()) : ?>
                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" style="display:inline-flex;margin-left:auto">
                        <input type="hidden" name="action" value="staark_sync_now">
                        <input type="hidden" name="return_to" value="support_ticket">
                        <input type="hidden" name="ticket_id" value="<?php echo esc_attr((string) $ticket_id); ?>">
                        <?php wp_nonce_field('staark_sync_now'); ?>
                        <button type="submit" class="button">Refresh from Staark</button>
                    </form>
                <?php endif; ?>
                <span class="staark-hub-mini-status staark-hub-mini-status--muted"><?php echo esc_html(staark_hub_support_ticket_label($ticket_id)); ?></span>
            </div>

            <section class="staark-hub-card staark-hub-ticket-detail-hero">
                <div class="staark-hub-ticket-detail-title">
                    <div>
                        <span class="staark-hub-card-label">Support request</span>
                        <h2><?php echo esc_html($ticket->post_title); ?></h2>
                        <p>Created <?php echo esc_html(get_the_date('Y-m-d H:i', $ticket)); ?> · stored locally in this WordPress installation.</p>
                    </div>
                    <div class="staark-hub-ticket-detail-badges">
                        <span class="staark-hub-priority staark-hub-priority--<?php echo esc_attr($priority ?: 'normal'); ?>"><?php echo esc_html($priority_label); ?></span>
                        <span class="staark-hub-ticket-status staark-hub-ticket-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(staark_hub_support_status_label($status)); ?></span>
                    </div>
                </div>

                <div class="staark-hub-ticket-detail-meta">
                    <div><span>Ticket</span><strong><?php echo esc_html(staark_hub_support_ticket_label($ticket_id)); ?></strong></div>
                    <div><span>Category</span><strong><?php echo esc_html($category_label); ?></strong></div>
                    <div><span>Priority</span><strong><?php echo esc_html($priority_label); ?></strong></div>
                    <div><span>Status</span><strong><?php echo esc_html(staark_hub_support_status_label($status)); ?></strong></div>
                </div>
            </section>

            <div class="staark-hub-grid staark-hub-grid--split staark-hub-ticket-detail-layout">
                <main class="staark-hub-ticket-detail-main">
                    <section class="staark-hub-card">
                        <span class="staark-hub-card-label">Message</span>
                        <h2>Request details</h2>
                        <div class="staark-hub-ticket-message"><?php echo nl2br(esc_html($ticket->post_content)); ?></div>
                    </section>

                    <section class="staark-hub-card">
                        <span class="staark-hub-card-label">Conversation</span>
                        <h2>Staark responses</h2>
                        <?php if ($replies === []) : ?>
                            <p>No public response has been received yet. Use <strong>Refresh from Staark</strong> after Staark updates the ticket.</p>
                        <?php else : ?>
                            <?php foreach ($replies as $reply) :
                                $reply_timestamp = strtotime($reply['createdAt']);
                                $reply_date = $reply_timestamp !== false
                                    ? wp_date('Y-m-d H:i', $reply_timestamp)
                                    : $reply['createdAt'];
                                ?>
                                <div class="staark-hub-ticket-message" style="margin-top:12px">
                                    <p style="margin:0 0 8px"><strong>Staark Inc.</strong> · <?php echo esc_html($reply_date); ?></p>
                                    <?php echo nl2br(esc_html($reply['body'])); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>

                    <section class="staark-hub-card">
                        <span class="staark-hub-card-label">Contact</span>
                        <h2>Requester</h2>
                        <dl class="staark-hub-details staark-hub-ticket-contact">
                            <div><dt>Name</dt><dd><?php echo esc_html($contact_name !== '' ? $contact_name : 'Not provided'); ?></dd></div>
                            <div><dt>Email</dt><dd><?php if (is_email($contact_email)) : ?><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a><?php else : ?>Not provided<?php endif; ?></dd></div>
                            <div><dt>Channel</dt><dd><?php echo esc_html($channel !== '' ? ucfirst($channel) : 'Local'); ?></dd></div>
                        </dl>
                    </section>
                </main>

                <aside class="staark-hub-ticket-detail-sidebar">
                    <section class="staark-hub-card staark-hub-card--dark">
                        <span class="staark-hub-card-label">Delivery</span>
                        <h2><?php echo esc_html($sync_label); ?></h2>
                        <dl class="staark-hub-details staark-hub-details--dark">
                            <div><dt>Sync state</dt><dd><?php echo esc_html($sync_state !== '' ? ucfirst($sync_state) : 'Pending'); ?></dd></div>
                            <div><dt>Hub sync</dt><dd><?php echo esc_html($synced_at !== '' ? $synced_at : 'Not synced yet'); ?></dd></div>
                            <div><dt>Email</dt><dd><?php echo $email_notified === 'yes' ? 'Notification sent' : 'Stored locally'; ?></dd></div>
                        </dl>
                    </section>

                    <section class="staark-hub-card">
                        <span class="staark-hub-card-label">Environment snapshot</span>
                        <h2>Website context</h2>
                        <dl class="staark-hub-details staark-hub-ticket-environment">
                            <div><dt>Website</dt><dd><?php if ($site_url !== '') : ?><a href="<?php echo esc_url($site_url); ?>" target="_blank" rel="noopener"><?php echo esc_html((string) (wp_parse_url($site_url, PHP_URL_HOST) ?: $site_url)); ?> ↗</a><?php else : ?>—<?php endif; ?></dd></div>
                            <div><dt>WordPress</dt><dd><?php echo esc_html((string) ($environment['wordpress'] ?? '—')); ?></dd></div>
                            <div><dt>PHP</dt><dd><?php echo esc_html((string) ($environment['php'] ?? '—')); ?></dd></div>
                            <div><dt>Theme</dt><dd><?php echo esc_html((string) ($environment['theme'] ?? '—')); ?></dd></div>
                            <div><dt>Staark Hub</dt><dd><?php echo esc_html((string) ($environment['hub'] ?? '—')); ?></dd></div>
                            <div><dt>Pending updates</dt><dd><?php echo esc_html((string) ($environment['updates'] ?? '—')); ?></dd></div>
                            <div><dt>Locale</dt><dd><?php echo esc_html((string) ($environment['locale'] ?? '—')); ?></dd></div>
                            <div><dt>Timezone</dt><dd><?php echo esc_html((string) ($environment['timezone'] ?? '—')); ?></dd></div>
                        </dl>
                    </section>
                </aside>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
