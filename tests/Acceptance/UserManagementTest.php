<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * User management + Access Level gating parity (ticket #13): the Admin
 * User's Users list and User detail pages, and the sidebar menu visibility
 * the Access Level drives.
 */
final class UserManagementTest extends AcceptanceTestCase
{
    public function testAnAdminSeesAllUsersSplitByActivationStatus(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        $list = $this->http('GET', '/dashboard/users');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<title>Users</title>', $list->body);

        // The Bootstrap 5 shell renders, sidebar included (#32): the
        // Access Level 1 menu shows USER and hides ACCOUNT.
        $this->assertStringContainsString('<p>USER</p>', $list->body);
        $this->assertStringNotContainsString('<p>ACCOUNT</p>', $list->body);

        // The breadcrumb rides the content header, as in the legacy page.
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="http://localhost:8000/dashboard">Home</a></li>', $list->body);
        $this->assertStringContainsString('<li class="breadcrumb-item active">Users</li>', $list->body);

        // User Active card: registered Users who are not the Admin — Jack.
        $this->assertStringContainsString('<h5 class="card-header">User Active</h5>', $list->body);
        $this->assertStringContainsString('<a href="http://localhost:8000/dashboard/user/2">Jack</a>', $list->body);

        // User Not Active card: the unactivated Ghost.
        $this->assertStringContainsString('<h5 class="card-header">User Not Active</h5>', $list->body);
        $this->assertStringContainsString('<a href="http://localhost:8000/dashboard/user/3">Ghost</a>', $list->body);

        // The Admin's own row never lists (the access_level != 1 filter) —
        // no name cell, no link to their own detail page.
        $this->assertStringNotContainsString('<td><a href="http://localhost:8000/dashboard/user/1">', $list->body);
    }

    public function testARegularUsersMenuHidesTheUserManagementEntry(): void
    {
        // The Access Level 2 mirror of the gating above: Jack's sidebar
        // shows ACCOUNT and hides USER, so the entry the Users pages hang
        // from is invisible below Access Level 1.
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);

        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<p>ACCOUNT</p>', $list->body);
        $this->assertStringNotContainsString('<p>USER</p>', $list->body);
    }

    public function testTheDetailPageOfARegisteredUserOffersDeactivation(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        $detail = $this->http('GET', '/dashboard/user/2');
        $this->assertSame(200, $detail->statusCode);
        $this->assertStringContainsString('<title>User Detail</title>', $detail->body);

        // Breadcrumb: Home > Users > Jack.
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="http://localhost:8000/dashboard/users">Users</a></li>', $detail->body);
        $this->assertStringContainsString('<li class="breadcrumb-item active">Jack</li>', $detail->body);

        // Jack is registered: the card carries the deactivate action — the
        // legacy page's Indonesian status line, byte-identical.
        $this->assertStringContainsString('<h5 class="card-header">Jack</h5>', $detail->body);
        $this->assertStringContainsString('<p>Status akun aktif, klik tombol berikut untuk mengaktifkan</p>', $detail->body);
        $this->assertStringContainsString('<a href="http://localhost:8000/auth/deactivateuser/2" class="btn btn-danger">Nonaktifkan User</a>', $detail->body);
    }

    public function testTheDetailPageOfAnUnactivatedUserOffersActivation(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        // Ghost (registered = 0): the mirror branch — activation button.
        $detail = $this->http('GET', '/dashboard/user/3');
        $this->assertSame(200, $detail->statusCode);
        $this->assertStringContainsString('<li class="breadcrumb-item active">Ghost</li>', $detail->body);
        $this->assertStringContainsString('<p>Status akun nonaktif, klik tombol berikut untuk menonaktifkan</p>', $detail->body);
        $this->assertStringContainsString('<a href="http://localhost:8000/auth/activateuser/3" class="btn btn-success">Aktifkan User</a>', $detail->body);
        $this->assertStringNotContainsString('Nonaktifkan User', $detail->body);
    }

    public function testAnUnknownUserIdStillRendersTheActivationBranch(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'admin@email.com', 'password' => 'admin1234']);

        // CI3 read the missing row's fields as null and rendered the empty
        // page (PHP 7.3's silent null field read): empty name, registered
        // null — the activation branch. The port keeps the same observable
        // render through null-coalescing guards instead of the read that
        // throws on PHP 8.5.
        $missing = $this->http('GET', '/dashboard/user/999');
        $this->assertSame(200, $missing->statusCode);
        $this->assertStringContainsString('<title>User Detail</title>', $missing->body);
        $this->assertStringContainsString('<li class="breadcrumb-item active"></li>', $missing->body);
        $this->assertStringContainsString('<h5 class="card-header"></h5>', $missing->body);
        $this->assertStringContainsString('<p>Status akun nonaktif, klik tombol berikut untuk menonaktifkan</p>', $missing->body);
        $this->assertStringContainsString('<a href="http://localhost:8000/auth/activateuser/" class="btn btn-success">Aktifkan User</a>', $missing->body);
    }
}
