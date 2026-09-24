# Staark WordPress — WP-6.0F Final RC checklist

Target plugin version: `0.6.0.11`

This release candidate is a stabilization gate. Do not add new product features
while running this checklist; log defects and fix only release blockers.

## 1. Automated smoke

```bash
bash scripts/rc-smoke.sh
```

Expected:

- all PHP files pass `php -l`;
- admin/accessibility JS passes `node --check` when Node is available;
- plugin header and `STAARK_HUB_VERSION` match;
- `git diff --check` is clean;
- `wp staark rc-check` reports all PASS in wp-env.

## 2. Fresh install

1. Start a clean wp-env with no old Staark options/posts.
2. Activate Staark Hub.
3. Confirm `staark_hub_installed_version = 0.6.0.11`.
4. Confirm Security and Performance cron events exist when their modules are enabled.
5. Open every Staark tab once and verify no PHP fatal/warning/notices.

## 3. Upgrade

1. Start from the previous stable checkpoint.
2. Keep existing branding, support tickets, connector pairing and module settings.
3. Replace/update the plugin to 0.6.0.11.
4. Confirm all existing data remains intact.
5. Confirm the installed-version marker updates to 0.6.0.11.

## 4. Tab smoke matrix

Test:

- Overview
- Website
- Security — scan + save switches
- SEO — save global/local settings and edit per-page metadata
- Performance — audit + save conservative optimizations
- Support — create a local ticket, open its detail view, read the full message and verify contact/environment/sync metadata
- Branding — save text/colors/assets
- Connect — test connection and support sync where a Hub endpoint is available

For every tab check normal load, form submit, success/error notice, refresh and
browser back/forward behavior.

## 5. Errors and logs

With development logging enabled during RC testing:

- browser console has no new Staark JS errors;
- `wp-content/debug.log` has no new Staark PHP warnings/notices/deprecations;
- network requests do not expose the connector secret;
- failed remote requests produce usable errors rather than fatals.

## 6. Responsive + accessibility

Viewport gates: `1440`, `1024`, `782`, `600`, `390` px.

Verify no horizontal page overflow, cards/forms remain usable, support tables
scroll inside their container, navigation remains reachable, and all primary
actions work with keyboard only. Confirm visible `:focus-visible` and reduced
motion behavior.

## 7. Lifecycle

Deactivate:

- Staark Security/Performance cron jobs are removed;
- settings, reports, connector identity and support tickets remain.

Reactivate:

- the plugin loads without migration errors;
- installed version is refreshed;
- enabled Security/Performance schedules are restored;
- previous settings/data remain available.

Uninstall default:

- cron jobs are removed;
- plugin-owned data is intentionally preserved for accidental reinstall recovery.

Destructive uninstall is tested separately only after defining:

```php
define('STAARK_HUB_REMOVE_DATA_ON_UNINSTALL', true);
```

That mode removes Staark options, Staark per-content SEO metadata and locally
stored Staark support tickets. It must never be enabled by the plugin itself.

## 8. RC exit criteria

RC passes only when all automated checks pass, every tab has completed the smoke
matrix, no release-blocking PHP/JS issue remains, responsive/accessibility gates
pass, and lifecycle behavior matches this document.


## 9. WP-6.0C Locked runtime gate

After the MU-loader is deployed:

```bash
wp staark managed loader status
wp staark managed mode locked
wp staark managed status
wp staark rc-check
```

Expected in Locked mode: runtime loader `mu`, MU bootstrap `yes`, and all RC
checks pass including **Locked MU runtime**.

Then prove activation-state independence:

```bash
wp plugin deactivate staark-core
wp staark rc-check
```

The command must still exist and pass because the MU-loader is authoritative.
Leave Locked directly; Staark restores the regular activation flag safely:

```bash
wp staark managed mode managed
```

Do not call `wp plugin activate staark-core` from an already-bootstrapped
managed Locked request; WordPress activation sandbox-includes the plugin file.

## 10. WP-6.0D production deployment gate

Managed and Locked production sites must have a readable managed release:

```bash
wp staark deployment status
wp staark deployment verify
wp staark rc-check
```

Expected in Managed mode: a readable `current` release with a manifest version.
Expected in Locked mode: the same plus runtime source `managed`.

The rollback round-trip must already have been exercised on the release candidate.
Return the `current` pointer to the newest tested release before release sign-off.

## 11. WP-6.0E support detail gate

Create a local support ticket and open it from the Support history table.

Verify the full message, category/priority/status, requester details, delivery/sync state,
stored environment snapshot, controlled invalid-ticket view, Back to Support, and responsive
behavior at 1440 / 1024 / 782 / 600 / 390 px.

## 12. WP-6.0F final release gate

No new product features are added during this phase.

Before sign-off:

```bash
git diff --check
bash scripts/rc-smoke.sh
wp staark deployment status
wp staark deployment verify
wp staark rc-check
```

Then run one final Locked handoff:

```bash
wp staark managed mode locked
wp staark deployment verify
wp staark rc-check
wp staark managed mode managed
wp staark rc-check
```

Release only when all automated checks pass, the managed release is readable, Locked runtime
boots from the managed source, Support detail passes its manual gate, browser/PHP logs are clean,
the client-facing smoke matrix passes, and only intentional files are staged.

