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
    $selected_id = isset($_GET['submission']) ? absint($_GET['submission']) : 0;
    $selected = $selected_id > 0 ? get_post($selected_id) : null;

    if ($selected instanceof WP_Post && $selected->post_type === 'staark_submission') {
        $status = (string) get_post_meta($selected_id, '_staark_submission_status', true);
        if ($status === 'new') {
            update_post_meta($selected_id, '_staark_submission_status', 'read');
            $status = 'read';
        }
        ?>
        <div class="wrap staark-hub-wrap">
            <div class="staark-hub-page-head">
                <div>
                    <p class="staark-hub-eyebrow"><?php esc_html_e('Forms', 'staark-core'); ?></p>
                    <h1><?php echo esc_html(staark_hub_form_submission_label($selected_id)); ?></h1>
                </div>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=staark-hub-forms')); ?>"><?php esc_html_e('Back to submissions', 'staark-core'); ?></a>
            </div>

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
                        <dt><?php esc_html_e('Email delivery', 'staark-core'); ?></dt>
                        <dd><?php echo esc_html((string) get_post_meta($selected_id, '_staark_submission_mail_state', true)); ?></dd>
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
        <div class="staark-hub-page-head">
            <div>
                <p class="staark-hub-eyebrow"><?php esc_html_e('Forms', 'staark-core'); ?></p>
                <h1><?php esc_html_e('Forms & Submissions', 'staark-core'); ?></h1>
                <p><?php esc_html_e('Native lead capture that stays on the website even if email or the remote Hub is temporarily unavailable.', 'staark-core'); ?></p>
            </div>
        </div>

        <?php if (isset($_GET['staark_forms']) && sanitize_key(wp_unslash($_GET['staark_forms'])) === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Form settings saved.', 'staark-core'); ?></p></div>
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
                    <?php submit_button(__('Save form settings', 'staark-core')); ?>
                </form>
            </aside>
        </div>
    </div>
    <?php
}
