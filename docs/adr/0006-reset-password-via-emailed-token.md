# Reset passwords through an emailed token

## Context

Issue #38 found the same class of hole #34 closed on the Setting seam, on
the forgot-password seam: `POST /auth/resetpassword` with any known email
rendered the set-password form directly, and `auth/setpassword/{segment}`
wrote the password for whatever id the segment carried — no verification
between the two steps. Anyone who knew a User's email could reset that
User's password, admin included, unauthenticated.

The issue left the fix open between two options: a real reset token with
an email round-trip, or a minimal verification gate before the write. A
gate that does not prove control of the mailbox proves nothing the
attacker lacks — the attack premise is already knowing the email — so the
token round-trip is the fix. The constraints around it:

- `tests/seed/schema.sql` is frozen at the pre-migration dump (spec #1),
  and the repo has no migrations story — stored tokens would need a
  schema change with no mechanism to apply it.
- The stack had no mail transport at all (`email.protocol = mail` with no
  MTA), and the acceptance suite asserts only external behavior at the
  HTTP seam — the round-trip had to be observable from outside the app.

## Decision

The reset write is authorized by a stateless Reset Token, not a stored
one: `{id_user}.{expires}.{signature}`, the signature HMAC-SHA256 over the
id, the expiry, and the User's current password hash, keyed by
`resettokens.secret` (unset secret fails closed; see `env`). Signing over
the password hash is what makes the token single-use without storage —
the reset write itself changes the hash, retiring the token and every
other outstanding one. The token travels only in an email sent through
the framework's Email service: Mailpit joins docker-compose as the
dev/test mailbox, and a real deployment points SMTP at its own server via
`env`. The forgot form's response no longer renders the set-password
form (its id-keyed action was the leak); the link lands on
`auth/setpassword/{token}`, which refuses expired, spent, and id-shaped
segments — the pre-fix attack — on both verbs, and checks the retype
match server-side, as the Setting flow has since #34. Acceptance tests
drive the whole cycle through the HTTP seam, reading the token out of
Mailpit's API the way a User reads it out of their inbox.

## Consequences

- Knowing a User's email is no longer enough to change their password;
  the mailbox behind it is required, and the id never reaches a response.
- Nothing is stored, so the schema stays frozen — but a token stays valid
  until it expires or the User's password changes, and expiry (60
  minutes) is asserted only by the signature check, not by a seam test:
  aging a token past its TTL inside one test run is not worth a
  clock-injection seam. The forged-signature and replay tests cover the
  verification paths that a wrong clock would corrupt.
- The unknown-email flash still says "Email not found", the legacy
  enumeration oracle; kept for parity, and now cheap: the token, not the
  response, is the capability.
- `docker-compose.yml` grows its first service beyond the pre-migration
  pair — dev/test-only, pinned (`axllent/mailpit:v1.32.0`), with the
  database service still untouched as spec #1 requires.
