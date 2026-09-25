<?php
/**
 * Staark Forms & Submissions.
 *
 * Conservative native contact form handling with local persistence, email
 * delivery, spam guardrails and an extension hook for future Hub sync.
 */

if (! defined('ABSPATH')) {
    exit;
}

const STAARK_HUB_FORMS_OPTION = 'staark_hub_forms_settings';
const STAARK_HUB_FORM_SHORTCODE = 'staark_contact_form';

/**
 * @return array{recipient_email:string,subject_prefix:string,success_message:string,privacy_url:string,notify:bool}
 */
function staark_hub_forms_settings(): array
{
    $saved = get_option(STAARK_HUB_FORMS_OPTION, []);
    if (! is_array($saved)) {
        $saved = [];
    }

    $defaults = [
        'recipient_email' => sanitize_email((string) get_option('admin_email')),
        'subject_prefix' => '[' . trim((string) get_bloginfo('name')) . ']',
        'success_message' => __('Tack! Ditt meddelande har skickats.', 'staark-core'),
        'privacy_url' => home_url('/integritetspolicy/'),
        'notify' => true,
    ];

    $settings = array_merge($defaults, $saved);
    $settings['recipient_email'] = sanitize_email((string) $settings['recipient_email']);
    if ($settings['recipient_email'] === '') {
        $settings['recipient_email'] = $defaults['recipient_email'];
    }
    $settings['subject_prefix'] = sanitize_text_field((string) $settings['subject_prefix']);
    $settings['success_message'] = sanitize_text_field((string) $settings['success_message']);
    $settings['privacy_url'] = esc_url_raw((string) $settings['privacy_url']);
    $settings['notify'] = ! empty($settings['notify']);

    return $settings;
}

/**
 * @param array<string,mixed> $settings
 */
function staark_hub_forms_save_settings(array $settings): void
{
    $recipient = isset($settings['recipient_email'])
        ? sanitize_email((string) $settings['recipient_email'])
        : '';

    if ($recipient === '') {
        $recipient = sanitize_email((string) get_option('admin_email'));
    }

    update_option(
        STAARK_HUB_FORMS_OPTION,
        [
            'recipient_email' => $recipient,
            'subject_prefix' => isset($settings['subject_prefix'])
                ? sanitize_text_field((string) $settings['subject_prefix'])
                : '',
            'success_message' => isset($settings['success_message'])
                ? sanitize_text_field((string) $settings['success_message'])
                : '',
            'privacy_url' => isset($settings['privacy_url'])
                ? esc_url_raw((string) $settings['privacy_url'])
                : '',
            'notify' => ! empty($settings['notify']),
        ],
        false
    );
}

/**
 * @return array<string,string>
 */
function staark_hub_form_statuses(): array
{
    return [
        'new' => __('New', 'staark-core'),
        'read' => __('Read', 'staark-core'),
        'replied' => __('Replied', 'staark-core'),
        'spam' => __('Spam', 'staark-core'),
    ];
}

function staark_hub_form_submission_label(int $submission_id): string
{
    return 'SFS-' . str_pad((string) $submission_id, 5, '0', STR_PAD_LEFT);
}

add_action('init', static function (): void {
    register_post_type(
        'staark_submission',
        [
            'labels' => [
                'name' => __('Staark Form Submissions', 'staark-core'),
                'singular_name' => __('Staark Form Submission', 'staark-core'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_rest' => false,
            'supports' => ['title', 'editor'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]
    );
});

/**
 * Hash-only rate-limit key. The raw visitor IP is never stored with a submission.
 */
function staark_hub_forms_rate_key(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

    return 'staark_form_rate_' . substr(hash('sha256', wp_salt('nonce') . '|' . $ip), 0, 32);
}

function staark_hub_forms_should_enqueue_assets(): bool
{
    if (is_admin()) {
        return false;
    }

    $post = get_post();

    return $post instanceof WP_Post
        && has_shortcode((string) $post->post_content, STAARK_HUB_FORM_SHORTCODE);
}

add_action('wp_enqueue_scripts', static function (): void {
    if (! staark_hub_forms_should_enqueue_assets()) {
        return;
    }

    wp_enqueue_style(
        'staark-hub-forms',
        staark_hub_runtime_url('assets/forms.css'),
        [],
        is_file(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
            ? (string) filemtime(STAARK_HUB_PLUGIN_DIR . 'assets/forms.css')
            : STAARK_HUB_VERSION
    );
});

/**
 * @param array<string,mixed> $atts
 */
function staark_hub_forms_shortcode(array $atts = []): string
{
    $atts = shortcode_atts(
        [
            'title' => __('Kontakta oss', 'staark-core'),
            'button' => __('Skicka meddelande', 'staark-core'),
            'form_id' => 'contact',
        ],
        $atts,
        STAARK_HUB_FORM_SHORTCODE
    );

    $settings = staark_hub_forms_settings();
    $state = isset($_GET['staark_form']) ? sanitize_key(wp_unslash($_GET['staark_form'])) : '';

    ob_start();
    ?>
    <div class="staark-form-shell">
        <?php if ($state === 'success') : ?>
            <div class="staark-form-notice staark-form-notice--success" role="status">
                <?php echo esc_html($settings['success_message']); ?>
            </div>
        <?php elseif ($state === 'error') : ?>
            <div class="staark-form-notice staark-form-notice--error" role="alert">
                <?php esc_html_e('Något gick fel. Kontrollera fälten och försök igen.', 'staark-core'); ?>
            </div>
        <?php endif; ?>

        <form class="staark-contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <?php wp_nonce_field('staark_submit_public_form', 'staark_form_nonce'); ?>
            <input type="hidden" name="action" value="staark_submit_public_form">
            <input type="hidden" name="form_id" value="<?php echo esc_attr(sanitize_key((string) $atts['form_id'])); ?>">
            <input type="hidden" name="started_at" value="<?php echo esc_attr((string) time()); ?>">
            <input type="hidden" name="source_url" value="<?php echo esc_url(home_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'))); ?>">

            <div class="staark-form-honeypot" aria-hidden="true">
                <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="staark-form-grid">
                <p class="staark-form-field">
                    <label for="staark-form-name"><?php esc_html_e('Namn', 'staark-core'); ?> *</label>
                    <input id="staark-form-name" type="text" name="name" autocomplete="name" required maxlength="120">
                </p>

                <p class="staark-form-field">
                    <label for="staark-form-email"><?php esc_html_e('E-post', 'staark-core'); ?> *</label>
                    <input id="staark-form-email" type="email" name="email" autocomplete="email" required maxlength="190">
                </p>

                <p class="staark-form-field">
                    <label for="staark-form-phone"><?php esc_html_e('Telefon', 'staark-core'); ?></label>
                    <input id="staark-form-phone" type="tel" name="phone" autocomplete="tel" maxlength="80">
                </p>

                <p class="staark-form-field">
                    <label for="staark-form-company"><?php esc_html_e('Företag', 'staark-core'); ?></label>
                    <input id="staark-form-company" type="text" name="company" autocomplete="organization" maxlength="160">
                </p>
            </div>

            <p class="staark-form-field">
                <label for="staark-form-message"><?php esc_html_e('Meddelande', 'staark-core'); ?> *</label>
                <textarea id="staark-form-message" name="message" rows="7" required maxlength="5000"></textarea>
            </p>

            <p class="staark-form-consent">
                <label>
                    <input type="checkbox" name="consent" value="1" required>
                    <span>
                        <?php esc_html_e('Jag godkänner att mina uppgifter används för att besvara min förfrågan.', 'staark-core'); ?>
                        <?php if ($settings['privacy_url'] !== '') : ?>
                            <a href="<?php echo esc_url($settings['privacy_url']); ?>"><?php esc_html_e('Integritetspolicy', 'staark-core'); ?></a>
                        <?php endif; ?>
                    </span>
                </label>
            </p>

            <p class="staark-form-actions">
                <button class="wp-element-button" type="submit"><?php echo esc_html((string) $atts['button']); ?></button>
            </p>
        </form>
    </div>
    <?php

    return (string) ob_get_clean();
}

add_shortcode(STAARK_HUB_FORM_SHORTCODE, 'staark_hub_forms_shortcode');

/**
 * @return array<string,string>
 */
function staark_hub_forms_submission_data(): array
{
    return [
        'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
        'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
        'phone' => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
        'company' => isset($_POST['company']) ? sanitize_text_field(wp_unslash($_POST['company'])) : '',
        'message' => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
        'form_id' => isset($_POST['form_id']) ? sanitize_key(wp_unslash($_POST['form_id'])) : 'contact',
        'source_url' => isset($_POST['source_url']) ? esc_url_raw(wp_unslash($_POST['source_url'])) : home_url('/'),
    ];
}

function staark_hub_forms_redirect(string $state): void
{
    $referer = wp_get_referer();
    if (! is_string($referer) || $referer === '') {
        $referer = home_url('/');
    }

    $referer = remove_query_arg(['staark_form'], $referer);
    wp_safe_redirect(add_query_arg('staark_form', $state, $referer));
    exit;
}

function staark_hub_forms_handle_public_submission(): void
{
    $nonce = isset($_POST['staark_form_nonce']) ? sanitize_text_field(wp_unslash($_POST['staark_form_nonce'])) : '';
    if (! wp_verify_nonce($nonce, 'staark_submit_public_form')) {
        staark_hub_forms_redirect('error');
    }

    $honeypot = isset($_POST['website']) ? trim((string) wp_unslash($_POST['website'])) : '';
    if ($honeypot !== '') {
        staark_hub_forms_redirect('success');
    }

    $started = isset($_POST['started_at']) ? absint($_POST['started_at']) : 0;
    $elapsed = $started > 0 ? time() - $started : 0;
    if ($started === 0 || $elapsed < 2 || $elapsed > 7200) {
        staark_hub_forms_redirect('error');
    }

    $rate_key = staark_hub_forms_rate_key();
    if (get_transient($rate_key)) {
        staark_hub_forms_redirect('error');
    }
    set_transient($rate_key, '1', MINUTE_IN_SECONDS);

    $data = staark_hub_forms_submission_data();
    $consent = ! empty($_POST['consent']);

    if (
        $data['name'] === ''
        || $data['email'] === ''
        || ! is_email($data['email'])
        || $data['message'] === ''
        || ! $consent
    ) {
        staark_hub_forms_redirect('error');
    }

    $submission_id = wp_insert_post(
        [
            'post_type' => 'staark_submission',
            'post_status' => 'private',
            'post_title' => wp_strip_all_tags($data['name'] . ' — ' . $data['email']),
            'post_content' => $data['message'],
        ],
        true
    );

    if (is_wp_error($submission_id)) {
        staark_hub_forms_redirect('error');
    }

    $submission_id = (int) $submission_id;
    update_post_meta($submission_id, '_staark_submission_status', 'new');
    update_post_meta($submission_id, '_staark_submission_name', $data['name']);
    update_post_meta($submission_id, '_staark_submission_email', $data['email']);
    update_post_meta($submission_id, '_staark_submission_phone', $data['phone']);
    update_post_meta($submission_id, '_staark_submission_company', $data['company']);
    update_post_meta($submission_id, '_staark_submission_form_id', $data['form_id']);
    update_post_meta($submission_id, '_staark_submission_source_url', $data['source_url']);
    update_post_meta($submission_id, '_staark_submission_consent_at', current_time('mysql', true));
    update_post_meta($submission_id, '_staark_submission_mail_state', 'not_sent');
    update_post_meta($submission_id, '_staark_submission_sync_state', 'pending');

    $settings = staark_hub_forms_settings();
    $mail_sent = false;

    if ($settings['notify'] && $settings['recipient_email'] !== '') {
        $subject = trim($settings['subject_prefix'] . ' ' . __('Ny kontaktförfrågan', 'staark-core'));
        $body = implode(
            "\n",
            [
                'Submission: ' . staark_hub_form_submission_label($submission_id),
                'Name: ' . $data['name'],
                'Email: ' . $data['email'],
                'Phone: ' . $data['phone'],
                'Company: ' . $data['company'],
                'Form: ' . $data['form_id'],
                'Source: ' . $data['source_url'],
                '',
                $data['message'],
            ]
        );

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        if (is_email($data['email'])) {
            $headers[] = 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>';
        }

        $mail_sent = wp_mail($settings['recipient_email'], $subject, $body, $headers);
        update_post_meta($submission_id, '_staark_submission_mail_state', $mail_sent ? 'sent' : 'failed');
    }

    /**
     * Fires after a valid submission has been stored locally.
     *
     * Future Hub sync can subscribe to this without changing the public form.
     */
    do_action('staark_hub_form_submitted', $submission_id, $data, $mail_sent);

    staark_hub_forms_redirect('success');
}

add_action('admin_post_nopriv_staark_submit_public_form', 'staark_hub_forms_handle_public_submission');
add_action('admin_post_staark_submit_public_form', 'staark_hub_forms_handle_public_submission');

add_action('admin_post_staark_save_forms_settings', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    check_admin_referer('staark_save_forms_settings');
    staark_hub_forms_save_settings(wp_unslash($_POST));

    wp_safe_redirect(admin_url('admin.php?page=staark-hub-forms&staark_forms=saved'));
    exit;
});

add_action('admin_post_staark_submission_status', static function (): void {
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    $submission_id = isset($_POST['submission_id']) ? absint($_POST['submission_id']) : 0;
    check_admin_referer('staark_submission_status_' . $submission_id);

    $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
    $statuses = staark_hub_form_statuses();

    if ($submission_id > 0 && get_post_type($submission_id) === 'staark_submission' && isset($statuses[$status])) {
        update_post_meta($submission_id, '_staark_submission_status', $status);
    }

    wp_safe_redirect(
        admin_url('admin.php?page=staark-hub-forms&submission=' . $submission_id)
    );
    exit;
});

/**
 * @return array{total:int,new:int,read:int,replied:int,spam:int}
 */
function staark_hub_forms_summary(): array
{
    $summary = [
        'total' => 0,
        'new' => 0,
        'read' => 0,
        'replied' => 0,
        'spam' => 0,
    ];

    $ids = get_posts(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
        ]
    );

    foreach ($ids as $id) {
        ++$summary['total'];
        $status = (string) get_post_meta((int) $id, '_staark_submission_status', true);
        if (isset($summary[$status])) {
            ++$summary[$status];
        }
    }

    return $summary;
}
