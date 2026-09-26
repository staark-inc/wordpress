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
const STAARK_HUB_FORM_MAX_PAYLOAD_BYTES = 32768;
const STAARK_HUB_FORM_RATE_WINDOW_SECONDS = 600;
const STAARK_HUB_FORM_RATE_WINDOW_MAX = 5;

/**
 * @return array{recipient_email:string,subject_prefix:string,success_message:string,privacy_url:string,notify:bool,mail_transport:string,smtp_host:string,smtp_port:int,smtp_encryption:string,smtp_auth:bool,smtp_username:string,smtp_password:string,smtp_from_email:string,smtp_from_name:string}
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
        'mail_transport' => 'wordpress',
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_auth' => true,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => sanitize_email((string) get_option('admin_email')),
        'smtp_from_name' => trim((string) get_bloginfo('name')),
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
    $settings['mail_transport'] = in_array((string) $settings['mail_transport'], ['wordpress', 'smtp'], true)
        ? (string) $settings['mail_transport']
        : 'wordpress';
    $settings['smtp_host'] = sanitize_text_field((string) $settings['smtp_host']);
    $settings['smtp_port'] = max(1, min(65535, absint($settings['smtp_port']) ?: 587));
    $settings['smtp_encryption'] = in_array((string) $settings['smtp_encryption'], ['none', 'tls', 'ssl'], true)
        ? (string) $settings['smtp_encryption']
        : 'tls';
    $settings['smtp_auth'] = ! empty($settings['smtp_auth']);
    $settings['smtp_username'] = sanitize_text_field((string) $settings['smtp_username']);
    $settings['smtp_password'] = (string) $settings['smtp_password'];
    $settings['smtp_from_email'] = sanitize_email((string) $settings['smtp_from_email']);
    if ($settings['smtp_from_email'] === '') {
        $settings['smtp_from_email'] = $defaults['smtp_from_email'];
    }
    $settings['smtp_from_name'] = sanitize_text_field((string) $settings['smtp_from_name']);
    if ($settings['smtp_from_name'] === '') {
        $settings['smtp_from_name'] = $defaults['smtp_from_name'];
    }

    return $settings;
}

/**
 * @param array<string,mixed> $settings
 */
function staark_hub_forms_save_settings(array $settings): void
{
    $current = staark_hub_forms_settings();
    $recipient = isset($settings['recipient_email'])
        ? sanitize_email((string) $settings['recipient_email'])
        : '';

    if ($recipient === '') {
        $recipient = sanitize_email((string) get_option('admin_email'));
    }

    $mail_transport = isset($settings['mail_transport'])
        ? sanitize_key((string) $settings['mail_transport'])
        : 'wordpress';
    if (! in_array($mail_transport, ['wordpress', 'smtp'], true)) {
        $mail_transport = 'wordpress';
    }

    $smtp_encryption = isset($settings['smtp_encryption'])
        ? sanitize_key((string) $settings['smtp_encryption'])
        : 'tls';
    if (! in_array($smtp_encryption, ['none', 'tls', 'ssl'], true)) {
        $smtp_encryption = 'tls';
    }

    $smtp_password = isset($settings['smtp_password'])
        ? (string) $settings['smtp_password']
        : '';
    $smtp_password = str_replace(["\r", "\n"], '', $smtp_password);
    if ($smtp_password === '') {
        $smtp_password = (string) $current['smtp_password'];
    }

    $smtp_port = isset($settings['smtp_port']) ? absint($settings['smtp_port']) : 587;
    $smtp_port = max(1, min(65535, $smtp_port ?: 587));

    $from_email = isset($settings['smtp_from_email'])
        ? sanitize_email((string) $settings['smtp_from_email'])
        : '';
    if ($from_email === '') {
        $from_email = sanitize_email((string) get_option('admin_email'));
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
            'mail_transport' => $mail_transport,
            'smtp_host' => isset($settings['smtp_host'])
                ? sanitize_text_field((string) $settings['smtp_host'])
                : '',
            'smtp_port' => $smtp_port,
            'smtp_encryption' => $smtp_encryption,
            'smtp_auth' => ! empty($settings['smtp_auth']),
            'smtp_username' => isset($settings['smtp_username'])
                ? sanitize_text_field((string) $settings['smtp_username'])
                : '',
            'smtp_password' => $smtp_password,
            'smtp_from_email' => $from_email,
            'smtp_from_name' => isset($settings['smtp_from_name'])
                ? sanitize_text_field((string) $settings['smtp_from_name'])
                : trim((string) get_bloginfo('name')),
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


/**
 * Read a scalar POST value without allowing nested arrays to reach WordPress
 * sanitizers. Malformed array payloads are treated as empty input.
 */
function staark_hub_forms_post_scalar(string $key): string
{
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Generic input reader; nonce verification is performed by the calling action.
    if (! isset($_POST[$key]) || ! is_scalar($_POST[$key])) {
        return '';
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Caller applies context-specific sanitization after nonce verification.
    return (string) wp_unslash($_POST[$key]);
}

function staark_hub_forms_request_method_is_post(): bool
{
    $request_method = isset($_SERVER['REQUEST_METHOD'])
        ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])))
        : '';

    return $request_method === 'POST';
}

/**
 * @return array{scheme:string,host:string,port:int}|null
 */
function staark_hub_forms_origin_parts(string $url): ?array
{
    $parts = wp_parse_url($url);
    if (! is_array($parts)) {
        return null;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($scheme === '' || $host === '') {
        return null;
    }

    $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);

    return [
        'scheme' => $scheme,
        'host' => $host,
        'port' => $port,
    ];
}

function staark_hub_forms_url_is_same_origin(string $url): bool
{
    $expected = staark_hub_forms_origin_parts(home_url('/'));
    $actual = staark_hub_forms_origin_parts($url);

    return $expected !== null && $actual !== null && $expected === $actual;
}

/**
 * HTML form POSTs are not protected by CORS. CSRF is therefore enforced with
 * a nonce plus Origin/Referer validation. Missing browser origin metadata is
 * tolerated because privacy software may remove it, but an explicitly foreign
 * Origin or Referer is rejected.
 */
function staark_hub_forms_request_is_same_origin(): bool
{
    $origin = isset($_SERVER['HTTP_ORIGIN']) && is_scalar($_SERVER['HTTP_ORIGIN'])
        ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN']))
        : '';

    if ($origin !== '') {
        if (strtolower($origin) === 'null') {
            return false;
        }

        return staark_hub_forms_url_is_same_origin($origin);
    }

    $referer = isset($_SERVER['HTTP_REFERER']) && is_scalar($_SERVER['HTTP_REFERER'])
        ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']))
        : '';

    if ($referer !== '') {
        return staark_hub_forms_url_is_same_origin($referer);
    }

    return true;
}

function staark_hub_forms_trusted_source_url(): string
{
    $referer = isset($_SERVER['HTTP_REFERER']) && is_scalar($_SERVER['HTTP_REFERER'])
        ? esc_url_raw((string) wp_unslash($_SERVER['HTTP_REFERER']))
        : '';

    if ($referer === '' || ! staark_hub_forms_url_is_same_origin($referer)) {
        return home_url('/');
    }

    return (string) remove_query_arg(['staark_form'], $referer);
}

function staark_hub_forms_text_length(string $value): int
{
    return function_exists('mb_strlen') ? (int) mb_strlen($value) : strlen($value);
}

function staark_hub_forms_form_signature(string $form_id, int $started_at): string
{
    return hash_hmac(
        'sha256',
        $form_id . '|' . $started_at,
        wp_salt('nonce')
    );
}

/**
 * @param array<string,string> $data
 */
function staark_hub_forms_submission_data_is_valid(array $data): bool
{
    if (
        $data['name'] === ''
        || $data['email'] === ''
        || ! is_email($data['email'])
        || $data['message'] === ''
    ) {
        return false;
    }

    return staark_hub_forms_text_length($data['name']) <= 120
        && staark_hub_forms_text_length($data['email']) <= 190
        && staark_hub_forms_text_length($data['phone']) <= 80
        && staark_hub_forms_text_length($data['company']) <= 160
        && staark_hub_forms_text_length($data['message']) <= 5000
        && staark_hub_forms_text_length($data['form_id']) <= 64;
}

/**
 * Guard every Forms admin action with POST + capability + nonce checks.
 */
function staark_hub_forms_admin_action_guard(string $nonce_action): void
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'staark-core'));
    }

    if (! staark_hub_forms_request_method_is_post()) {
        wp_die(
            esc_html__('This action only accepts POST requests.', 'staark-core'),
            esc_html__('Method not allowed', 'staark-core'),
            ['response' => 405]
        );
    }

    check_admin_referer($nonce_action);
}

/**
 * Security posture rendered by the Forms admin screen.
 *
 * @return array<string,string>
 */
function staark_hub_forms_security_summary(): array
{
    return [
        'csrf' => __('Per-form WordPress nonce + same-origin request validation', 'staark-core'),
        'cors' => __('Same-origin only — no cross-origin Forms API is exposed', 'staark-core'),
        'bot' => __('Honeypot + signed form timing token', 'staark-core'),
        'rate' => sprintf(
            /* translators: 1: max submissions, 2: minutes. */
            __('1/minute and max %1$d/%2$d minutes per visitor + form', 'staark-core'),
            STAARK_HUB_FORM_RATE_WINDOW_MAX,
            (int) (STAARK_HUB_FORM_RATE_WINDOW_SECONDS / 60)
        ),
        'payload' => __('32 KB request cap + server-side field length validation', 'staark-core'),
        'source' => __('Source URL is derived server-side from a same-origin Referer', 'staark-core'),
    ];
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
function staark_hub_forms_rate_key(string $form_id, string $bucket = 'cooldown'): string
{
    $ip = function_exists('staark_hub_client_ip')
        ? staark_hub_client_ip()
        : (isset($_SERVER['REMOTE_ADDR']) && is_scalar($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown');
    $form_id = sanitize_key($form_id);
    $bucket = sanitize_key($bucket);

    return 'staark_form_rate_' . substr(
        hash('sha256', wp_salt('nonce') . '|' . $ip . '|' . $form_id . '|' . $bucket),
        0,
        32
    );
}

function staark_hub_forms_rate_limit_exceeded(string $form_id): bool
{
    if (get_transient(staark_hub_forms_rate_key($form_id, 'cooldown'))) {
        return true;
    }

    $window_count = (int) get_transient(staark_hub_forms_rate_key($form_id, 'window'));

    return $window_count >= STAARK_HUB_FORM_RATE_WINDOW_MAX;
}

function staark_hub_forms_rate_limit_commit(string $form_id): void
{
    set_transient(
        staark_hub_forms_rate_key($form_id, 'cooldown'),
        '1',
        MINUTE_IN_SECONDS
    );

    $window_key = staark_hub_forms_rate_key($form_id, 'window');
    $window_count = (int) get_transient($window_key);
    set_transient(
        $window_key,
        (string) ($window_count + 1),
        STAARK_HUB_FORM_RATE_WINDOW_SECONDS
    );
}

function staark_hub_forms_should_enqueue_assets(): bool
{
    if (is_admin()) {
        return false;
    }

    $post = get_post();

    $in_post = $post instanceof WP_Post
        && has_shortcode((string) $post->post_content, STAARK_HUB_FORM_SHORTCODE);

    // A block theme can place the form inside a template pattern rather than
    // the post body. Let that theme request the existing form stylesheet.
    return (bool) apply_filters('staark_hub_forms_should_enqueue_assets', $in_post);
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
    $form_id = substr(sanitize_key((string) $atts['form_id']), 0, 64);
    if ($form_id === '') {
        $form_id = 'contact';
    }
    $started_at = time();
    $form_signature = staark_hub_forms_form_signature($form_id, $started_at);

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
            <?php wp_nonce_field('staark_submit_public_form:' . $form_id, 'staark_form_nonce'); ?>
            <input type="hidden" name="action" value="staark_submit_public_form">
            <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
            <input type="hidden" name="started_at" value="<?php echo esc_attr((string) $started_at); ?>">
            <input type="hidden" name="form_sig" value="<?php echo esc_attr($form_signature); ?>">

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

// Shortcode blocks in patterns and template parts can render before the usual
// post-content shortcode pass. Resolve the Hub shortcodes in either location.
add_filter('render_block_core/shortcode', static function (string $content): string {
    foreach ([STAARK_HUB_FORM_SHORTCODE, 'staark_brand', 'staark_brand_tagline', 'staark_brand_copyright', 'staark_theme_page_links'] as $shortcode) {
        if (has_shortcode($content, $shortcode)) {
            return do_shortcode(shortcode_unautop($content));
        }
    }

    return $content;
});

/**
 * @return array<string,string>
 */
function staark_hub_forms_submission_data(): array
{
    $form_id = substr(sanitize_key(staark_hub_forms_post_scalar('form_id')), 0, 64);
    if ($form_id === '') {
        $form_id = 'contact';
    }

    return [
        'name' => sanitize_text_field(staark_hub_forms_post_scalar('name')),
        'email' => sanitize_email(staark_hub_forms_post_scalar('email')),
        'phone' => sanitize_text_field(staark_hub_forms_post_scalar('phone')),
        'company' => sanitize_text_field(staark_hub_forms_post_scalar('company')),
        'message' => sanitize_textarea_field(staark_hub_forms_post_scalar('message')),
        'form_id' => $form_id,
        'source_url' => staark_hub_forms_trusted_source_url(),
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

function staark_hub_forms_effective_smtp_password(): string
{
    if (defined('STAARK_FORMS_SMTP_PASSWORD')) {
        return (string) STAARK_FORMS_SMTP_PASSWORD;
    }

    $settings = staark_hub_forms_settings();

    return (string) $settings['smtp_password'];
}

/**
 * Configure PHPMailer for Staark form email only.
 *
 * The hook is attached immediately before wp_mail() and removed immediately
 * afterwards, so SMTP settings here do not unexpectedly change password reset,
 * WooCommerce or other WordPress email flows.
 *
 * @param object $phpmailer PHPMailer instance created by WordPress.
 */
function staark_hub_forms_configure_phpmailer($phpmailer): void
{
    $settings = staark_hub_forms_settings();
    if ($settings['mail_transport'] !== 'smtp' || $settings['smtp_host'] === '') {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = $settings['smtp_host'];
    $phpmailer->Port = (int) $settings['smtp_port'];
    $phpmailer->SMTPAuth = (bool) $settings['smtp_auth'];
    $phpmailer->SMTPAutoTLS = $settings['smtp_encryption'] !== 'none';

    if ($settings['smtp_encryption'] === 'none') {
        $phpmailer->SMTPSecure = '';
    } else {
        $phpmailer->SMTPSecure = $settings['smtp_encryption'];
    }

    if ($settings['smtp_auth']) {
        $phpmailer->Username = $settings['smtp_username'];
        $phpmailer->Password = staark_hub_forms_effective_smtp_password();
    }

    if (is_email($settings['smtp_from_email'])) {
        try {
            $phpmailer->setFrom(
                $settings['smtp_from_email'],
                $settings['smtp_from_name'],
                false
            );
        } catch (Throwable $error) {
            // wp_mail_failed will surface transport failures to our diagnostics.
        }
    }
}

/**
 * Execute one wp_mail call with the Staark Forms transport scoped to that call.
 *
 * @param string|string[] $to
 * @param string|string[] $headers
 * @return array{sent:bool,error:string}
 */
function staark_hub_forms_mail(
    $to,
    string $subject,
    string $message,
    $headers = []
): array {
    $settings = staark_hub_forms_settings();

    if ($settings['mail_transport'] === 'smtp' && $settings['smtp_host'] === '') {
        return [
            'sent' => false,
            'error' => __('SMTP is selected but no SMTP host is configured.', 'staark-core'),
        ];
    }

    if (
        $settings['mail_transport'] === 'smtp'
        && $settings['smtp_auth']
        && $settings['smtp_username'] === ''
    ) {
        return [
            'sent' => false,
            'error' => __('SMTP authentication is enabled but the username is empty.', 'staark-core'),
        ];
    }

    if (
        $settings['mail_transport'] === 'smtp'
        && $settings['smtp_auth']
        && staark_hub_forms_effective_smtp_password() === ''
    ) {
        return [
            'sent' => false,
            'error' => __('SMTP authentication is enabled but the password is empty.', 'staark-core'),
        ];
    }

    $mail_error = '';
    $failure_listener = static function (WP_Error $error) use (&$mail_error): void {
        $mail_error = sanitize_text_field($error->get_error_message());

        $data = $error->get_error_data();
        if (is_array($data) && isset($data['phpmailer_exception_code'])) {
            $mail_error .= ' (code ' . absint($data['phpmailer_exception_code']) . ')';
        }
    };

    add_action('wp_mail_failed', $failure_listener);
    if ($settings['mail_transport'] === 'smtp') {
        add_action('phpmailer_init', 'staark_hub_forms_configure_phpmailer', 1000);
    }

    try {
        $sent = wp_mail($to, $subject, $message, $headers);
    } finally {
        remove_action('wp_mail_failed', $failure_listener);
        if ($settings['mail_transport'] === 'smtp') {
            remove_action('phpmailer_init', 'staark_hub_forms_configure_phpmailer', 1000);
        }
    }

    if (! $sent && $mail_error === '') {
        $mail_error = __('WordPress mail transport returned false without a detailed transport error.', 'staark-core');
    }

    return [
        'sent' => (bool) $sent,
        'error' => $mail_error,
    ];
}

/**
 * Send a transport test without creating a fake form submission.
 *
 * @return array{sent:bool,error:string}
 */
function staark_hub_forms_send_test_email(string $recipient): array
{
    $recipient = sanitize_email($recipient);
    if ($recipient === '') {
        return [
            'sent' => false,
            'error' => __('Enter a valid test recipient email address.', 'staark-core'),
        ];
    }

    $settings = staark_hub_forms_settings();
    $transport_label = $settings['mail_transport'] === 'smtp' ? 'SMTP' : 'WordPress default';
    $subject = sprintf(
        /* translators: %s: site name. */
        __('[%s] Staark mail transport test', 'staark-core'),
        trim((string) get_bloginfo('name'))
    );
    $message = implode(
        "\n",
        [
            __('Staark Forms mail transport test.', 'staark-core'),
            '',
            'Transport: ' . $transport_label,
            'Site: ' . home_url('/'),
            'Time: ' . current_time('mysql', true) . ' UTC',
        ]
    );

    return staark_hub_forms_mail(
        $recipient,
        $subject,
        $message,
        ['Content-Type: text/plain; charset=UTF-8']
    );
}

/**
 * Send or retry the notification email for a stored submission.
 *
 * Delivery diagnostics are persisted so a failed mail transport never hides
 * the actual form submission from the site owner.
 */
function staark_hub_forms_send_notification(int $submission_id): bool
{
    $submission = get_post($submission_id);
    if (! $submission instanceof WP_Post || $submission->post_type !== 'staark_submission') {
        return false;
    }

    $settings = staark_hub_forms_settings();
    $form_id_for_mail = (string) get_post_meta($submission_id, '_staark_submission_form_id', true);
    $recipients = function_exists('staark_hub_fb_recipients')
        ? staark_hub_fb_recipients($form_id_for_mail)
        : ($settings['recipient_email'] !== '' ? [$settings['recipient_email']] : []);

    if (! $settings['notify'] || $recipients === []) {
        update_post_meta($submission_id, '_staark_submission_mail_state', 'disabled');
        update_post_meta($submission_id, '_staark_submission_mail_error', '');
        return false;
    }

    $name = (string) get_post_meta($submission_id, '_staark_submission_name', true);
    $email = (string) get_post_meta($submission_id, '_staark_submission_email', true);
    $phone = (string) get_post_meta($submission_id, '_staark_submission_phone', true);
    $company = (string) get_post_meta($submission_id, '_staark_submission_company', true);
    $form_id = (string) get_post_meta($submission_id, '_staark_submission_form_id', true);
    $source_url = (string) get_post_meta($submission_id, '_staark_submission_source_url', true);

    $is_booking = function_exists('staark_hub_fb_is_booking') && staark_hub_fb_is_booking($submission_id);
    $form_label = function_exists('staark_hub_fb_form') ? staark_hub_fb_form($form_id)['label'] : $form_id;
    $booking_lines = $is_booking ? staark_hub_fb_booking_lines($submission_id) : [];

    $subject_text = $is_booking ? __('Ny bokningsförfrågan', 'staark-core') : __('Ny kontaktförfrågan', 'staark-core');
    if ($is_booking && $booking_lines !== []) {
        $subject_text .= ' – ' . $name . ', ' . $booking_lines[0][1];
    } elseif ($name !== '') {
        $subject_text .= ' – ' . $name;
    }
    $subject = trim($settings['subject_prefix'] . ' ' . $subject_text);

    $lines = [
        'Submission: ' . staark_hub_form_submission_label($submission_id),
        'Form: ' . $form_label . ($form_label !== $form_id ? ' (' . $form_id . ')' : ''),
        'Name: ' . $name,
        'Email: ' . $email,
        'Phone: ' . $phone,
    ];
    if ($company !== '') {
        $lines[] = 'Company: ' . $company;
    }
    $lines[] = 'Source: ' . $source_url;

    if ($booking_lines !== []) {
        $lines[] = '';
        foreach ($booking_lines as $booking_line) {
            $lines[] = $booking_line[0] . ': ' . $booking_line[1];
        }
    }

    $lines[] = '';
    $lines[] = (string) $submission->post_content;
    $lines[] = '';
    $lines[] = ($is_booking ? 'Confirm or decline: ' : 'Open in Inbox: ')
        . admin_url('admin.php?page=staark-hub-inbox&submission=' . $submission_id);

    $body = implode("\n", $lines);

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if (is_email($email)) {
        // The name is visitor input: strip address separators and quotes so it
        // cannot add extra Reply-To recipients.
        $reply_name = trim(preg_replace('/[,;:<>"\x5c\r\n]+/', ' ', $name) ?? '');
        $headers[] = 'Reply-To: ' . ($reply_name !== '' ? $reply_name . ' ' : '') . '<' . $email . '>';
    }

    $mail_result = staark_hub_forms_mail(
        $recipients,
        $subject,
        $body,
        $headers
    );
    $sent = $mail_result['sent'];
    $mail_error = $mail_result['error'];

    $attempts = (int) get_post_meta($submission_id, '_staark_submission_mail_attempts', true);
    update_post_meta($submission_id, '_staark_submission_mail_attempts', $attempts + 1);
    update_post_meta($submission_id, '_staark_submission_mail_last_attempt', current_time('mysql', true));
    update_post_meta($submission_id, '_staark_submission_mail_recipient', implode(', ', $recipients));
    update_post_meta($submission_id, '_staark_submission_mail_transport', $settings['mail_transport']);
    update_post_meta($submission_id, '_staark_submission_mail_state', $sent ? 'sent' : 'failed');

    if ($sent) {
        delete_post_meta($submission_id, '_staark_submission_mail_error');
    } else {
        if ($mail_error === '') {
            $mail_error = __('WordPress mail transport returned false without a detailed transport error.', 'staark-core');
        }
        update_post_meta($submission_id, '_staark_submission_mail_error', $mail_error);
    }

    return $sent;
}

function staark_hub_forms_handle_public_submission(): void
{
    if (! staark_hub_forms_request_method_is_post()) {
        staark_hub_forms_redirect('error');
    }

    $content_length = isset($_SERVER['CONTENT_LENGTH']) && is_scalar($_SERVER['CONTENT_LENGTH'])
        ? absint($_SERVER['CONTENT_LENGTH'])
        : 0;
    if ($content_length > STAARK_HUB_FORM_MAX_PAYLOAD_BYTES) {
        staark_hub_forms_redirect('error');
    }

    if (! staark_hub_forms_request_is_same_origin()) {
        staark_hub_forms_redirect('error');
    }

    $form_id = substr(sanitize_key(staark_hub_forms_post_scalar('form_id')), 0, 64);
    if ($form_id === '') {
        $form_id = 'contact';
    }

    $nonce = sanitize_text_field(staark_hub_forms_post_scalar('staark_form_nonce'));
    if (! wp_verify_nonce($nonce, 'staark_submit_public_form:' . $form_id)) {
        staark_hub_forms_redirect('error');
    }

    $honeypot = trim(staark_hub_forms_post_scalar('website'));
    if ($honeypot !== '') {
        staark_hub_forms_redirect('success');
    }

    $started = absint(staark_hub_forms_post_scalar('started_at'));
    $signature = sanitize_text_field(staark_hub_forms_post_scalar('form_sig'));
    $expected_signature = staark_hub_forms_form_signature($form_id, $started);
    $elapsed = $started > 0 ? time() - $started : 0;

    if (
        $started === 0
        || $signature === ''
        || ! hash_equals($expected_signature, $signature)
        || $elapsed < 2
        || $elapsed > 43200
    ) {
        staark_hub_forms_redirect('error');
    }

    $data = staark_hub_forms_submission_data();
    $consent = staark_hub_forms_post_scalar('consent') === '1';

    if (! staark_hub_forms_submission_data_is_valid($data) || ! $consent) {
        staark_hub_forms_redirect('error');
    }

    if (staark_hub_forms_rate_limit_exceeded($form_id)) {
        staark_hub_forms_redirect('error');
    }
    staark_hub_forms_rate_limit_commit($form_id);

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

    /**
     * Fires after a submission and its core fields are stored, before any
     * email is sent. Forms & Booking stores the type and booking details here.
     */
    do_action('staark_hub_form_stored', $submission_id, $data);

    $mail_sent = staark_hub_forms_send_notification($submission_id);

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

add_action('admin_post_staark_submission_retry_mail', static function (): void {
    $submission_id = absint(staark_hub_forms_post_scalar('submission_id'));
    staark_hub_forms_admin_action_guard('staark_submission_retry_mail_' . $submission_id);

    $state = 'failed';
    if ($submission_id > 0 && get_post_type($submission_id) === 'staark_submission') {
        $state = staark_hub_forms_send_notification($submission_id) ? 'sent' : 'failed';
    }

    wp_safe_redirect(
        add_query_arg(
            [
                'page' => 'staark-hub-inbox',
                'submission' => $submission_id,
                'fb_notice' => 'mail_retry',
                'fb_mail' => $state,
            ],
            admin_url('admin.php')
        )
    );
    exit;
});

/**
 * @return array{total:int,new:int,read:int,replied:int,spam:int}
 */
function staark_hub_forms_summary(): array
{
    global $wpdb;

    $summary = [
        'total' => 0,
        'new' => 0,
        'read' => 0,
        'replied' => 0,
        'spam' => 0,
    ];

    // One grouped query instead of one meta lookup per submission.
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT COALESCE(pm.meta_value, '') AS status, COUNT(*) AS total
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
             WHERE p.post_type = %s AND p.post_status IN ('private', 'publish')
             GROUP BY pm.meta_value",
            '_staark_submission_status',
            'staark_submission'
        ),
        ARRAY_A
    );

    foreach ((array) $rows as $row) {
        $count = (int) ($row['total'] ?? 0);
        $summary['total'] += $count;
        $status = (string) ($row['status'] ?? '');
        if ($status !== 'total' && isset($summary[$status])) {
            $summary[$status] += $count;
        }
    }

    return $summary;
}
