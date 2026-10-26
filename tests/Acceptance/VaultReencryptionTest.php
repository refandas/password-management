<?php

declare(strict_types=1);

namespace Tests\Acceptance;

/**
 * The one-time cutover re-encryption (ticket #15, ADR-0003): every Vault
 * Account credential stored in the legacy CI3 format is decrypted with the
 * ported legacy routine and re-encrypted with the CI4 encrypter — the same
 * per-account key, the same column — and nothing a User can see changes.
 *
 * Two seams, both spec #1's: the CLI boundary for the command itself
 * (dry-run report, the run, the verify gate — asserted on their output and
 * exit codes), and the HTTP boundary for the data-integrity proof (the
 * detail pages render the same plaintexts before and after the run).
 *
 * The tests run in declaration order and the run re-encrypts the whole
 * table, so scripts/run-acceptance.sh gives this file the pristine
 * legacy-seeded fixture first and reseeds before the parity suite. Run
 * from a mid-suite state it still proves the invariant — the dry-run's
 * counts are parsed, never assumed — but at least one legacy-format row
 * must remain, so the files ahead of this one must not re-encrypt them all.
 */
final class VaultReencryptionTest extends AcceptanceTestCase
{
    /**
     * The seed fixture's Vault Accounts (tests/seed/plaintexts.php): Media
     * Type and the plaintexts each detail page must render — the values the
     * fixture generator encrypted in the legacy CI3 format. A row still
     * stored in the legacy format always carries its seed plaintexts (only
     * the generator writes that format; the app's write path re-encrypts
     * with the CI4 encrypter).
     */
    private const SEED = [
        1 => ['Instagram', 'jack.sparrow@seamail.example', 'insta-pass-0123'],
        2 => ['Twitter', 'jackdraws', 'tweet-pass-3f2a'],
        3 => ['Amazon', 'jack@amazon.example', 'amaz0n-pr1me-K7'],
        4 => ['Steam', 'steamjack', 'p2L!ay-L4ter'],
    ];

    public function testTheDryRunReportsThePlanAndWritesNothing(): void
    {
        [$code, $out] = $this->spark('vault:reencrypt --dry-run');

        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString('Dry run — nothing was written.', $out);

        // The plan's shape: how many rows would be re-encrypted, how many
        // need nothing. Parsed, not assumed — the count depends on the
        // fixture state this file starts from.
        [$wouldReencrypt, $alreadyCi4] = $this->planCounts($out);
        $this->assertGreaterThanOrEqual(1, $wouldReencrypt, 'at least one legacy-format Vault Account must remain for the cutover proof');

        // Every planned row is named by id and Media Type — and never by a
        // plaintext: the CLI report must not print credentials.
        foreach ($this->plannedRows($out) as $id => $media) {
            $this->assertSame(self::SEED[$id][0], $media, "account {$id}: planned Media Type");
        }

        foreach (self::SEED as $id => $seed) {
            $this->assertStringNotContainsString($seed[1], $out, "account {$id}: plaintext must not reach the report");
            $this->assertStringNotContainsString($seed[2], $out, "account {$id}: plaintext must not reach the report");
        }

        // The proof the dry run wrote nothing: verify still fails on every
        // row the plan named — they are all still stored legacy.
        [$verifyCode, $verifyOut] = $this->spark('vault:reencrypt --verify');
        $this->assertSame(1, $verifyCode, $verifyOut);
        $this->assertMatchesRegularExpression(sprintf('/^%d of \d+ Vault Account\(s\) failed verification\.$/m', $wouldReencrypt), $verifyOut);
    }

    public function testTheRunReencryptsAndEveryDetailPageKeepsItsPlaintexts(): void
    {
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $before = $this->detailPlaintexts();

        // The plan this run executes, parsed from the dry run.
        [$dryCode, $dryOut] = $this->spark('vault:reencrypt --dry-run');
        $this->assertSame(0, $dryCode, $dryOut);
        [$wouldReencrypt, $alreadyCi4] = $this->planCounts($dryOut);
        $planned = $this->plannedRows($dryOut);

        [$code, $out] = $this->spark('vault:reencrypt');
        $this->assertSame(0, $code, $out);
        $this->assertMatchesRegularExpression(
            sprintf('/^%d Vault Account\(s\) re-encrypted; %d already in CI4 format \(skipped\)\.$/m', $wouldReencrypt, $alreadyCi4),
            $out,
        );

        // Nothing a User can see changed: every detail page renders exactly
        // the plaintexts it rendered before the re-encryption.
        $this->assertSame($before, $this->detailPlaintexts());

        // And the legacy-seeded rows the plan named still show their seed
        // plaintexts — the values the fixture generator encrypted, back
        // through the app's read path after the transformation.
        foreach ($planned as $id => $media) {
            $detail = $this->http('GET', '/account/detail_account/' . $id);

            $this->assertSame(200, $detail->statusCode, "account {$id} ({$media}): status");
            $this->assertStringContainsString(
                sprintf('name="username_email" value="%s"', self::SEED[$id][1]),
                $detail->body,
                "account {$id} ({$media}): decrypted username/email",
            );
            $this->assertStringContainsString(
                sprintf('name="password" value="%s"', self::SEED[$id][2]),
                $detail->body,
                "account {$id} ({$media}): decrypted password",
            );
        }
    }

    public function testVerifyAssertsTheIntegrityOfEveryRowAndARerunIsANoOp(): void
    {
        // Declaration order: the run already happened, so every row is in
        // the CI4 format now. The gate's count is the app's own inventory,
        // counted across the two seams: the rows verify covers must be
        // exactly the rows the list serves.
        $this->http('POST', '/auth/login', ['email' => 'jack@email.com', 'password' => 'user1234']);
        $inventory = count($this->detailPlaintexts());

        [$code, $out] = $this->spark('vault:reencrypt --verify');
        $this->assertSame(0, $code, $out);
        $this->assertStringContainsString(sprintf('All %d Vault Account(s) verified.', $inventory), $out);

        // Idempotent: nothing left in the legacy format, so a second run
        // is a clean no-op — the cutover command can be re-run safely.
        [$rerunCode, $rerunOut] = $this->spark('vault:reencrypt');
        $this->assertSame(0, $rerunCode, $rerunOut);
        $this->assertStringContainsString(sprintf('Nothing to re-encrypt — %d Vault Account(s) already in CI4 format.', $inventory), $rerunOut);

        // And the vault still reads through the seam after the no-op —
        // account 2's seed plaintexts, present in every fixture state.
        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertStringContainsString('<title>Account</title>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-user me-2"></i>jackdraws</label>', $list->body);
        $this->assertStringContainsString('<label><i class="fas fa-unlock-alt me-2"></i>tweet-pass-3f2a</label>', $list->body);
    }

    /**
     * The dry-run report's plan: [would be re-encrypted, already CI4].
     * Parsed, never assumed — the counts depend on the fixture state this
     * file starts from.
     *
     * @return array{0: int, 1: int}
     */
    private function planCounts(string $dryRunOutput): array
    {
        $this->assertSame(1, preg_match('/^(\d+) Vault Account\(s\) would be re-encrypted; (\d+) already in CI4 format\.$/m', $dryRunOutput, $plan), $dryRunOutput);

        return [(int) $plan[1], (int) $plan[2]];
    }

    /**
     * The rows the dry-run report plans to transform: id => Media Type.
     *
     * @return array<int, string>
     */
    private function plannedRows(string $dryRunOutput): array
    {
        preg_match_all('/^\s*Vault Account (\d+)\s+([^:]+): legacy → CI4$/mu', $dryRunOutput, $matches, PREG_SET_ORDER);

        $rows = [];
        foreach ($matches as $match) {
            $rows[(int) $match[1]] = trim($match[2]);
        }

        return $rows;
    }

    /**
     * Every Vault Account the list page offers, with the plaintexts its
     * detail page renders — the read-path inventory, through the seam.
     * The caller must hold Jack's session (his are the only Vault
     * Accounts the list carries).
     *
     * @return array<int, array{0: string, 1: string}> id => [username_email, password]
     */
    private function detailPlaintexts(): array
    {
        $list = $this->http('GET', '/account');
        $this->assertSame(200, $list->statusCode);
        $this->assertGreaterThanOrEqual(1, preg_match_all('#/account/detail_account/(\d+)#', $list->body, $links), 'the list must offer at least one detail link');

        $plaintexts = [];
        foreach (array_unique(array_map(intval(...), $links[1])) as $id) {
            $detail = $this->http('GET', '/account/detail_account/' . $id);
            $this->assertSame(200, $detail->statusCode, "account {$id}: detail status");

            $this->assertSame(1, preg_match('/name="username_email" value="([^"]*)"/', $detail->body, $username), "account {$id}: username/email field");
            $this->assertSame(1, preg_match('/name="password" value="([^"]*)"/', $detail->body, $password), "account {$id}: password field");

            $plaintexts[$id] = [$username[1], $password[1]];
        }

        return $plaintexts;
    }
}
