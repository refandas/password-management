<?php

namespace App\Controllers;

use App\Models\AccountModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Vault Account pages, ported from the CI3 Account controller
 * (pre-migration): the read path (ticket #11) and the write path —
 * add, edit, delete (ticket #12).
 */
class Account extends BaseController
{
    /**
     * Show the logged-in User's Vault Accounts joined with their Media
     * Types, credentials decrypted (legacy index()).
     */
    public function index(): string|RedirectResponse
    {
        if (session('is_logged') == 1) {
            $accounts = model(AccountModel::class)->getAccountByUserID(session('id_user'));

            return $this->layoutPage('account/index', 'Account', ['accounts' => $accounts]);
        }

        return $this->legacyRedirect('auth');
    }

    /**
     * Show one Vault Account's decrypted credentials (legacy
     * detail_account()). Ported without a gate, exactly as in CI3: the
     * legacy controller checked no session and matched no ownership — any
     * caller, anonymous included, could open any account's decrypted detail
     * page. The parity hole is kept until the post-migration fixes.
     *
     * The view's null-coalescing guards cover the no-row case: CI3 read a
     * missing row's fields as null and rendered the empty form, where an
     * unguarded read on PHP 8.5 would throw.
     */
    public function detailAccount($id_account = null): string
    {
        $account = model(AccountModel::class)->getAccountByID($id_account, true);

        return $this->layoutPage('account/detail_account', 'Detail Account', ['account' => $account]);
    }

    /**
     * Show the add-account form (legacy add_account()). Ported without an
     * else branch, exactly as in CI3: the gate rendered nothing when it
     * failed — an empty 200, not a redirect.
     */
    public function addAccount(): string
    {
        if (session('is_logged') == 1) {
            $accountMedia = model(AccountModel::class)->getAccountMedia();

            return $this->layoutPage('account/add_account', 'Add Account', ['account_media' => $accountMedia]);
        }

        return '';
    }

    /**
     * Store a new Vault Account (legacy add()). The legacy controller read
     * the session User without gating the write: an anonymous add carries a
     * null id_user into the insert, which the NOT NULL column rejects — no
     * gate is added here.
     */
    public function add(): RedirectResponse
    {
        model(AccountModel::class)->addAccount([
            'id_user'        => session('id_user'),
            'username_email' => $this->request->getPost('username_email'),
            'password'       => $this->request->getPost('password'),
            'id_media'       => $this->request->getPost('media'),
        ]);

        return $this->legacyRedirect('account');
    }

    /**
     * Re-encrypt a Vault Account's credentials (legacy update()): the
     * POSTed values replace the stored ones under the row's existing
     * per-account key; every other column stays as stored. Ungated in CI3
     * — no gate added.
     */
    public function update($id_account = null): RedirectResponse
    {
        $account = model(AccountModel::class)->getAccountByID($id_account);

        if ($account !== null) {
            $account['username_email'] = (string) $this->request->getPost('username_email');
            $account['password']       = (string) $this->request->getPost('password');

            model(AccountModel::class)->editAccount($account);
        }

        return $this->legacyRedirect('account');
    }

    /**
     * Delete a Vault Account (legacy delete()), byte-identical flash
     * included. The legacy model's delete path carried the accidental
     * assignment — a dead write with no observable effect, ported as the
     * clean delete it always behaved as (spec #1; ADR-0003 tracks the
     * quirk on its post-migration list). Ungated in CI3 — no gate added.
     */
    public function delete($id_account = null): RedirectResponse
    {
        model(AccountModel::class)->deleteAccount($id_account);

        session()->setFlashdata('success', '<div style="color: #00c054">Delete account successfully.</div>');

        return $this->legacyRedirect('account');
    }
}
