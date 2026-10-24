<?php

namespace App\Models;

use App\Libraries\LegacyEncryption;
use CodeIgniter\Encryption\Encryption;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use CodeIgniter\Encryption\Handlers\OpenSSLHandler;
use CodeIgniter\Model;
use Config\Encryption as EncryptionConfig;

/**
 * Vault Account data access, ported from the CI3 AccountModel (pre-migration).
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
     * All Media Types ordered by name (legacy getAccountMedia()).
     */
    public function getAccountMedia(): array
    {
        return $this->db->table('account_media')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Store a new Vault Account (legacy addAccount()): generate its own
     * per-account key and encrypt the credential with the CI4 encrypter
     * (ADR-0003) before the insert.
     */
    public function addAccount(array $data): void
    {
        $rawKey          = Encryption::createKey(32);
        $data['key_str'] = bin2hex($rawKey);

        $this->db->table('account')->insert($this->encryptAccount($data, $rawKey));
    }

    /**
     * Re-encrypt and update one Vault Account (legacy editAccount()) — the
     * row's existing per-account key, new ciphertext for the credential.
     */
    public function editAccount(array $data): void
    {
        $data = $this->encryptAccount($data, hex2bin((string) $data['key_str']));

        $idAccount = $data['id_account'];
        unset($data['id_account']);

        $this->db->table('account')->where('id_account', $idAccount)->update($data);
    }

    /**
     * Delete one Vault Account by id (legacy deleteAccount(), minus the
     * accidental string-offset assignment — a dead write with no
     * observable effect, ported as clean code per spec #1; ADR-0003 keeps
     * the quirk on its post-migration verification list).
     */
    public function deleteAccount(int|string $id_account): void
    {
        $this->db->table('account')->where('id_account', $id_account)->delete();
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
     * Encrypt username_email and password with the CI4 encrypter, keyed by
     * the row's raw per-account key (legacy encryptAccount(), moved from
     * CI3's encryption library to the framework's).
     */
    private function encryptAccount(array $data, string $rawKey): array
    {
        $encrypter = $this->ci4Encrypter();

        $data['username_email'] = $encrypter->encrypt((string) $data['username_email'], $rawKey);
        $data['password']       = $encrypter->encrypt((string) $data['password'], $rawKey);

        return $data;
    }

    /**
     * The CI4 encrypter as the app configures it (OpenSSL, AES-256-CTR,
     * SHA-512, base64 envelope) — the per-account key rides each call.
     */
    private function ci4Encrypter(): OpenSSLHandler
    {
        return new OpenSSLHandler(config(EncryptionConfig::class));
    }

    /**
     * Decrypt username_email and password with the row's own per-account key
     * (CI3 re-fetched each row's key by id — the redundant per-row key fetch
     * the spec absorbs into clean code; the key is already in the row).
     *
     * Rows written before the port — the seed fixture, and production data
     * until the cutover re-encryption — carry the legacy CI3 format; rows
     * written by the port carry the CI4 encrypter's format under the same
     * key scheme (ADR-0003). The legacy routine reads its own; everything
     * else falls through to the CI4 encrypter.
     */
    private function decryptAccount(array $account): array
    {
        $rawKey = hex2bin((string) $account['key_str']);

        $account['username_email'] = $this->decryptCredential($account['username_email'], $rawKey);
        $account['password']       = $this->decryptCredential($account['password'], $rawKey);

        return $account;
    }

    /**
     * One stored credential back to plaintext. Null where neither routine
     * authenticates the value — CI3 returned FALSE and the views rendered
     * it empty; the port keeps that render.
     */
    private function decryptCredential(?string $stored, string $rawKey): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $legacy = LegacyEncryption::decrypt($stored, $rawKey);
        if ($legacy !== null) {
            return $legacy;
        }

        try {
            return $this->ci4Encrypter()->decrypt($stored, $rawKey);
        } catch (EncryptionException) {
            return null;
        }
    }
}
