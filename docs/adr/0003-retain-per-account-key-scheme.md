# Retain the per-account key stored beside ciphertext for the migration

Each Vault Account's credentials are AES-256-CTR encrypted with a per-account random key, hex-encoded into the same DB row (`account.key_str`). The one-time re-encryption at cutover re-encrypts with CI4's encrypter using **the same per-account keys in the same column**, rather than redesigning key derivation around a server-side master key. Deliberate: one variable (the framework) changes at a time, honoring parity-first (ADR-0002).

**Consequences** (accepted 2026-10-22): the scheme is knowingly weak — read access to the database defeats it — and is flagged by the owner as an **important post-migration fix**, alongside the unverified-email password-reset flow, the accidental `deleteAccount()` assignment, the redundant per-row key fetch, and the mixed English/Indonesian UI strings. These land as backlog issues when the spec is published.
