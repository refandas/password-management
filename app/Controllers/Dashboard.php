<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * The gated dashboard, ported from the CI3 Dashboard controller
 * (pre-migration). Each route re-checks the session exactly where CI3
 * checked it, so redirect targets (and their chains) stay identical.
 */
class Dashboard extends BaseController
{
    /**
     * Show the dashboard — only with is_logged set.
     */
    public function index(): string|RedirectResponse
    {
        if (session('is_logged') == 1) {
            return $this->page('dashboard/index', 'Dashboard');
        }

        return $this->legacyRedirect('auth');
    }

    /**
     * Show all Users — Access Level 1 only; everyone else bounces to the
     * dashboard (legacy users()). The view filters the Admin's own rows
     * out (access_level != 1) and splits the rest by activation status.
     */
    public function users(): string|RedirectResponse
    {
        if (session('access_level') == 1) {
            $users = model(UserModel::class)->getUsers();

            return $this->layoutPage('dashboard/users', 'Users', ['users' => $users]);
        }

        return $this->legacyRedirect('dashboard');
    }

    /**
     * Show one User — Access Level 1 only (legacy user()). The view's
     * null-coalescing guards cover the no-row case: CI3 on PHP 7.3 read a
     * missing row's fields as null and rendered the empty page (name
     * blanks, the unactivated branch with an empty activate href), where
     * the unguarded read would throw on PHP 8.5.
     */
    public function user($id_user = null): string|RedirectResponse
    {
        if (session('access_level') == 1) {
            $user = model(UserModel::class)->findUserByID($id_user);

            return $this->layoutPage('dashboard/user', 'User Detail', ['user' => $user, 'id_user' => $id_user]);
        }

        return $this->legacyRedirect('dashboard');
    }

    /**
     * Logout: drop the auth session keys and return to the login page.
     * Ported 1:1 from the legacy unset_userdata list — `email` was never set
     * (a legacy no-op, kept for fidelity) and `id_user` deliberately
     * survives, exactly as in CI3: the session cookie continues and a fresh
     * login reuses it.
     */
    public function logout(): RedirectResponse
    {
        session()->remove(['email', 'access_level', 'name', 'is_logged']);

        return $this->legacyRedirect('auth');
    }
}
