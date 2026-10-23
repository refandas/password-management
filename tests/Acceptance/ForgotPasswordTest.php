<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Forgot/reset password parity (ticket #10): the email-only reset cycle,
 * unverified — exactly the legacy weakness, deliberately kept (verification
 * is deferred to post-migration by spec #1).
 *
 * Flow as ported: the forgot form POSTs the email to /auth/resetpassword;
 * an existing email renders the set-password form directly (a POST that
 * answers a page, 200), then /auth/setpassword/{id} stores the hash and
 * flashes. The fixture's Jack password is rotated back at the end — the
 * suite is not re-seeded between tests, only between runs.
 */
final class ForgotPasswordTest extends AcceptanceTestCase
{
    public function testTheForgotPasswordPageRendersTheLegacyForm(): void
    {
        $response = $this->http('GET', '/auth/forgotpassword');

        $this->assertSame(200, $response->statusCode);
        $this->assertStringContainsString('<title>Forgot Password</title>', $response->body);
        $this->assertStringContainsString('<form action="http://localhost:8000/auth/resetpassword" method="POST"', $response->body);
        $this->assertStringContainsString('Check Account', $response->body);
    }

    public function testResettingAnUnknownEmailFlashesEmailNotFound(): void
    {
        $response = $this->http('POST', '/auth/resetpassword', [
            'email' => 'nobody@nowhere.example',
        ]);

        $this->assertSame(303, $response->statusCode);
        $this->assertSame('http://localhost:8000/auth/forgotpassword', $response->location());

        $forgot = $this->http('GET', '/auth/forgotpassword');
        $this->assertSame(200, $forgot->statusCode);
        $this->assertStringContainsString('<div style="color: #a71d2a">Email not found</div>', $forgot->body);

        // Flash data survives exactly one request.
        $again = $this->http('GET', '/auth/forgotpassword');
        $this->assertStringNotContainsString('Email not found', $again->body);
    }

    public function testTheForgotResetCycleChangesThePassword(): void
    {
        // The email alone yields the reset form — no verification step.
        $form = $this->http('POST', '/auth/resetpassword', [
            'email' => 'jack@email.com',
        ]);

        $this->assertSame(200, $form->statusCode);
        $this->assertStringContainsString('<title>Reset Password</title>', $form->body);
        $this->assertStringContainsString('Reset Password for <b>Jack</b>', $form->body);
        $this->assertStringContainsString('<form action="http://localhost:8000/auth/setpassword/2" method="POST"', $form->body);

        // Setting the new password flashes and returns to the login page.
        $set = $this->http('POST', '/auth/setpassword/2', [
            'password' => 'rotated-9876',
        ]);

        $this->assertSame(303, $set->statusCode);
        $this->assertSame('http://localhost:8000/auth', $set->location());

        $login = $this->http('GET', '/auth');
        $this->assertSame(200, $login->statusCode);
        $this->assertStringContainsString('<div style="color: #00c054">Reset password success.</div>', $login->body);

        // The old password is dead: wrong-password flash on the login page.
        $old = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $old->statusCode);
        $this->assertSame('http://localhost:8000/auth', $old->location());
        $this->assertStringContainsString(
            '<div style="color: #a71d2a">Incorrect password!</div>',
            $this->http('GET', '/auth')->body,
        );

        // The new password logs in.
        $new = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'rotated-9876']);
        $this->assertSame(303, $new->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $new->location());

        // Restore the fixture password through the same flow.
        $this->http('GET', '/dashboard/logout');
        $this->http('POST', '/auth/resetpassword', ['email' => 'jack@email.com']);
        $restore = $this->http('POST', '/auth/setpassword/2', ['password' => 'user1234']);
        $this->assertSame(303, $restore->statusCode);

        $again = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $again->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $again->location());
    }
}
