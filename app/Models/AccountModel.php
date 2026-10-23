<?php

namespace App\Models;

use App\Libraries\LegacyEncryption;
use CodeIgniter\Model;

/**
 * Vault Account data access, ported from the CI3 AccountModel (pre-migration).
 * The write methods port with ticket #12.
 */
class AccountModel extends Model
{
    protected $table      = 'account';
    protected $primaryKey = 'id_account';
    protected $returnType = 'array';

    /**
     * One User's Vault Accounts joined with their Media Types, credentials
     * decrypted (legacy getAccountByUserID()). Same join and same columns as
     * CI3, no ORDER BY — the rows come back in the legacy order.
     */
    public function getAccountByUserID(?int $id_user): array
    {
        $accounts = $this->db->table('account a')
            ->select('*')
            ->join('account_media am', 'am.id_media = a.id_media')
            ->where('a.id_user', $id_user)
            ->get()
            ->getResultArray();

        foreach ($accounts as &$account) {
            $account = $this->decryptAccount($account);
        }

        return $accounts;
    }

    /**
     * One Vault Account by id (legacy getAccountByID()) — decrypted when
     * $decrypt, as stored otherwise. Null when the id matches no row.
     */
    public function getAccountByID(int|string $id_account, bool $decrypt = false): ?array
    {
        $account = $this->where('id_account', $id_account)->first();

        return $account === null || ! $decrypt ? $account : $this->decryptAccount($account);
    }

    /**
     * Decrypt username_email and password with the row's own per-account key.
     * (CI3 re-fetched each row's key by id — the redundant per-row key fetch
     * the spec absorbs into clean code; the key is already in the row.)
     */
    private function decryptAccount(array $account): array
    {
        $key = hex2bin((string) $account['key_str']);

        $account['username_email'] = LegacyEncryption::decrypt($account['username_email'], $key);
        $account['password']       = LegacyEncryption::decrypt($account['password'], $key);

        return $account;
    }
}
