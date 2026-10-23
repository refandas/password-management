<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Dashboard gating parity (ticket #9): the stub dashboard, the session gate,
 * the Access Level gate on the Users routes, and logout.
 */
final class DashboardTest extends AcceptanceTestCase
{
    public function testALoggedInUserLandsOnTheDashboard(): void
    {
        $login = $this->http('POST', '/auth/login', [
            'email'    => 'jack@email.com',
            'password' => 'user1234',
        ]);

        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $login->location());

        $dashboard = $this->http('GET', '/dashboard');
        $this->assertSame(200, $dashboard->statusCode);
        $this->assertStringContainsString('<title>Dashboard</title>', $dashboard->body);

        // The session `name` key from login renders on the dashboard.
        $this->assertStringContainsString('HELLO JACK', $dashboard->body);
    }

    public function testUnauthenticatedRequestsToGatedRoutesRedirectToLogin(): void
    {
        $dashboard = $this->http('GET', '/dashboard');
        $this->assertSame(307, $dashboard->statusCode);
        $this->assertSame('http://localhost:8000/auth', $dashboard->location());

        // The Users routes bounce to the dashboard first — the legacy
        // two-hop chain to the login page.
        $users = $this->http('GET', '/dashboard/users');
        $this->assertSame(307, $users->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $users->location());

        $hop = $this->http('GET', '/dashboard');
        $this->assertSame(307, $hop->statusCode);
        $this->assertSame('http://localhost:8000/auth', $hop->location());

        $user = $this->http('GET', '/dashboard/user/2');
        $this->assertSame(307, $user->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $user->location());

        // Legacy auto-routing accepted any segment: the gate answers, not a
        // 404, so non-numeric ids bounce the same way.
        $nonNumeric = $this->http('GET', '/dashboard/user/abc');
        $this->assertSame(307, $nonNumeric->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $nonNumeric->location());
    }

    public function testAnAdminReachesTheUsersPages(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        $users = $this->http('GET', '/dashboard/users');
        $this->assertSame(200, $users->statusCode);
        $this->assertStringContainsString('<title>Users</title>', $users->body);

        $user = $this->http('GET', '/dashboard/user/2');
        $this->assertSame(200, $user->statusCode);
        $this->assertStringContainsString('<title>User Detail</title>', $user->body);
    }

    public function testARegularUserIsBouncedFromTheUsersRoutesToTheDashboard(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        $users = $this->http('GET', '/dashboard/users');
        $this->assertSame(307, $users->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $users->location());

        // Still logged in, so the dashboard itself renders.
        $dashboard = $this->http('GET', '/dashboard');
        $this->assertSame(200, $dashboard->statusCode);
        $this->assertStringContainsString('HELLO JACK', $dashboard->body);
    }

    public function testLogoutEndsTheSession(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(200, $this->http('GET', '/dashboard')->statusCode);

        $logout = $this->http('GET', '/dashboard/logout');
        $this->assertSame(307, $logout->statusCode);
        $this->assertSame('http://localhost:8000/auth', $logout->location());

        // The session is gone: the dashboard now bounces to the login page.
        $after = $this->http('GET', '/dashboard');
        $this->assertSame(307, $after->statusCode);
        $this->assertSame('http://localhost:8000/auth', $after->location());

        // The legacy logout keeps the session cookie alive (id_user even
        // survives) — a fresh login on the same session just works.
        $relogin = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $relogin->statusCode);
        $this->assertSame(200, $this->http('GET', '/dashboard')->statusCode);
    }
}
