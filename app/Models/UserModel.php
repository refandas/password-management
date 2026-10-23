<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * User data access, ported from the CI3 UserModel (pre-migration).
 */
class UserModel extends Model
{
    protected $table            = 'user';
    protected $primaryKey       = 'id_user';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['email', 'name', 'password', 'registered', 'access_level'];
    protected $useTimestamps    = false;

    /**
     * The User with this email, or null. Like the CI3 row_array() port, an
     * absent row is null (legacy: "0" under loose comparison).
     */
    public function findUserByEmail(?string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Whether email + password authenticate (portable password_hash).
     */
    public function authenticate(?string $email, ?string $password): bool
    {
        $user = $this->findUserByEmail($email);
        if ($user === null) {
            return false;
        }

        return password_verify((string) $password, $user['password']);
    }
}
