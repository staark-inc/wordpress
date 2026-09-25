<?php
/**
 * Staark Forms admin page.
 */

if (! defined('ABSPATH')) {
    exit;
}

function staark_hub_render_forms(): void
{
    if (! current_user_can('manage_options')) {
        return;
    }

    $settings = staark_hub_forms_settings();
    $summary = staark_hub_forms_summary();
    $security = staark_hub_forms_security_summary();
    $selected_id = isset($_GET['submission']) ? absint($_GET['submission']) : 0;
    $selected = $selected_id > 0 ? get_post($selected_id) : null;

    $mail_test = null;
    if (isset($_GET['staark_forms']) && sanitize_key(wp_unslash($_GET['staark_forms'])) === 'mail_test') {
        $mail_test_key = 'staark_forms_mail_test_' . get_current_user_id();
        $mail_test = get_transient($mail_test_key);
        delete_transient($mail_test_key);
        if (! is_array($mail_test)) {
            $mail_test = null;
        }
    }

    if ($selected instanceof WP_Post && $selected->post_type === 'staark_submission') {
        $status = (string) get_post_meta($selected_id, '_staark_submission_status', true);
        if ($status === 'new') {
            update_post_meta($selected_id, '_staark_submission_status', 'read');
            $status = 'read';
        }
        $mail_state = (string) get_post_meta($selected_id, '_staark_submission_mail_state', true);
        $mail_error = (string) get_post_meta($selected_id, '_staark_submission_mail_error', true);
        $mail_recipient = (string) get_post_meta($selected_id, '_staark_submission_mail_recipient', true);
        $mail_transport = (string) get_post_meta($selected_id, '_staark_submission_mail_transport', true);
        $mail_attempts = (int) get_post_meta($selected_id, '_staark_submission_mail_attempts', true);
        $mail_last_attempt = (string) get_post_meta($selected_id, '_staark_submission_mail_last_attempt', true);
        $form_id = (string) get_post_meta($selected_id, '_staark_submission_form_id', true);
        $consent_at = (string) get_post_meta($selected_id, '_staark_submission_consent_at', true);
        ?>
        <div class="wrap staark-hub-wrap">
            <?php staark_hub_header('Forms', staark_hub_form_submission_label($selected_id)); ?>
            <p class="staark-hub-backlink"><a href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-forms')); ?>">← <?php esc_html_e('Back to submissions', 'staark-core'); ?></a></p>

            <?php if (isset($_GET['mail_retry'])) :
                $retry_state = sanitize_key(wp_unslash($_GET['mail_retry']));
                ?>
                <div class="notice <?php echo $retry_state === 'sent' ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                    <p><?php echo esc_html($retry_state === 'sent' ? __('Email notification sent.', 'staark-core') : __('Email notification failed. See delivery diagnostics below.', 'staark-core')); ?></p>
                </div>
            <?php endif; ?>

            <div class="staark-forms-detail">
                <div class="staark-forms-detail__main">
                    <h2><?php echo esc_html((string) get_post_meta($selected_id, '_staark_submission_name', true)); ?></h2>
                    <p class="staark-forms-message"><?php echo nl2br(esc_html((string) $selected->post_content)); ?></p>
                </div>

                <aside class="staark-forms-detail__side">
                    <dl>
                        <dt><?php esc_html_e('Email', 'staark-core'); ?></dt>
                        <dd><a href="mailto:<?php echo esc_attr((string) get_post_meta($selected_id, '_staark_submission_email', true)); ?>"><?php echo esc_html((string) get_post_meta($selected_id, '_staark_submission_email', true)); ?></a></dd>
                        <dt><?php esc_html_e('Phone', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html((string) get_post_meta($selected_id, '_staark_submission_phone', true)); ?></dd>
                        <dt><?php esc_html_e('Company', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html((string) get_post_meta($selected_id, '_staark_submission_company', true)); ?></dd>
                        <dt><?php esc_html_e('Source', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html((string) get_post_meta($selected_id, '_staark_submission_source_url', true)); ?></dd>
                        <dt><?php esc_html_e('Form ID', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($form_id !== '' ? $form_id : '—'); ?></dd>
                        <dt><?php esc_html_e('Consent', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($consent_at !== '' ? __('Yes', 'staark-core') . ' · ' . $consent_at . ' UTC' : __('No record', 'staark-core')); ?></dd>
                        <dt><?php esc_html_e('Email delivery', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($mail_state !== '' ? $mail_state : 'not_sent'); ?></dd>
                        <dt><?php esc_html_e('Email recipient', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($mail_recipient !== '' ? $mail_recipient : '—'); ?></dd>
                        <dt><?php esc_html_e('Mail transport', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($mail_transport !== '' ? $mail_transport : 'wordpress'); ?></dd>
                        <dt><?php esc_html_e('Email attempts', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html((string) $mail_attempts); ?></dd>
                        <dt><?php esc_html_e('Last email attempt', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html($mail_last_attempt !== '' ? $mail_last_attempt . ' UTC' : '—'); ?></dd>
                        <?php if ($mail_error !== '') : ?>
                            <dt><?php esc_html_e('Mail error', 'staark-core'); ?></dt>
                            <dd><code><?php echo esc_html($mail_error); ?></code></dd>
                        <?php endif; ?>
                        <dt><?php esc_html_e('Received', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html(get_post_time('Y-m-d H:i', false, $selected)); ?></dd>
                    </dl>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('staark_submission_status_' . $selected_id); ?>
                        <input type="hidden" name="action" value="staark_submission_status">
                        <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $selected_id); ?>">
                        <label for="staark-submission-status"><strong><?php esc_html_e('Status', 'staark-core'); ?></strong></label>
                        <select id="staark-submission-status" name="status">
                            <?php foreach (staark_hub_form_statuses() as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php submit_button(__('Update status', 'staark-core'), 'secondary', 'submit', false); ?>
                    </form>

                    <?php if ($settings['notify']) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="staark-forms-retry-mail">
                            <?php wp_nonce_field('staark_submission_retry_mail_' . $selected_id); ?>
                            <input type="hidden" name="action" value="staark_submission_retry_mail">
                            <input type="hidden" name="submission_id" value="<?php echo esc_attr((string) $selected_id); ?>">
                            <?php submit_button(__('Retry email notification', 'staark-core'), 'secondary', 'submit', false); ?>
                        </form>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
        <?php
        return;
    }

    $submissions = get_posts(
        [
            'post_type' => 'staark_submission',
            'post_status' => ['private', 'publish'],
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => true,
        ]
    );
    ?>
    <div class="wrap staark-hub-wrap">
        <?php staark_hub_header('Forms', __('Forms & Submissions', 'staark-core')); ?>

        <?php if (isset($_GET['staark_forms']) && sanitize_key(wp_unslash($_GET['staark_forms'])) === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Form settings saved.', 'staark-core'); ?></p></div>
        <?php endif; ?>

        <?php if (is_array($mail_test)) : ?>
            <div class="notice <?php echo ! empty($mail_test['sent']) ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                <p>
                    <?php if (! empty($mail_test['sent'])) : ?>
                        <?php echo esc_html(sprintf(__('Test email sent to %s.', 'staark-core'), (string) $mail_test['recipient'])); ?>
                    <?php else : ?>
                        <?php echo esc_html(__('Test email failed.', 'staark-core')); ?>
                        <?php if (! empty($mail_test['error'])) : ?>
                            <code><?php echo esc_html((string) $mail_test['error']); ?></code>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="staark-forms-stats">
            <div><strong><?php echo esc_html((string) $summary['total']); ?></strong><span><?php esc_html_e('Total', 'staark-core'); ?></span></div>
            <div><strong><?php echo esc_html((string) $summary['new']); ?></strong><span><?php esc_html_e('New', 'staark-core'); ?></span></div>
            <div><strong><?php echo esc_html((string) $summary['replied']); ?></strong><span><?php esc_html_e('Replied', 'staark-core'); ?></span></div>
            <div><strong><?php echo esc_html((string) $summary['spam']); ?></strong><span><?php esc_html_e('Spam', 'staark-core'); ?></span></div>
        </div>

        <div class="staark-forms-layout">
            <section class="staark-forms-panel">
                <h2><?php esc_html_e('Submissions', 'staark-core'); ?></h2>
                <?php if ($submissions === []) : ?>
                    <p><?php esc_html_e('No submissions yet.', 'staark-core'); ?></p>
                <?php else : ?>
                    <div class="staark-forms-table-wrap">
                        <table class="widefat striped staark-forms-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Status', 'staark-core'); ?></th>
                                    <th><?php esc_html_e('Contact', 'staark-core'); ?></th>
                                    <th><?php esc_html_e('Company', 'staark-core'); ?></th>
                                    <th><?php esc_html_e('Source', 'staark-core'); ?></th>
                                    <th><?php esc_html_e('Received', 'staark-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $submission) :
                                    $id = (int) $submission->ID;
                                    $status = (string) get_post_meta($id, '_staark_submission_status', true);
                                    $name = (string) get_post_meta($id, '_staark_submission_name', true);
                                    $email = (string) get_post_meta($id, '_staark_submission_email', true);
                                    $company = (string) get_post_meta($id, '_staark_submission_company', true);
                                    $source = (string) get_post_meta($id, '_staark_submission_source_url', true);
                                    ?>
                                    <tr>
                                        <td><span class="staark-form-status staark-form-status--<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></span></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-forms&submission=' . $id)); ?>"><strong><?php echo esc_html($name); ?></strong></a><br>
                                            <span><?php echo esc_html($email); ?></span>
                                        </td>
                                        <td><?php echo esc_html($company); ?></td>
                                        <td class="staark-forms-source"><?php echo esc_html($source); ?></td>
                                        <td><?php echo esc_html(get_post_time('Y-m-d H:i', false, $submission)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="staark-forms-panel">
                <h2><?php esc_html_e('Form settings', 'staark-core'); ?></h2>
                <p class="description"><code>[staark_contact_form]</code></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('staark_save_forms_settings'); ?>
                    <input type="hidden" name="action" value="staark_save_forms_settings">

                    <p>
                        <label for="staark-recipient"><strong><?php esc_html_e('Recipient email', 'staark-core'); ?></strong></label><br>
                        <input id="staark-recipient" class="regular-text" type="email" name="recipient_email" value="<?php echo esc_attr($settings['recipient_email']); ?>">
                    </p>
                    <p>
                        <label for="staark-subject-prefix"><strong><?php esc_html_e('Email subject prefix', 'staark-core'); ?></strong></label><br>
                        <input id="staark-subject-prefix" class="regular-text" type="text" name="subject_prefix" value="<?php echo esc_attr($settings['subject_prefix']); ?>">
                    </p>
                    <p>
                        <label for="staark-success-message"><strong><?php esc_html_e('Success message', 'staark-core'); ?></strong></label><br>
                        <input id="staark-success-message" class="large-text" type="text" name="success_message" value="<?php echo esc_attr($settings['success_message']); ?>">
                    </p>
                    <p>
                        <label for="staark-privacy-url"><strong><?php esc_html_e('Privacy policy URL', 'staark-core'); ?></strong></label><br>
                        <input id="staark-privacy-url" class="large-text" type="url" name="privacy_url" value="<?php echo esc_attr($settings['privacy_url']); ?>">
                    </p>
                    <p>
                        <label><input type="checkbox" name="notify" value="1" <?php checked($settings['notify']); ?>> <?php esc_html_e('Send an email notification for new submissions', 'staark-core'); ?></label>
                    </p>

                    <hr>
                    <h3><?php esc_html_e('Form security', 'staark-core'); ?></h3>
                    <p class="description"><?php esc_html_e('Public submissions are same-origin by design. CORS is not opened because normal HTML form POSTs are protected by CSRF controls, not by CORS.', 'staark-core'); ?></p>
                    <dl>
                        <?php foreach ($security as $security_key => $security_value) : ?>
                            <dt><strong><?php echo esc_html(strtoupper($security_key)); ?></strong></dt>
                            <dd><?php echo esc_html($security_value); ?></dd>
                        <?php endforeach; ?>
                    </dl>

                    <hr>
                    <h3><?php esc_html_e('Mail transport', 'staark-core'); ?></h3>
                    <p class="description"><?php esc_html_e('SMTP here is scoped to Staark Forms only and does not replace the mail transport used by other WordPress plugins.', 'staark-core'); ?></p>
                    <p>
                        <label for="staark-mail-transport"><strong><?php esc_html_e('Transport', 'staark-core'); ?></strong></label><br>
                        <select id="staark-mail-transport" name="mail_transport">
                            <option value="wordpress" <?php selected($settings['mail_transport'], 'wordpress'); ?>><?php esc_html_e('WordPress default', 'staark-core'); ?></option>
                            <option value="smtp" <?php selected($settings['mail_transport'], 'smtp'); ?>><?php esc_html_e('SMTP', 'staark-core'); ?></option>
                        </select>
                    </p>
                    <p>
                        <label for="staark-smtp-host"><strong><?php esc_html_e('SMTP host', 'staark-core'); ?></strong></label><br>
                        <input id="staark-smtp-host" class="regular-text" type="text" name="smtp_host" value="<?php echo esc_attr($settings['smtp_host']); ?>" placeholder="smtp.example.com">
                    </p>
                    <p>
                        <label for="staark-smtp-port"><strong><?php esc_html_e('Port', 'staark-core'); ?></strong></label><br>
                        <input id="staark-smtp-port" class="small-text" type="number" min="1" max="65535" name="smtp_port" value="<?php echo esc_attr((string) $settings['smtp_port']); ?>">
                    </p>
                    <p>
                        <label for="staark-smtp-encryption"><strong><?php esc_html_e('Encryption', 'staark-core'); ?></strong></label><br>
                        <select id="staark-smtp-encryption" name="smtp_encryption">
                            <option value="tls" <?php selected($settings['smtp_encryption'], 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected($settings['smtp_encryption'], 'ssl'); ?>>SSL</option>
                            <option value="none" <?php selected($settings['smtp_encryption'], 'none'); ?>><?php esc_html_e('None', 'staark-core'); ?></option>
                        </select>
                    </p>
                    <p>
                        <label><input type="checkbox" name="smtp_auth" value="1" <?php checked($settings['smtp_auth']); ?>> <?php esc_html_e('SMTP authentication', 'staark-core'); ?></label>
                    </p>
                    <p>
                        <label for="staark-smtp-username"><strong><?php esc_html_e('Username', 'staark-core'); ?></strong></label><br>
                        <input id="staark-smtp-username" class="regular-text" type="text" name="smtp_username" value="<?php echo esc_attr($settings['smtp_username']); ?>" autocomplete="off">
                    </p>
                    <p>
                        <label for="staark-smtp-password"><strong><?php esc_html_e('Password', 'staark-core'); ?></strong></label><br>
                        <input id="staark-smtp-password" class="regular-text" type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="<?php echo esc_attr($settings['smtp_password'] !== '' ? __('Saved — leave blank to keep it', 'staark-core') : __('Enter SMTP password', 'staark-core')); ?>">
                    </p>
                    <p class="description">
                        <?php if (defined('STAARK_FORMS_SMTP_PASSWORD')) : ?>
                            <?php esc_html_e('SMTP password is supplied by STAARK_FORMS_SMTP_PASSWORD and is not stored by this form.', 'staark-core'); ?>
                        <?php else : ?>
                            <?php esc_html_e('The SMTP password is stored in a non-autoloaded WordPress option and is never rendered back into this page. For managed production sites it can instead be supplied with STAARK_FORMS_SMTP_PASSWORD.', 'staark-core'); ?>
                        <?php endif; ?>
                    </p>
                    <p>
                        <label for="staark-smtp-from-email"><strong><?php esc_html_e('From email', 'staark-core'); ?></strong></label><br>
                        <input id="staark-smtp-from-email" class="regular-text" type="email" name="smtp_from_email" value="<?php echo esc_attr($settings['smtp_from_email']); ?>">
                    </p>
                    <p>
                        <label for="staark-smtp-from-name"><strong><?php esc_html_e('From name', 'staark-core'); ?></strong></label><br>
                        <input id="staark-smtp-from-name" class="regular-text" type="text" name="smtp_from_name" value="<?php echo esc_attr($settings['smtp_from_name']); ?>">
                    </p>
                    <?php submit_button(__('Save form settings', 'staark-core')); ?>
                </form>

                <hr>
                <h3><?php esc_html_e('Send test email', 'staark-core'); ?></h3>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('staark_forms_test_mail'); ?>
                    <input type="hidden" name="action" value="staark_forms_test_mail">
                    <p>
                        <label for="staark-test-email"><strong><?php esc_html_e('Test recipient', 'staark-core'); ?></strong></label><br>
                        <input id="staark-test-email" class="regular-text" type="email" name="test_email" value="<?php echo esc_attr($settings['recipient_email']); ?>">
                    </p>
                    <?php submit_button(__('Send test email', 'staark-core'), 'secondary', 'submit', false); ?>
                </form>
            </aside>
        </div>
    </div>
    <?php
}
