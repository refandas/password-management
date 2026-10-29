<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Reset Token settings (#38), bound from `env` like the database group.
 * The secret signs emailed Reset Tokens; an unset secret makes the flow
 * fail closed rather than sign with a default key anyone could forge.
 */
final class ResetTokens extends BaseConfig
{
    public string $secret = '';

    /**
     * How long an emailed link stays valid.
     */
    public int $ttlSeconds = 3600;
}
