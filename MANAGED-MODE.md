# Staark WordPress 6.0 — Managed Mode deployment

WP-6.0B adds WordPress-level plugin protection on top of the 6.0A authority model. Managed and Locked modes now hide Staark Core from non-operator plugin management and block client-side deactivation, deletion, manual replacement and file editing. The MU-loader arrives in 6.0C.

## Safe bootstrap

```bash
npx wp-env run cli wp staark managed status
# After 6.0B, inspect any administrator's effective policy:
# npx wp-env run cli wp staark managed inspect <user>
npx wp-env run cli wp staark managed grant admin
npx wp-env run cli wp staark managed operators
npx wp-env run cli wp staark managed mode managed
npx wp-env run cli wp staark managed status
npx wp-env run cli wp staark rc-check
```

Replace `admin` with the Staark operator's WordPress user ID, login or email.
The account must have `manage_options`.

## Recovery

Before protection is enabled, return the site to Normal with:

```bash
npx wp-env run cli wp staark managed mode normal
```

A non-Normal mode cannot be enabled without at least one Staark operator, and
the last operator cannot be revoked while the site is Managed or Locked.

## Server-forced mode

A deployment may make the policy immutable from wp-admin/WP-CLI by defining:

```php
define('STAARK_HUB_MANAGED_MODE', 'managed');
```

Accepted values are `normal`, `managed`, and `locked`. An invalid value safely
falls back to Normal.

Do not use `locked` as the final production lock until the MU-loader/protection
patches are installed and their recovery path has been tested.


## 6.0B plugin protection

When the site is `managed` or `locked`, a WordPress administrator who is not a
Staark operator:

- does not see Staark Hub in **Plugins → Installed Plugins**;
- cannot deactivate, delete, manually update or overwrite Staark Core through wp-admin;
- cannot mutate Staark Core through the WordPress plugin REST endpoint;
- cannot use the built-in plugin/theme file editors;
- does not see the Managed policy page in the Staark Hub navigation.

Staark operators keep normal wp-admin control. WP-CLI and cron remain exempt so
server-side recovery and managed maintenance still work.

### Emergency protection bypass

If a deployment needs emergency recovery before an operator can be fixed, add:

```php
define('STAARK_HUB_DISABLE_PROTECTION', true);
```

This disables the 6.0B WordPress-level protection without changing the saved
Managed Mode. `wp staark rc-check` will then fail the Managed protection gate
while the site remains Managed/Locked, which makes the bypass visible during
release checks.

This is UI/application protection, not filesystem DRM. Direct server or
filesystem access can still replace PHP files. 6.0C adds the MU-loader so the
managed runtime no longer depends on normal plugin activation state.

## 6.0B.1 operator-capability recursion hotfix

Managed protection runs inside WordPress capability mapping. Operator detection must therefore never call `user_can()`/`current_user_can()` from that path, because doing so re-enters `map_meta_cap`. 0.6.0.2 uses the already-resolved `WP_User::allcaps` map for the administrator prerequisite and keeps the operator meta check separate.
