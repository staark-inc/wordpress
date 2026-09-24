# Staark WordPress 6.0D — production deployment & recovery

6.0D separates the **managed runtime** from the normal WordPress plugin lifecycle.
The normal `wp-content/plugins/staark-core` package becomes the update/recovery
source. Locked sites boot the immutable managed release selected by `current`.

## Production layout

```text
wp-content/
├── mu-plugins/
│   └── staark-loader.php
├── plugins/
│   └── staark-core/                 # package/update + recovery source
└── staark-managed/
    ├── current  -> releases/<release-id>
    ├── previous -> releases/<release-id>
    ├── .deploy.lock
    └── releases/
        └── <release-id>/
            ├── staark-core.php
            ├── includes/
            ├── admin/
            ├── assets/
            ├── deployment/
            └── .staark-release.json
```

Do not copy client uploads, themes or WordPress core into `staark-managed`.

## First production deployment

Keep the site in `Managed` while preparing the first release:

```bash
wp staark managed mode managed
wp staark deployment deploy
wp staark deployment status
wp staark deployment verify
```

Then enable Locked and verify a new request boots from the managed path:

```bash
wp staark managed mode locked
wp staark deployment status
wp staark deployment verify
wp staark rc-check
```

Locked is release-ready only when `Runtime source` is `managed` and the RC
`Production managed runtime` gate passes.

## Update flow

1. Update/replace the server-owned `wp-content/plugins/staark-core` package.
2. Run `wp staark deployment deploy` from WP-CLI.
3. Run `wp staark deployment verify` and `wp staark rc-check`.
4. Test the website/admin. `previous` remains available for rollback.

`deploy` stages a complete release, writes a manifest, installs the matching
MU-loader and atomically switches `current`. It never changes Managed Mode.

A different trusted package directory can be used explicitly:

```bash
wp staark deployment deploy --source=/srv/staark/releases/staark-core
```

## Rollback

From a healthy WordPress/WP-CLI runtime:

```bash
wp staark deployment rollback
wp staark deployment verify
wp staark rc-check
```

The next request boots the previous release.

## Out-of-band recovery

If WordPress cannot boot, use the filesystem-only script from the Staark source
repository. It does not load WordPress:

```bash
WP_ROOT=/var/www/html bash scripts/managed-recover.sh status
WP_ROOT=/var/www/html bash scripts/managed-recover.sh rollback
```

If both a managed release and its loader are suspect but the standard plugin
package is known-good:

```bash
WP_ROOT=/var/www/html bash scripts/managed-recover.sh fallback-plugin
```

That moves only the `current` symlink aside. No release is deleted. The loader
can then fall back to `wp-content/plugins/staark-core/staark-core.php`.

To restore the loader from the selected managed release:

```bash
WP_ROOT=/var/www/html bash scripts/managed-recover.sh restore-loader
```

## Server constants

Existing emergency controls remain supported:

```php
define('STAARK_HUB_DISABLE_MU_LOADER', true);
define('STAARK_HUB_LOCKED_CORE_FILE', '/absolute/path/staark-core.php');
```

6.0D also understands:

```php
define('STAARK_HUB_REQUIRE_MANAGED_RUNTIME', true);
```

When enabled, Locked loader fallback to the normal plugin package is disabled.
Use this only after `wp staark deployment verify` passes and out-of-band recovery
has been tested. The default remains recovery-friendly.

This is operational hardening, not DRM. A server administrator with filesystem
access can always replace the runtime.


## 6.0D.2 safe Locked exit

Do not run `wp plugin activate staark-core` while a managed Locked runtime is
already bootstrapped. WordPress activation sandbox-includes the regular plugin
file in the same request, which can redeclare Staark functions.

Exit Locked directly instead:

```bash
wp staark managed mode managed
```

If the normal plugin activation flag was removed, Staark restores that database
flag without including the plugin package, changes the deployment mode, and
re-establishes Staark lifecycle schedules. The next request then boots the
regular plugin normally.


## 6.0D.3 externally mapped loader rollback

Development and container deployments may bind-mount the bundled Staark
MU-loader directly onto `wp-content/mu-plugins/staark-loader.php`. Such a target
cannot be atomically replaced by the deployment manager.

When the target is deployment-mapped **and still byte-identical to the active
Staark package's bundled loader**, release deployment and rollback now leave the
externally managed loader in place and atomically switch only the managed
release pointers. If the mounted target does not match the active package,
Staark still fails closed instead of guessing which loader owns the runtime.

Normal production copies that are not externally mounted keep the original
behavior: the selected release's loader is staged and replaced before the
managed release pointer changes.
