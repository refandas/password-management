<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Authentication pages, ported from the CI3 Auth controller (pre-migration).
 */
class Auth extends BaseController
{
    /**
     * Show the login form (legacy default route).
     */
    public function index(): string
    {
        return $this->page('auth/login', 'Login - Password Management');
    }

    /**
     * Authenticate email + password. On success, set the legacy session keys
     * (id_user, name, access_level, is_logged) and land on the dashboard.
     * On failure, flash the legacy error: distinct strings for an unknown
     * email and a wrong password — and, ported as-is, no flash at all for a
     * correct password on an unactivated account (the CI3 else-branch falls
     * through both conditions).
     */
    public function login(): RedirectResponse
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $users       = model(UserModel::class);
        $user        = $users->findUserByEmail($email);
        $login_valid = $users->authenticate($email, $password);

        if ($login_valid && $user['registered'] == 1) {
            session()->set([
                'id_user'      => $user['id_user'],
                'name'         => $user['name'],
                'access_level' => $user['access_level'],
                'is_logged'    => true,
            ]);

            return $this->legacyRedirect('dashboard');
        }

        if ($user === null) {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">This account is not registered!</div>');
        } elseif (! $login_valid) {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">Incorrect password!</div>');
        }

        return $this->legacyRedirect('auth');
    }
}
