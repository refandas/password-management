# PHP & CodeIgniter Migration Research — 2026-10-22

**Question:** What is the migration path from this app's current versions (PHP 7.3 runtime, CodeIgniter 3.1.10) to the latest PHP and a current framework version, including deprecated functions and breaking changes in the codebase?

This file feeds `/grill-with-docs`. Factual claims cite primary sources; assessments are marked as such.

## Current state (audited from the repo)

| Component | Version | Evidence |
| --- | --- | --- |
| Framework | CodeIgniter **3.1.10** (Jan 2019) | `system/core/CodeIgniter.php:58`; [release feed](https://github.com/bcit-ci/CodeIgniter/releases.atom) |
| PHP runtime | **7.3** (Apache image) | `Dockerfile:1` (`FROM php:7.3-apache`) |
| Composer floor | `php >=7.1.0`, phpunit `4.* || 5.*` | `composer.json:14,21` |
| DB driver | mysqli | `docker-php-ext-install mysqli` (`Dockerfile:3`) |
| App size | 56 PHP files in `application/` + `index.php` | local scan |

## Finding 1 — Target PHP: **8.5.12** (decided 2026-10-22)

- Newest stable is **PHP 8.5.12, released 21 Oct 2026**. (Confirmed by the maintainer 2026-10-22; the `php:8.5.12-apache` image tag is verified present on Docker Hub. The [php.net releases](https://www.php.net/releases/) page showed 8.5.11 / 24 Sep 2026 at fetch time — patch-level stale.)
- Support: 8.5 active until 31 Dec 2027 / security until 2029; 8.4 active until 31 Dec 2026 / security 2028; 8.3 security until 2027; **8.2 security ends 31 Dec 2026**; 8.0 and 8.1 are EOL ([supported versions](https://www.php.net/supported-versions)).
- php.net currently shows a banner about critical RCE vulnerabilities — running an EOL-era 7.3 in production is the strongest argument for this migration.
- **Decision:** target **PHP 8.5.12** (`php:8.5.12-apache`). 8.4 is not the target.

## Finding 2 — Language-level changes 7.3 → 8.5 that matter for *this* codebase

Headline items per official migration guide, filtered to what legacy CI3-era apps actually hit:

- **PHP 8.0 is the big cliff** ([incompatible changes](https://www.php.net/manual/en/migration80.incompatible.php)): `each()`, `create_function()`, `money_format()`, `get_magic_quotes_*()` **removed**; curly-brace offsets `$str{0}` removed; `0 == "foo"` now false (string-to-number comparison change — affects `if ($x == 0)` on form input); many notices/warnings became `TypeError`/`Error` (undefined array keys now warn; invalid `count()` throws); `.` vs `+` precedence changed; `@` no longer silences fatals; PDO defaults to exception error mode.
- **7.4 deprecations that since became removals** ([source](https://www.php.net/manual/en/migration74.deprecated.php)): curly-brace offsets, `(real)` cast, reversed `implode()` args, `array_key_exists()` on objects.
- **PHP 8.1** ([deprecations](https://www.php.net/manual/en/migration81.deprecated.php)): passing `null` to non-nullable internal-function params now deprecated — the most common source of log noise in older code; `strftime()`/`gmstrftime()` deprecated; MySQLi deprecations.
- **PHP 8.2** ([deprecations](https://www.php.net/manual/en/migration82.deprecated.php)): **dynamic properties deprecated** (`$obj->undeclared = x`) unless `#[\AllowDynamicProperties]` — CI3's own `system/` code leans on dynamic properties heavily, so this lands on the framework, not on us; `${var}` string interpolation deprecated.
- **PHP 8.3** ([incompatible](https://www.php.net/manual/en/migration83.incompatible.php)): minor for web apps — `range()` validation, negative array index behavior, `DateException` hierarchy.
- **PHP 8.4** ([deprecations](https://www.php.net/manual/en/migration84.deprecated.php)): **implicitly nullable parameters** (`function f(T $x = null)`) deprecated — flagged by the guide itself as the top legacy-code impact; MySQLi `ping()`/`kill()`/`refresh()` deprecated.
- **PHP 8.5** ([incompatible](https://www.php.net/manual/en/migration85.incompatible.php), [deprecations](https://www.php.net/manual/en/migration85.deprecated.php)): `PDO::MYSQL_ATTR_*` and other driver constants deprecated in favour of `Pdo\Mysql::…`; `mysqli_execute()` alias deprecated; `curl_close()`/`imagedestroy()` no-ops; new warnings on int/NAN casts.

## Finding 3 — CodeIgniter 3 is a dead end for "latest PHP"

- CI3 is in maintenance mode, "receiving mostly just security updates"; README targets PHP 5.6+ and points new work to CI4 ([bcit-ci/CodeIgniter](https://github.com/bcit-ci/CodeIgniter)).
- The last CI3 release is **3.1.13, 3 Mar 2022** ([release feed](https://github.com/bcit-ci/CodeIgniter/releases.atom)) — no release has ever been tested against PHP 8.3/8.4/8.5. The CI3 user guide states no upper PHP ceiling ([requirements](https://codeigniter.com/userguide3/general/requirements.html)), but absence of a ceiling is not support: running 3.1.13 on PHP 8.5 is unverified territory. *(Assessment: bumping 3.1.10 → 3.1.13 gets four years of fixes but still leaves the framework unmaintained beyond that.)*

## Finding 4 — CodeIgniter 4 target

- Current version **4.7.5**, requires **PHP 8.2+** with `intl` and `mbstring` mandatory ([CI4 requirements](https://codeigniter.com/user_guide/intro/requirements.html)) — compatible with the PHP 8.5.12 target.
- Docker implication: base image becomes `php:8.5.12-apache` **plus** `docker-php-ext-install intl` (needs `libicu-dev`) and `mbstring`.

## Finding 5 — CI3 → CI4 is officially a rewrite, not an upgrade

From the official ["Upgrading from 3.x to 4.x" guide](https://codeigniter.com/user_guide/installation/upgrade_4xx.html):

- "There is no 12-step checklist for upgrading" — you start a **fresh CI4 project** and port components in.
- **No automated upgrade path exists.**
- Structural changes: everything namespaced; `application/` → `app/`; docroot becomes `public/` (Apache vhost + `.htaccess` move); superobject and magic properties gone, replaced by Services/Factories; routing explicit by default; hooks → Events; several libraries removed (CAPTCHA, FTP, Cart, XML-RPC, Zip); `$this->db` → `$db`; `$this->load->view()` → `echo view()`; sessions rewritten (files in `writable/`); per-library upgrade guides provided.

## Codebase audit (local scan, `application/` + `index.php`)

The application code is unusually clean at the PHP-language level:

| Pattern | Hits | Notes |
| --- | --- | --- |
| `create_function` / `each()` / `strftime` / `money_format` / `get_magic_quotes` / `mcrypt` / `ereg` / `split(` / curly-brace offsets / `=& new` | **0** | no removed-function usage |
| `mysql_` (non-mysqli) | 1 | comment only — `application/config/database.php:37` |
| `$this->load->` | **69** | CI3 idiom — this is the CI4 porting surface |
| `$this->db->` | **35** | ditto (models + migrations) |

*(Assessment: PHP-level breakage risk concentrates in CI3's own `system/` code — dynamic properties (8.2), implicitly nullable params (8.4) — which we cannot patch maintainably on an unmaintained framework. That argues the framework move is the real migration; the app code mostly rides along.)*

## Verdict

Two coherent paths, one rejected:

- **A. Bump only:** CI 3.1.13 + newest PHP that runs warning-free. Cheap, but CI3 is security-fixes-only with nothing tested past 2022 — contradicts the goal of "latest" anything. *Rejected as the end goal; useful only as an interim step.*
- **B. CI3 → CI4 4.7.x on PHP 8.5.12:** satisfies both goals at once; rewrite-level effort per Finding 5, but the app is small (56 files, ~100 CI3-idiom call sites) and has near-zero removed-function debt.
- **C. Rebuild on Laravel/Symfony:** strictly more work than B for the same PHP outcome; only worth it if there are framework grievances beyond PHP version.

## Open decisions (for `/grill-with-docs`)

1. ~~PHP 8.5 vs 8.4~~ — **decided 2026-10-22: PHP 8.5.12** (`php:8.5.12-apache`).
2. Path B vs C — is staying on CodeIgniter itself a requirement?
3. Big-bang (fresh CI4 skeleton, port everything, cut over) vs strangler (CI4 app serving, CI3 mounted per-route)?
4. Interim step A (3.1.13 + PHP 8.2 in Docker) before/during the rewrite, or straight to the target?
5. Testing strategy: `composer.json` pins phpunit 4/5 (EOL-era) — modern PHPUnit requires current PHP; tests must be rebuilt as part of the migration. TDD seam for the port?
6. DB layer: stay mysqli-style active-record usage, or move to CI4's model/query-builder conventions during the port?

## Sources

- [PHP releases](https://www.php.net/releases/) · [PHP supported versions](https://www.php.net/supported-versions) · Docker Hub `php:8.5.12-apache` tag (verified via `docker manifest inspect`, 2026-10-22)
- PHP migration guides: [7.4 deprecated](https://www.php.net/manual/en/migration74.deprecated.php) · [8.0 incompatible](https://www.php.net/manual/en/migration80.incompatible.php) · [8.1 deprecated](https://www.php.net/manual/en/migration81.deprecated.php) · [8.2 deprecated](https://www.php.net/manual/en/migration82.deprecated.php) · [8.3 incompatible](https://www.php.net/manual/en/migration83.incompatible.php) · [8.4 deprecated](https://www.php.net/manual/en/migration84.deprecated.php) · [8.5 incompatible](https://www.php.net/manual/en/migration85.incompatible.php) · [8.5 deprecated](https://www.php.net/manual/en/migration85.deprecated.php)
- CodeIgniter: [CI3 repo](https://github.com/bcit-ci/CodeIgniter) · [CI3 releases feed](https://github.com/bcit-ci/CodeIgniter/releases.atom) · [CI3 requirements](https://codeigniter.com/userguide3/general/requirements.html) · [CI4 requirements](https://codeigniter.com/user_guide/intro/requirements.html) · [Upgrading 3.x → 4.x](https://codeigniter.com/user_guide/installation/upgrade_4xx.html) · [CI4 upgrade database](https://codeigniter.com/user_guide/installation/upgrade_database.html)
