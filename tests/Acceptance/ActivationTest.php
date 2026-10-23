<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Admin activate/deactivate parity (ticket #10): the GET anchors on the User
 * detail page — auth/activateuser/{id} and auth/deactivateuser/{id} — flip
 * `registered`, flash the legacy Indonesian messages, and redirect to
 * dashboard/users where the flash renders.
 *
 * The legacy controller gates nothing on these endpoints: no session, no
 * Access Level — any caller flips the bit (a parity hole deliberately kept;
 * verification is post-migration). This file runs before AuthTest, so every
 * test restores the fixture's registered flags before it ends.
 */
final class ActivationTest extends AcceptanceTestCase
{
    public function testAnAdminActivatingAUserFlashesAndLetsTheUserLogIn(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        // The legacy anchors are GET links — a GET redirect is 307.
        $activate = $this->http('GET', '/auth/activateuser/3');
        $this->assertSame(307, $activate->statusCode);
        $this->assertSame('http://localhost:8000/dashboard/users', $activate->location());

        $users = $this->http('GET', '/dashboard/users');
        $this->assertSame(200, $users->statusCode);
        $this->assertStringContainsString('<div style="color: #00c054">Aktivasi akun berhasil</div>', $users->body);

        // The toggle is real: Ghost (registered = 0 in the seed) logs in.
        $this->http('GET', '/dashboard/logout');
        $login = $this->http('POST', '/auth/login', ['email' => 'ghost@email.com', 'password' => 'ghost1234']);

        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $login->location());
        $this->assertSame(200, $this->http('GET', '/dashboard')->statusCode);

        // Restore the fixture: Ghost back to unactivated, asserting the
        // deactivate flash on the way (it is the only render of it here).
        $this->http('GET', '/dashboard/logout');
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);
        $this->http('GET', '/auth/deactivateuser/3');
        $restored = $this->http('GET', '/dashboard/users');
        $this->assertStringContainsString('<div style="color: #00c054">Deaktivasi akun berhasil</div>', $restored->body);
    }

    public function testAnAdminDeactivatingAUserFlashesAndBlocksLogin(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        $deactivate = $this->http('GET', '/auth/deactivateuser/2');
        $this->assertSame(307, $deactivate->statusCode);
        $this->assertSame('http://localhost:8000/dashboard/users', $deactivate->location());

        $users = $this->http('GET', '/dashboard/users');
        $this->assertSame(200, $users->statusCode);
        $this->assertStringContainsString('<div style="color: #00c054">Deaktivasi akun berhasil</div>', $users->body);

        // The toggle is real: Jack's correct password now falls through the
        // unactivated branch — redirected, no flash at all.
        $this->http('GET', '/dashboard/logout');
        $login = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/auth', $login->location());

        $loginPage = $this->http('GET', '/auth');
        $this->assertStringNotContainsString('Incorrect password!', $loginPage->body);
        $this->assertStringNotContainsString('This account is not registered!', $loginPage->body);

        // Restore the fixture: Jack back to activated.
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);
        $this->http('GET', '/auth/activateuser/2');
        $relogin = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame('http://localhost:8000/dashboard', $relogin->location());
    }

    public function testTheActivationEndpointsAreNotGated(): void
    {
        // No session at all: the legacy controller checks nothing — the
        // update runs and the redirect fires. Kept for parity.
        $activate = $this->http('GET', '/auth/activateuser/3');
        $this->assertSame(307, $activate->statusCode);
        $this->assertSame('http://localhost:8000/dashboard/users', $activate->location());

        $login = $this->http('POST', '/auth/login', ['email' => 'ghost@email.com', 'password' => 'ghost1234']);
        $this->assertSame('http://localhost:8000/dashboard', $login->location());

        // Restore: still ungated, Ghost is deactivated again.
        $this->http('GET', '/dashboard/logout');
        $this->http('GET', '/auth/deactivateuser/3');
        $blocked = $this->http('POST', '/auth/login', ['email' => 'ghost@email.com', 'password' => 'ghost1234']);
        $this->assertSame('http://localhost:8000/auth', $blocked->location());
    }
}
