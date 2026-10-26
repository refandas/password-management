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
