# Staark WordPress 6.0 — Managed Mode deployment

WP-6.0C adds the Locked must-use runtime on top of the 6.0A authority model and 6.0B WordPress-level plugin protection. Managed mode keeps the regular plugin lifecycle; Locked mode is bootstrapped by an MU-loader and no longer depends on the normal `active_plugins` flag.

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

Do not force `locked` in server configuration until the MU-loader is installed and its recovery path has been tested.


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


## 6.0C MU-loader / Locked runtime

The bundled loader lives at:

```text
plugins/staark-core/deployment/staark-loader.php
```

Production target:

```text
wp-content/mu-plugins/staark-loader.php
```

In the development `wp-env` the target is mapped automatically. After applying
6.0C restart `wp-env` once so the new mapping is mounted.

Check/install from WP-CLI:

```bash
wp staark managed loader status
wp staark managed loader install
```

Locked mode refuses to enable unless the loader exists. Then:

```bash
wp staark managed mode locked
wp staark managed status
wp staark rc-check
```

A Locked request must report `Loader = mu` / `Bootstrapped by MU = yes`, and the
RC check gains the **Locked MU runtime** gate.

### Activation-state independence test

While Locked, server/WP-CLI may remove the regular activation flag:

```bash
wp plugin deactivate staark-core
wp staark rc-check
```

Staark must still load and the RC check must stay green because the MU-loader
boots core first. This is the defining 6.0C behavior.

Return from Locked directly with Staark's managed-mode command:

```bash
wp staark managed mode managed
```

If the regular plugin activation flag was removed, Staark restores it without
sandbox-including the plugin a second time in the already-running Locked
request. The next request then resumes the normal plugin lifecycle safely.

### MU-loader recovery controls

Emergency server bypass:

```php
define('STAARK_HUB_DISABLE_MU_LOADER', true);
```

This does not modify the saved mode. If Staark Core is still active normally,
`wp staark rc-check` will deliberately fail the Locked MU runtime gate so the
bypass cannot be mistaken for a healthy Locked deployment.

An alternative server-controlled core path can be provided with:

```php
define('STAARK_HUB_LOCKED_CORE_FILE', '/absolute/path/to/staark-core.php');
```

The loader does not obfuscate or encrypt Staark source. Direct filesystem/server
access remains authoritative by design.


## 6.0C.1 wp-env mapped-loader behavior

`wp-env` mounts `deployment/staark-loader.php` directly at the MU-plugin target.
Because that target is a container mount point, WordPress must not try to overwrite
or unlink it like a normal copied file.

`wp staark managed loader install` is therefore idempotent when the mounted/copied
loader already matches the bundled source. `wp staark managed loader remove` emits
a warning instead of failing when the target is deployment-mounted; remove the
mapping in `.wp-env.json` and restart wp-env if the development mount itself must
be removed. Production copies that are not deployment-mounted remain removable in
Managed/Normal mode.


## 6.0C.2 container mount-point detection

Some Docker storage drivers expose a wp-env file mapping with different device/inode
values at the source and mounted target. 0.6.0.5 therefore detects the exact MU-loader
target in `/proc/self/mountinfo` before falling back to device/inode comparison. This
keeps `loader remove` non-destructive for bind-mounted development files and makes
`Deployment mapped` accurate across wp-env/Docker variants.
