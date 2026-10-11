# Requirements (proposed by the group, not professor-mandated)

Roles: administrator, technician, security auditor, staff (one role per user).

| Module | Behaviour |
|---|---|
| Auth | Login with throttling; inactive users blocked; CSRF on all forms |
| Assets | Admin creates/edits; unique asset tag; everyone can search/view; assets are retired, never deleted |
| Maintenance | Staff/technician submit requests; admin assigns; assigned technician starts/completes (summary required); requester may cancel while open |
| Security audits | Auditor creates audit, records findings with severity, assigns remediation owner; findings follow open→in_progress→resolved (or accepted_risk) |
| Activity log | Append-only record of actor, action, target, outcome, request id; admin-only view |
| Reports | Findings CSV export (formula-injection safe); filtered activity log |

Assumptions: single role per user (use a pivot table if multiple roles are required); no email notifications; no file uploads (evidence is referenced as text).
