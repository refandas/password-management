<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UserModel;
use Config\ResetTokens;
use RuntimeException;

/**
 * Emailed Reset Tokens (#38): the capability the set-password write now
 * requires, replacing the raw id_user the segment used to carry.
 *
 * The token is stateless — `{id_user}.{expires}.{signature}`, the signature
 * HMAC-SHA256 over the id, the expiry, and the User's current password hash.
 * Signing over the hash is what makes a token single-use in effect: the
 * reset write itself changes the hash, so a used (or replayed) token stops
 * verifying — as does every outstanding token after any password write.
 * Nothing is stored, keeping the schema frozen at spec #1's verbatim dump.
 */
final class ResetToken
{
    /**
     * Mint the Reset Token for this User row.
     */
    public static function mint(array $user): string
    {
        $settings = config(ResetTokens::class);
        $expires  = time() + $settings->ttlSeconds;

        return $user['id_user'] . '.' . $expires . '.' . self::signature($user, $expires);
    }

    /**
     * The User row the token authorizes, or null: bad shape, unknown User,
     * expired, or signed against a password the User no longer has.
     */
    public static function verify(?string $token): ?array
    {
        if ($token === null || preg_match('/^([0-9]+)\.([0-9]+)\.([0-9a-f]{64})$/', $token, $parts) !== 1) {
            return null;
        }

        $user = model(UserModel::class)->findUserByID($parts[1]);
        if ($user === null || (int) $parts[2] < time()) {
            return null;
        }

        return hash_equals(self::signature($user, (int) $parts[2]), $parts[3]) ? $user : null;
    }

    private static function signature(array $user, int $expires): string
    {
        $secret = config(ResetTokens::class)->secret;
        if ($secret === '') {
            throw new RuntimeException('resetTokens.secret is not set (see `env`).');
        }

        return hash_hmac('sha256', $user['id_user'] . '.' . $expires . '.' . $user['password'], $secret);
    }
}
