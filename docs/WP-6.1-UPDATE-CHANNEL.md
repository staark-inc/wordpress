# Staark WordPress — WP-6.1 Update Channel

WP-6.1 closes the managed-site loop: Staark Core and Staark Theme can discover a release, verify it, stage it and install it without relying on WordPress.org.

## Release manifest

Default endpoint:

```text
GET https://staarkinc.com/api/hub/wordpress/releases?channel=stable&hub=<installed-version>
```

Connected sites sign the request with the existing Staark connector. A development manifest can be injected with:

```php
define('STAARK_HUB_UPDATE_MANIFEST_URL', 'https://example.test/staark-releases.json');
```

Manifest schema v1:

```json
{
  "schema": 1,
  "channel": "stable",
  "generatedAt": "2026-09-24T18:00:00Z",
  "releases": {
    "core": {
      "version": "0.6.1.1",
      "package": "https://releases.staarkinc.com/wordpress/staark-core-0.6.1.1.zip",
      "sha256": "<64 hex chars>",
      "signature": "<optional base64 Ed25519 signature>",
      "requires": { "wordpress": "6.6", "php": "8.0" },
      "notes": "Release notes"
    },
    "theme": {
      "slug": "staark",
      "version": "1.1.0",
      "package": "https://releases.staarkinc.com/wordpress/staark-theme-1.1.0.zip",
      "sha256": "<64 hex chars>",
      "signature": "<optional base64 Ed25519 signature>",
      "requires": { "wordpress": "6.6", "php": "8.0" },
      "notes": "Release notes"
    }
  }
}
```

## Package verification

Every package must:

- use HTTPS outside local/development environments;
- match the manifest SHA256;
- pass PHP `-l` preflight for every PHP file;
- match the expected package version;
- match the expected Staark Core/Theme structure.

Optional Ed25519 signatures are supported. Configure a base64 public key:

```php
define('STAARK_HUB_UPDATE_PUBLIC_KEY', '<base64 public key>');
define('STAARK_HUB_REQUIRE_SIGNED_UPDATES', true);
```

The signed message is:

```text
<type>|<version>|<sha256>|<package-url>
```

where type is `core` or `theme`.

## Staark Core install flow

1. Fetch + normalize manifest.
2. Verify requirements, signature (when configured), SHA256 and PHP syntax.
3. Extract to a temporary staging area.
4. Validate the Staark Core package.
5. Atomically move the normal plugin package into rollback position.
6. Atomically switch the staged package into `wp-content/plugins/staark-core`.
7. Deploy the same package to `wp-content/staark-managed/releases/`.
8. Verify the managed release.
9. Keep the old regular plugin package until the next request.
10. On the next request, run RC health checks. Success deletes the backup; failure restores the old package and managed `previous` release.

Externally mounted plugin directories (for example wp-env development mappings) are intentionally not rewritten by the updater. Update the mapped source outside WordPress instead.

## Staark Theme install flow

1. Fetch + verify package.
2. Validate `style.css`, version and Staark block-theme structure.
3. Stage the theme beside the installed theme.
4. Move the old theme into rollback position.
5. Atomically switch the new theme in.
6. Verify the installed theme version.
7. Roll back immediately if verification fails.

Externally mounted theme directories are not rewritten.

## WP-CLI

```bash
wp staark updates status
wp staark updates check
wp staark updates install core --yes
wp staark updates install theme --yes
wp staark updates rollback-core --yes
```

The admin Updates page allows checking for releases. In Managed/Locked mode only a Staark operator can install them.

## Scheduling

Staark schedules a twice-daily manifest check. Deactivation/uninstall clears the update cron. Client data and update state remain preserved by default uninstall policy.
