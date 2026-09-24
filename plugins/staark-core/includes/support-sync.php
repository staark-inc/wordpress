<?php
/**
 * Bidirectional support state for tickets synced with the main Staark Hub.
 *
 * WordPress remains the local client-facing copy. The Hub is authoritative for
 * remote status changes and public Staark replies after the ticket is synced.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Normalize Hub support status values into WordPress ticket meta values.
 */
function staark_hub_support_remote_status_key(string $status): string
{
    $status = sanitize_key(strtolower(trim($status)));

    return in_array($status, ['open', 'in_progress', 'waiting_client', 'resolved'], true)
        ? $status
        : 'open';
}

/**
 * Normalize one public Hub reply.
 *
 * @param mixed $value
 * @return array{id:string,body:string,createdAt:string}|null
 */
function staark_hub_support_remote_reply($value): ?array
{
    if (! is_array($value)) {
        return null;
    }

    $id = isset($value['id']) ? sanitize_text_field((string) $value['id']) : '';
    $body = isset($value['body']) ? sanitize_textarea_field((string) $value['body']) : '';
    $created_at = isset($value['createdAt']) ? sanitize_text_field((string) $value['createdAt']) : '';

    if ($id === '' || $body === '' || $created_at === '') {
        return null;
    }

    return [
        'id' => substr($id, 0, 120),
        'body' => substr($body, 0, 5000),
        'createdAt' => substr($created_at, 0, 80),
    ];
}

/**
 * Apply Hub-authoritative support status and public replies to local tickets.
 *
 * @param array<string,mixed> $remote
 */
function staark_hub_support_apply_remote_tickets(array $remote): int
{
    $tickets = isset($remote['tickets']) && is_array($remote['tickets'])
        ? $remote['tickets']
        : [];

    $received = 0;

    foreach ($tickets as $value) {
        if (! is_array($value)) {
            continue;
        }

        $ticket_id = isset($value['localId']) ? absint($value['localId']) : 0;
        if ($ticket_id <= 0 || get_post_type($ticket_id) !== 'staark_ticket') {
            continue;
        }

        if (isset($value['status']) && is_string($value['status'])) {
            update_post_meta(
                $ticket_id,
                '_staark_ticket_status',
                staark_hub_support_remote_status_key($value['status'])
            );
        }

        if (isset($value['updatedAt']) && is_string($value['updatedAt'])) {
            update_post_meta(
                $ticket_id,
                '_staark_ticket_remote_updated_at',
                sanitize_text_field($value['updatedAt'])
            );
        }

        if (isset($value['replies']) && is_array($value['replies'])) {
            $normalized = [];
            $seen = [];

            foreach (array_slice($value['replies'], -50) as $reply_value) {
                $reply = staark_hub_support_remote_reply($reply_value);
                if ($reply === null || isset($seen[$reply['id']])) {
                    continue;
                }

                $seen[$reply['id']] = true;
                $normalized[] = $reply;
            }

            usort(
                $normalized,
                static fn (array $a, array $b): int => strcmp($a['createdAt'], $b['createdAt'])
            );

            update_post_meta($ticket_id, '_staark_ticket_replies', $normalized);
        }

        ++$received;
    }

    return $received;
}

/**
 * Public replies currently stored for a local support ticket.
 *
 * @return array<int,array{id:string,body:string,createdAt:string}>
 */
function staark_hub_support_replies(int $ticket_id): array
{
    $saved = get_post_meta($ticket_id, '_staark_ticket_replies', true);
    if (! is_array($saved)) {
        return [];
    }

    $replies = [];
    foreach ($saved as $value) {
        $reply = staark_hub_support_remote_reply($value);
        if ($reply !== null) {
            $replies[] = $reply;
        }
    }

    usort(
        $replies,
        static fn (array $a, array $b): int => strcmp($a['createdAt'], $b['createdAt'])
    );

    return $replies;
}

/**
 * Refresh support state when a client opens the Support screen.
 *
 * This is intentionally rate-limited. Manual "Refresh from Staark" bypasses
 * this lock and always performs an explicit signed sync.
 */
function staark_hub_support_maybe_refresh(): void
{
    if (! function_exists('staark_hub_connection_is_connected') || ! staark_hub_connection_is_connected()) {
        return;
    }

    if (get_transient('staark_hub_support_refresh_lock')) {
        return;
    }

    set_transient('staark_hub_support_refresh_lock', '1', 5 * MINUTE_IN_SECONDS);

    if (function_exists('staark_hub_sync_pending_records')) {
        staark_hub_sync_pending_records();
    }
}
