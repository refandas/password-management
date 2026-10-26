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

    /**
     * Show the registration form (legacy register()).
     */
    public function register(): string
    {
        return $this->page('auth/register', 'Register Now!');
    }

    /**
     * Create a User from the registration form. Success flashes the legacy
     * activation-required message — which, exactly as in CI3, overstates:
     * the row lands with registered = 1 and logs in immediately. Failure
     * (the insert returns false) bounces back to the form, with no flash.
     */
    public function create(): RedirectResponse
    {
        $data = [
            'name'     => $this->request->getPost('name'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        if (model(UserModel::class)->registration($data)) {
            session()->setFlashdata('success', '<div style="color: #00c054">Registration success! Please activate account before login.</div>');

            return $this->legacyRedirect('auth');
        }

        return $this->legacyRedirect('auth/register');
    }

    /**
     * Show the forgot-password form (legacy forgotPassword()).
     */
    public function forgotPassword(): string
    {
        return $this->page('auth/forgot', 'Forgot Password');
    }

    /**
     * Look up the email from the forgot form: found → the reset form renders
     * directly (a POST that answers a page); missing → the legacy flash and
     * back to the form. No verification sits between the two steps — the
     * known weakness, kept for parity (post-migration backlog).
     */
    public function resetPassword(): string|RedirectResponse
    {
        $email = $this->request->getPost('email');
        $users = model(UserModel::class);

        if ($users->userIsExists($email)) {
            return $this->page('auth/set_password', 'Reset Password', ['user' => $users->findUserByEmail($email)]);
        }

        session()->setFlashdata('failed', '<div style="color: #a71d2a">Email not found</div>');

        return $this->legacyRedirect('auth/forgotpassword');
    }

    /**
     * Store the new password from the reset form (legacy setPassword()).
     * Flashes success unconditionally — like CI3, no check that the update
     * matched a row.
     */
    public function setPassword($id_user = null): RedirectResponse
    {
        model(UserModel::class)->setPassword((string) $this->request->getPost('password'), $id_user);

        session()->setFlashdata('success', '<div style="color: #00c054">Reset password success.</div>');

        return $this->legacyRedirect('auth');
    }

    /**
     * Activate a User (legacy activateUser()) — the GET anchor on the User
     * detail page. The legacy controller checks nothing here: no session,
     * no Access Level — any caller flips the bit (a parity hole kept until
     * the post-migration fixes).
     */
    public function activateUser($id_user = null): RedirectResponse
    {
        if (model(UserModel::class)->activateUser($id_user)) {
            session()->setFlashdata('success', '<div style="color: #00c054">Aktivasi akun berhasil</div>');
        } else {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">Aktivasi akun gagal</div>');
        }

        return $this->legacyRedirect('dashboard/users');
    }

    /**
     * Deactivate a User (legacy deactivateUser()) — the anchor on an active
     * User's detail page. Same ungated parity hole as activateUser().
     */
    public function deactivateUser($id_user = null): RedirectResponse
    {
        if (model(UserModel::class)->disableUser($id_user)) {
            session()->setFlashdata('success', '<div style="color: #00c054">Deaktivasi akun berhasil</div>');
        } else {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">Deaktivasi akun gagal</div>');
        }

        return $this->legacyRedirect('dashboard/users');
    }
}
