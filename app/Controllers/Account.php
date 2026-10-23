<?php

namespace App\Controllers;

use App\Models\AccountModel;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Vault Account pages, ported from the CI3 Account controller
 * (pre-migration). The write methods (add, add_account, update, delete)
 * port with ticket #12; their URLs 404 until then.
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
}
