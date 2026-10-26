<?php

namespace App\Commands;

use App\Libraries\LegacyEncryption;
use App\Models\AccountModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use CodeIgniter\Encryption\Handlers\OpenSSLHandler;
use Config\Encryption as EncryptionConfig;
use Throwable;
use ValueError;

/**
 * The one-time cutover re-encryption (spec #1, ADR-0003).
 *
 * Every Vault Account credential stored in the legacy CI3 format is
 * decrypted with the ported legacy routine and re-encrypted with the CI4
 * encrypter — the same per-account key, the same columns, so only the
 * ciphertext format changes. Runs once, inside the maintenance window,
 * after the final data sync; see docs/cutover-runbook.md.
 *
 * Three modes:
 *   --dry-run   report the planned transformation, write nothing;
 *   (none)      re-encrypt, all-or-nothing in one transaction;
 *   --verify    the post-cutover gate: every credential must decrypt with
 *               the CI4 encrypter under its per-account key.
 *
 * The write pass goes through AccountModel::editAccount() — the app's own
 * write path (ticket #12) — so the re-encrypted rows are exactly what the
 * app itself writes. Rows already in the CI4 format are skipped (the
 * command is idempotent); a row no mode may touch — an empty or corrupt
 * credential, a mixed-format row, an invalid key — aborts the whole run
 * before anything is written, so the cutover gate surfaces damaged data
 * instead of absorbing it.
 */
final class VaultReencrypt extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     *
     * @var string
     */
    protected $group = 'App';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'vault:reencrypt';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'One-time cutover: re-encrypt Vault Account credentials from the legacy CI3 format to the CI4 encrypter (ADR-0003).';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'vault:reencrypt [--dry-run] [--verify]';

    /**
     * the Command's Options
     *
     * @var array<string, string>
     */
    protected $options = [
        '--dry-run' => 'Report the planned transformation without writing anything.',
        '--verify'  => 'Assert every Vault Account decrypts with the CI4 encrypter (the post-cutover integrity gate).',
    ];

    /**
     * Actually execute the command.
     *
     * @param array<array-key, string|null> $params
     */
    public function run(array $params)
    {
        $dryRun = CLI::getOption('dry-run') !== null;
        $verify = CLI::getOption('verify') !== null;

        if ($dryRun && $verify) {
            CLI::error('Pick one mode: --dry-run, --verify, or neither for the re-encryption run.');

            return EXIT_ERROR;
        }

        return $verify ? $this->verify() : $this->transform($dryRun);
    }

    /**
     * Dry-run or the re-encryption run. One scan pass classifies every row
     * first: nothing is written unless every row is readable, so a corrupt
     * row aborts the whole cutover instead of half-migrating the vault.
     */
    private function transform(bool $dryRun): int
    {
        CLI::write('Vault Account re-encryption (ADR-0003): legacy CI3 format → CI4 encrypter, same per-account key', 'yellow');
        CLI::newLine();

        $model = model(AccountModel::class);
        $rows  = $this->scan($model);

        $legacy     = $this->rowsInState($rows, 'legacy');
        $ci4        = $this->rowsInState($rows, 'ci4');
        $unreadable = $this->rowsInState($rows, 'unreadable');

        if ($unreadable !== []) {
            foreach ($unreadable as $row) {
                CLI::error(sprintf('  Vault Account %d  %s: %s', $row['id_account'], $row['media_name'], $row['reason']));
            }
            CLI::error('');
            CLI::error(sprintf('%d Vault Account(s) unreadable — nothing was written. Fix or remove the row(s) before the cutover.', count($unreadable)));

            return EXIT_ERROR;
        }

        if ($legacy === []) {
            CLI::write(sprintf('Nothing to re-encrypt — %d Vault Account(s) already in CI4 format.', count($ci4)));

            return EXIT_SUCCESS;
        }

        if ($dryRun) {
            foreach ($legacy as $row) {
                CLI::write(sprintf('  Vault Account %d  %s: legacy → CI4 (username_email, password)', $row['id_account'], $row['media_name']));
            }
            CLI::newLine();
            CLI::write(sprintf('%d Vault Account(s) would be re-encrypted; %d already in CI4 format.', count($legacy), count($ci4)));
            CLI::write('Dry run — nothing was written.');

            return EXIT_SUCCESS;
        }

        // All-or-nothing: one transaction around every row, so a mid-run
        // failure leaves the vault exactly as it was.
        $db = db_connect();
        $db->transStart();

        try {
            foreach ($legacy as $row) {
                // editAccount() re-encrypts both fields under the row's
                // existing per-account key — the app's own write path.
                $model->editAccount([
                    'id_account'     => $row['id_account'],
                    'username_email' => $row['legacy_username_email'],
                    'password'       => $row['legacy_password'],
                    'key_str'        => $row['key_str'],
                ]);
            }
        } catch (Throwable $e) {
            $db->transRollback();
            CLI::newLine();
            CLI::error('Re-encryption failed — rolled back, nothing was written.');
            CLI::error('  ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if (! $db->transComplete()) {
            CLI::newLine();
            CLI::error('Re-encryption failed — rolled back, nothing was written.');

            return EXIT_ERROR;
        }

        foreach ($legacy as $row) {
            CLI::write(sprintf('  Vault Account %d  %s: re-encrypted', $row['id_account'], $row['media_name']));
        }
        foreach ($ci4 as $row) {
            CLI::write(sprintf('  Vault Account %d  %s: already CI4 (skipped)', $row['id_account'], $row['media_name']));
        }
        CLI::newLine();
        CLI::write(sprintf('%d Vault Account(s) re-encrypted; %d already in CI4 format (skipped).', count($legacy), count($ci4)), 'green');

        return EXIT_SUCCESS;
    }

    /**
     * The post-cutover gate (and the dry-run's no-write proof, in the
     * acceptance suite): a Vault Account passes only when both credentials
     * decrypt with the CI4 encrypter under the row's own key.
     */
    private function verify(): int
    {
        CLI::write('Vault Account integrity check (ADR-0003): every credential must decrypt with the CI4 encrypter under its per-account key', 'yellow');
        CLI::newLine();

        $rows = $this->scan(model(AccountModel::class));

        $failures = 0;
        foreach ($rows as $row) {
            // One stream per report block: failing rows and their summary
            // all go to STDERR, so a piped or logged run keeps their order.
            if ($row['state'] === 'ci4') {
                CLI::write(sprintf('  Vault Account %d  %s: OK', $row['id_account'], $row['media_name']));

                continue;
            }

            $failures++;
            CLI::error(sprintf(
                '  Vault Account %d  %s: %s',
                $row['id_account'],
                $row['media_name'],
                $row['state'] === 'legacy' ? 'still legacy CI3 format' : $row['reason'],
            ));
        }

        if ($failures > 0) {
            CLI::error('');
            CLI::error(sprintf('%d of %d Vault Account(s) failed verification.', $failures, count($rows)));

            return EXIT_ERROR;
        }

        CLI::newLine();

        CLI::write(sprintf('All %d Vault Account(s) verified.', count($rows)), 'green');

        return EXIT_SUCCESS;
    }

    /**
     * Classify every Vault Account row, in id order, by which routine
     * decrypts its credentials:
     *
     *   'legacy'     both fields decrypt with the ported legacy routine —
     *                the pre-cutover state, the rows the run transforms
     *                (their plaintexts ride along for the write pass);
     *   'ci4'        both fields decrypt with the CI4 encrypter —
     *                port-written or already re-encrypted, nothing to do;
     *   'unreadable' a field is empty or authenticates under neither
     *                routine, the two fields read under different
     *                routines, or key_str is not valid hex — the row no
     *                mode may touch.
     *
     * The format split mirrors AccountModel's read fallback (legacy first,
     * CI4 after), which stays private — the command needs per-field,
     * format-aware reads the model doesn't expose.
     *
     * @return list<array<string, int|string|null>>
     */
    private function scan(AccountModel $model): array
    {
        $encrypter = new OpenSSLHandler(config(EncryptionConfig::class));

        $mediaNames = [];
        foreach ($model->getAccountMedia() as $media) {
            $mediaNames[$media['id_media']] = $media['name'];
        }

        $rows = [];
        foreach ($model->orderBy('id_account', 'ASC')->findAll() as $account) {
            $row = [
                'id_account'            => (int) $account['id_account'],
                'media_name'            => $mediaNames[$account['id_media']] ?? ('media ' . $account['id_media']),
                'key_str'               => $account['key_str'],
                'state'                 => 'ci4',
                'reason'                => null,
                'legacy_username_email' => null,
                'legacy_password'       => null,
            ];

            try {
                $rawKey = hex2bin((string) $account['key_str']);
            } catch (ValueError) {
                $row['state']  = 'unreadable';
                $row['reason'] = 'key_str is not valid hex';
                $rows[]        = $row;

                continue;
            }

            $legacyUsername = LegacyEncryption::decrypt($account['username_email'], $rawKey);
            $legacyPassword = LegacyEncryption::decrypt($account['password'], $rawKey);

            if ($legacyUsername !== null && $legacyPassword !== null) {
                $row['state']                 = 'legacy';
                $row['legacy_username_email'] = $legacyUsername;
                $row['legacy_password']       = $legacyPassword;
            } else {
                $ci4Username = $this->ci4Decrypt($encrypter, $account['username_email'], $rawKey);
                $ci4Password = $this->ci4Decrypt($encrypter, $account['password'], $rawKey);

                if ($ci4Username === null || $ci4Password === null) {
                    $row['state']  = 'unreadable';
                    $row['reason'] = $this->unreadableReason($account, $legacyUsername, $legacyPassword, $ci4Username, $ci4Password);
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Why a row no mode may touch, field by field: a field with nothing
     * stored is named empty; a field neither routine decrypts is named
     * corrupt. When every field reads under some routine but not the same
     * one, the row is mixed — neither app ever wrote that shape, so no
     * single transformation applies.
     *
     * @param array<string, int|string|null> $account The raw stored row.
     */
    private function unreadableReason(array $account, ?string $legacyUsername, ?string $legacyPassword, ?string $ci4Username, ?string $ci4Password): string
    {
        $fields = [
            'username_email' => [$account['username_email'], $legacyUsername, $ci4Username],
            'password'       => [$account['password'], $legacyPassword, $ci4Password],
        ];

        $reasons = [];
        foreach ($fields as $name => [$stored, $legacy, $ci4]) {
            if ($stored === null || $stored === '') {
                $reasons[] = "{$name} is empty";
            } elseif ($legacy === null && $ci4 === null) {
                $reasons[] = "{$name} unreadable — neither the legacy routine nor the CI4 encrypter decrypts it";
            }
        }

        if ($reasons !== []) {
            return implode('; ', $reasons);
        }

        return sprintf(
            'mixed formats — username_email is %s, password is %s',
            $ci4Username !== null ? 'CI4' : 'legacy',
            $ci4Password !== null ? 'CI4' : 'legacy',
        );
    }

    /**
     * One credential through the CI4 encrypter as the app configures it,
     * null where it does not authenticate.
     */
    private function ci4Decrypt(OpenSSLHandler $encrypter, ?string $stored, string $rawKey): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            return $encrypter->decrypt($stored, $rawKey);
        } catch (EncryptionException) {
            return null;
        }
    }

    /**
     * @param list<array<string, int|string|null>> $rows
     *
     * @return list<array<string, int|string|null>>
     */
    private function rowsInState(array $rows, string $state): array
    {
        return array_values(array_filter($rows, static fn (array $row): bool => $row['state'] === $state));
    }
}
