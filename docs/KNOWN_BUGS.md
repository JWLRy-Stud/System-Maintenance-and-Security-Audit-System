# Known bugs and risks

Every OPEN item below is ALSO marked in the source with a comment `BUG B-nn [OPEN]` at the line to change (search for `BUG B-`), so it travels with the Repomix snapshot. When you fix one, delete that comment and mark the item FIXED here.

How to use: give your Claude chat ONE item ID (e.g. "fix B-09") plus a fresh Repomix snapshot. Each item has what is wrong, how to see it, and the suggested fix. Add or update a test for every fix, then mark the item FIXED here.
Status: FIXED = fixed in stage 2 with a test (tests/Feature/*AuthorityTest.php, ChecklistManageTest.php). OPEN = not fixed.
Found by reading the code and running the test suite. Anything marked "unverified" was not reproduced at runtime.

## Fixed in stage 2

| ID | Severity | Problem | Where | Test |
|---|---|---|---|---|
| B-01 | High | A user deactivated while logged in kept access to every route without a role gate (dashboard, My tasks, finding updates, password change) until the session expired. | `routes/web.php` auth group; fixed by `EnsureUserIsActive` | `AccountAuthorityTest::test_user_deactivated_while_signed_in_is_signed_out_on_next_request` |
| B-02 | Medium | A technician who submitted a request was redirected to a 403: technicians could only see requests assigned to them, not ones they raised. | `MaintenanceController` | `MaintenanceAuthorityTest::test_technician_can_open_the_request_they_just_submitted` |
| B-03 | Medium | Completing maintenance set the asset back to `active` even if an admin had retired it meanwhile, or another request on it was still in progress. | `MaintenanceService` | `test_completing_work_does_not_reactivate_a_retired_asset`, `test_asset_stays_under_maintenance_while_another_request_is_in_progress` |
| B-04 | High | Findings raised automatically from a failed check could never get an owner or due date, so they never reached anyone's "My tasks". | `SecurityAuditController` (new `assignFinding`) | `AuditAuthorityTest::test_owner_can_assign_finding_and_it_appears_in_my_tasks` |
| B-05 | High | A finding's assignee (any role) could accept the risk or reopen their own finding. | `AuditFindingPolicy::transition` | `test_assignee_cannot_accept_risk_or_reopen_but_can_resolve` |
| B-06 | Medium | Findings could be assigned to inactive users (server accepted any existing user id). | `SecurityAuditController::activeUser()` | `test_only_owner_or_admin_can_assign_and_inactive_or_closed_are_rejected` |
| B-07 | Low | CSV formula-injection guard missed values starting with TAB or CR. | `ReportController` | `test_findings_csv_neutralises_formula_prefixes_including_tab` |
| B-08 | High | An admin could deactivate the last active admin (or themselves), locking everyone out of user management. | `UserAdminService` | `test_last_active_admin_cannot_be_demoted_or_deactivated` |
| B-09 | Low | UI showed buttons that returned 403 (other auditors on an audit page, finding actions for the wrong role), dashboard counts and asset maintenance history covered records the viewer could not open. | views + `DashboardController`, `AssetController::show` | `test_non_owner_auditor_sees_audit_read_only`, `test_finding_buttons_match_what_the_viewer_may_do`, `test_asset_page_lists_only_maintenance_the_viewer_can_open` |

## Open

### Security
- **B-10 (High if exposed directly): login throttle can be bypassed.** `bootstrap/app.php` calls `trustProxies(at: '*')`. With every proxy trusted, a client can send a forged `X-Forwarded-For`, so `throttle:5,1` (keyed by IP) never triggers. Fix: read trusted proxies from an env var (`TRUSTED_PROXIES`, your host's proxy IPs/CIDRs) instead of `*`; test with a request that sends a spoofed header. Depends on the hosting setup, so confirm with docs/DEPLOYMENT.md first.
- **B-11 (Medium): throttle is per IP only.** One attacker per account is limited, but many accounts behind one office IP lock each other out and distributed attacks on one account are not limited. Fix: `RateLimiter::for('login')` keyed by lowercase email + IP.
- **B-12 (Medium): changing your own password does not sign out your other sessions.** Fix: add `auth.session` (`AuthenticateSession`) to the auth group and call `Auth::logoutOtherDevices()` in `AccountController::update`.
- **B-13 (Medium): `role` and `status` are in `User::$fillable`.** Any future `$user->update($request->all())` becomes privilege escalation. Fix: guard them, use `forceFill` in `UserAdminService`/`UserController::store`/seeder; test that mass assignment of `role` is ignored.
- **B-14 (Medium): demo seeder creates users with the password `password` including an admin.** Fix: refuse to seed demo users when `app()->environment('production')`; check docs/DEPLOYMENT.md still shows the right first-admin steps.
- **B-15 (Low): weak password policy.** Only `min:8`. Fix: `Password::min(10)->mixedCase()->numbers()` in one place (store, update, change password); optionally force a change on first login for admin-created accounts.
- **B-16 (Low): audit trail gaps.** Failed logins are logged without attempted email or IP. Denied attempts are logged only for maintenance and finding transitions, not for other 403s (users, checklists, assignment). `ActivityLogService` stores the request id with `app()->instance`, which would leak across requests under Octane/long-running workers. Immutability is model-level only (mass `delete()` bypasses it); restrict DB grants in production.
- **B-17 (Low): Bootstrap and fonts load from CDNs without Subresource Integrity hashes.** Fix: add `integrity`/`crossorigin` or self-host.

### Workflow / data
- **B-18 (Medium): a maintenance request cannot be reassigned.** `TRANSITIONS['assigned']` has no `assigned`. If the technician is deactivated the request is stuck (an admin can still start/complete it). Fix: allow `assigned -> assigned` (admin only, assign form also on assigned requests) and test it.
- **B-19 (Low): status transitions are not row-locked.** Two simultaneous requests could both pass the transition check (for example complete twice and write two completion records). Fix: `lockForUpdate()` on the row inside the transaction and re-check the status.
- **B-20 (Low): admin can set an asset to `under_maintenance` by hand, and retire an asset that has open maintenance requests.** This conflicts with the workflow that manages that status. Fix: hide `under_maintenance` in the asset form; block retiring while requests are open (or confirm).
- **B-21 (Low): asset `assigned_user_id` accepts inactive users.** Same fix as B-06 in `AssetRequest`.
- **B-22 (Low, unverified): `UserController::store` does not lowercase emails.** MySQL's default collation is case-insensitive so duplicates are probably still caught; confirm.

### Housekeeping
- **B-23: stage 2 views were never viewed in a browser.** The new assign panel on the audit page is an inline expanding `<details>`; check it on mobile width and in dark mode (`public/css/ui.css`, `.assign-form`).
- **B-24: `repomix.config.json` is referenced by docs/AI_CONTEXT.md but was not in the snapshot that stage 2 was built from, nor were `composer.lock`, `package-lock.json` or binary files (favicon).** Check they exist in the real repo and that the snapshot is not leaking `.env`.
- **B-25: tests ran on SQLite, production is MySQL.** Re-run `php artisan test` against MySQL; `lockForUpdate()` and collation behave differently there.
