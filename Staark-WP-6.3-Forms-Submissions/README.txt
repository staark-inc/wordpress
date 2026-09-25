Staark WordPress — WP-6.3 Forms & Submissions
==============================================

Target baseline
---------------
Staark Core 0.6.2.0 / main after WP-6.2.
The installer bumps Staark Core to 0.6.3.0.

What this package adds
----------------------
- Native shortcode: [staark_contact_form]
- Fields: name, email, phone, company, message, consent
- Local persistence as private staark_submission records
- Admin: Staark Hub -> Forms
- Submission states: new, read, replied, spam
- Email notification through wp_mail()
- Reply-To set to the submitter email
- Honeypot spam guard
- Minimum submit-time check
- One-minute per-IP hash rate limit (raw IP is not stored)
- Consent timestamp
- Source URL and form ID
- Mail state: sent / failed / not_sent
- Future Hub sync hook: staark_hub_form_submitted
- Connector reports forms capability + summary

Important architecture note
---------------------------
WP-6.3 deliberately stores the submission locally first. A failed email therefore
never deletes the lead. Direct CRM/Hub lead sync should be added only when the
matching Staark Hub API contract is deployed, so this patch does not send form
payloads to a non-existent/unknown endpoint.

Install
-------
1. Extract this ZIP anywhere.
2. Run:

   bash WP-6.3-apply.sh ~/staark-wp-kit

3. If green, test in wp-env:

   cd ~/staark-wp-kit
   npx wp-env run cli php -l /var/www/html/wp-content/plugins/staark-core/includes/forms.php
   npx wp-env run cli php -l /var/www/html/wp-content/plugins/staark-core/admin/forms-page.php
   npx wp-env run cli wp eval 'echo STAARK_HUB_VERSION . PHP_EOL;'

4. Add the form to a page using:

   [staark_contact_form]

   Optional:
   [staark_contact_form button="Skicka" form_id="contact-page"]

5. Open:
   Staark Hub -> Forms

6. Set recipient email and privacy policy URL.

Suggested manual acceptance test
--------------------------------
- Valid form creates one submission and shows success.
- Submission appears under Staark Hub -> Forms.
- Email state becomes sent (or failed while the submission remains stored).
- Missing consent/email/message rejects submission.
- Honeypot submission is silently discarded.
- Immediate repeat submission is rate-limited.
- Status can be changed to read/replied/spam.
- Mobile form layout is one column.

Rollback before commit
----------------------
If you have not committed yet:

   git restore plugins/staark-core/staark-core.php
   rm -f plugins/staark-core/includes/forms.php
   rm -f plugins/staark-core/admin/forms-page.php
   rm -f plugins/staark-core/assets/forms.css

Release
-------
After acceptance testing, commit WP-6.3 and tag v0.6.3.0.
