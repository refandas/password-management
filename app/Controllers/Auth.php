<?php

namespace App\Controllers;

use App\Libraries\ResetToken;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Email;
use Config\ResetTokens;

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
     * Look up the email from the forgot form (#38): found → a Reset Token
     * is minted and the link emailed to the User, and the forgot page
     * answers with the green flash — the set-password form no longer
     * renders here, since the form carried the id the write accepted,
     * which was the hole. Missing → the legacy flash and back to the form.
     */
    public function resetPassword(): RedirectResponse
    {
        $email = $this->request->getPost('email');
        $user  = model(UserModel::class)->findUserByEmail($email);

        if ($user !== null) {
            $this->sendResetLink($user);
            session()->setFlashdata('success', '<div style="color: #00c054">A password reset link has been sent to your email.</div>');
        } else {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">Email not found</div>');
        }

        return $this->legacyRedirect('auth/forgotpassword');
    }

    /**
     * Serve the emailed link's set-password page, and store the new password
     * from it (#38). The segment is the Reset Token, never the User id: an
     * invalid, expired, or already-spent token — and any id-shaped segment,
     * the pre-fix attack — bounces to the forgot page with the red flash
     * and writes nothing. The retype match is checked server-side, like the
     * Setting flow's since #34; success keeps the legacy flash and
     * redirect.
     */
    public function setPassword($token = null): string|RedirectResponse
    {
        $user = ResetToken::verify($token);

        if ($user === null) {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">The reset link is invalid or expired.</div>');

            return $this->legacyRedirect('auth/forgotpassword');
        }

        if (strtolower($this->request->getMethod()) === 'get') {
            return $this->page('auth/set_password', 'Reset Password', ['user' => $user, 'token' => (string) $token]);
        }

        $password = (string) $this->request->getPost('password');
        $retype   = (string) $this->request->getPost('password_check');

        if ($password !== $retype) {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">Passwords do not match!</div>');

            return $this->legacyRedirect('auth/forgotpassword');
        }

        model(UserModel::class)->setPassword($password, $user['id_user']);

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
            session()->setFlashdata('success', '<div style="color: #00c054">User activated successfully.</div>');
        } else {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">User activation failed.</div>');
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
            session()->setFlashdata('success', '<div style="color: #00c054">User deactivated successfully.</div>');
        } else {
            session()->setFlashdata('failed', '<div style="color: #a71d2a">User deactivation failed.</div>');
        }

        return $this->legacyRedirect('dashboard/users');
    }

    /**
     * Mint the Reset Token and email the link (#38). A failed send is
     * logged and swallowed — the response stays identical either way, so a
     * probe learns nothing about the mailbox behind an email address.
     */
    private function sendResetLink(array $user): void
    {
        $token   = ResetToken::mint($user);
        $minutes = intdiv(config(ResetTokens::class)->ttlSeconds + 59, 60);

        $email = service('email');
        $email->setFrom(config(Email::class)->fromEmail, config(Email::class)->fromName);
        $email->setTo($user['email']);
        $email->setSubject('Reset your password');
        $email->setMessage(
            'Hi ' . $user['name'] . ",\n\n"
            . "Use this link within {$minutes} minutes to reset your password:\n\n"
            . base_url('auth/setpassword/' . $token) . "\n\n"
            . 'If you did not request this, ignore this email.'
        );

        if (! $email->send()) {
            log_message('error', 'Reset link failed to send for user ' . $user['id_user'] . '.');
        }
    }
}
