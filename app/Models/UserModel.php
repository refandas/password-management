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

    /**
     * Register a User (legacy registration()): Access Level 2, and — the
     * legacy quirk — registered = 1, so the "activate before login" flash
     * overstates: the account logs in immediately. Ported as-is.
     */
    public function registration(array $data): bool
    {
        $data['password']     = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $data['access_level'] = 2;
        $data['registered']   = 1;

        return $this->insert($data, false);
    }

    /**
     * Overwrite a User's password (legacy setPassword(); its quirk of also
     * SETting id_user to its own value is dropped — the observable update
     * is identical).
     */
    public function setPassword(string $password, int|string $id_user): bool
    {
        return $this->update($id_user, ['password' => password_hash($password, PASSWORD_DEFAULT)]);
    }

    /**
     * Overwrite a User's profile — email and name (legacy updateUser();
     * like setPassword(), its quirk of also SETting id_user to its own
     * value is dropped, the observable update identical).
     */
    public function updateUser(?string $email, ?string $name, int|string $id_user): bool
    {
        return $this->update($id_user, ['email' => $email, 'name' => $name]);
    }

    /**
     * Delete a User (legacy deleteUser()). Their Vault Accounts go with
     * them — the schema's ON DELETE CASCADE, unchanged by the migration.
     */
    public function deleteUser(int|string $id_user): void
    {
        $this->delete($id_user);
    }

    /**
     * Mark a User activated (legacy activateUser()). Like CI3's update(),
     * true whenever the query runs — including when the id matches no row.
     */
    public function activateUser(int|string $id_user): bool
    {
        return $this->update($id_user, ['registered' => 1]);
    }

    /**
     * Mark a User deactivated (legacy disableUser()).
     */
    public function disableUser(int|string $id_user): bool
    {
        return $this->update($id_user, ['registered' => 0]);
    }

    /**
     * Every User row (legacy getUsers()): no ORDER BY, so the rows list in
     * table order — id order for the seeded rows — exactly as in CI3.
     */
    public function getUsers(): array
    {
        return $this->findAll();
    }

    /**
     * The User with this id_user, or null when no row matches (legacy
     * findUserByID(); the missing-row case renders the empty page below).
     */
    public function findUserByID(int|string|null $id_user): ?array
    {
        return $this->where('id_user', $id_user)->first();
    }

    /**
     * Whether a User exists for this email (legacy userIsExists(), whose
     * $this->UserModel self-reference resolved to the model itself).
     */
    public function userIsExists(?string $email): bool
    {
        return $this->findUserByEmail($email) !== null;
    }
}
