<?php
declare(strict_types=1);
namespace Tests\Acceptance;
/**
 * UI shell regression (ticket #32): every page renders styled from
 * self-hosted assets.
 *
 * The CI4 port moved the legacy AdminLTE markup but never landed
 * public/assets/: every base_url('assets/...') reference 404'd and every
 * page pulled jQuery, Google Fonts and ionicons from CDNs — invisible to
 * the suite because no test ever resolved an asset. These tests pin that
 * failure mode shut:
 *
 *   - every stylesheet/script a page references answers 200
 *   - pages reference no third-party origin: vault pages stay off CDNs
 *   - the payload carries no jQuery (the shell is Bootstrap 5 + vanilla JS)
 */
final class ShellTest extends AcceptanceTestCase
{
    public function testTheAuthShellResolvesEveryAssetItReferences(): void
    {
        $page = $this->http('GET', '/auth');
        $this->assertSame(200, $page->statusCode);
        $this->assertEveryReferencedAssetResolves($page->body);
        $this->assertNoThirdPartyOrigin($page->body);
    }

    public function testVaultPagesResolveEveryAssetAndStayOffThirdPartyOrigins(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $page = $this->http('GET', '/account');
        $this->assertSame(200, $page->statusCode);
        // The Bootstrap 5 offcanvas sidebar renders — the owner-kept
        // layout decision of #32.
        $this->assertStringContainsString('id="appSidebar"', $page->body);
        $this->assertStringContainsString('data-bs-target="#appSidebar"', $page->body);
        $this->assertEveryReferencedAssetResolves($page->body);
        $this->assertNoThirdPartyOrigin($page->body);
        $this->assertStringNotContainsString('jquery', strtolower($page->body));
    }

    public function testTheDashboardLandingPageAssemblesTheShell(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $page = $this->http('GET', '/dashboard');
        $this->assertSame(200, $page->statusCode);
        // #35: the landing page assembles the full shell, not just the
        // content wrapper — the sidebar reaches the page users see first.
        $this->assertStringContainsString('id="appSidebar"', $page->body);
        $this->assertStringContainsString('data-bs-target="#appSidebar"', $page->body);
        $this->assertStringContainsString('class="app-footer"', $page->body);
        $this->assertEveryReferencedAssetResolves($page->body);
        $this->assertNoThirdPartyOrigin($page->body);
        $this->assertStringNotContainsString('jquery', strtolower($page->body));
    }

    public function testTheChangePasswordPageCarriesNoJquery(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $setting = $this->http('GET', '/setting');
        $this->assertSame(200, $setting->statusCode);
        // The change-password link carries the user id this test needs.
        $this->assertSame(1, preg_match('#setting/password/(\d+)#', $setting->body, $m));
        $page = $this->http('GET', '/setting/password/' . $m[1]);
        $this->assertSame(200, $page->statusCode);
        $this->assertStringNotContainsString('jquery', strtolower($page->body));
        $this->assertEveryReferencedAssetResolves($page->body);
        $this->assertNoThirdPartyOrigin($page->body);
    }

    public function testRegisterPageCarriesNoJquery(): void
    {
        $page = $this->http('GET', '/auth/register');
        $this->assertSame(200, $page->statusCode);
        $this->assertStringNotContainsString('jquery', strtolower($page->body));
        $this->assertEveryReferencedAssetResolves($page->body);
    }

    /**
     * @return list<string> href/src values that reference a stylesheet or script
     */
    private function assetUrls(string $body): array
    {
        preg_match_all('/(?:href|src)="([^"]+)"/', $body, $matches);
        return array_values(array_filter(
            $matches[1],
            static fn (string $url): bool => (bool) preg_match('~\.css|\.js|/assets/~', $url),
        ));
    }

    private function assertEveryReferencedAssetResolves(string $body): void
    {
        foreach ($this->assetUrls($body) as $url) {
            $path = (string) parse_url($url, PHP_URL_PATH);
            $asset = $this->http('GET', $path);
            $this->assertSame(200, $asset->statusCode, "asset no longer resolves: {$path}");
        }
    }

    private function assertNoThirdPartyOrigin(string $body): void
    {
        foreach ($this->assetUrls($body) as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === null) {
                continue; // same-origin relative reference
            }
            $this->assertContains(
                $host,
                ['localhost', '127.0.0.1'],
                "third-party asset origin in payload: {$url}",
            );
        }
    }
}
