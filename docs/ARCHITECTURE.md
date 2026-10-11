# Architecture
Request flow: route → `auth` + `role` middleware → Form Request / inline validation → controller → service (workflow rules, DB transaction, activity log) → Blade view.

Record-level access (e.g. a technician only sees their own requests) is enforced in controllers/services, not only in the UI.

Maintenance: open → assigned → in_progress → completed; open/assigned → cancelled.
Findings: open → in_progress → resolved; open → accepted_risk; in_progress → open.

Known simplifications: roles are checked via middleware instead of Policies; findings can be updated by their assignee through `PATCH /findings/{id}` but non-auditor assignees have no list view of them yet.
