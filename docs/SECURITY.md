# Security notes
- Passwords hashed (Laravel `hashed` cast). Seeded demo passwords must be changed.
- Server-side role middleware on every protected route; ownership checks in controllers.
- CSRF via Laravel defaults; login throttled to 5/min; session regenerated on login.
- Activity log never stores passwords/tokens; `ActivityLog` blocks update/delete at the model level (DB-level restrictions such as revoking UPDATE/DELETE grants are recommended for production).
- Set `APP_DEBUG=false` in production so stack traces are not shown.
