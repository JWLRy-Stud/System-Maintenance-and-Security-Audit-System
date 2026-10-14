# Security notes
- Passwords hashed (Laravel `hashed` cast). Seeded demo passwords must be changed.
- Server-side role middleware on every protected route; record-level rules in Policies (docs/PERMISSIONS.md).
- Deactivated users are signed out on their next request; role changes, password resets and deactivation revoke that user's sessions (database session driver).
- The last active admin cannot be demoted or deactivated; admins cannot change their own role or deactivate themselves.
- CSRF via Laravel defaults; login locks an account+IP for a minute after 5 FAILED attempts (successful logins are not counted); session regenerated on login.
- Activity log never stores passwords/tokens; `ActivityLog` blocks update/delete at the model level (DB-level restrictions such as revoking UPDATE/DELETE grants are recommended for production).
- Set `APP_DEBUG=false` in production so stack traces are not shown.
