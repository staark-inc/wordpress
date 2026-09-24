# Staark WordPress 6.0 — Managed Mode deployment

WP-6.0A establishes the authority model only. It does **not** yet hide, block,
or move Staark Hub into an MU-loader.

## Safe bootstrap

```bash
npx wp-env run cli wp staark managed status
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
