<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Auth core parity (ticket #9): the login page and the POST /auth/login
 * outcomes — success, the three error states, and their byte-identical
 * legacy flash messages.
 *
 * Every expectation here is oracle-derived: the legacy app (pre-migration
 * branch) answers the same request the same way, including redirect status
 * codes (CI3's redirect() sends 303 after POST, 307 after GET on HTTP/1.1).
 */
final class AuthTest extends AcceptanceTestCase
{
    public function testTheDefaultRouteRendersTheLoginPage(): void
    {
        $response = $this->http('GET', '/');

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('<title>Login - Password Management</title>', $response->body);
        $this->assertStringContainsString('<form action="http://localhost:8000/auth/login" method="POST"', $response->body);
    }

    public function testLoginWithAnUnregisteredEmailFlashesNotRegistered(): void
    {
        $response = $this->http('POST', '/auth/login', [
            'email'    => 'nobody@nowhere.example',
            'password' => 'whatever1234',
        ]);

        $this->assertSame(303, $response->statusCode);
        $this->assertSame('http://localhost:8000/auth', $response->location());

        $login = $this->http('GET', '/auth');
        $this->assertSame(200, $login->statusCode);
        $this->assertStringContainsString(
            '<div style="color: #a71d2a">This account is not registered!</div>',
            $login->body,
        );

        // Flash data survives exactly one request — the next render is clean.
        $again = $this->http('GET', '/auth');
        $this->assertStringNotContainsString('This account is not registered!', $again->body);
    }

    public function testLoginWithAWrongPasswordFlashesIncorrectPassword(): void
    {
        $response = $this->http('POST', '/auth/login', [
            'email'    => 'jack@email.com',
            'password' => 'not-the-password',
        ]);

        $this->assertSame(303, $response->statusCode);
        $this->assertSame('http://localhost:8000/auth', $response->location());

        $login = $this->http('GET', '/auth');
        $this->assertSame(200, $login->statusCode);
        $this->assertStringContainsString(
            '<div style="color: #a71d2a">Incorrect password!</div>',
            $login->body,
        );
    }

    public function testLoginWithAnUnactivatedAccountRedirectsWithoutAnyFlash(): void
    {
        $response = $this->http('POST', '/auth/login', [
            'email'    => 'ghost@email.com',
            'password' => 'ghost1234',
        ]);

        $this->assertSame(303, $response->statusCode);
        $this->assertSame('http://localhost:8000/auth', $response->location());

        // The legacy fall-through: a correct password on an unactivated
        // account sets no flash message at all.
        $login = $this->http('GET', '/auth');
        $this->assertSame(200, $login->statusCode);
        $this->assertStringNotContainsString('This account is not registered!', $login->body);
        $this->assertStringNotContainsString('Incorrect password!', $login->body);
    }
}
