<?php
/**
 * Generate the Ed25519 key pair that signs Staark WordPress releases.
 *
 * Run once, on your own computer (never on a server or in CI):
 *
 *   php scripts/generate-update-key.php
 *
 * It writes the PUBLIC key to plugins/staark-core/update-signing.pub (commit
 * this file) and prints the SECRET key once. Store the secret key as the
 * GitHub Actions secret STAARK_UPDATE_SIGNING_KEY in the "release"
 * environment, keep an offline backup (password manager), and never commit
 * it. Anyone with the secret key can sign updates for every Staark site.
 *
 * Requires PHP with the sodium extension (bundled since PHP 7.2).
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

if (! function_exists('sodium_crypto_sign_keypair')) {
    fwrite(STDERR, "The sodium extension is required.\n");
    exit(1);
}

$root = dirname(__DIR__);
$pub_file = $root . '/plugins/staark-core/update-signing.pub';

if (is_file($pub_file) && ! in_array('--force', $argv, true)) {
    fwrite(STDERR, "plugins/staark-core/update-signing.pub already exists.\n");
    fwrite(STDERR, "Rotating the key means sites only accept releases signed with the new key\n");
    fwrite(STDERR, "after they have installed a release that contains it. Re-run with --force to rotate.\n");
    exit(1);
}

$pair = sodium_crypto_sign_keypair();
$public = base64_encode(sodium_crypto_sign_publickey($pair));
$secret = base64_encode(sodium_crypto_sign_secretkey($pair));

file_put_contents($pub_file, $public . "\n");

echo "Public key written to plugins/staark-core/update-signing.pub:\n";
echo "  {$public}\n\n";
echo "SECRET key — add it as the GitHub secret STAARK_UPDATE_SIGNING_KEY, then clear your terminal:\n\n";
echo "  {$secret}\n\n";
echo "Next: commit update-signing.pub and tag a release. The release workflow signs\n";
echo "the manifest and refuses to publish if the secret does not match the public key.\n";
