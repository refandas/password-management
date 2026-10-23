<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Tracer bullet for ticket #8: one end-to-end assertion through the whole
 * path — container, Apache, docroot, CI4 on PHP 8.5.12.
 */
final class HomeTest extends AcceptanceTestCase
{
    public function testTheDefaultRouteAnswersHttp(): void
    {
        $response = $this->http('GET', '/');

        $this->assertSame(200, $response->statusCode);
    }
}
