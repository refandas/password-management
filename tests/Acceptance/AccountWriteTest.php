<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Vault Account write path parity (ticket #12): the add form, add, edit,
 * delete — each verified through the list and detail pages, the same seam
 * the read path (ticket #11) proved.
 *
 * The gates and quirks are the legacy controller's, ported as-is: add and
 * add_account read the session but add_account renders an empty page (no
 * else branch in CI3), update and delete check nothing at all. The parity
 * holes stay until the post-migration fixes (spec #1).
 *
 * One CI3 quirk is deliberately not ported: bare /account/delete 404s —
 * the last test pins that divergence (issue #30).
 *
 * The tests run in declaration order against the once-seeded database
 * (the runner re-seeds every full run): the add test's row feeds the edit
 * and delete tests, and the ungated test consumes rows nothing after it
 * reads. Run the file or the suite — not single methods with --filter.
 */
final class AccountWriteTest extends AcceptanceTestCase
{
    public function testTheAddAccountFormPageRendersForALoggedInUser(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        $form = $this->http('GET', '/account/add_account');
        $this->assertSame(200, $form->statusCode);
        $this->assertStringContainsString('<title>Add Account</title>', $form->body);

        // The form posts to the legacy add URL.
        $this->assertStringContainsString('<form action="http://localhost:8000/account/add" method="POST">', $form->body);
        $this->assertStringContainsString('name="username_email"', $form->body);
        $this->assertStringContainsString('name="password"', $form->body);

        // The Media Type select lists all 14 Media Types in the order MySQL
        // returns them under the legacy getAccountMedia() ordering —
        // name ASC (both stacks share the collation, so the order agrees).
        $this->assertSame(14, substr_count($form->body, '<option value='));

        $ordered = [
            '<option value="12">Amazon</option>',
            '<option value="11">American Express</option>',
            '<option value="13">Apple</option>',
            '<option value="3">Facebook</option>',
            '<option value="4">Google</option>',
            '<option value="1">Instagram</option>',
            '<option value="8">Line</option>',
            '<option value="10">Mastercard</option>',
            '<option value="5">Medium</option>',
            '<option value="6">Quora</option>',
            '<option value="14">Steam</option>',
            '<option value="7">Trello</option>',
            '<option value="2">Twitter</option>',
            '<option value="9">Visa</option>',
        ];

        $position = 0;
        foreach ($ordered as $option) {
            $next = strpos($form->body, $option, $position);
            $this->assertNotFalse($next, $option);
            $this->assertGreaterThan($position, $next, "{$option} must come after the previous option");
            $position = $next;
        }
    }

    public function testTheAddAccountFormPageRendersEmptyForAnonymousVisitors(): void
    {
        // The legacy gate had no else branch: not logged in meant the
        // controller rendered nothing — a 200 with no page furniture, not
        // a redirect. (The port's answer carries only the CI4 debug
        // toolbar's scripts — the suite's known noise class.)
        $form = $this->http('GET', '/account/add_account');
        $this->assertSame(200, $form->statusCode);
        $this->assertStringNotContainsString('<title>', $form->body);
        $this->assertStringNotContainsString('ADD ACCOUNTS', $form->body);
        $this->assertStringNotContainsString('<option value=', $form->body);
    }

    public function testAddingAVaultAccountStoresItAndTheDetailPageShowsExactlyWhatWasEntered(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        $add = $this->http('POST', '/account/add', [
            'username_email' => 'jack.sails@seamail.example',
            'password'       => 'br1ng-me-that-horizon',
            'media'          => '5',
        ]);

        // Post/Redirect/Get to the list, the legacy redirect() rule.
        $this->assertSame(303, $add->statusCode);
        $this->assertSame('http://localhost:8000/account', $add->location());

        // The new row's detail page shows exactly what was entered: the
        // credential was stored encrypted and decrypts back to the input.
        $detail = $this->http('GET', '/account/detail_account/5');
        $this->assertSame(200, $detail->statusCode);
        $this->assertStringContainsString('<title>Detail Account</title>', $detail->body);
        $this->assertStringContainsString('name="username_email" value="jack.sails@seamail.example"', $detail->body);
        $this->assertStringContainsString('name="password" value="br1ng-me-that-horizon"', $detail->body);

        // And the list carries the new card — Medium, from the Media Type
        // the form posted.
        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<h5 class="card-header"><i class="fab fa-medium me-2"></i>Medium</h5>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-user me-2"></i>jack.sails@seamail.example</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-unlock-alt me-2"></i>br1ng-me-that-horizon</label>', $list->body);
    }

    public function testEditingAVaultAccountReEncryptsTheStoredCredential(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // Edit a legacy-format row (id 1, seeded CI3 ciphertext)…
        $edit = $this->http('POST', '/account/update/1', [
            'username_email' => 'jack.sparrow.cursed@seamail.example',
            'password'       => 'why-is-the-rum-gone',
        ]);

        $this->assertSame(303, $edit->statusCode);
        $this->assertSame('http://localhost:8000/account', $edit->location());

        $detail = $this->http('GET', '/account/detail_account/1');
        $this->assertSame(200, $detail->statusCode);
        $this->assertStringContainsString('name="username_email" value="jack.sparrow.cursed@seamail.example"', $detail->body);
        $this->assertStringContainsString('name="password" value="why-is-the-rum-gone"', $detail->body);

        // …and a port-written row (id 5, the account the add test stored).
        // Both re-encrypt under their existing key and read back.
        $this->http('POST', '/account/update/5', [
            'username_email' => 'jack.sails@seamail.example',
            'password'       => 'the-black-pearl-sails',
        ]);

        $detail = $this->http('GET', '/account/detail_account/5');
        $this->assertSame(200, $detail->statusCode);
        $this->assertStringContainsString('name="username_email" value="jack.sails@seamail.example"', $detail->body);
        $this->assertStringContainsString('name="password" value="the-black-pearl-sails"', $detail->body);

        // The list reflects the edit, and the edit changed only the
        // credentials — the card keeps its Media Type and the old
        // plaintexts are gone.
        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<label><i class="fas fa-user me-2"></i>jack.sparrow.cursed@seamail.example</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-unlock-alt me-2"></i>why-is-the-rum-gone</label>', $list->body);
        $this->assertStringContainsString('<h5 class="card-header"><i class="fab fa-instagram me-2"></i>Instagram</h5>', $list->body);
        $this->assertStringNotContainsString('insta-pass-0123', $list->body);
    }

    public function testDeletingAVaultAccountRemovesItAndShowsTheLegacySuccessFlash(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // The Delete button is a link on the detail page. The delete
        // response itself is not asserted: on the oracle the accidental
        // assignment's "Illegal string offset" warning starts output
        // before the redirect header, answering a broken 200 under its
        // dev-mode display_errors — the same noise class ticket #11
        // excluded. Production legacy redirected; the clean port keeps
        // that. Parity lives in the effects below, which both stacks
        // agree on.
        $delete = $this->http('GET', '/account/delete/5');
        $this->assertContains($delete->statusCode, [200, 307]);

        // The flash is the legacy string, byte for byte, on the next page.
        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<div style="color: #00c054">Delete account successfully.</div>', $list->body);

        // The entry is gone: no card for the deleted account…
        $this->assertStringNotContainsString('jack.sails@seamail.example', $list->body);
        $this->assertStringNotContainsString('<h5 class="card-header"><i class="fab fa-medium me-2"></i>Medium</h5>', $list->body);

        // …and its detail page renders the empty form (the missing-row
        // render ticket #11 pinned).
        $detail = $this->http('GET', '/account/detail_account/5');
        $this->assertSame(200, $detail->statusCode);
        $this->assertStringContainsString('name="username_email" value=""', $detail->body);
        $this->assertStringContainsString('name="password" value=""', $detail->body);

        // The flash is a flash: gone on the next request.
        $again = $this->http('GET', '/account');
        $this->assertStringNotContainsString('Delete account successfully.', $again->body);
    }

    public function testTheWritePathsAreUngatedAsInLegacy(): void
    {
        // The CI3 write methods checked no session (add read the session
        // User but gated nothing). Anonymous add carries a null id_user
        // into the insert and the NOT NULL column rejects it — a database
        // error page on both stacks, and no row.
        $add = $this->http('POST', '/account/add', [
            'username_email' => 'anon@nowhere.example',
            'password'       => 'anon-pass',
            'media'          => '3',
        ]);
        $this->assertSame(500, $add->statusCode);

        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $list = $this->http('GET', '/account');
        $this->assertStringNotContainsString('anon@nowhere.example', $list->body);

        // Anonymous update and delete need no session at all — they act on
        // any Vault Account (the parity hole the ungated detail page
        // opened, kept until the post-migration fixes).
        $update = $this->http('POST', '/account/update/3', [
            'username_email' => 'anon-rewrote@nowhere.example',
            'password'       => 'anon-pass',
        ]);
        $this->assertSame(303, $update->statusCode);
        $this->assertSame('http://localhost:8000/account', $update->location());

        $detail = $this->http('GET', '/account/detail_account/3');
        $this->assertSame(200, $detail->statusCode);
        $this->assertStringContainsString('name="username_email" value="anon-rewrote@nowhere.example"', $detail->body);
        $this->assertStringContainsString('name="password" value="anon-pass"', $detail->body);

        $this->http('GET', '/account/delete/4');
        $after = $this->http('GET', '/account');
        $this->assertSame(200, $after->statusCode);
        $this->assertStringNotContainsString('<h5 class="card-header"><i class="fab fa-steam me-2"></i>Steam</h5>', $after->body);
    }

    public function testDeleteWithoutAnIdIsA404(): void
    {
        // CI3 auto-routing answered the bare URL with delete(null): the
        // where matched no row, the flash still said "Delete account
        // successfully.", and the list followed. The defined route requires
        // the segment, so both verbs 404 — the deliberate divergence
        // (issue #30) pinned here.
        $this->assertSame(404, $this->http('GET', '/account/delete')->statusCode);
        $this->assertSame(404, $this->http('POST', '/account/delete')->statusCode);
    }
}
