<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Forgot/reset password: the email-only reset cycle through an emailed Reset
 * Token (#38). The forgot form POSTs the email to /auth/resetpassword; an
 * existing email sends the reset link to the User's mailbox — Mailpit in this
 * stack, the acceptance mailbox — instead of rendering the set-password
 * form; /auth/setpassword/{token}, keyed by the token and never the User id,
 * stores the hash. Before #38 the segment was the raw id_user and no
 * verification sat between the steps, so anyone who knew an email could
 * reset any User's password — the id-shaped and forged-token tests pin that
 * shut.
 *
 * The fixture's Jack password is rotated and restored within the cycle test
 * — the suite is not re-seeded between tests, only between runs.
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

    public function testAKnownEmailSendsAResetLinkInsteadOfRenderingTheForm(): void
    {
        $this->purgeMailbox();

        $response = $this->http('POST', '/auth/resetpassword', [
            'email' => 'jack@email.com',
        ]);

        $this->assertSame(303, $response->statusCode);
        $this->assertSame('http://localhost:8000/auth/forgotpassword', $response->location());

        // The confirmation page carries the green flash and no set-password
        // form — the User id never reaches the response.
        $forgot = $this->http('GET', '/auth/forgotpassword');
        $this->assertStringContainsString('<div style="color: #00c054">A password reset link has been sent to your email.</div>', $forgot->body);
        $this->assertStringNotContainsString('setpassword/', $forgot->body);

        // The token went to the User's mailbox — the only place it
        // appears: id, expiry, signature. Never a bare id.
        $token = $this->tokenFromMailbox('jack@email.com');
        $this->assertNotNull($token, 'the reset email reached the mailbox');
        $this->assertMatchesRegularExpression('#^[0-9]+\.[0-9]{10}\.[0-9a-f]{64}$#', $token);
    }

    public function testTheEmailedTokenSetsTheNewPassword(): void
    {
        $this->purgeMailbox();
        $this->http('POST', '/auth/resetpassword', ['email' => 'jack@email.com']);
        $token = $this->tokenFromMailbox('jack@email.com');
        $this->assertNotNull($token, 'the reset email reached the mailbox');

        // The link lands on the set-password form, keyed by the token.
        $form = $this->http('GET', '/auth/setpassword/' . $token);
        $this->assertSame(200, $form->statusCode);
        $this->assertStringContainsString('<form action="http://localhost:8000/auth/setpassword/' . $token . '" method="POST"', $form->body);
        $this->assertStringContainsString('Reset Password for <b>Jack</b>', $form->body);

        // Setting the new password flashes and returns to the login page.
        $set = $this->http('POST', '/auth/setpassword/' . $token, [
            'password'       => 'rotated-9876',
            'password_check' => 'rotated-9876',
        ]);
        $this->assertSame(303, $set->statusCode);
        $this->assertSame('http://localhost:8000/auth', $set->location());
        $login = $this->http('GET', '/auth');
        $this->assertStringContainsString('<div style="color: #00c054">Reset password success.</div>', $login->body);

        // The old password is dead: wrong-password flash on the login page.
        $old = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $old->statusCode);
        $this->assertStringContainsString(
            '<div style="color: #a71d2a">Incorrect password!</div>',
            $this->http('GET', '/auth')->body,
        );

        // The new password logs in.
        $new = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'rotated-9876']);
        $this->assertSame(303, $new->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $new->location());
        $this->http('GET', '/dashboard/logout');

        // The token is spent with the write — a replay is refused with the
        // invalid flash and writes nothing (#38: the signature covers the
        // password hash, so the token died with the password it reset).
        $replay = $this->http('POST', '/auth/setpassword/' . $token, [
            'password'       => 'replay-attack',
            'password_check' => 'replay-attack',
        ]);
        $this->assertSame(303, $replay->statusCode);
        $this->assertSame('http://localhost:8000/auth/forgotpassword', $replay->location());
        $forgot = $this->http('GET', '/auth/forgotpassword');
        $this->assertStringContainsString('<div style="color: #a71d2a">The reset link is invalid or expired.</div>', $forgot->body);
        $still = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'rotated-9876']);
        $this->assertSame('http://localhost:8000/dashboard', $still->location());
        $this->http('GET', '/dashboard/logout');

        // Restore the fixture password through a fresh token.
        $this->purgeMailbox();
        $this->http('POST', '/auth/resetpassword', ['email' => 'jack@email.com']);
        $restore = $this->http('POST', '/auth/setpassword/' . $this->tokenFromMailbox('jack@email.com'), [
            'password'       => 'user1234',
            'password_check' => 'user1234',
        ]);
        $this->assertSame(303, $restore->statusCode);
        $again = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame('http://localhost:8000/dashboard', $again->location());
        $this->http('GET', '/dashboard/logout');
    }

    public function testAMismatchedRetypeWritesNothing(): void
    {
        $this->purgeMailbox();
        $this->http('POST', '/auth/resetpassword', ['email' => 'jack@email.com']);
        $token = $this->tokenFromMailbox('jack@email.com');

        $mismatch = $this->http('POST', '/auth/setpassword/' . $token, [
            'password'       => 'typo-password',
            'password_check' => 'typo-passwrod',
        ]);

        $this->assertSame(303, $mismatch->statusCode);
        $this->assertSame('http://localhost:8000/auth/forgotpassword', $mismatch->location());
        $page = $this->http('GET', '/auth/forgotpassword');
        $this->assertStringContainsString('<div style="color: #a71d2a">Passwords do not match!</div>', $page->body);

        // No write: the seed password still logs in — same server-side
        // retype the Setting flow gained in #34.
        $login = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $login->location());
        $this->http('GET', '/dashboard/logout');
    }

    public function testAnIdShapedSegmentIsRefusedAndWritesNothing(): void
    {
        // #38's Risk: pre-fix, the segment was the raw id_user, so knowing
        // an email was enough to reset that User's password. Both verbs on
        // an id-shaped segment are refused with the invalid flash.
        $post = $this->http('POST', '/auth/setpassword/2', [
            'password'       => 'mutiny-on-the-bounty',
            'password_check' => 'mutiny-on-the-bounty',
        ]);
        $this->assertSame(303, $post->statusCode);
        $this->assertSame('http://localhost:8000/auth/forgotpassword', $post->location());

        $get = $this->http('GET', '/auth/setpassword/2');
        $this->assertSame(307, $get->statusCode);
        $this->assertSame('http://localhost:8000/auth/forgotpassword', $get->location());
        $page = $this->http('GET', '/auth/forgotpassword');
        $this->assertStringContainsString('<div style="color: #a71d2a">The reset link is invalid or expired.</div>', $page->body);

        // No write: Jack's seed password still logs in — the forged one
        // would have replaced it.
        $login = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $login->location());
        $this->http('GET', '/dashboard/logout');
    }

    public function testAForgedTokenSignatureIsRefused(): void
    {
        // Right shape, wrong signature — and a far-future expiry, so only
        // the HMAC check can refuse it.
        $forged  = '2.' . (time() + 3600) . '.' . str_repeat('ab', 32);
        $attack  = $this->http('POST', '/auth/setpassword/' . $forged, ['password' => 'forged-token']);
        $this->assertSame(303, $attack->statusCode);
        $this->assertSame('http://localhost:8000/auth/forgotpassword', $attack->location());

        // No write: the seed password still logs in.
        $login = $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $this->assertSame(303, $login->statusCode);
        $this->assertSame('http://localhost:8000/dashboard', $login->location());
        $this->http('GET', '/dashboard/logout');
    }

    /**
     * The acceptance mailbox (Mailpit) this stack sends mail to — the same
     * role the User's inbox plays in a real deployment.
     */
    private function mailboxUrl(): string
    {
        return rtrim((string) (getenv('ACCEPTANCE_MAILPIT_URL') ?: 'http://mailpit:8025'), '/');
    }

    private function purgeMailbox(): void
    {
        file_get_contents($this->mailboxUrl() . '/api/v1/messages', false, stream_context_create([
            'http' => ['method' => 'DELETE'],
        ]));
    }

    /**
     * The Reset Token for this email from its newest message, or null.
     */
    private function tokenFromMailbox(string $email): ?string
    {
        $search = file_get_contents($this->mailboxUrl() . '/api/v1/search?query=' . urlencode('to:"' . $email . '"'));
        if ($search === false) {
            return null;
        }
        $messages = json_decode($search, true)['messages'] ?? [];

        foreach ($messages as $message) {
            $detail = file_get_contents($this->mailboxUrl() . '/api/v1/message/' . $message['ID']);
            if ($detail === false) {
                continue;
            }
            $text = (string) (json_decode($detail, true)['Text'] ?? '');
            if (preg_match('#auth/setpassword/[0-9]+\.[0-9]+\.[0-9a-f]{64}#', $text, $match) === 1) {
                return substr($match[0], strlen('auth/setpassword/'));
            }
        }

        return null;
    }
}
