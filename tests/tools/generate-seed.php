<?php

declare(strict_types=1);

/**
 * Generates tests/seed/seed.sql from tests/seed/plaintexts.php.
 *
 * Vault Account credentials are encrypted in the legacy CI3 format so that
 * both the legacy oracle (pre-migration branch) and the ported app can
 * decrypt them. The format is ported verbatim from CI3 3.1.10's
 * system/libraries/Encryption.php (OpenSSL driver, which PHP 7.3 always
 * selected since mcrypt no longer exists):
 *
 *   enc_key = hkdf(key, sha512, zero-salt, 32, 'encryption')
 *   auth_key = hkdf(key, sha512, zero-salt, 64, 'authentication')
 *   payload = base64(iv || aes-256-ctr(plaintext, enc_key, iv))
 *   stored  = hex(hmac_sha512(payload, auth_key)) || payload
 *
 * Determinism: per-account keys and per-field IVs derive from the account id,
 * so regeneration is byte-stable (test fixture only). Each field gets its own
 * IV — the real app draws a random IV per encrypt() call, and reusing one IV
 * across the two fields of an account would reuse a CTR keystream.
 *
 * Usage:
 *   php tests/tools/generate-seed.php           # write tests/seed/seed.sql
 *   php tests/tools/generate-seed.php --check   # decrypt seed.sql, compare to plaintexts
 */

const SEED_FILE = __DIR__ . '/../seed/seed.sql';

function ci3_hkdf(string $key, string $digest, ?string $salt, ?int $length, string $info): string
{
    $digestSize = 64; // sha512, the only digest CI3's encrypt() derives keys with
    $length ??= $digestSize;
    $salt = ($salt === null || $salt === '') ? str_repeat("\0", $digestSize) : $salt;

    $prk = hash_hmac($digest, $key, $salt, true);
    $out = '';
    for ($block = '', $i = 1; strlen($out) < $length; $i++) {
        $block = hash_hmac($digest, $block . $info . chr($i), $prk, true);
        $out .= $block;
    }

    return substr($out, 0, $length);
}

function ci3_encrypt(string $plaintext, string $rawKey, string $iv): string
{
    $encKey = ci3_hkdf($rawKey, 'sha512', null, strlen($rawKey), 'encryption');
    $payload = base64_encode($iv . openssl_encrypt($plaintext, 'aes-256-ctr', $encKey, OPENSSL_RAW_DATA, $iv));
    $authKey = ci3_hkdf($rawKey, 'sha512', null, null, 'authentication');

    return hash_hmac('sha512', $payload, $authKey, false) . $payload;
}

function ci3_decrypt(string $stored, string $rawKey): ?string
{
    [$hmac, $payload] = [substr($stored, 0, 128), substr($stored, 128)];
    $authKey = ci3_hkdf($rawKey, 'sha512', null, null, 'authentication');
    if (!hash_equals($hmac, hash_hmac('sha512', $payload, $authKey, false))) {
        return null;
    }

    $raw = base64_decode($payload, true);
    if ($raw === false) {
        return null;
    }

    return openssl_decrypt(
        substr($raw, 16),
        'aes-256-ctr',
        ci3_hkdf($rawKey, 'sha512', null, strlen($rawKey), 'encryption'),
        OPENSSL_RAW_DATA,
        substr($raw, 0, 16),
    );
}

function accountKey(int $idAccount): string
{
    return hash('sha256', 'seed-account-key-' . $idAccount, true);
}

function fieldIv(int $idAccount, string $field): string
{
    return substr(hash('sha256', bin2hex(accountKey($idAccount)) . '|' . $field, true), 0, 16);
}

function sqlValue(?string $value): string
{
    return $value === null ? 'NULL' : "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
}

function insert(string $table, array $columns, array $rows): string
{
    $values = implode(",\n", array_map(
        static fn (array $row): string => '(' . implode(', ', array_map(
            static fn (int|string|null $value): string => is_int($value) ? (string) $value : sqlValue($value),
            $row,
        )) . ')',
        $rows,
    ));

    return "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n{$values};\n";
}

function generate(array $fixture): string
{
    $sql = "-- GENERATED FILE — do not edit.\n"
        . "-- Regenerate with: php tests/tools/generate-seed.php (source: tests/seed/plaintexts.php)\n"
        . "-- Vault Account credentials use the legacy CI3 encryption format (see file header\n"
        . "-- of tests/tools/generate-seed.php), so the legacy oracle decrypts them too.\n"
        . "\n"
        . "START TRANSACTION;\n"
        . "SET NAMES utf8mb4;\n\n";

    $sql .= insert('access_rights', ['access_level', 'status_access'], $fixture['access_rights']) . "\n";
    $sql .= insert('feature', ['id_feature', 'feature_name', 'url'], $fixture['feature']) . "\n";
    $sql .= insert('access_list', ['access_level', 'id_feature'], $fixture['access_list']) . "\n";
    $sql .= insert('account_media', ['id_media', 'logo', 'name'], $fixture['account_media']) . "\n";

    $sql .= insert('user', ['id_user', 'email', 'name', 'password', 'registered', 'access_level'], array_map(
        static fn (array $u): array => [
            $u['id_user'], $u['email'], $u['name'], $u['password_hash'],
            $u['registered'], $u['access_level'],
        ],
        $fixture['users'],
    )) . "\n";

    $sql .= insert('account', ['id_account', 'id_user', 'username_email', 'password', 'id_media', 'key_str'], array_map(
        static function (array $a): array {
            $key = accountKey($a['id_account']);

            return [
                $a['id_account'],
                $a['id_user'],
                ci3_encrypt($a['username_email'], $key, fieldIv($a['id_account'], 'username_email')),
                ci3_encrypt($a['password'], $key, fieldIv($a['id_account'], 'password')),
                $a['id_media'],
                bin2hex($key),
            ];
        },
        $fixture['accounts'],
    )) . "\n";

    return $sql . "COMMIT;\n";
}

function check(array $fixture): int
{
    if (!is_file(SEED_FILE)) {
        fwrite(STDERR, "seed.sql not found — generate it first.\n");

        return 1;
    }

    // Regeneration is deterministic: a file that differs from a fresh
    // generation is stale or hand-edited.
    if (file_get_contents(SEED_FILE) !== generate($fixture)) {
        fwrite(STDERR, "seed.sql differs from a fresh generation — regenerate it.\n");

        return 1;
    }

    $failures = 0;

    foreach ($fixture['users'] as $user) {
        $ok = password_verify($user['password'], $user['password_hash']);
        if (!$ok) {
            $failures++;
        }
        printf("user %-16s hash => %s\n", $user['email'], $ok ? 'verifies' : 'FAIL');
    }

    foreach ($fixture['accounts'] as $account) {
        $key = accountKey($account['id_account']);

        foreach (['username_email', 'password'] as $field) {
            $stored = ci3_encrypt($account[$field], $key, fieldIv($account['id_account'], $field));
            $plain = ci3_decrypt($stored, $key);
            $ok = $plain === $account[$field];
            if (!$ok) {
                $failures++;
            }
            printf("account %d %-14s => %s\n", $account['id_account'], $field, $ok ? (string) $plain : 'FAIL');
        }
    }

    if ($failures > 0) {
        fwrite(STDERR, "\nSeed does not round-trip to the fixture plaintexts.\n");

        return 1;
    }

    echo "\nseed.sql round-trips to the fixture plaintexts.\n";

    return 0;
}

$fixture = require __DIR__ . '/../seed/plaintexts.php';

if (in_array('--check', $argv, true)) {
    exit(check($fixture));
}

file_put_contents(SEED_FILE, generate($fixture));
echo 'Wrote ' . SEED_FILE . "\n";
exit(0);
