# Handoff checklist: continuing with a different Claude account / new chat

A new chat knows NOTHING about earlier chats. Give it the code snapshot + the status file every time.

## 1. Before you switch (current owner)
- [x] Commit and push everything: `git status` is clean, `git push`
- [x] Update `docs/PROGRESS.md` (what is done, what failed, the next task) and push
- [x] Run `php artisan test` and write the result into PROGRESS.md
- [x] Make sure `.env` is NOT committed (`git check-ignore .env` prints `.env`)

## 2. Set up the other person/account
- [x] `git pull` (or clone), then follow `docs/SETUP.md` (Ubuntu) or `docs/WINDOWS_SETUP.md`
- [x] Project runs: `php artisan serve` and login works with admin@example.com / password
- [x] `php artisan test` runs (note failures verbatim)

## 3. Start the new Claude chat
- [x] Generate snapshot: `npx repomix -c repomix.config.json` (creates `system.xml`)
- [x] Open `system.xml` and search for `PASSWORD`, `KEY`, `SECRET`: only placeholders may appear
- [x] Attach `system.xml` to the new chat (it already contains `docs/PROGRESS.md`)
- [x] Paste the starter prompt from `docs/AI_CONTEXT.md`, then describe ONE task from "Known gaps"
- [x] If the snapshot is too big for the free plan, use a focused one:
      `npx repomix --include "app/**,routes/**,resources/views/**,tests/**,database/migrations/**,docs/PROGRESS.md" -o feature.xml`

## 4. Applying Claude's answer
- [x] Copy each file at the exact path Claude gives (full contents, replace the whole file)
- [ ] If Claude says a file was renamed or deleted, delete the old file yourself: copying or unzipping over the repo never removes files (stage 2 left a dead `resources/views/checklists/create.blade.php` this way)
- [ ] `php artisan migrate` if Claude added a migration
- [ ] `php artisan test`; if anything fails, paste the FULL error back into the same chat
- [ ] Do not ask for many features in one message; finish one, commit, then the next
- [ ] Commit with a clear message after tests pass: `git add . && git commit -m "..."`

## 5. Manual walkthrough (after each change)
Last run by Claude: all 79 steps below, driven over real HTTP against a freshly seeded app (SQLite, sandbox). That does NOT replace you clicking through once on your own machine (MySQL, real browser), and it never looked at how pages appear (phone width, dark mode).

**Prepare**
- [ ] Local/dev database only: `php artisan migrate:fresh --seed` (wipes everything), then `php artisan serve`
- [ ] Demo accounts, password `password`: `admin@`, `technician@`, `auditor@`, `staff@` + `example.com`. Use one private window per role, or log out between roles
- [ ] Logging in is not rate limited, but 5 WRONG passwords for one email lock that email for a minute ("Too many failed attempts")

**admin**
- [ ] Assets > New asset `WALK-001` appears in the list; creating the same tag again shows "already been taken"
- [ ] Users > New user (role technician) appears in the list; Edit changes the name and saves
- [ ] Activity log lists `asset.created` and `user.created`

**staff**
- [ ] Maintenance > New request for WALK-001: you land on the request page with "Request submitted"
- [ ] Typing `/assets/create`, `/users`, `/audits`, `/checklists`, `/reports/logs` in the address bar shows 403 for each

**admin + technician + staff (maintenance flow)**
- [ ] admin assigns the request to a technician
- [ ] technician: Start work (asset shows "Under maintenance"); Complete with an empty summary shows an error; with a summary the request is completed and the asset is "Active" again
- [ ] staff sees the completion summary, and the request is listed on the WALK-001 asset page

**auditor**
- [ ] New audit from the seeded template. Mark one item Fail without a severity: error. With a severity: a finding appears
- [ ] Record a second finding by hand
- [ ] On the audit page, Assign the automatic finding to staff with a due date
- [ ] Complete audit while items are still pending: error. Answer everything, then complete
- [ ] Export findings CSV and open it: cells starting with `=`, `+`, `-`, `@` are shown with a leading `'`

**assignee (staff)**
- [ ] My tasks lists the finding with only "Start work" / "Mark resolved" buttons (no "Accept risk")
- [ ] Before completing the audit: change the failed item back to Pass: its finding becomes Resolved by itself

**second auditor + checklist templates**
- [ ] Log in as a different auditor: the first auditor's audit opens read-only (no "Complete audit" or "Record a finding")
- [ ] Create a template; Edit it (questions change); the other auditor gets 403 on its edit page; Delete it. Audits already started keep their questions

**invalid action is rejected and logged**
- [ ] As admin open an open request in two browser tabs. In tab 1 cancel it. In tab 2 click Assign: error "Cannot move a cancelled request to assigned", nothing changes, and the activity log shows the action with outcome `denied`

**accounts**
- [ ] staff > Change password: wrong current password gives an error; the right one shows "Password updated". Log out: the old password fails, the new one works
- [ ] admin deactivates a user who is currently logged in elsewhere: their next click sends them to the login page, and they cannot log in again
- [ ] admin cannot deactivate themselves, change their own role, or demote/deactivate the last active admin

**look and feel (only a human can check this)**
- [ ] Open the audit page (Assign panel), Users > Edit and Checklists > Edit at phone width and in dark mode (known open item B-23 in KNOWN_BUGS.md)

## 6. Free-plan tips
- Free chats have message/length limits: one small task per chat, attach only what is needed
- Start a fresh chat when the old one gets long, and re-attach `system.xml` + starter prompt
- Never paste `.env`, passwords, or tokens into any chat
