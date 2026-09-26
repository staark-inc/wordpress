# Signed updates

Staark Core installs plugin and theme updates from the release manifest.
Every release in the manifest carries an Ed25519 signature. Once the plugin
knows the public key, it installs only releases whose signature matches:

- a missing signature is refused;
- a wrong signature or a changed version / SHA256 is refused;
- a manifest that points a theme at another folder (slug) is refused;
- packages are only downloaded from allowed hosts (`github.com`,
  `staarkinc.com` and their subdomains; extend with
  `STAARK_HUB_UPDATE_PACKAGE_HOSTS`).

The signed message is `staark-release-v1|<type>|<slug>|<version>|<sha256>`.
The package URL is not part of it, so the Hub may mirror packages; the
SHA256 binds the content.

## One-time setup

1. On your own computer, in the repository:

   ```bash
   php scripts/generate-update-key.php
   ```

   It writes the public key to `plugins/staark-core/update-signing.pub` and
   prints the secret key once.

2. In GitHub → Settings → Environments, create an environment named
   `release`:
   - add the secret `STAARK_UPDATE_SIGNING_KEY` with the printed secret key;
   - under "Deployment branches and tags", allow only tags matching `v*`;
   - optionally add yourself as a required reviewer.

   Keep an offline copy of the secret key (password manager). Do not commit
   it and do not paste it anywhere else.

3. Commit `plugins/staark-core/update-signing.pub`, bump the Staark Core
   version, merge to `main` and tag the release as usual.

The release workflow now:

- runs the Verify checks first (`needs: verify`);
- refuses tags that are not on `main`;
- signs the manifest (`scripts/sign-release-manifest.php`), and fails if the
  secret is missing or does not match `update-signing.pub`;
- publishes the release as a draft, checks that all 6 assets are there, then
  makes it public.

Emergency only: the repository variable `ALLOW_UNSIGNED_RELEASE=true` lets a
release go out without a signature. Sites that already have the key refuse
it, so it only helps sites still on an older Staark Core.

## Rollout

- Sites on Staark Core **before** the key was added keep accepting unsigned
  updates. They show "Update signatures are not verified" on the Updates
  screen.
- The first signed release installs normally (the old plugin does not check
  signatures). From then on each site verifies every update.
- The Hub endpoint (`/api/hub/wordpress/releases`) must pass the `signature`
  field of each release through unchanged. If the Hub rebuilds the manifest,
  copy `signature` from the GitHub manifest.

## Rotating the key

Sites only trust a new key after they installed a release that contains it:

1. `php scripts/generate-update-key.php --force` (writes the new public key,
   prints the new secret; keep the old secret in GitHub for now).
2. Set the repository variable `STAARK_UPDATE_KEY_ROTATION=true`, commit the
   new `update-signing.pub` and release. This one release is signed with the
   **old** secret and ships the **new** public key.
3. Replace the GitHub secret with the new secret key and delete the
   `STAARK_UPDATE_KEY_ROTATION` variable. Later releases use the new key.
