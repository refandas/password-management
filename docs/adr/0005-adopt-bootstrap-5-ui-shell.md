# Adopt Bootstrap 5 for the UI shell; AdminLTE not restored

The CI4 port (ADR-0002) moved the legacy AdminLTE view markup 1:1, but
`public/assets/` never landed: every `base_url('assets/...')` reference
404'd and every page leaned on three CDNs (ionicons, Google Fonts,
jQuery) for the pieces that still rendered. The acceptance suite never
caught it because it asserts rendered strings, and no test resolved an
asset. The missing payload posed a restore-or-replace question of the
same shape as the bare `/account/delete` 404 (#30) — except this one
had no pin anywhere — and AdminLTE 3 is Bootstrap 4 + jQuery 3.4, a
maintenance-mode stack we would commit only to delete later.

We decided to rewrite the shell on Bootstrap 5.3 and vendor it
self-hosted under `public/assets/` (with Font Awesome 5.15.4 for the
icons, including the DB-driven media-type logos the vault list joins
in). The sidebar layout stays — an owner decision — as an offcanvas
drawer below lg and a static dark rail at lg and up. The register and
set_password password-match scripts are vanilla JS; jQuery is gone
from the payload.

Consequences: vault pages make zero third-party requests, and ShellTest
pins the failure mode that made this decision necessary — every
referenced asset answers 200, no external origins, no jQuery. The
"full AdminLTE sandwich" assumption the acceptance tests carried is
superseded: they now assert the Bootstrap 5 shell with the sidebar
still asserted. EN/ID UI string normalization (#4) stays a separate
follow-up.
