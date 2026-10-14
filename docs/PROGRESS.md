# Project progress log (keep this updated; paste/attach it in every new Claude chat)

## Done
- Foundation: Laravel 12, MySQL, Blade + Bootstrap 5, login (locks after 5 failed attempts per email+IP), 4 roles via `RoleMiddleware`, seeded demo data
- Assets: CRUD (admin), search/filter, unique tag, retire instead of delete
- Maintenance: request -> assign -> in progress -> completed (or cancelled), completion record, role/ownership checks
- Security audits: audits, findings with severity/assignee/due date, finding workflow, CSV export, "My tasks" page for assignees
- Audit checklist templates: auditors create templates (one question per line), pick one when starting an audit (questions are copied), answer pass/fail/N-A; a fail raises a linked finding; audit cannot complete while items are pending
- Activity log (append-only, admin view with filters), dashboard, user management (deactivate), change password
- Stage 2 (authority + checklist management), see docs/PERMISSIONS.md:
  - Laravel Policies (`app/Policies`) are the single source of "who may do what"; middleware is only the coarse gate; views use `@can`
  - `EnsureUserIsActive` middleware: a user deactivated while signed in is signed out on the next request
  - Admin can edit users (name, email, role) and reset passwords; role/password change or deactivation revokes sessions; last active admin and self-lockout protections (`UserAdminService`)
  - Finding assignment (`PATCH /findings/{id}/assignment`); assignees may only start work / resolve, never accept risk or reopen
  - Checklist templates can be edited/deleted (creator or admin); fail -> pass closes the linked finding, back to fail reopens it (`AuditCheckService`)
  - Login lockout now counts only failed attempts per email+IP (was: every login per IP)
  - Bug fixes listed in docs/KNOWN_BUGS.md (marked FIXED)
- Docs: SETUP (Ubuntu), WINDOWS_SETUP, DEPLOYMENT, AI_CONTEXT, HANDOFF_CHECKLIST

## Verified by us (update after running!)
- [x] App runs locally on Ubuntu; login and dashboard work
- [x] `php artisan test` passes: 55 tests, 279 assertions (run by Claude in a sandbox on PHP 8.3 + SQLite, Oct 2026). Re-run on your machine (MySQL) and record the result: ____
- [ ] Stage 2 pages render without errors for every role (verified over HTTP) but have never been looked at in a browser (assign panel on audit page, user edit page, checklist edit page; see B-23)
- [x] Manual walkthrough of every role, 79/79 steps, run by Claude over real HTTP in a sandbox (found and fixed B-11). Still to do by a human on a real machine: HANDOFF_CHECKLIST section 5, ticking the boxes
- [ ] First deployment done (host: ____ )

## Known gaps / ideas (pick ONE per chat). Bugs live in docs/KNOWN_BUGS.md
1. Checks have no evidence upload (needs storage, file validation, access control).
2. No email notifications, or pagination on the audit detail page.
3. Activity log immutability is enforced at the model level only (production: also restrict DB grants).
4. No password reset by email (admin can reset passwords by hand).
5. Reports are limited to the findings CSV and the filtered activity log.
6. Denied attempts on policy-protected actions are logged only for maintenance and finding transitions.

## Decisions
- One role per user (`users.role`), not a roles table.
- Authorization lives in `app/Policies/*` (admin passes via `AdminBypass`); the legality of a status move is checked separately in the service/controller.
- Auditors can read every audit but change only their own (admin: all). Assignees of a finding (any role) can only start work and resolve.
- Maintenance transitions: `MaintenanceRequest::TRANSITIONS`; finding transitions: `AuditFinding::TRANSITIONS`.
