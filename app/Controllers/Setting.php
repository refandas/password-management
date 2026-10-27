<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * The settings pages, ported from the CI3 Setting controller
 * (pre-migration): a User editing their own profile, rotating their
 * password, and deactivating or deleting their own User — the profile
 * form, the password change, and the deactivate / delete anchors.
 */
class Setting extends BaseController
{
    /**
     * Show the settings page (legacy index()): the session User's profile
     * form with the password / deactivate / delete anchors. Ported without
     * a gate, exactly as in CI3: the page reads only the session's
     * id_user — never is_logged — so it renders for anyone whose session
     * still carries an id_user (it survives logout; the dashboard/logout
     * quirk) and renders the empty form when it carries none. The parity
     * hole is kept until the post-migration fixes.
     */
    public function index(): string
    {
        $user = model(UserModel::class)->findUserByID(session('id_user'));

        return $this->layoutPage('setting/index', 'Setting', ['user' => $user]);
    }

    /**
     * Store the profile form (legacy update()). Ported without a gate or
     * ownership check, exactly as in CI3: the id comes from the URL, so
     * any caller rewrites any User row — the parity hole kept until the
     * post-migration fixes. Redirects with no flash, like CI3.
     */
    public function update($id_user = null): RedirectResponse
    {
        model(UserModel::class)->updateUser(
            $this->request->getPost('email'),
            $this->request->getPost('name'),
            $id_user,
        );

        return $this->legacyRedirect('setting');
    }

    /**
     * Show the retype-password form (legacy password()). Post-migration
     * fix (#34): the id must be the session User's own — any other id
     * (or a caller with no session id_user) bounces to the settings page
     * with the red ownership flash, so no caller reads another User's
     * form. The own-id no-row case still renders the empty heading and
     * action through the view's null-coalescing guards (the detail
     * page's no-row render, same class).
     */
    public function password($id_user = null): string|RedirectResponse
    {
        if ((string) $id_user !== (string) session('id_user')) {
            session()->setFlashdata('success', '<div style="color: #a71d2a">You can only change your own password.</div>');
            return $this->legacyRedirect('setting');
        }
        $user = model(UserModel::class)->findUserByID($id_user);
        return $this->layoutPage('setting/change_password', 'Change Password', ['user' => $user]);
    }

    /**
     * Store the new password (legacy setPassword()) — the retype form's
     * action, spelled lowercase as the legacy view wrote it (CI3 method
     * names were case-insensitive, so the spelling served).
     * Post-migration fix (#34): the id must be the session User's own —
     * any other id (or a caller with no session id_user) bounces to the
     * settings page with the red ownership flash, and nothing is
     * written. #34 also moves the retype match server-side: password_check
     * is read, and a mismatch bounces with the red flash before any write.
     * Flashes the same success string as the auth reset; the flash fires
     * even when the id matches no row.
     */
    public function setPassword($id_user = null): RedirectResponse
    {
        if ((string) $id_user !== (string) session('id_user')) {
            session()->setFlashdata('success', '<div style="color: #a71d2a">You can only change your own password.</div>');
            return $this->legacyRedirect('setting');
        }
        $password = (string) $this->request->getPost('password');
        $retype   = (string) $this->request->getPost('password_check');
        if ($password !== $retype) {
            session()->setFlashdata('success', '<div style="color: #a71d2a">Passwords do not match!</div>');
            return $this->legacyRedirect('setting');
        }
        model(UserModel::class)->setPassword($password, $id_user);
        session()->setFlashdata('success', '<div style="color: #00c054">Reset password success.</div>');
        return $this->legacyRedirect('setting');
    }

    /**
     * Deactivate the User (legacy deactivate()): registered = 0, the
     * legacy flash, and a redirect to the login page. Ported without a
     * gate or ownership check, exactly as in CI3 — any caller deactivates
     * any User. The session is left untouched, like CI3: the deactivation
     * bites on the next login, not the current one.
     */
    public function deactivate($id_user = null): RedirectResponse
    {
        model(UserModel::class)->disableUser($id_user);

        session()->setFlashdata('success', '<div style="color: #00c054">Account has been deactivated.</div>');

        return $this->legacyRedirect('auth');
    }

    /**
     * Delete the User (legacy delete()): the row goes — their Vault
     * Accounts with it, the schema's ON DELETE CASCADE — the legacy flash
     * (red-keyed, flashed under success, no trailing period), and a
     * redirect to the login page. Ported without a gate or ownership
     * check, exactly as in CI3: any caller deletes any User.
     */
    public function delete($id_user = null): RedirectResponse
    {
        model(UserModel::class)->deleteUser($id_user);

        session()->setFlashdata('success', '<div style="color: #a71d2a">Account has been deleted</div>');

        return $this->legacyRedirect('auth');
    }
}
