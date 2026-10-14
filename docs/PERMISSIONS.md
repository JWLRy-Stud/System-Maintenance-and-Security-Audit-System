# Who may do what

Enforced server-side by `role` middleware (coarse) and Policies in `app/Policies` (record level). Views use `@can`, so a button appears only if the server would allow it. Tests: `tests/Feature/*AuthorityTest.php`.
Admin passes every policy (`AdminBypass`); whether a status move is *legal* is checked separately (`TRANSITIONS`).

| Area | Admin | Auditor | Technician | Staff |
|---|---|---|---|---|
| Assets | create/edit/retire | view | view | view |
| Users | create, edit, change role, reset password, activate/deactivate | - | - | - |
| Maintenance: see | all | none | assigned to them or raised by them | their own |
| Maintenance: submit | yes | no | yes | yes |
| Maintenance: assign | yes | no | no | no |
| Maintenance: start / complete | yes | no | only if assigned to them | no |
| Maintenance: cancel | yes | no | own request while still open | own request while still open |
| Audits: view | all | all (read only unless owner) | no | no |
| Audits: answer checks, add findings, complete | all | own audits | no | no |
| Findings: assign owner / due date | all | own audits | no | no |
| Findings: start work, resolve | all | own audits | if assigned to them | if assigned to them |
| Findings: accept risk, reopen | all | own audits | no (even if assigned) | no (even if assigned) |
| Checklist templates: view / use | yes | yes | no | no |
| Checklist templates: edit / delete | all | only ones they created | no | no |
| Reports (CSV), activity log | log + CSV | CSV | no | no |

Account safeguards: a deactivated user is signed out on the next request; you cannot change your own role or deactivate yourself; the last active admin cannot be demoted or deactivated.
