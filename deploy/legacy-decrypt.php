<?php
/**
 * One-time helper for the cutover from enc:-prefixed secrets to Jenkins
 * credentials. Decrypts a value produced by the retired
 * ApplicationUtils::safeEncrypt() so it can be pasted into Jenkins.
 *
 *   php deploy/legacy-decrypt.php 'enc:AbC...=='     (or without the enc: prefix)
 *
 * Must run on the OSE network: the key comes from the same unauthenticated
 * http://coderepo:8088 service the app used to call. Delete this file once
 * every environment's secrets are in Jenkins and rotated (deploy/SECRETS.md).
 */

if ($argc < 2) {
    fwrite(STDERR, "usage: php deploy/legacy-decrypt.php '<enc:value>'\n");
    exit(2);
}

$encrypted = $argv[1];
if (str_starts_with($encrypted, 'enc:')) {
    $encrypted = substr($encrypted, 4);
}

$body = @file_get_contents('http://coderepo:8088');
if ($body === false) {
    fwrite(STDERR, "could not reach http://coderepo:8088 -- run this from inside the OSE network\n");
    exit(1);
}
$key = substr(json_decode($body)->data ?? '', 0, 32);
if (strlen($key) !== 32) {
    fwrite(STDERR, "key service returned an unexpected payload\n");
    exit(1);
}

$decoded = base64_decode($encrypted, true);
if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
    fwrite(STDERR, "value is not a valid enc: payload\n");
    exit(1);
}
$nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
if ($plain === false) {
    fwrite(STDERR, "decryption failed (wrong key or tampered value)\n");
    exit(1);
}
echo $plain, PHP_EOL;
