<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Settings parity (ticket #14): a User editing their own profile, rotating
 * their password, and deactivating or deleting their own User — the
 * profile form, the password change, and the deactivate / delete anchors.
 *
 * The legacy Setting controller gates nothing: index() reads only the
 * session's id_user (never is_logged), and the write methods check no
 * session and no ownership — any caller edits, disables, or deletes any
 * User row. The parity holes are kept until the post-migration fixes
 * (spec #1), pinned by the ungated test below.
 *
 * The tests run in declaration order against the once-seeded database
 * (the runner re-seeds every full run): the profile, password, and
 * deactivate tests restore Jack's seed values before they end, so the
 * fixture this file leaves matches the one it found. Run the file or the
 * suite — not single methods with --filter.
 */
final class SettingTest extends AcceptanceTestCase
{
    public function testTheSettingsPageRendersTheProfileFormForALoggedInUser(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        $page = $this->http('GET', '/setting');
        $this->assertSame(200, $page->statusCode);
        $this->assertStringContainsString('<title>Setting</title>', $page->body);

        // The Bootstrap 5 shell renders, sidebar included (#32): the
        // Setting menu item that leads here.
        $this->assertStringContainsString('<p>Setting</p>', $page->body);

        // The profile form carries the session User's own row: the update
        // action targets its id, prefilled with name and email.
        $this->assertStringContainsString('<h3 class="card-title">YOUR ACCOUNT</h3>', $page->body);
        $this->assertStringContainsString('<form action="http://localhost:8000/setting/update/2" method="POST">', $page->body);
        $this->assertStringContainsString('<input type="text" value="Jack" class="form-control" name="name">', $page->body);
        $this->assertStringContainsString('<input type="email" value="jack@email.com" class="form-control" name="email">', $page->body);
        $this->assertStringContainsString('<button type="submit" class="btn btn-outline-dark form-control">Update Profile</button>', $page->body);

        // The anchors ride the User's own id too: the password form, the
        // deactivate button, and the delete link inside its modal.
        $this->assertStringContainsString('<a href="http://localhost:8000/setting/password/2" class="btn btn-outline-dark form-control">Change Password</a>', $page->body);
        $this->assertStringContainsString('<a href="http://localhost:8000/setting/deactivate/2" class="btn btn-outline-dark form-control col-md-3">Deactivate</a>', $page->body);
        $this->assertStringContainsString('<a href="http://localhost:8000/setting/delete/2" class="btn btn-outline-danger">Delete</a>', $page->body);
    }

    public function testUpdatingTheProfilePersistsAndRendersTheNewValues(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // The profile form posts to the update URL — Post/Redirect/Get back
        // to the settings page, the legacy redirect() rule. The legacy
        // update() flashes nothing; the re-render carries the new row.
        $update = $this->http('POST', '/setting/update/2', [
            'name'  => 'Jack Sparrow',
            'email' => 'captainjack@seamail.example',
        ]);

        $this->assertSame(303, $update->statusCode);
        $this->assertSame('http://localhost:8000/setting', $update->location());

        $page = $this->http('GET', '/setting');
        $this->assertSame(200, $page->statusCode);
        $this->assertStringContainsString('<input type="text" value="Jack Sparrow" class="form-control" name="name">', $page->body);
        $this->assertStringContainsString('<input type="email" value="captainjack@seamail.example" class="form-control" name="email">', $page->body);

        // And flashes nothing: the card header's flash slot stays empty —
        // no success div anywhere on the re-render.
        $this->assertStringNotContainsString('color: #00c054', $page->body);

        // The persist is real: the new email authenticates the same row.
        $this->http('GET', '/dashboard/logout');
        $relogin = $this->http('POST', '/auth/login', ['email' => 'captainjack@seamail.example', 'password' => 'user1234']);
        $this->assertSame(303, $relogin->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $relogin->location());

        // Restore the fixture: Jack's seed name and email.
        $this->http('POST', '/setting/update/2', ['name' => 'Jack', 'email' => 'jack@email.com']);
        $restored = $this->http('GET', '/setting');
        $this->assertStringContainsString('<input type="text" value="Jack" class="form-control" name="name">', $restored->body);
        $this->assertStringContainsString('<input type="email" value="jack@email.com" class="form-control" name="email">', $restored->body);
    }

    public function testTheChangePasswordPageRendersWithItsCapitalSBreadcrumbLink(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // The Change Password anchor opens the retype form — password plus
        // password_check; the match is client-side jQuery only, the legacy
        // controller never read the second field.
        $form = $this->http('GET', '/setting/password/2');
        $this->assertSame(200, $form->statusCode);
        $this->assertStringContainsString('<title>Change Password</title>', $form->body);
        $this->assertStringContainsString('<h5 class="mb-3">Reset Password for <b>Jack</b></h5>', $form->body);
        $this->assertStringContainsString('<form action="http://localhost:8000/setting/setpassword/2" method="POST" id="login" class="mx-auto p-5">', $form->body);
        $this->assertStringContainsString('name="password" id="password"', $form->body);
        $this->assertStringContainsString('name="password_check" id="password2"', $form->body);

        // The page's breadcrumb spells the settings URL with a capital S —
        // CI3 matched controller names case-insensitively (ucfirst), so
        // both spellings served; CI4 matches exactly, so the alias is
        // registered.
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="http://localhost:8000/Setting">Setting</a></li>', $form->body);

        $capital = $this->http('GET', '/Setting');
        $this->assertSame(200, $capital->statusCode);
        $this->assertStringContainsString('<title>Setting</title>', $capital->body);
    }

    public function testChangingThePasswordRoundTripsTheLogin(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // The retype form posts to the lowercase setpassword URL — CI3
        // method names were case-insensitive, so the legacy view's
        // spelling served; CI4 matches exactly, so the route carries it.
        // Post/Redirect/Get to the settings page, where the legacy flash
        // renders in the card header.
        $set = $this->http('POST', '/setting/setpassword/2', [
            'password'       => 'the-trusted-helm',
            'password_check' => 'the-trusted-helm',
        ]);

        $this->assertSame(303, $set->statusCode);
        $this->assertSame('http://localhost:8000/setting', $set->location());

        $page = $this->http('GET', '/setting');
        $this->assertSame(200, $page->statusCode);
        $this->assertStringContainsString('<div style="color: #00c054">Reset password success.</div>', $page->body);

        // The flash is a flash: gone on the next request.
        $again = $this->http('GET', '/setting');
        $this->assertStringNotContainsString('Reset password success.', $again->body);

        // The round-trip: the new password logs in…
        $this->http('GET', '/dashboard/logout');
        $newLogin = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'the-trusted-helm']);
        $this->assertSame(303, $newLogin->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $newLogin->location());

        // …and the old one fails with the legacy wrong-password flash.
        $this->http('GET', '/dashboard/logout');
        $oldLogin = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $oldLogin->statusCode);
        $this->assertSame('http://localhost:8000/auth', $oldLogin->location());

        $loginPage = $this->http('GET', '/auth');
        $this->assertStringContainsString('<div style="color: #a71d2a">Incorrect password!</div>', $loginPage->body);

        // Restore the fixture: the seed password back on Jack's row.
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'the-trusted-helm']);
        $this->http('POST', '/setting/setpassword/2', ['password' => 'user1234', 'password_check' => 'user1234']);
        $restored = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame('http://localhost:8000/dashboard', $restored->location());
    }

    public function testDeactivatingYourOwnUserBlocksTheNextLogin(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        // The Deactivate anchor is a GET link — a GET redirect is 307, to
        // the login page where the legacy flash renders.
        $deactivate = $this->http('GET', '/setting/deactivate/2');
        $this->assertSame(307, $deactivate->statusCode);
        $this->assertSame('http://localhost:8000/auth', $deactivate->location());

        $loginPage = $this->http('GET', '/auth');
        $this->assertSame(200, $loginPage->statusCode);
        $this->assertStringContainsString('<div style="color: #00c054">Account has been deactivated.</div>', $loginPage->body);

        // The block is real: Jack's correct password falls through the
        // unactivated branch — redirected, no flash at all.
        $login = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/auth', $login->location());

        $page = $this->http('GET', '/auth');
        $this->assertStringNotContainsString('Incorrect password!', $page->body);
        $this->assertStringNotContainsString('This account is not registered!', $page->body);

        // Restore the fixture: Jack back to activated through the ungated
        // auth activation anchor (pinned by ActivationTest).
        $this->http('GET', '/auth/activateuser/2');
        $restored = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame('http://localhost:8000/dashboard', $restored->location());
    }

    public function testTheSettingsPageStillRendersAfterLogout(): void
    {
        // The id_user-survives-logout quirk composed with the ungated
        // index(): dashboard/logout drops email, access_level, name, and
        // is_logged but keeps id_user (the ported unset list), and index()
        // reads only id_user — so the profile keeps rendering, logged out.
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->http('GET', '/dashboard/logout');

        $afterLogout = $this->http('GET', '/setting');
        $this->assertSame(200, $afterLogout->statusCode);
        $this->assertStringContainsString('<input type="text" value="Jack" class="form-control" name="name">', $afterLogout->body);
        $this->assertStringContainsString('<input type="email" value="jack@email.com" class="form-control" name="email">', $afterLogout->body);
    }

    public function testDeletingYourOwnUserRemovesTheUserRow(): void
    {
        // A throwaway User deletes themselves.
        [$email, $id] = $this->throwawayUserToDelete();

        // The modal's Delete link is a GET link — a GET redirect is 307,
        // to the login page. The legacy flash is red-keyed but flashed
        // under success, byte-identical — no trailing period.
        $delete = $this->http('GET', '/setting/delete/' . $id);
        $this->assertSame(307, $delete->statusCode);
        $this->assertSame('http://localhost:8000/auth', $delete->location());

        $loginPage = $this->http('GET', '/auth');
        $this->assertSame(200, $loginPage->statusCode);
        $this->assertStringContainsString('<div style="color: #a71d2a">Account has been deleted</div>', $loginPage->body);

        // The User is gone: the email now answers the not-registered
        // flash on the next login attempt.
        $login = $this->http('POST', '/auth/login', ['email' => $email, 'password' => 'castaway1234']);
        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/auth', $login->location());

        $page = $this->http('GET', '/auth');
        $this->assertStringContainsString('<div style="color: #a71d2a">This account is not registered!</div>', $page->body);
    }

    public function testTheSettingsPagesAreUngatedAsInLegacy(): void
    {
        // index() reads only the session's id_user — never is_logged — so
        // an anonymous visitor gets the page too: the empty-form render
        // (the null-User render, the detail page's no-row case, same
        // class). The retype form is ungated the same way: any caller
        // gets any User's page.
        $page = $this->http('GET', '/setting');
        $this->assertSame(200, $page->statusCode);
        $this->assertStringContainsString('<title>Setting</title>', $page->body);
        $this->assertStringContainsString('<form action="http://localhost:8000/setting/update/" method="POST">', $page->body);
        $this->assertStringContainsString('<input type="text" value="" class="form-control" name="name">', $page->body);
        $this->assertStringContainsString('<input type="email" value="" class="form-control" name="email">', $page->body);

        $form = $this->http('GET', '/setting/password/2');
        $this->assertSame(200, $form->statusCode);
        $this->assertStringContainsString('<h5 class="mb-3">Reset Password for <b>Jack</b></h5>', $form->body);

        // The write methods check no session at all: an anonymous POST
        // rewrites any User row — Jack's profile here, with no flash.
        $rewrite = $this->http('POST', '/setting/update/2', [
            'name'  => 'Rewritten',
            'email' => 'rewritten@nowhere.example',
        ]);
        $this->assertSame(303, $rewrite->statusCode);
        $this->assertSame('http://localhost:8000/setting', $rewrite->location());

        $this->http('POST', '/auth/login', ['email' => 'rewritten@nowhere.example', 'password' => 'user1234']);
        $rewritten = $this->http('GET', '/setting');
        $this->assertStringContainsString('<input type="text" value="Rewritten" class="form-control" name="name">', $rewritten->body);

        // Restore Jack's seed profile before the next scenario.
        $this->http('POST', '/setting/update/2', ['name' => 'Jack', 'email' => 'jack@email.com']);

        // Anonymous deactivate: any caller flips any User's bit — the
        // same 307 and flash as the logged-in one, and the block bites.
        $deactivate = $this->http('GET', '/setting/deactivate/2');
        $this->assertSame(307, $deactivate->statusCode);
        $this->assertSame('http://localhost:8000/auth', $deactivate->location());

        $blocked = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame('http://localhost:8000/auth', $blocked->location());

        // Restore through the ungated activation anchor (ActivationTest).
        $this->http('GET', '/auth/activateuser/2');

        // Anonymous delete: any caller removes any User — a throwaway.
        [$email, $id] = $this->throwawayUserToDelete();

        $this->http('GET', '/dashboard/logout');
        $delete = $this->http('GET', '/setting/delete/' . $id);
        $this->assertSame(307, $delete->statusCode);
        $this->assertSame('http://localhost:8000/auth', $delete->location());

        $gone = $this->http('POST', '/auth/login', ['email' => $email, 'password' => 'castaway1234']);
        $this->assertSame('http://localhost:8000/auth', $gone->location());
    }

    /**
     * Register a throwaway User, log in as them, and read their delete
     * anchor's id off the settings page (the anchor carries the session
     * User's own id — not assumed, since RegistrationTest's leftover row
     * may have taken the next one). The email is unique per run: a
     * leftover throwaway from a failed earlier run would otherwise be the
     * row the login matches (both stacks read the first row for an email)
     * and the delete would remove it instead of this run's User.
     *
     * @return array{0: string, 1: string} The throwaway's email and id.
     */
    private function throwawayUserToDelete(): array
    {
        $email = 'castaway-' . uniqid('run-', false) . '@email.com';

        $this->http('POST', '/auth/create', [
            'name'     => 'Castaway',
            'email'    => $email,
            'password' => 'castaway1234',
        ]);
        $this->http('POST', '/auth/login', ['email' => $email, 'password' => 'castaway1234']);

        $settings = $this->http('GET', '/setting');
        $this->assertSame(1, preg_match('#setting/delete/(\d+)#', $settings->body, $anchors));

        return [$email, $anchors[1]];
    }
}
