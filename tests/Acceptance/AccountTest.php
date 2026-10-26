<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Vault Account read path parity (ticket #11): the list page and the detail
 * page, joined with Media Types, credentials decrypted with the ported
 * legacy-compatible routine (AES-256-CTR, per-account key — the format in
 * docs/testing/acceptance-suite.md "Legacy encryption format").
 *
 * The seed fixture stores every credential in the legacy CI3 format, so each
 * plaintext assertion below is the decryption routine proven end-to-end
 * through the HTTP seam: a port that diverges from CI3's format by one byte
 * renders garbage or nothing where these expect the seed plaintexts.
 */
final class AccountTest extends AcceptanceTestCase
{
    public function testARegularUserSeesTheirVaultAccountsListedWithMediaTypes(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<title>Account</title>', $list->body);

        // The Bootstrap 5 shell renders, sidebar included (#32): the
        // Access Level 2 menu item that leads here.
        $this->assertStringContainsString('<p>ACCOUNT</p>', $list->body);

        // One card per Vault Account: Media Type icon and name from the
        // join, username/email and password decrypted on the list — the
        // legacy getAccountByUserID() decrypted every row it listed.
        $this->assertStringContainsString('<h5 class="card-header"><i class="fab fa-instagram mr-2"></i>Instagram</h5>', $list->body);
        $this->assertStringContainsString('<h5 class="card-header"><i class="fab fa-twitter mr-2"></i>Twitter</h5>', $list->body);
        $this->assertStringContainsString('<h5 class="card-header"><i class="fab fa-amazon mr-2"></i>Amazon</h5>', $list->body);
        $this->assertStringContainsString('<h5 class="card-header"><i class="fab fa-steam mr-2"></i>Steam</h5>', $list->body);

        $this->assertStringContainsString('<label><i class="fas fa-user mr-2"></i>jack.sparrow@seamail.example</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-user mr-2"></i>jackdraws</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-user mr-2"></i>jack@amazon.example</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-user mr-2"></i>steamjack</label>', $list->body);

        $this->assertStringContainsString('<label><i class="fas fa-unlock-alt mr-2"></i>insta-pass-0123</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-unlock-alt mr-2"></i>tweet-pass-3f2a</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-unlock-alt mr-2"></i>amaz0n-pr1me-K7</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-unlock-alt mr-2"></i>p2L!ay-L4ter</label>', $list->body);

        // The detail link rides on each card.
        $this->assertStringContainsString('<a href="http://localhost:8000/account/detail_account/1" class="btn btn-outline-dark">Action</a>', $list->body);

        // The join carries no ORDER BY, so the cards render in the legacy
        // row order — account id order for the seeded rows.
        $instagram = strpos($list->body, 'fab fa-instagram');
        $twitter   = strpos($list->body, 'fab fa-twitter');
        $amazon    = strpos($list->body, 'fab fa-amazon');
        $steam     = strpos($list->body, 'fab fa-steam');

        $this->assertGreaterThan($instagram, $twitter);
        $this->assertGreaterThan($twitter, $amazon);
        $this->assertGreaterThan($amazon, $steam);
    }

    public function testUnauthenticatedRequestsToTheListRedirectToLogin(): void
    {
        $list = $this->http('GET', '/account');
        $this->assertSame(307, $list->statusCode);
        $this->assertSame('http://localhost:8000/auth', $list->location());

        // account/index is the sidebar's href — CI3 auto-routing served the
        // list there too, so the gate answers there as well.
        $sidebarHref = $this->http('GET', '/account/index');
        $this->assertSame(307, $sidebarHref->statusCode);
        $this->assertSame('http://localhost:8000/auth', $sidebarHref->location());
    }

    public function testTheDetailPageShowsTheSeedPlaintextsExactly(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // Every seeded Vault Account, with its known plaintexts — the
        // decryption routine proven against each legacy-encrypted row.
        $seed = [
            1 => ['jack.sparrow@seamail.example', 'insta-pass-0123'],
            2 => ['jackdraws', 'tweet-pass-3f2a'],
            3 => ['jack@amazon.example', 'amaz0n-pr1me-K7'],
            4 => ['steamjack', 'p2L!ay-L4ter'],
        ];

        foreach ($seed as $id_account => $plaintexts) {
            $detail = $this->http('GET', '/account/detail_account/' . $id_account);

            $this->assertSame(200, $detail->statusCode, "account {$id_account}: status");
            $this->assertStringContainsString('<title>Detail Account</title>', $detail->body, "account {$id_account}: title");
            $this->assertStringContainsString(
                sprintf('<form action="http://localhost:8000/account/update/%d" method="POST">', $id_account),
                $detail->body,
                "account {$id_account}: edit form action",
            );
            $this->assertStringContainsString(
                sprintf('name="username_email" value="%s"', $plaintexts[0]),
                $detail->body,
                "account {$id_account}: decrypted username/email",
            );
            $this->assertStringContainsString(
                sprintf('name="password" value="%s"', $plaintexts[1]),
                $detail->body,
                "account {$id_account}: decrypted password",
            );
        }
    }

    public function testTheListShowsOnlyTheLoggedInUsersVaultAccounts(): void
    {
        // Every seeded Vault Account belongs to Jack; the Admin User owns none.
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<title>Account</title>', $list->body);

        // No account cards, and none of Jack's decrypted credentials leak
        // onto the Admin's list.
        $this->assertStringNotContainsString('<h5 class="card-header"><i class="fab fa-', $list->body);
        $this->assertStringNotContainsString('jack.sparrow@seamail.example', $list->body);
        $this->assertStringNotContainsString('insta-pass-0123', $list->body);
    }

    public function testTheDetailPageIsUngatedAsInLegacy(): void
    {
        // The parity hole, pinned: CI3's detail_account() checked no session
        // and matched no ownership, so an anonymous caller — or any other
        // User — can read any Vault Account's decrypted credentials. Ported
        // as-is until the post-migration fixes (spec #1).
        $anonymous = $this->http('GET', '/account/detail_account/1');
        $this->assertSame(200, $anonymous->statusCode);
        $this->assertStringContainsString('<title>Detail Account</title>', $anonymous->body);
        $this->assertStringContainsString('name="username_email" value="jack.sparrow@seamail.example"', $anonymous->body);
        $this->assertStringContainsString('name="password" value="insta-pass-0123"', $anonymous->body);

        // Cross-user too: the Admin User opens Jack's Vault Account.
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);
        $crossUser = $this->http('GET', '/account/detail_account/2');
        $this->assertSame(200, $crossUser->statusCode);
        $this->assertStringContainsString('name="username_email" value="jackdraws"', $crossUser->body);
        $this->assertStringContainsString('name="password" value="tweet-pass-3f2a"', $crossUser->body);
    }

    public function testAnUnknownAccountIdStillRendersTheEmptyDetailForm(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // CI3 read the missing row's fields as null and rendered the empty
        // form; the port keeps the same observable render (200, empty
        // values) instead of the unguarded read, which throws on PHP 8.5.
        $missing = $this->http('GET', '/account/detail_account/999');
        $this->assertSame(200, $missing->statusCode);
        $this->assertStringContainsString('<title>Detail Account</title>', $missing->body);
        $this->assertStringContainsString('name="username_email" value=""', $missing->body);
        $this->assertStringContainsString('name="password" value=""', $missing->body);
    }
}
