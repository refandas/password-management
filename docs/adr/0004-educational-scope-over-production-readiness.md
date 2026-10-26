# Keep educational scope over production readiness

This project has two jobs: it is where the owner practices applied
cryptography, and it was the exercise bed for upgrading a legacy
CodeIgniter 3 / PHP 7.3 app to current versions (ADR-0001, ADR-0002).
Several artifacts around it — the cutover runbook, its rollback window,
the acceptance suite run against the serving host — look
production-shaped, and in fact the app does carry the owner's real
credentials, as a single-user, self-hosted service.

We decided that it will not pursue production readiness: no security
audit, no hardening pass, no compliance work, no multi-user or
public-facing scale. The per-account key stored beside ciphertext
(ADR-0003) and the deliberately deferred weaknesses (issues #2–#7) stay
as they are — chosen for instruction rather than defense.

Consequences: the README leads with an educational-scope callout; the
cutover runbook carries a framing note that it operates a personal
project, not production tooling; the README disclaims warranties rather
than promising to decline any particular feature request; and the
GLOSSARY keeps "framework upgrade" and "re-encryption cutover" apart so
"migration" stops doing both jobs.
