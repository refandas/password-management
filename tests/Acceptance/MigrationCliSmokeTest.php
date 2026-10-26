<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * Framework migration CLI smoke check (ticket #15): the legacy CLI
 * migration tool — the CLI-only CI3 controller on the pre-migration branch
 * (application/controllers/Migration.php) — is superseded by the framework's
 * own migration CLI (spec #1), and the smoke check proves the successor is
 * alive: the command boots the framework, discovers the runner, and exits
 * cleanly. This is spec #1's justified CLI-level, non-HTTP exception, at
 * the CLI seam (AcceptanceTestCase::spark()).
 *
 * The baseline schema is frozen for the migration (spec #1): no framework
 * migrations exist yet, so `migrate:status` reports exactly that — and the
 * first schema change after the port starts from this proven tool.
 */
final class MigrationCliSmokeTest extends AcceptanceTestCase
{
    public function testTheFrameworkMigrationCliRuns(): void
    {
        [$code, $out] = $this->spark('migrate:status');

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('No migrations were found.', $out);
    }
}
