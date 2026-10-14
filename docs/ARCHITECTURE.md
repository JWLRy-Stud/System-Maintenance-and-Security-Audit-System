# Architecture
Request flow: route → `auth` + `active` + `role` middleware → Form Request / inline validation → controller → service (workflow rules, DB transaction, activity log) → Blade view.

Record-level access (e.g. a technician only sees their own requests) is enforced in controllers/services, not only in the UI.

Maintenance: open → assigned → in_progress → completed; open/assigned → cancelled.
Findings: open → in_progress → resolved; open → accepted_risk; in_progress → open.

Authorization: `role` middleware is the coarse gate; record-level rules are Laravel Policies in `app/Policies` (see PERMISSIONS.md), called with `Gate::authorize()` in controllers/services and `@can` in views so buttons match what the server allows. Non-auditor assignees use the "My tasks" page.
