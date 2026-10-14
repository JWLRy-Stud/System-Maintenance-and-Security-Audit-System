# Known bugs and risks

## Rules for Claude chat (read these first, every time)
1. **Two bugs per chat.** Fix at most TWO open items per conversation (take the next two in the Open list unless I name others), so the chat does not hit its limit early. Stop after those two, even if more look easy.
2. **Flag what you see.** If you notice any other bug, risk or gap while reading code, do NOT fix it now. Add a comment at the exact line, `BUG B-nn [OPEN]: what is wrong. FIX: ...` (next free number, use the file's comment style: `//`, `{{-- --}}`, `<!-- -->`, `/* */`), and add the item to the Open list below. Add the flag only to files you are already returning.
3. **Remove when done.** When you fix an item: delete its `BUG B-nn [OPEN]` comment from the source, move its row to the Fixed table with the test name, and add or update a PHPUnit test for it.
4. **Return complete files.** Give every changed file (source, tests, these docs) with its exact path and COMPLETE contents. Do not claim tests passed; I run `php artisan test` and report back.
5. Keep the Open list in the same order and keep this rules block in this file.

Every OPEN item below is ALSO marked in the source with a comment `BUG B-nn [OPEN]` at the line to change (search for `BUG B-`), so it travels with the Repomix snapshot. When you fix one, delete that comment and mark the item FIXED here.

How to use: give your Claude chat ONE item ID (e.g. "fix B-09") plus a fresh Repomix snapshot. Each item has what is wrong, how to see it, and the suggested fix. Add or update a test for every fix, then mark the item FIXED here.
Status: FIXED = fixed in stage 2 with a test (tests/Feature/*AuthorityTest.php, ChecklistManageTest.php). OPEN = not fixed.
Found by reading the code and running the test suite. Anything marked "unverified" was not reproduced at runtime.

## Fixed (stage 2 and later)

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
| B-11 | Medium | Login throttle (`throttle:5,1`) counted every login, successful ones too, per IP: one person trying the four demo roles, or five people on one office IP, got locked out for a minute (found while doing the manual walkthrough). Now only FAILED attempts count, per email + IP, and a success clears the counter. | `AuthController::login` | `AuthorizationTest::test_many_successful_logins_from_one_ip_are_not_blocked`, `test_five_failed_attempts_lock_that_account_even_for_the_right_password`, `test_successful_login_resets_the_failed_counter` |
| B-09 | Low | UI showed buttons that returned 403 (other auditors on an audit page, finding actions for the wrong role), dashboard counts and asset maintenance history covered records the viewer could not open. | views + `DashboardController`, `AssetController::show` | `test_non_owner_auditor_sees_audit_read_only`, `test_finding_buttons_match_what_the_viewer_may_do`, `test_asset_page_lists_only_maintenance_the_viewer_can_open` |
| B-10 | High | `trustProxies(at: '*')` let a client send a forged `X-Forwarded-For`, so the login lockout key (email + IP) changed on every request and never triggered. Now only the proxies in the `TRUSTED_PROXIES` env var are trusted (empty = none). | `bootstrap/app.php`, `.env.example`, `docs/DEPLOYMENT.md`, `phpunit.xml` | `AuthorizationTest::test_forged_forwarded_for_header_cannot_dodge_the_login_lockout` |
| B-12 | Medium | Changing your own password did not sign out your other sessions. `auth.session` added to the auth group and `Auth::logoutOtherDevices()` called in `AccountController::update`. | `routes/web.php`, `AccountController` | `AccountAuthorityTest::test_changing_password_signs_out_other_sessions_but_keeps_this_one` |
| B-13 | Medium | `role` and `status` were in `User::$fillable`, so any future `$user->update($request->all())` would be privilege escalation. Both are now guarded and set with `forceFill()`. | `User`, `UserAdminService`, `UserController::store`, `DatabaseSeeder`, `docs/DEPLOYMENT.md` (first-admin step) | `AccountAuthorityTest::test_role_and_status_are_ignored_when_mass_assigned`, `test_admin_creating_a_user_sets_the_chosen_role_and_forces_active_status`, `DemoSeederTest::test_demo_seeder_creates_the_four_demo_users_with_their_roles_outside_production` |
| B-14 | Medium | The demo seeder created users with the password `password` (including an admin) in any environment. It now does nothing when `app()->environment('production')` and prints a warning. | `DatabaseSeeder`, `docs/DEPLOYMENT.md` | `DemoSeederTest::test_demo_seeder_refuses_to_run_in_production` |
| B-15 | Low | Password policy was only `min:8`. One shared rule `User::passwordRule()` (min 10, upper + lower case, number) now applies to admin-created users, admin password resets and the change-password form; form hints updated. | `User`, `AccountController`, `UserController`, 3 views | `AccountAuthorityTest::test_weak_passwords_are_rejected_everywhere_a_password_is_set`, `test_a_password_that_meets_the_policy_is_accepted` |
| B-16 | Low | Audit trail gaps: failed and locked-out logins had no attempted email or IP; only maintenance and finding transitions logged denials; the request id was kept in a process-wide container binding. Now `activity_logs` has `ip_address` and `detail` (new migration), every 403 is logged centrally as `access.denied` (without double-logging), the request id lives on the request, and the log page shows detail and IP. | `ActivityLogService`, `AuthController`, `bootstrap/app.php`, migration `2025_01_03_000000`, `reports/logs.blade.php` | `AuditTrailTest` (5 tests) |

## Open

### Security
- **B-26 (Low): the change-password form has no rate limit on `current_password`.** A hijacked session can brute-force the current password without touching the login lockout. Fix: `RateLimiter` keyed on user id, failures only, like `AuthController::login`; test five wrong attempts then a right one is refused.
- **B-27 (Low): only the default "trust no proxy" case of `TRUSTED_PROXIES` is tested.** Fix: a test that boots the app with `TRUSTED_PROXIES` set and checks `X-Forwarded-For` is honoured from that proxy IP and ignored from any other.
- **B-28 (Low): the first-admin step in `docs/DEPLOYMENT.md` types the password into `php artisan tinker`,** which stores it in the shell's psysh history. Fix: an `app:create-admin` artisan command with a hidden password prompt (test it), then document that instead.
- **B-29 (Low): admin-created accounts never have to change the password the admin chose.** Fix: `users.must_change_password` flag (migration), set on create and on admin reset, middleware redirects to the password page until cleared; test it.
- **B-30 (Low): the activity log is append-only in the model only.** A mass `delete()`/`truncate` or direct SQL bypasses it. Fix: production DB user with only `SELECT, INSERT` on `activity_logs` (see `docs/DEPLOYMENT.md`), optionally a DB trigger; verify the grants on the real server.
- **B-31 (Low): `config/database.php` uses `PDO::MYSQL_ATTR_SSL_CA`, deprecated on PHP 8.4+.** Every test prints a deprecation notice (67 in the last run), which hides real output. Fix: `\Pdo\Mysql::ATTR_SSL_CA` when the class exists, else the old constant, in both MySQL connections.
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
