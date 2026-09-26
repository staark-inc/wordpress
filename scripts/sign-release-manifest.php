<?php
/**
 * Sign every release in a Staark WordPress update manifest (used by CI).
 *
 *   STAARK_UPDATE_SIGNING_KEY=<base64 secret key> \
 *     php scripts/sign-release-manifest.php dist/staark-wordpress-manifest.json
 *
 * Adds a detached Ed25519 `signature` to each release. The signed message is
 * the one Staark Core verifies (staark_hub_update_signature_message()):
 *
 *   staark-release-v1|<type>|<slug>|<version>|<sha256>
 *
 * Fails when the secret key does not belong to the public key committed in
 * plugins/staark-core/update-signing.pub, so a release can never ship a
 * plugin that rejects its own signatures.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

function fail(string $message): void
{
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$path = $argv[1] ?? '';
if ($path === '' || ! is_readable($path)) {
    fail('Usage: php scripts/sign-release-manifest.php <manifest.json>');
}

if (! function_exists('sodium_crypto_sign_detached')) {
    fail('The sodium extension is required.');
}

$secret = base64_decode(trim((string) getenv('STAARK_UPDATE_SIGNING_KEY')), true);
if (! is_string($secret) || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
    fail('STAARK_UPDATE_SIGNING_KEY is missing or not a base64 Ed25519 secret key (see scripts/generate-update-key.php).');
}

$root = dirname(__DIR__);
$pub_file = $root . '/plugins/staark-core/update-signing.pub';
$committed = is_readable($pub_file) ? trim((string) file_get_contents($pub_file)) : '';
$public = base64_encode(sodium_crypto_sign_publickey_from_secretkey($secret));

if ($committed === '') {
    fail('plugins/staark-core/update-signing.pub is missing. Run scripts/generate-update-key.php and commit the public key.');
}
if (! hash_equals($committed, $public)) {
    // Key rotation: one release is signed with the OLD secret while it ships
    // the NEW public key (docs/update-signing.md). Only then may they differ.
    if (getenv('STAARK_UPDATE_KEY_ROTATION') !== 'true') {
        fail('The signing secret does not match plugins/staark-core/update-signing.pub.');
    }
    fwrite(STDERR, "Key rotation release: signing with a key that differs from update-signing.pub.\n");
}

$slugs = [
    'core' => 'staark-core',
    'theme' => 'staark',
    'salong' => 'staark-salong',
    'bygg' => 'staark-bygg',
    'gastfrihet' => 'staark-gastfrihet',
];

$manifest = json_decode((string) file_get_contents($path), true);
if (! is_array($manifest) || ! isset($manifest['releases']) || ! is_array($manifest['releases'])) {
    fail('Invalid manifest.');
}

foreach ($manifest['releases'] as $type => &$release) {
    if (! isset($slugs[$type])) {
        fail("Unknown release type {$type}: add it here and in staark_hub_update_theme_releases().");
    }

    $message = implode('|', [
        'staark-release-v1',
        $type,
        $slugs[$type],
        (string) $release['version'],
        strtolower((string) $release['sha256']),
    ]);
    $signature = sodium_crypto_sign_detached($message, $secret);

    if (! sodium_crypto_sign_verify_detached($signature, $message, base64_decode($public, true))) {
        fail("Self-check failed for {$type}.");
    }

    $release['signature'] = base64_encode($signature);
    echo "Signed {$type} {$release['version']}\n";
}
unset($release);

$manifest['signatureScheme'] = 'staark-release-v1/ed25519';
file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
sodium_memzero($secret);
