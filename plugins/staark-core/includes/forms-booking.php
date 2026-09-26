<?php
/**
 * Staark Forms & Booking.
 *
 * Builds on the Staark Forms submission store (`staark_submission`):
 * - a form registry (label, type, recipients, auto-reply per form_id);
 * - bookings: structured booking fields, booking statuses and status emails;
 * - customer emails (receipt, confirmed, declined, cancelled, manual reply)
 *   from editable templates;
 * - admin notifications: menu badge and an admin bar bell;
 * - an activity log per submission.
 *
 * @package StaarkCore
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_FB_REGISTRY_OPTION = 'staark_hub_form_registry';
const STAARK_HUB_FB_NOTIFY_OPTION = 'staark_hub_forms_notifications';
const STAARK_HUB_FB_COUNTS_TRANSIENT = 'staark_hub_fb_counts';

/**
 * Name of the section in the admin menu, header and admin bar.
 * Filter `staark_hub_inbox_label` to rename it.
 */
function staark_hub_fb_label(): string
{
    return (string) apply_filters('staark_hub_inbox_label', 'S-Hub Inbox');
}

/**
 * Whether the active theme exposes the Booking module in S-Hub Inbox.
 *
 * Core defaults to message-only. Themes that provide booking flows opt in
 * through `staark_hub_booking_enabled`.
 */
function staark_hub_fb_booking_enabled(): bool
{
    return (bool) apply_filters('staark_hub_booking_enabled', false);
}

/**
 * Admin screens of the Forms & Booking section.
 *
 * @return array<string,string> slug => label
 */
function staark_hub_fb_pages(): array
{
    $pages = [
        'staark-hub-inbox' => __('Inbox', 'staark-core'),
    ];

    if (staark_hub_fb_booking_enabled()) {
        $pages['staark-hub-bookings'] = __('Bookings', 'staark-core');
    }

    $pages['staark-hub-form-list'] = __('Forms', 'staark-core');
    $pages['staark-hub-notifications'] = __('Notifications', 'staark-core');
    $pages['staark-hub-form-settings'] = __('Settings', 'staark-core');

    return $pages;
}

/**
 * Capability for handling requests and bookings. Filterable so restaurant or
 * hotel staff with an Editor account can manage bookings.
 */
function staark_hub_fb_cap(): string
{
    return (string) apply_filters('staark_hub_forms_capability', 'manage_options');
}

function staark_hub_fb_url(string $page = 'staark-hub-inbox', array $args = []): string
{
    return add_query_arg(array_merge(['page' => $page], $args), admin_url('admin.php'));
}

/* ------------------------------------------------------------------ */
/* Form registry                                                       */
/* ------------------------------------------------------------------ */

/**
 * Form IDs used by Staark themes, with their default label and type.
 *
 * @return array<string,array{label:string,kind:string}>
 */
function staark_hub_fb_known_forms(): array
{
    $forms = [
        'contact' => ['label' => __('Contact form', 'staark-core'), 'kind' => 'message'],
        'staark-home' => ['label' => __('Contact (Showcase)', 'staark-core'), 'kind' => 'message'],
        'local-business-contact' => ['label' => __('Contact (Local Business)', 'staark-core'), 'kind' => 'message'],
        'bygg-offert' => ['label' => __('Quote request (Bygg)', 'staark-core'), 'kind' => 'message'],
        'salong-bokning' => ['label' => __('Salon booking', 'staark-core'), 'kind' => 'booking'],
        'gast-bord' => ['label' => __('Table booking', 'staark-core'), 'kind' => 'booking'],
        'gast-rum' => ['label' => __('Room booking', 'staark-core'), 'kind' => 'booking'],
    ];

    return (array) apply_filters('staark_hub_known_forms', $forms);
}

/**
 * Number of submissions per form_id.
 *
 * @return array<string,int>
 */
function staark_hub_fb_form_usage(): array
{
    global $wpdb;

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT pm.meta_value AS form_id, COUNT(*) AS total
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s AND p.post_type = %s
             GROUP BY pm.meta_value",
            '_staark_submission_form_id',
            'staark_submission'
        ),
        ARRAY_A
    );

    $usage = [];
    foreach ((array) $rows as $row) {
        $id = sanitize_key((string) ($row['form_id'] ?? ''));
        if ($id !== '') {
            $usage[$id] = (int) $row['total'];
        }
    }

    return $usage;
}

/**
 * Registry of every known or used form: label, type, recipients, auto-reply.
 *
 * @return array<string,array{label:string,kind:string,recipients:string,autoreply:bool,count:int,known:bool}>
 */
function staark_hub_fb_registry(): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    $saved = get_option(STAARK_HUB_FB_REGISTRY_OPTION, []);
    $saved = is_array($saved) ? $saved : [];
    $known = staark_hub_fb_known_forms();
    $usage = staark_hub_fb_form_usage();
    $registry = [];

    foreach (array_unique(array_merge(array_keys($known), array_keys($usage), array_keys($saved))) as $form_id) {
        $form_id = sanitize_key((string) $form_id);
        if ($form_id === '') {
            continue;
        }

        $base = $known[$form_id] ?? ['label' => $form_id, 'kind' => 'message'];
        $own = isset($saved[$form_id]) && is_array($saved[$form_id]) ? $saved[$form_id] : [];
        $kind = (string) ($own['kind'] ?? $base['kind']);

        $registry[$form_id] = [
            'label' => sanitize_text_field((string) (($own['label'] ?? '') !== '' ? $own['label'] : $base['label'])),
            'kind' => in_array($kind, ['message', 'booking'], true) ? $kind : 'message',
            'recipients' => staark_hub_fb_clean_recipients((string) ($own['recipients'] ?? '')),
            'autoreply' => array_key_exists('autoreply', $own) ? ! empty($own['autoreply']) : true,
            'count' => $usage[$form_id] ?? 0,
            'known' => isset($known[$form_id]),
        ];
    }

    // Forms in use first, then alphabetically.
    uasort($registry, static function (array $a, array $b): int {
        return [$b['count'] > 0, $a['label']] <=> [$a['count'] > 0, $b['label']];
    });

    $cache = $registry;

    return $registry;
}

function staark_hub_fb_form(string $form_id): array
{
    $registry = staark_hub_fb_registry();

    return $registry[$form_id] ?? [
        'label' => $form_id !== '' ? $form_id : __('Contact form', 'staark-core'),
        'kind' => 'message',
        'recipients' => '',
        'autoreply' => true,
        'count' => 0,
        'known' => false,
    ];
}

/**
 * True when the form's type is decided: a known Staark form or one saved
 * under S-Hub Inbox → Forms (forms that only appear in the inbox are not).
 */
function staark_hub_fb_form_is_configured(string $form_id): bool
{
    $saved = get_option(STAARK_HUB_FB_REGISTRY_OPTION, []);

    return isset(staark_hub_fb_known_forms()[$form_id]) || (is_array($saved) && isset($saved[$form_id]));
}

function staark_hub_fb_clean_recipients(string $value): string
{
    $emails = [];
    foreach (preg_split('/[\s,;]+/', $value) ?: [] as $email) {
        $email = sanitize_email($email);
        if ($email !== '' && is_email($email)) {
            $emails[] = $email;
        }
    }

    return implode(', ', array_unique($emails));
}

/**
 * Admin recipients for a form: its own list, else the default recipient.
 *
 * @return list<string>
 */
function staark_hub_fb_recipients(string $form_id): array
{
    $own = staark_hub_fb_form($form_id)['recipients'];
    if ($own !== '') {
        return array_map('trim', explode(',', $own));
    }

    $default = staark_hub_forms_settings()['recipient_email'];

    return $default !== '' ? [$default] : [];
}

/**
 * @param array<string,mixed> $input form_id => fields
 */
function staark_hub_fb_save_registry(array $input): void
{
    $saved = [];

    foreach ($input as $form_id => $fields) {
        $form_id = sanitize_key((string) $form_id);
        if ($form_id === '' || ! is_array($fields)) {
            continue;
        }

        $kind = sanitize_key((string) ($fields['kind'] ?? 'message'));
        $saved[$form_id] = [
            'label' => sanitize_text_field((string) ($fields['label'] ?? '')),
            'kind' => in_array($kind, ['message', 'booking'], true) ? $kind : 'message',
            'recipients' => staark_hub_fb_clean_recipients((string) ($fields['recipients'] ?? '')),
            'autoreply' => ! empty($fields['autoreply']),
        ];
    }

    update_option(STAARK_HUB_FB_REGISTRY_OPTION, $saved, false);
}

/* ------------------------------------------------------------------ */
/* Bookings                                                            */
/* ------------------------------------------------------------------ */

/**
 * @return array<string,string>
 */
function staark_hub_booking_statuses(): array
{
    return [
        'pending' => __('Pending', 'staark-core'),
        'confirmed' => __('Confirmed', 'staark-core'),
        'declined' => __('Declined', 'staark-core'),
        'cancelled' => __('Cancelled', 'staark-core'),
        'completed' => __('Completed', 'staark-core'),
    ];
}

/**
 * @return array<string,string>
 */
function staark_hub_booking_types(): array
{
    return [
        'table' => __('Table', 'staark-core'),
        'room' => __('Room', 'staark-core'),
        'appointment' => __('Appointment', 'staark-core'),
        'other' => __('Booking', 'staark-core'),
    ];
}

function staark_hub_fb_valid_date(string $value): string
{
    $value = trim($value);
    if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        return '';
    }

    return $value;
}

/**
 * Optional structured booking fields posted by booking forms.
 *
 * booking_type, booking_date (Y-m-d), booking_end_date (Y-m-d),
 * booking_time (free text, e.g. "19:00" or "Förmiddag"), booking_guests,
 * booking_item (room type, service …).
 *
 * @return array{type:string,date:string,end_date:string,time:string,guests:int,item:string}
 */
function staark_hub_fb_booking_request_fields(): array
{
    $type = sanitize_key(staark_hub_forms_post_scalar('booking_type'));
    $date = staark_hub_fb_valid_date(staark_hub_forms_post_scalar('booking_date'));
    $end = staark_hub_fb_valid_date(staark_hub_forms_post_scalar('booking_end_date'));

    if ($end !== '' && ($date === '' || $end <= $date)) {
        $end = '';
    }

    return [
        'type' => isset(staark_hub_booking_types()[$type]) ? $type : '',
        'date' => $date,
        'end_date' => $end,
        'time' => mb_substr(sanitize_text_field(staark_hub_forms_post_scalar('booking_time')), 0, 40),
        'guests' => min(999, absint(staark_hub_forms_post_scalar('booking_guests'))),
        'item' => mb_substr(sanitize_text_field(staark_hub_forms_post_scalar('booking_item')), 0, 120),
    ];
}

function staark_hub_fb_is_booking(int $submission_id): bool
{
    return get_post_meta($submission_id, '_staark_submission_kind', true) === 'booking';
}

/**
 * @return array{type:string,date:string,end_date:string,time:string,guests:int,item:string,status:string}
 */
function staark_hub_fb_booking(int $submission_id): array
{
    $status = (string) get_post_meta($submission_id, '_staark_booking_status', true);

    return [
        'type' => (string) get_post_meta($submission_id, '_staark_booking_type', true),
        'date' => (string) get_post_meta($submission_id, '_staark_booking_date', true),
        'end_date' => (string) get_post_meta($submission_id, '_staark_booking_end_date', true),
        'time' => (string) get_post_meta($submission_id, '_staark_booking_time', true),
        'guests' => (int) get_post_meta($submission_id, '_staark_booking_guests', true),
        'item' => (string) get_post_meta($submission_id, '_staark_booking_item', true),
        'status' => isset(staark_hub_booking_statuses()[$status]) ? $status : 'pending',
    ];
}

/**
 * Store type and booking details right after a submission is created.
 */
add_action('staark_hub_form_stored', static function (int $submission_id, array $data): void {
    $form = staark_hub_fb_form((string) $data['form_id']);
    // Registered booking forms create bookings. A registered message form
    // never does, even if booking_* fields are posted to it. A form that is
    // not registered yet (custom theme) becomes a booking only when it sends
    // a valid booking date; its type can be fixed under S-Hub Inbox → Forms.
    $is_booking = $form['kind'] === 'booking'
        || (! staark_hub_fb_form_is_configured((string) $data['form_id']) && staark_hub_fb_valid_date(staark_hub_forms_post_scalar('booking_date')) !== '');
    $booking = $is_booking ? staark_hub_fb_booking_request_fields() : [];

    update_post_meta($submission_id, '_staark_submission_kind', $is_booking ? 'booking' : 'message');

    if ($is_booking) {
        if ($booking['type'] === '') {
            $booking['type'] = str_contains((string) $data['form_id'], 'rum') ? 'room'
                : (str_contains((string) $data['form_id'], 'bord') ? 'table'
                : (str_contains((string) $data['form_id'], 'salong') ? 'appointment' : 'other'));
        }

        update_post_meta($submission_id, '_staark_booking_type', $booking['type']);
        update_post_meta($submission_id, '_staark_booking_date', $booking['date']);
        update_post_meta($submission_id, '_staark_booking_end_date', $booking['end_date']);
        update_post_meta($submission_id, '_staark_booking_time', $booking['time']);
        update_post_meta($submission_id, '_staark_booking_guests', $booking['guests']);
        update_post_meta($submission_id, '_staark_booking_item', $booking['item']);
        update_post_meta($submission_id, '_staark_booking_status', 'pending');
        // Sortable key: date + time ("2026-10-02 19:00"); undated bookings sort last.
        update_post_meta($submission_id, '_staark_booking_sort', ($booking['date'] !== '' ? $booking['date'] : '9999-12-31') . ' ' . $booking['time']);
    }

    staark_hub_fb_log($submission_id, 'received', $is_booking ? __('Booking request received', 'staark-core') : __('Message received', 'staark-core'));
    staark_hub_fb_flush_counts();
}, 10, 2);

/**
 * After the admin notification: send the customer receipt.
 */
add_action('staark_hub_form_submitted', static function (int $submission_id, array $data, bool $mail_sent): void {
    staark_hub_fb_log(
        $submission_id,
        'admin_mail',
        $mail_sent
            ? sprintf(__('Notification sent to %s', 'staark-core'), (string) get_post_meta($submission_id, '_staark_submission_mail_recipient', true))
            : __('Admin notification not sent', 'staark-core'),
        $mail_sent
    );

    $settings = staark_hub_fb_notify_settings();
    $form = staark_hub_fb_form((string) $data['form_id']);

    if ($settings['customer_receipt'] && $form['autoreply']) {
        $skip = staark_hub_fb_autoreply_blocked($submission_id);
        if ($skip !== '') {
            staark_hub_fb_log($submission_id, 'customer_mail', sprintf(__('Auto-reply not sent: %s', 'staark-core'), $skip), false);
            return;
        }

        staark_hub_fb_send_customer_mail(
            $submission_id,
            staark_hub_fb_is_booking($submission_id) ? 'receipt_booking' : 'receipt_message'
        );
    }
}, 20, 3);

/**
 * Guard for the automatic "we received your request" email.
 *
 * The public form lets anyone type any email address, so an auto-reply can
 * be abused to send mail from the site's domain to strangers. Auto-replies
 * never repeat the visitor's message (see staark_hub_fb_template_values()),
 * and they are skipped when the request looks like spam or a limit is hit:
 * - the name or message contains a link;
 * - more than N auto-replies were sent this hour (default 30);
 * - the same address already got 2 auto-replies in the last 24 hours.
 *
 * @return string Reason when blocked, '' when the auto-reply may be sent.
 */
function staark_hub_fb_autoreply_blocked(int $submission_id): string
{
    $email = strtolower((string) get_post_meta($submission_id, '_staark_submission_email', true));
    $text = (string) get_post_meta($submission_id, '_staark_submission_name', true)
        . ' ' . (string) get_post_meta($submission_id, '_staark_booking_item', true)
        . ' ' . (string) get_post_field('post_content', $submission_id);

    // Email addresses (anna@gmail.com) are normal in a message, not links.
    $text = (string) preg_replace('/[^\s@<>]+@[^\s@<>]+/', ' ', $text);
    if (preg_match('~(https?://|www\.|\b[a-z0-9-]+\.(?:com|net|org|info|io|ru|cn|xyz|top|link|click|site|online)\b)~i', $text)) {
        return __('the request contains a link', 'staark-core');
    }

    $hourly_max = max(1, absint(apply_filters('staark_hub_autoreply_hourly_limit', 30)));
    $hour_key = 'staark_fb_ar_h_' . gmdate('YmdH');
    $hour_count = (int) get_transient($hour_key);
    if ($hour_count >= $hourly_max) {
        return __('hourly auto-reply limit reached', 'staark-core');
    }

    $per_address_max = max(1, absint(apply_filters('staark_hub_autoreply_address_limit', 2)));
    $address_key = 'staark_fb_ar_a_' . substr(hash_hmac('sha256', $email, wp_salt('nonce')), 0, 24);
    $address_count = (int) get_transient($address_key);
    if ($address_count >= $per_address_max) {
        return __('this address already received auto-replies today', 'staark-core');
    }

    set_transient($hour_key, $hour_count + 1, HOUR_IN_SECONDS + MINUTE_IN_SECONDS);
    set_transient($address_key, $address_count + 1, DAY_IN_SECONDS);

    return '';
}

/* ------------------------------------------------------------------ */
/* Activity log                                                        */
/* ------------------------------------------------------------------ */

function staark_hub_fb_log(int $submission_id, string $type, string $text, bool $ok = true): void
{
    $log = get_post_meta($submission_id, '_staark_submission_log', true);
    $log = is_array($log) ? $log : [];
    $log[] = [
        't' => time(),
        'type' => sanitize_key($type),
        'text' => sanitize_text_field($text),
        'ok' => $ok,
        'user' => get_current_user_id(),
    ];

    update_post_meta($submission_id, '_staark_submission_log', array_slice($log, -50));
}

/**
 * @return list<array{t:int,type:string,text:string,ok:bool,user:int}>
 */
function staark_hub_fb_get_log(int $submission_id): array
{
    $log = get_post_meta($submission_id, '_staark_submission_log', true);

    return is_array($log) ? array_values($log) : [];
}

/* ------------------------------------------------------------------ */
/* Notification settings and templates                                 */
/* ------------------------------------------------------------------ */

/**
 * @return array<string,array{label:string,description:string,subject:string,body:string}>
 */
function staark_hub_fb_default_templates(): array
{
    return [
        'receipt_booking' => [
            'label' => __('Booking request received', 'staark-core'),
            'description' => __('Sent automatically when a booking request arrives.', 'staark-core'),
            'subject' => 'Vi har tagit emot din bokningsförfrågan – {site}',
            'body' => "Hej {first_name}!\n\nTack för din bokningsförfrågan. Vi återkommer så snart vi har gått igenom den.\n\n{details}\n\nObservera att bokningen gäller först när du fått vår bekräftelse.\n\nVänliga hälsningar\n{site}\n{site_url}",
        ],
        'receipt_message' => [
            'label' => __('Message received', 'staark-core'),
            'description' => __('Sent automatically when a contact or quote request arrives.', 'staark-core'),
            'subject' => 'Tack för ditt meddelande – {site}',
            'body' => "Hej {first_name}!\n\nTack för att du kontaktade oss. Vi har tagit emot ditt meddelande och svarar så snart vi kan.\n\nVänliga hälsningar\n{site}\n{site_url}",
        ],
        'confirmed' => [
            'label' => __('Booking confirmed', 'staark-core'),
            'description' => __('Sent when you confirm a booking.', 'staark-core'),
            'subject' => 'Din bokning är bekräftad – {site}',
            'body' => "Hej {first_name}!\n\nDin bokning är bekräftad. Varmt välkommen!\n\n{details}\n\n{note}\n\nBehöver du ändra eller avboka? Svara på det här mejlet eller ring oss.\n\nVänliga hälsningar\n{site}\n{site_url}",
        ],
        'declined' => [
            'label' => __('Booking declined', 'staark-core'),
            'description' => __('Sent when you decline a booking request.', 'staark-core'),
            'subject' => 'Angående din bokningsförfrågan – {site}',
            'body' => "Hej {first_name}!\n\nTack för din förfrågan. Tyvärr kan vi inte ta emot bokningen som du önskade.\n\n{details}\n\n{note}\n\nHör gärna av dig om du vill hitta en annan tid.\n\nVänliga hälsningar\n{site}\n{site_url}",
        ],
        'cancelled' => [
            'label' => __('Booking cancelled', 'staark-core'),
            'description' => __('Sent when you cancel a confirmed booking.', 'staark-core'),
            'subject' => 'Din bokning är avbokad – {site}',
            'body' => "Hej {first_name}!\n\nDin bokning har avbokats.\n\n{details}\n\n{note}\n\nVälkommen att boka en ny tid när det passar dig.\n\nVänliga hälsningar\n{site}\n{site_url}",
        ],
        'reply' => [
            'label' => __('Reply', 'staark-core'),
            'description' => __('Subject and signature for replies written in the Inbox.', 'staark-core'),
            'subject' => 'Svar från {site}',
            'body' => "Hej {first_name}!\n\n{note}\n\nVänliga hälsningar\n{site}\n{site_url}",
        ],
    ];
}

/**
 * @return array{admin_badge:bool,admin_bar:bool,customer_receipt:bool,customer_status:bool,templates:array<string,array{subject:string,body:string}>}
 */
function staark_hub_fb_notify_settings(): array
{
    $saved = get_option(STAARK_HUB_FB_NOTIFY_OPTION, []);
    $saved = is_array($saved) ? $saved : [];
    $defaults = staark_hub_fb_default_templates();
    $templates = [];

    foreach ($defaults as $key => $template) {
        $own = isset($saved['templates'][$key]) && is_array($saved['templates'][$key]) ? $saved['templates'][$key] : [];
        $subject = trim((string) ($own['subject'] ?? ''));
        $body = trim((string) ($own['body'] ?? ''));
        $templates[$key] = [
            'subject' => $subject !== '' ? $subject : $template['subject'],
            'body' => $body !== '' ? $body : $template['body'],
        ];
    }

    return [
        'admin_badge' => ! array_key_exists('admin_badge', $saved) || ! empty($saved['admin_badge']),
        'admin_bar' => ! array_key_exists('admin_bar', $saved) || ! empty($saved['admin_bar']),
        'customer_receipt' => ! array_key_exists('customer_receipt', $saved) || ! empty($saved['customer_receipt']),
        'customer_status' => ! array_key_exists('customer_status', $saved) || ! empty($saved['customer_status']),
        'templates' => $templates,
    ];
}

/**
 * @param array<string,mixed> $input
 */
function staark_hub_fb_save_notify_settings(array $input): void
{
    $defaults = staark_hub_fb_default_templates();
    $templates = [];

    foreach (array_keys($defaults) as $key) {
        $own = isset($input['templates'][$key]) && is_array($input['templates'][$key]) ? $input['templates'][$key] : [];
        $subject = sanitize_text_field((string) ($own['subject'] ?? ''));
        $body = sanitize_textarea_field((string) ($own['body'] ?? ''));

        // Store only what differs from the default, so default copy can improve later.
        $templates[$key] = [
            'subject' => $subject === $defaults[$key]['subject'] ? '' : $subject,
            'body' => str_replace("\r\n", "\n", $body) === $defaults[$key]['body'] ? '' : $body,
        ];
    }

    update_option(
        STAARK_HUB_FB_NOTIFY_OPTION,
        [
            'admin_badge' => ! empty($input['admin_badge']),
            'admin_bar' => ! empty($input['admin_bar']),
            'customer_receipt' => ! empty($input['customer_receipt']),
            'customer_status' => ! empty($input['customer_status']),
            'templates' => $templates,
        ],
        false
    );
}

/**
 * Placeholders available in templates.
 *
 * @return array<string,string>
 */
function staark_hub_fb_placeholders(): array
{
    return [
        '{name}' => __('Full name', 'staark-core'),
        '{first_name}' => __('First name', 'staark-core'),
        '{details}' => __('Booking details (date, time, guests …)', 'staark-core'),
        '{date}' => __('Booking date', 'staark-core'),
        '{end_date}' => __('Check-out date', 'staark-core'),
        '{time}' => __('Booking time', 'staark-core'),
        '{guests}' => __('Number of guests', 'staark-core'),
        '{item}' => __('Room type / service', 'staark-core'),
        '{reference}' => __('Reference number', 'staark-core'),
        '{message}' => __('Customer message (left out of automatic receipts)', 'staark-core'),
        '{note}' => __('Your note (written when confirming / replying)', 'staark-core'),
        '{site}' => __('Site name', 'staark-core'),
        '{site_url}' => __('Site address', 'staark-core'),
        '{phone}' => __('Your phone (Staark Hub SEO)', 'staark-core'),
    ];
}

/**
 * "lördag 3 oktober 2026". Customer templates and booking details are
 * Swedish, so dates are too, whatever the admin language is. Filter
 * `staark_hub_fb_format_date` to change it.
 */
function staark_hub_fb_format_date(string $date): string
{
    if ($date === '' || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
        return $date;
    }

    $days = ['söndag', 'måndag', 'tisdag', 'onsdag', 'torsdag', 'fredag', 'lördag'];
    $months = ['januari', 'februari', 'mars', 'april', 'maj', 'juni', 'juli', 'augusti', 'september', 'oktober', 'november', 'december'];
    $weekday = (int) gmdate('w', (int) gmmktime(12, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]));
    $text = $days[$weekday] . ' ' . (int) $m[3] . ' ' . $months[(int) $m[2] - 1] . ' ' . $m[1];

    return (string) apply_filters('staark_hub_fb_format_date', $text, $date);
}

/**
 * Human readable booking details for emails and the admin.
 *
 * @return list<array{0:string,1:string}>
 */
function staark_hub_fb_booking_lines(int $submission_id): array
{
    if (! staark_hub_fb_is_booking($submission_id)) {
        return [];
    }

    $booking = staark_hub_fb_booking($submission_id);
    $room = $booking['type'] === 'room';
    $lines = [];

    if ($booking['date'] !== '') {
        $lines[] = [$room ? 'Incheckning' : 'Datum', staark_hub_fb_format_date($booking['date'])];
    }
    if ($booking['end_date'] !== '') {
        $lines[] = ['Utcheckning', staark_hub_fb_format_date($booking['end_date'])];
        $nights = (int) round((strtotime($booking['end_date']) - strtotime($booking['date'])) / DAY_IN_SECONDS);
        if ($nights > 0) {
            $lines[] = ['Nätter', (string) $nights];
        }
    }
    if ($booking['time'] !== '') {
        $lines[] = ['Tid', $booking['time']];
    }
    if ($booking['guests'] > 0) {
        $lines[] = [$room ? 'Gäster' : 'Antal gäster', (string) $booking['guests']];
    }
    if ($booking['item'] !== '') {
        $lines[] = [$room ? 'Rum' : ($booking['type'] === 'appointment' ? 'Behandling' : ($booking['type'] === 'table' ? 'Tillfälle' : 'Önskemål')), $booking['item']];
    }
    $lines[] = ['Referens', staark_hub_form_submission_label($submission_id)];

    return $lines;
}

/**
 * @return array<string,string>
 */
function staark_hub_fb_template_values(int $submission_id, string $note = '', bool $automatic = false): array
{
    $name = trim((string) get_post_meta($submission_id, '_staark_submission_name', true));
    if ($automatic) {
        // Automatic emails go to an address typed by an anonymous visitor:
        // only keep a plain name (letters, spaces, - and '), never free text.
        $name = trim(mb_substr((string) preg_replace("/[^\\p{L}\\p{M} '\\-]+/u", '', $name), 0, 40));
    }
    $first = $name !== '' ? (string) preg_split('/\s+/', $name)[0] : '';
    $booking = staark_hub_fb_is_booking($submission_id) ? staark_hub_fb_booking($submission_id) : null;
    $details = implode("\n", array_map(static fn (array $line): string => $line[0] . ': ' . $line[1], staark_hub_fb_booking_lines($submission_id)));
    $seo = function_exists('staark_hub_seo_settings') ? staark_hub_seo_settings() : [];
    $post = get_post($submission_id);

    return [
        '{name}' => $name,
        '{first_name}' => $first,
        '{details}' => $details,
        '{date}' => $booking ? staark_hub_fb_format_date($booking['date']) : '',
        '{end_date}' => $booking ? staark_hub_fb_format_date($booking['end_date']) : '',
        '{time}' => $booking ? $booking['time'] : '',
        '{guests}' => $booking && $booking['guests'] > 0 ? (string) $booking['guests'] : '',
        '{item}' => $booking ? $booking['item'] : '',
        '{reference}' => staark_hub_form_submission_label($submission_id),
        '{message}' => ! $automatic && $post instanceof WP_Post ? (string) $post->post_content : '',
        '{note}' => trim($note),
        '{site}' => trim(wp_strip_all_tags((string) get_bloginfo('name'))),
        '{site_url}' => home_url('/'),
        '{phone}' => isset($seo['phone']) ? (string) $seo['phone'] : '',
    ];
}

function staark_hub_fb_render_template(string $text, array $values): string
{
    $text = strtr($text, $values);
    // Empty placeholders leave blank blocks; collapse them.
    $text = preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", $text));

    return trim((string) $text);
}

/**
 * Send one customer email from a template.
 */
function staark_hub_fb_send_customer_mail(int $submission_id, string $template, string $note = '', string $subject_override = ''): bool
{
    $email = (string) get_post_meta($submission_id, '_staark_submission_email', true);
    $templates = staark_hub_fb_notify_settings()['templates'];

    if (! is_email($email) || ! isset($templates[$template])) {
        return false;
    }

    $values = staark_hub_fb_template_values($submission_id, $note, str_starts_with($template, 'receipt_'));
    $subject = staark_hub_fb_render_template($subject_override !== '' ? $subject_override : $templates[$template]['subject'], $values);
    $body = staark_hub_fb_render_template($templates[$template]['body'], $values);

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    $form_id = (string) get_post_meta($submission_id, '_staark_submission_form_id', true);
    $reply_to = staark_hub_fb_recipients($form_id)[0] ?? '';
    if ($reply_to !== '' && is_email($reply_to)) {
        $headers[] = 'Reply-To: ' . trim(wp_strip_all_tags((string) get_bloginfo('name'))) . ' <' . $reply_to . '>';
    }

    $result = staark_hub_forms_mail($email, $subject, $body, $headers);
    $labels = staark_hub_fb_default_templates();

    staark_hub_fb_log(
        $submission_id,
        'customer_mail',
        $result['sent']
            ? sprintf(__('Email to customer: %s', 'staark-core'), $labels[$template]['label'])
            : sprintf(__('Email to customer failed (%1$s): %2$s', 'staark-core'), $labels[$template]['label'], $result['error']),
        $result['sent']
    );

    return $result['sent'];
}

/* ------------------------------------------------------------------ */
/* Counts, badge and admin bar                                         */
/* ------------------------------------------------------------------ */

function staark_hub_fb_flush_counts(): void
{
    delete_transient(STAARK_HUB_FB_COUNTS_TRANSIENT);
}

/**
 * Meta query matching regular messages while excluding booking submissions.
 *
 * Older submissions may not have `_staark_submission_kind`, so missing kind is
 * treated as a regular message for backwards compatibility.
 *
 * @return array<string|int,mixed>
 */
function staark_hub_fb_message_meta_query(): array
{
    return [
        'relation' => 'OR',
        ['key' => '_staark_submission_kind', 'value' => 'message'],
        ['key' => '_staark_submission_kind', 'compare' => 'NOT EXISTS'],
    ];
}

/**
 * Meta query for items that need attention: unread messages and pending
 * bookings (spam excluded).
 *
 * @return array<string|int,mixed>
 */
function staark_hub_fb_attention_meta_query(): array
{
    if (! staark_hub_fb_booking_enabled()) {
        return [
            'relation' => 'AND',
            staark_hub_fb_message_meta_query(),
            ['key' => '_staark_submission_status', 'value' => 'new'],
        ];
    }

    return [
        'relation' => 'OR',
        [
            'key' => '_staark_submission_status',
            'value' => 'new',
        ],
        [
            'relation' => 'AND',
            [
                'key' => '_staark_booking_status',
                'value' => 'pending',
            ],
            [
                'key' => '_staark_submission_status',
                'value' => 'spam',
                'compare' => '!=',
            ],
        ],
    ];
}

function staark_hub_fb_count(array $meta_query): int
{
    $query = new WP_Query(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => $meta_query,
            'suppress_filters' => true,
        ]
    );

    return (int) $query->found_posts;
}

/**
 * @return array{attention:int,new:int,pending:int,today:int,upcoming:int,total:int,bookings:int,spam:int,today_date:string,booking_enabled:bool}
 */
function staark_hub_fb_counts(): array
{
    $booking_enabled = staark_hub_fb_booking_enabled();
    $cached = get_transient(STAARK_HUB_FB_COUNTS_TRANSIENT);
    if (
        is_array($cached)
        && isset($cached['attention'], $cached['today_date'], $cached['booking_enabled'])
        && $cached['today_date'] === wp_date('Y-m-d')
        && (bool) $cached['booking_enabled'] === $booking_enabled
    ) {
        return $cached;
    }

    $today = wp_date('Y-m-d');
    $active = ['key' => '_staark_booking_status', 'value' => ['pending', 'confirmed'], 'compare' => 'IN'];
    $new_query = [['key' => '_staark_submission_status', 'value' => 'new']];
    $total_query = [];
    $spam_query = [['key' => '_staark_submission_status', 'value' => 'spam']];

    if (! $booking_enabled) {
        $message_scope = staark_hub_fb_message_meta_query();
        $new_query[] = $message_scope;
        $total_query[] = $message_scope;
        $spam_query[] = $message_scope;
    }

    $counts = [
        'attention' => staark_hub_fb_count(staark_hub_fb_attention_meta_query()),
        'new' => staark_hub_fb_count($new_query),
        'pending' => $booking_enabled
            ? staark_hub_fb_count([
                ['key' => '_staark_booking_status', 'value' => 'pending'],
                ['key' => '_staark_submission_status', 'value' => 'spam', 'compare' => '!='],
            ])
            : 0,
        'today' => $booking_enabled
            ? staark_hub_fb_count([$active, ['key' => '_staark_booking_date', 'value' => $today]])
            : 0,
        'upcoming' => $booking_enabled
            ? staark_hub_fb_count([$active, ['key' => '_staark_booking_date', 'value' => $today, 'compare' => '>=']])
            : 0,
        'total' => staark_hub_fb_count($total_query),
        'bookings' => $booking_enabled
            ? staark_hub_fb_count([['key' => '_staark_submission_kind', 'value' => 'booking']])
            : 0,
        'spam' => staark_hub_fb_count($spam_query),
        'today_date' => $today,
        'booking_enabled' => $booking_enabled,
    ];

    // Every write flushes this, so a long TTL only matters for the date rollover (checked above).
    set_transient(STAARK_HUB_FB_COUNTS_TRANSIENT, $counts, HOUR_IN_SECONDS);

    return $counts;
}

/**
 * Latest items that need attention.
 *
 * @return list<WP_Post>
 */
function staark_hub_fb_attention_items(int $limit = 5): array
{
    return get_posts(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => staark_hub_fb_attention_meta_query(),
            'suppress_filters' => true,
        ]
    );
}

/**
 * One-line summary of a submission, used by the admin bar and lists.
 */
function staark_hub_fb_item_summary(WP_Post $post): string
{
    $id = (int) $post->ID;
    $name = (string) get_post_meta($id, '_staark_submission_name', true);

    if (! staark_hub_fb_is_booking($id)) {
        return sprintf(__('Message · %s', 'staark-core'), $name);
    }

    $booking = staark_hub_fb_booking($id);
    $when = $booking['date'] !== '' ? wp_date('j M', (int) strtotime($booking['date'] . ' 12:00:00')) : '';
    if ($booking['time'] !== '' && strlen($booking['time']) <= 5) {
        $when .= ' ' . $booking['time'];
    }

    return trim(sprintf(__('Booking · %1$s · %2$s', 'staark-core'), $name, $when), ' ·');
}

add_action('admin_bar_menu', static function (WP_Admin_Bar $bar): void {
    if (! is_user_logged_in() || ! current_user_can(staark_hub_fb_cap()) || ! staark_hub_fb_notify_settings()['admin_bar']) {
        return;
    }

    $count = staark_hub_fb_counts()['attention'];

    $bar->add_node(
        [
            'id' => 'staark-fb',
            'title' => '<span class="ab-icon" aria-hidden="true"></span>'
                . ($count > 0 ? '<span class="staark-fb-count">' . esc_html((string) $count) . '</span>' : '')
                . '<span class="screen-reader-text">' . esc_html(sprintf(_n('%d request needs attention', '%d requests need attention', $count, 'staark-core'), $count)) . '</span>',
            'href' => staark_hub_fb_url(),
            'meta' => ['class' => $count > 0 ? 'staark-fb-has-items' : 'staark-fb-empty', 'title' => staark_hub_fb_label()],
        ]
    );

    $items = $count > 0 ? staark_hub_fb_attention_items(5) : [];
    foreach ($items as $item) {
        $bar->add_node(
            [
                'parent' => 'staark-fb',
                'id' => 'staark-fb-item-' . $item->ID,
                'title' => esc_html(staark_hub_fb_item_summary($item)),
                'href' => staark_hub_fb_url('staark-hub-inbox', ['submission' => $item->ID]),
            ]
        );
    }

    $bar->add_node(
        [
            'parent' => 'staark-fb',
            'id' => 'staark-fb-all',
            'title' => $count > 0 ? esc_html__('Open Inbox', 'staark-core') : esc_html__('No new requests · Open Inbox', 'staark-core'),
            'href' => staark_hub_fb_url(),
        ]
    );

    $bar->add_node(
        [
            'parent' => 'staark-fb',
            'id' => 'staark-fb-bookings',
            'title' => esc_html__('Bookings', 'staark-core'),
            'href' => staark_hub_fb_url('staark-hub-bookings'),
        ]
    );
}, 80);

function staark_hub_fb_admin_bar_css(): string
{
    return '#wpadminbar #wp-admin-bar-staark-fb > .ab-item .ab-icon:before{content:"\f16d";top:2px}'
        . '#wpadminbar #wp-admin-bar-staark-fb .staark-fb-count{display:inline-block;min-width:18px;height:18px;margin-left:2px;padding:0 5px;border-radius:9px;background:#d63638;color:#fff;font-size:11px;font-weight:600;line-height:18px;text-align:center;box-sizing:border-box}'
        . '#wpadminbar #wp-admin-bar-staark-fb.staark-fb-empty > .ab-item .ab-icon:before{opacity:.6}';
}

add_action('admin_bar_init', static function (): void {
    if (is_user_logged_in() && current_user_can(staark_hub_fb_cap())) {
        wp_add_inline_style('admin-bar', staark_hub_fb_admin_bar_css());
    }
});

/* ------------------------------------------------------------------ */
/* Admin actions                                                       */
/* ------------------------------------------------------------------ */

function staark_hub_fb_guard(string $nonce_action, string $capability = ''): void
{
    if (! current_user_can($capability !== '' ? $capability : staark_hub_fb_cap())) {
        wp_die(esc_html__('You are not allowed to do this.', 'staark-core'), '', ['response' => 403]);
    }

    if (! isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
        wp_die(esc_html__('Invalid request.', 'staark-core'), '', ['response' => 405]);
    }

    check_admin_referer($nonce_action);
}

function staark_hub_fb_submission_id_from_post(): int
{
    $id = absint(staark_hub_forms_post_scalar('submission_id'));

    return $id > 0 && get_post_type($id) === 'staark_submission' ? $id : 0;
}

/**
 * Redirect back to the screen the action came from.
 */
function staark_hub_fb_back(array $args = []): void
{
    $return = esc_url_raw(staark_hub_forms_post_scalar('return_to'));
    $url = $return !== '' && str_starts_with($return, admin_url()) ? $return : staark_hub_fb_url();
    $url = remove_query_arg(['fb_notice', 'fb_mail'], $url);

    wp_safe_redirect(add_query_arg($args, $url));
    exit;
}

/**
 * Change a booking status, optionally emailing the customer.
 */
function staark_hub_fb_set_booking_status(int $submission_id, string $status, string $note = '', bool $email = false): array
{
    $statuses = staark_hub_booking_statuses();
    if (! isset($statuses[$status]) || ! staark_hub_fb_is_booking($submission_id)) {
        return ['ok' => false, 'mail' => ''];
    }

    $previous = staark_hub_fb_booking($submission_id)['status'];
    update_post_meta($submission_id, '_staark_booking_status', $status);
    staark_hub_fb_log(
        $submission_id,
        'status',
        sprintf(__('Status: %1$s → %2$s', 'staark-core'), $statuses[$previous], $statuses[$status]) . ($note !== '' ? ' · ' . $note : '')
    );

    $mail = '';
    if ($email && in_array($status, ['confirmed', 'declined', 'cancelled'], true)) {
        $mail = staark_hub_fb_send_customer_mail($submission_id, $status, $note) ? 'sent' : 'failed';
    }

    $message_status = (string) get_post_meta($submission_id, '_staark_submission_status', true);
    if ($message_status !== 'spam') {
        update_post_meta($submission_id, '_staark_submission_status', $mail === 'sent' ? 'replied' : ($message_status === 'new' ? 'read' : $message_status));
    }

    staark_hub_fb_flush_counts();

    /**
     * Fires after a booking status change.
     */
    do_action('staark_hub_booking_status_changed', $submission_id, $status, $previous, $note);

    return ['ok' => true, 'mail' => $mail];
}

add_action('admin_post_staark_fb_booking_status', static function (): void {
    $id = staark_hub_fb_submission_id_from_post();
    staark_hub_fb_guard('staark_fb_booking_' . $id);

    $result = staark_hub_fb_set_booking_status(
        $id,
        sanitize_key(staark_hub_forms_post_scalar('status')),
        sanitize_textarea_field(staark_hub_forms_post_scalar('note')),
        staark_hub_forms_post_scalar('notify_customer') === '1'
    );

    staark_hub_fb_back(['fb_notice' => $result['ok'] ? 'status' : 'error', 'fb_mail' => $result['mail']]);
});

add_action('admin_post_staark_fb_booking_edit', static function (): void {
    $id = staark_hub_fb_submission_id_from_post();
    staark_hub_fb_guard('staark_fb_booking_edit_' . $id);

    if ($id > 0 && staark_hub_fb_is_booking($id)) {
        $date = staark_hub_fb_valid_date(staark_hub_forms_post_scalar('booking_date'));
        $end = staark_hub_fb_valid_date(staark_hub_forms_post_scalar('booking_end_date'));
        if ($end !== '' && $end <= $date) {
            $end = '';
        }
        $time = mb_substr(sanitize_text_field(staark_hub_forms_post_scalar('booking_time')), 0, 40);

        update_post_meta($id, '_staark_booking_date', $date);
        update_post_meta($id, '_staark_booking_end_date', $end);
        update_post_meta($id, '_staark_booking_time', $time);
        update_post_meta($id, '_staark_booking_guests', min(999, absint(staark_hub_forms_post_scalar('booking_guests'))));
        update_post_meta($id, '_staark_booking_item', mb_substr(sanitize_text_field(staark_hub_forms_post_scalar('booking_item')), 0, 120));
        update_post_meta($id, '_staark_booking_sort', ($date !== '' ? $date : '9999-12-31') . ' ' . $time);
        staark_hub_fb_log($id, 'edit', __('Booking details updated', 'staark-core'));
        staark_hub_fb_flush_counts();
    }

    staark_hub_fb_back(['fb_notice' => 'saved']);
});

add_action('admin_post_staark_fb_reply', static function (): void {
    $id = staark_hub_fb_submission_id_from_post();
    staark_hub_fb_guard('staark_fb_reply_' . $id);

    $body = sanitize_textarea_field(staark_hub_forms_post_scalar('reply_body'));
    $subject = sanitize_text_field(staark_hub_forms_post_scalar('reply_subject'));
    $mail = '';

    if ($id > 0 && trim($body) !== '') {
        $sent = staark_hub_fb_send_customer_mail($id, 'reply', $body, $subject);
        $mail = $sent ? 'sent' : 'failed';
        if ($sent) {
            update_post_meta($id, '_staark_submission_status', 'replied');
            staark_hub_fb_flush_counts();
        }
    }

    staark_hub_fb_back(['fb_notice' => 'reply', 'fb_mail' => $mail]);
});

add_action('admin_post_staark_fb_message_status', static function (): void {
    $id = staark_hub_fb_submission_id_from_post();
    staark_hub_fb_guard('staark_fb_message_' . $id);

    $status = sanitize_key(staark_hub_forms_post_scalar('status'));
    if ($id > 0 && isset(staark_hub_form_statuses()[$status])) {
        update_post_meta($id, '_staark_submission_status', $status);
        staark_hub_fb_log($id, 'status', sprintf(__('Marked as %s', 'staark-core'), staark_hub_form_statuses()[$status]));
        staark_hub_fb_flush_counts();
    }

    staark_hub_fb_back(['fb_notice' => 'status']);
});

add_action('admin_post_staark_fb_delete', static function (): void {
    $id = staark_hub_fb_submission_id_from_post();
    staark_hub_fb_guard('staark_fb_delete_' . $id);

    if ($id > 0) {
        wp_delete_post($id, true);
        staark_hub_fb_flush_counts();
    }

    wp_safe_redirect(staark_hub_fb_url('staark-hub-inbox', ['fb_notice' => 'deleted']));
    exit;
});

add_action('admin_post_staark_fb_bulk', static function (): void {
    staark_hub_fb_guard('staark_fb_bulk');

    $action = sanitize_key(staark_hub_forms_post_scalar('bulk_action'));
    $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('absint', wp_unslash($_POST['ids'])) : [];
    $done = 0;

    foreach ($ids as $id) {
        if ($id <= 0 || get_post_type($id) !== 'staark_submission') {
            continue;
        }

        if ($action === 'delete') {
            wp_delete_post($id, true);
        } elseif (in_array($action, ['read', 'new', 'spam'], true)) {
            update_post_meta($id, '_staark_submission_status', $action);
        } elseif (in_array($action, ['confirmed', 'declined', 'completed'], true) && staark_hub_fb_is_booking($id)) {
            staark_hub_fb_set_booking_status($id, $action, '', false);
        } else {
            continue;
        }

        ++$done;
    }

    staark_hub_fb_flush_counts();
    staark_hub_fb_back(['fb_notice' => 'bulk', 'fb_done' => $done]);
});

add_action('admin_post_staark_fb_save_forms', static function (): void {
    staark_hub_fb_guard('staark_fb_save_forms', 'manage_options');

    $forms = isset($_POST['forms']) && is_array($_POST['forms']) ? wp_unslash($_POST['forms']) : [];
    staark_hub_fb_save_registry($forms);

    wp_safe_redirect(staark_hub_fb_url('staark-hub-form-list', ['fb_notice' => 'saved']));
    exit;
});

add_action('admin_post_staark_fb_save_notifications', static function (): void {
    staark_hub_fb_guard('staark_fb_save_notifications', 'manage_options');

    $post = wp_unslash($_POST);
    staark_hub_fb_save_notify_settings(is_array($post) ? $post : []);

    // Admin email settings live in the Forms settings option.
    staark_hub_fb_save_forms_settings_subset(
        [
            'recipient_email' => (string) ($post['recipient_email'] ?? ''),
            'subject_prefix' => (string) ($post['subject_prefix'] ?? ''),
            'notify' => ! empty($post['notify']),
        ]
    );

    wp_safe_redirect(staark_hub_fb_url('staark-hub-notifications', ['fb_notice' => 'saved']));
    exit;
});

add_action('admin_post_staark_fb_save_settings', static function (): void {
    staark_hub_fb_guard('staark_fb_save_settings', 'manage_options');

    $post = wp_unslash($_POST);
    $post = is_array($post) ? $post : [];
    $keys = ['success_message', 'privacy_url', 'mail_transport', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];
    $subset = array_intersect_key($post, array_flip($keys));
    $subset['smtp_auth'] = ! empty($post['smtp_auth']);

    staark_hub_fb_save_forms_settings_subset($subset);

    wp_safe_redirect(staark_hub_fb_url('staark-hub-form-settings', ['fb_notice' => 'saved']));
    exit;
});

add_action('admin_post_staark_fb_test_mail', static function (): void {
    staark_hub_fb_guard('staark_fb_test_mail', 'manage_options');

    $recipient = sanitize_email(staark_hub_forms_post_scalar('test_email'));
    $result = staark_hub_forms_send_test_email($recipient);
    set_transient('staark_forms_mail_test_' . get_current_user_id(), $result + ['recipient' => $recipient], 5 * MINUTE_IN_SECONDS);

    wp_safe_redirect(staark_hub_fb_url('staark-hub-form-settings', ['fb_notice' => 'mail_test']));
    exit;
});

add_action('admin_post_staark_fb_preview_mail', static function (): void {
    staark_hub_fb_guard('staark_fb_preview_mail', 'manage_options');

    $template = sanitize_key(staark_hub_forms_post_scalar('template'));
    $recipient = sanitize_email((string) wp_get_current_user()->user_email);
    $templates = staark_hub_fb_notify_settings()['templates'];
    $ok = false;

    if (isset($templates[$template]) && is_email($recipient)) {
        $values = [
            '{name}' => 'Anna Andersson',
            '{first_name}' => 'Anna',
            '{details}' => "Datum: " . staark_hub_fb_format_date(wp_date('Y-m-d', time() + WEEK_IN_SECONDS)) . "\nTid: 19:00\nAntal gäster: 4\nReferens: SFS-00042",
            '{date}' => staark_hub_fb_format_date(wp_date('Y-m-d', time() + WEEK_IN_SECONDS)),
            '{end_date}' => '',
            '{time}' => '19:00',
            '{guests}' => '4',
            '{item}' => '',
            '{reference}' => 'SFS-00042',
            '{message}' => 'Hej! Vi är fyra personer och en av oss är glutenintolerant.',
            '{note}' => 'Vi har reserverat ett bord vid fönstret åt er.',
            '{site}' => trim(wp_strip_all_tags((string) get_bloginfo('name'))),
            '{site_url}' => home_url('/'),
            '{phone}' => '',
        ];
        $result = staark_hub_forms_mail(
            $recipient,
            '[TEST] ' . staark_hub_fb_render_template($templates[$template]['subject'], $values),
            staark_hub_fb_render_template($templates[$template]['body'], $values),
            ['Content-Type: text/plain; charset=UTF-8']
        );
        $ok = $result['sent'];
    }

    wp_safe_redirect(staark_hub_fb_url('staark-hub-notifications', ['fb_notice' => $ok ? 'preview_sent' : 'preview_failed']));
    exit;
});

/**
 * Save part of the Forms settings without resetting the other keys.
 *
 * @param array<string,mixed> $subset
 */
function staark_hub_fb_save_forms_settings_subset(array $subset): void
{
    $current = staark_hub_forms_settings();
    // An empty password field keeps the stored password (see save function).
    $current['smtp_password'] = '';

    staark_hub_forms_save_settings(array_merge($current, $subset));
}

/* ------------------------------------------------------------------ */
/* One-time upgrade of earlier submissions                             */
/* ------------------------------------------------------------------ */

/**
 * Submissions stored before Forms & Booking have no type. Classify them by
 * form and read booking details from the message that the Staark theme
 * booking forms composed ("Datum: … (2026-10-02)", "Tid: 19:00" …).
 */
function staark_hub_fb_upgrade_submissions(): int
{
    $ids = get_posts(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => 500,
            'fields' => 'ids',
            'meta_query' => [['key' => '_staark_submission_kind', 'compare' => 'NOT EXISTS']],
            'suppress_filters' => true,
        ]
    );

    $today = wp_date('Y-m-d');

    foreach ($ids as $id) {
        $id = (int) $id;
        $form = staark_hub_fb_form((string) get_post_meta($id, '_staark_submission_form_id', true));

        if ($form['kind'] !== 'booking') {
            update_post_meta($id, '_staark_submission_kind', 'message');
            continue;
        }

        $text = (string) get_post_field('post_content', $id);
        preg_match_all('/\((\d{4}-\d{2}-\d{2})\)/', $text, $dates);
        $date = staark_hub_fb_valid_date((string) ($dates[1][0] ?? ''));
        $end = staark_hub_fb_valid_date((string) ($dates[1][1] ?? ''));
        $time = preg_match('/^Tid(?: på dagen)?:\s*(.+)$/mu', $text, $m) ? trim($m[1]) : '';
        $guests = preg_match('/^(?:Antal gäster|Gäster):\s*(\d+)/mu', $text, $m) ? (int) $m[1] : 0;
        $item = preg_match('/^(?:Rumstyp|Behandling):\s*(.+)$/mu', $text, $m) ? trim($m[1]) : '';
        $type = str_contains($text, 'Rumsbokning') ? 'room' : (str_contains($text, 'Bordsbokning') ? 'table' : 'appointment');

        update_post_meta($id, '_staark_submission_kind', 'booking');
        update_post_meta($id, '_staark_booking_type', $type);
        update_post_meta($id, '_staark_booking_date', $date);
        update_post_meta($id, '_staark_booking_end_date', $end !== '' && $end > $date ? $end : '');
        update_post_meta($id, '_staark_booking_time', mb_substr($time === '—' ? '' : $time, 0, 40));
        update_post_meta($id, '_staark_booking_guests', $guests);
        update_post_meta($id, '_staark_booking_item', mb_substr($item === '—' ? '' : $item, 0, 120));
        update_post_meta($id, '_staark_booking_status', $date !== '' && $date < $today ? 'completed' : 'pending');
        update_post_meta($id, '_staark_booking_sort', ($date !== '' ? $date : '9999-12-31') . ' ' . $time);
    }

    staark_hub_fb_flush_counts();

    return count($ids);
}

add_action('admin_init', static function (): void {
    if ((int) get_option('staark_hub_fb_schema', 0) >= 1 || ! current_user_can(staark_hub_fb_cap()) || wp_doing_ajax()) {
        return;
    }

    // Batches of 500 per admin page load; the schema flag is only set once
    // nothing is left, so large inboxes finish over a few page loads.
    if (staark_hub_fb_upgrade_submissions() < 500) {
        update_option('staark_hub_fb_schema', 1, true);
    }
});
