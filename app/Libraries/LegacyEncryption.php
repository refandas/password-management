<?php

namespace App\Libraries;

/**
 * The legacy CI3-compatible decryption routine (spec #1, ADR-0003).
 *
 * Vault Account credentials are encrypted at rest in the CI3 format —
 * ported from CI3 3.1.10's system/libraries/Encryption.php (OpenSSL driver,
 * the only one PHP 7.3 selected) as the app configured it: cipher aes-256,
 * mode ctr, the raw per-account key from account.key_str. The format:
 *
 *   enc_key  = hkdf(key, sha512, zero-salt, strlen(key), 'encryption')
 *   auth_key = hkdf(key, sha512, zero-salt, 64, 'authentication')
 *   payload  = base64(iv || aes-256-ctr(plaintext, enc_key, iv))
 *   stored   = hex(hmac_sha512(payload, auth_key)) || payload
 *
 * The same format is documented in docs/testing/acceptance-suite.md and
 * implemented by the fixture generator (tests/tools/generate-seed.php);
 * the acceptance suite proves agreement through the HTTP seam — the app
 * must decrypt exactly what the generator encrypts onto the seed rows.
 *
 * PHP's hash_hkdf() is byte-identical to CI3's hand-rolled HKDF for both
 * derivations (sha512, zero-salt of digest size) — verified against the
 * CI3 source.
 */
final class LegacyEncryption
{
    private const DIGEST = 'sha512';

    /** HMAC hex-prefix length: sha512 = 64 bytes = 128 hex characters. */
    private const HMAC_LENGTH = 128;

    /** CTR-mode IV length for AES-256. */
    private const IV_LENGTH = 16;

    /**
     * Decrypt one legacy-format stored value with its raw per-account key.
     * Null where CI3 returned FALSE (authentication failure, bad payload) —
     * the views render null as empty, exactly as CI3 rendered FALSE.
     */
    public static function decrypt(?string $stored, string $rawKey): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $hmac    = substr($stored, 0, self::HMAC_LENGTH);
        $payload = substr($stored, self::HMAC_LENGTH);

        $authKey = hash_hkdf(self::DIGEST, $rawKey, 64, 'authentication');
        if (! hash_equals($hmac, hash_hmac(self::DIGEST, $payload, $authKey))) {
            return null;
        }

        $raw = base64_decode($payload, true);
        if ($raw === false) {
            return null;
        }

        // The encryption key is derived at strlen(key) — 32 bytes for the
        // per-account keys — which is the prefix of hash_hkdf's 64-byte output.
        $encKey = hash_hkdf(self::DIGEST, $rawKey, strlen($rawKey), 'encryption');

        $plaintext = openssl_decrypt(
            substr($raw, self::IV_LENGTH),
            'aes-256-ctr',
            $encKey,
            OPENSSL_RAW_DATA,
            substr($raw, 0, self::IV_LENGTH),
        );

        return $plaintext === false ? null : $plaintext;
    }
}
