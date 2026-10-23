<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Registration parity (ticket #10): the register form and the POST
 * /auth/create outcome — the legacy activation-required flash, byte-identical.
 *
 * The message overstates, exactly as in CI3: registration inserts
 * registered = 1, so the new User logs in immediately — there is no
 * activation step. Ported as-is (spec #1: fixes are post-migration).
 *
 * The created User row survives to the end of the run (no delete endpoint
 * ports before ticket 19); run-acceptance.sh re-seeds between runs, and this
 * file runs last in the suite.
 */
final class RegistrationTest extends AcceptanceTestCase
{
    public function testTheRegisterPageRendersTheLegacyForm(): void
    {
        $response = $this->http('GET', '/auth/register');

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('<title>Register Now!</title>', $response->body);
        $this->assertStringContainsString('<form action="http://localhost:8000/auth/create" method="POST"', $response->body);
        // The legacy typo renders today as it did on PHP 7.3.
        $this->assertStringContainsString('Accept license aggrement.', $response->body);
        $this->assertStringContainsString('Same password', $response->body);
    }

    public function testRegistrationCreatesAUserAndFlashesTheActivationMessage(): void
    {
        $create = $this->http('POST', '/auth/create', [
            'name'     => 'New Visitor',
            'email'    => 'visitor@email.com',
            'password' => 'visitor1234',
        ]);

        $this->assertSame(303, $create->statusCode);
        $this->assertSame('http://localhost:8000/auth', $create->location());

        $login = $this->http('GET', '/auth');
        $this->assertSame(200, $login->statusCode);
        $this->assertStringContainsString(
            '<div style="color: #00c054">Registration success! Please activate account before login.</div>',
            $login->body,
        );

        // Flash data survives exactly one request — the next render is clean.
        $again = $this->http('GET', '/auth');
        $this->assertStringNotContainsString('Registration success!', $again->body);

        // The quirk behind the message: registered = 1 is inserted, so the
        // fresh account reaches the dashboard on the first login.
        $first = $this->http('POST', '/auth/login', [
            'email'    => 'visitor@email.com',
            'password' => 'visitor1234',
        ]);

        $this->assertSame(303, $first->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $first->location());

        $dashboard = $this->http('GET', '/dashboard');
        $this->assertSame(200, $dashboard->statusCode);
        $this->assertStringContainsString('HELLO NEW VISITOR', $dashboard->body);
    }
}
