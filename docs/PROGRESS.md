# Project progress log (keep this updated; paste/attach it in every new Claude chat)

## Done
- Foundation: Laravel 12, MySQL, Blade + Bootstrap 5, login (throttled), 4 roles via `RoleMiddleware`, seeded demo data
- Assets: CRUD (admin), search/filter, unique tag, retire instead of delete
- Maintenance: request -> assign -> in progress -> completed (or cancelled), completion record, role/ownership checks
- Security audits: audits, findings with severity/assignee/due date, finding workflow, CSV export, "My tasks" page for assignees
- Audit checklist templates: auditors create templates (one question per line), pick one when starting an audit (questions are copied), answer pass/fail/N-A; a fail raises a linked finding; audit cannot complete while items are pending
- Activity log (append-only, admin view with filters), dashboard, user management (deactivate), change password
- Docs: SETUP (Ubuntu), WINDOWS_SETUP, DEPLOYMENT, AI_CONTEXT, HANDOFF_CHECKLIST

## Verified by us (update after running!)
- [x] App runs locally on Ubuntu; login and dashboard work
- [ ] `php artisan test` passes (record the result here: ____ )
- [ ] Manual walkthrough of every role (see HANDOFF_CHECKLIST section 5)
- [ ] First deployment done (host: ____ )

## Known gaps / ideas (pick ONE per chat)
1. Checklist templates cannot be edited or deleted yet; checks have no evidence upload; changing a check from fail back to pass does not close its finding.
2. Roles are enforced with middleware + controller checks, not Laravel Policies.
3. No email notifications, file/evidence uploads, or pagination on the audit detail page.
4. Activity log immutability is enforced at the model level only (production: also restrict DB grants).
5. No password reset by email (admin can deactivate/create users).
6. Reports are limited to the findings CSV and the filtered activity log.

## Decisions
- One role per user (`users.role`), not a roles table.
- Maintenance transitions: `MaintenanceRequest::TRANSITIONS`; finding transitions: `AuditFinding::TRANSITIONS`.
