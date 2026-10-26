<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use PHPUnit\Framework\TestCase;

/**
 * Drives the running app over plain HTTP.
 *
 * This is the one pre-agreed seam (spec #1): the HTTP boundary of the running
 * app in docker-compose. The suite never boots the framework in-process and
 * never cuts into the codebase — it issues plain HTTP requests with a cookie
 * jar and asserts external behavior only: status codes, redirects, rendered
 * content, flash message text, decrypted values on the detail page.
 *
 * Point it at another stack (e.g. the legacy oracle) with ACCEPTANCE_BASE_URL.
 *
 * spark() is the companion seam for CLI tools (spec #1's justified CLI-level
 * exception): the cutover re-encryption command and the framework migration
 * CLI are proven as subprocesses, asserting their reports and exit codes.
 */
abstract class AcceptanceTestCase extends TestCase
{
    private string $baseUrl;

    /** Cookie jar for this test: the session survives across requests within it. */
    private string $cookieJar;

    protected function setUp(): void
    {
        $this->baseUrl = rtrim((string) (getenv('ACCEPTANCE_BASE_URL') ?: 'http://localhost:8000'), '/');
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'acceptance-jar-');
    }

    protected function tearDown(): void
    {
        @unlink($this->cookieJar);
    }

    /**
     * Perform one HTTP request against the running app.
     *
     * Redirects are never followed automatically: a redirect is an outcome to
     * assert (status + Location), and the test then requests the target itself.
     */
    protected function http(string $method, string $path, array $form = []): HttpResponse
    {
        $handle = curl_init($this->baseUrl . $path);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_TIMEOUT => 10,
        ]);
        if ($form !== []) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($form));
        }

        $raw = curl_exec($handle);
        if ($raw === false) {
            $this->fail(sprintf('HTTP %s %s%s failed: %s', $method, $this->baseUrl, $path, curl_error($handle)));
        }

        $response = HttpResponse::fromRaw(
            (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
            (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE),
            (string) $raw,
        );
        curl_close($handle);

        return $response;
    }

    /**
     * Run one `php spark` command in a subprocess and return its exit code
     * and combined output — the CLI boundary, never in-process.
     *
     * Always runs this checkout's framework CLI, whatever ACCEPTANCE_BASE_URL
     * points the HTTP seam at: an oracle comparison drives the legacy stack
     * over HTTP while the CLI commands still target this stack and its own
     * database.
     *
     * Skipped when the framework is not present (vendor/) — the acceptance
     * seam needs the web container anyway.
     *
     * @param string $command A literal spark invocation, e.g. 'vault:reencrypt --dry-run'.
     *
     * @return array{0: int, 1: string} Exit code and output, ANSI color codes stripped.
     */
    protected function spark(string $command): array
    {
        $root = dirname(__DIR__, 2);

        if (! is_file($root . '/spark') || ! is_dir($root . '/vendor')) {
            $this->markTestSkipped('the framework CLI needs vendor/ — run the suite inside the web container');
        }

        exec('php ' . escapeshellarg($root . '/spark') . ' ' . $command . ' 2>&1', $lines, $code);

        return [$code, preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $lines))];
    }
}
