# UI refresh: install notes

UI-only change. No controllers, models, routes, migrations or tests are touched.

## Copy these into the repo (same paths)
- public/css/ui.css (new design system: edit the tokens at the top of :root to retheme)
- resources/views/layouts/app.blade.php (replaced: sidebar shell)
- resources/views/layouts/notices.blade.php (new: flash + error banners)
- resources/views/components/*.blade.php (new: icon, status, role, page-header, empty, finding-actions, theme-toggle)
- every page view under resources/views/ (replaced)

## Then
1. `php artisan view:clear`
2. `php artisan test` and paste any failures back
3. Walk each role through the app (HANDOFF_CHECKLIST section 5)

## Notes
- Still Bootstrap 5 from the CDN, so there is no npm build step. ui.css loads after it and restyles it.
- Fonts (Instrument Sans + Newsreader) come from Google Fonts; with no internet the app falls back to system fonts.
- Small UX additions that need no backend change: status filter chips on Maintenance, "Report an issue" on an asset page (pre-selects the asset), pass/fail/N/A buttons on checklists (severity appears only on Fail), confirmation before completing an audit.
- Behaviour intentionally changed: a checklist item that is still pending no longer silently defaults to "pass" when saved; you must choose a result.
- The brand name is "Maintenance & Audit" in layouts/app.blade.php and login.blade.php; change it there if the new system has a name.

## Dark mode
- Dark is the default for everyone. The sidebar has a "Switch to light mode" button (an icon button top-right on the sign-in page).
- The choice is saved in the browser (localStorage key `theme`), per device, and applied before the page paints so there is no flash.
- All colours are tokens in public/css/ui.css: light values in `:root`, dark values in `[data-theme="dark"]`. To make light the default instead, change `'dark'` to `'light'` in the head script in layouts/app.blade.php and set `data-theme`/`data-bs-theme` on `<html>` to `light`.
