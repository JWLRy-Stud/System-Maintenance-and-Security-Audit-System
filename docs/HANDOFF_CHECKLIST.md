# Handoff checklist: continuing with a different Claude account / new chat

A new chat knows NOTHING about earlier chats. Give it the code snapshot + the status file every time.

## 1. Before you switch (current owner)
- [ ] Commit and push everything: `git status` is clean, `git push`
- [ ] Update `docs/PROGRESS.md` (what is done, what failed, the next task) and push
- [ ] Run `php artisan test` and write the result into PROGRESS.md
- [ ] Make sure `.env` is NOT committed (`git check-ignore .env` prints `.env`)

## 2. Set up the other person/account
- [ ] `git pull` (or clone), then follow `docs/SETUP.md` (Ubuntu) or `docs/WINDOWS_SETUP.md`
- [ ] Project runs: `php artisan serve` and login works with admin@example.com / password
- [ ] `php artisan test` runs (note failures verbatim)

## 3. Start the new Claude chat
- [ ] Generate snapshot: `npx repomix -c repomix.config.json` (creates `system.xml`)
- [ ] Open `system.xml` and search for `PASSWORD`, `KEY`, `SECRET`: only placeholders may appear
- [ ] Attach `system.xml` to the new chat (it already contains `docs/PROGRESS.md`)
- [ ] Paste the starter prompt from `docs/AI_CONTEXT.md`, then describe ONE task from "Known gaps"
- [ ] If the snapshot is too big for the free plan, use a focused one:
      `npx repomix --include "app/**,routes/**,resources/views/**,tests/**,database/migrations/**,docs/PROGRESS.md" -o feature.xml`

## 4. Applying Claude's answer
- [ ] Copy each file at the exact path Claude gives (full contents, replace the whole file)
- [ ] `php artisan migrate` if Claude added a migration
- [ ] `php artisan test`; if anything fails, paste the FULL error back into the same chat
- [ ] Do not ask for many features in one message; finish one, commit, then the next
- [ ] Commit with a clear message after tests pass: `git add . && git commit -m "..."`

## 5. Manual walkthrough (after each change)
- [ ] admin: create asset, create user, view activity log
- [ ] staff: submit maintenance request; cannot open /assets/create, /users, /audits (403)
- [ ] admin: assign request to technician; technician: start work, complete with summary
- [ ] auditor: create audit, add finding, assign it, export CSV; assignee sees it under "My tasks"
- [ ] invalid action (e.g. completing an open request) shows an error and is logged as `denied`
- [ ] change password works; deactivated user cannot log in

## 6. Free-plan tips
- Free chats have message/length limits: one small task per chat, attach only what is needed
- Start a fresh chat when the old one gets long, and re-attach `system.xml` + starter prompt
- Never paste `.env`, passwords, or tokens into any chat
