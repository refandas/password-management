# Target PHP 8.5.12 for the platform migration

We are migrating off PHP 7.3 / CodeIgniter 3.1.10 (EOL-era runtime; framework in security-fixes-only mode). We decided to target **PHP 8.5.12** (`php:8.5.12-apache`, tag verified on Docker Hub 2026-10-22) rather than the more conservative 8.4: "latest" is the stated goal of the effort, 8.5 has the longest support runway (active to 2027-12-31, security to 2029), and the audited codebase has no removed-function usage that would force an intermediate version. Research and audit: `docs/research/2026-10-22-php-and-codeigniter-migration.md`.
