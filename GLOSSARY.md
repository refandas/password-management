# Password Management

A self-hosted password manager: Users store Vault Accounts (credentials for other services) encrypted at rest, organized by Media Type. Exists to keep the owner's credentials in one privately-controlled place.

## Language

**User**:
A human identity that logs into the app: name, email, password, and an Access Level.
_Avoid_: account (reserved for Vault Account), member, login

**Vault Account**:
A stored credential entry belonging to a User: a username/email and password for one external service, encrypted at rest with its own key.
_Avoid_: account (when meaning this, always the full term), entry, credential record

**Media Type**:
The service category a Vault Account belongs to (Instagram, Twitter, …), carrying a display name and icon.
_Avoid_: media, account media, site

**Access Level**:
A User attribute gating which menus and management features they can reach.
_Avoid_: role, permission

**Framework upgrade**:
A move of the app to newer PHP and CodeIgniter versions — one of this project's two learning goals.
_Avoid_: migration (say re-encryption cutover for the data-side move; historically both were called "migration")

**Re-encryption cutover**:
The one-time move of every Vault Account's stored credential from the legacy encryption scheme to the current one.
_Avoid_: migration (on its own), rekey, conversion
