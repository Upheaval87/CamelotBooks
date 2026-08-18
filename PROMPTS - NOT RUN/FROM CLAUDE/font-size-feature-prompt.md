# Font Size Adjustment Feature — Accounting System

Add a multi-level font size adjustment feature to the accounting system, replacing the current two-mode (normal/largest) toggle. Use accounting-software-appropriate sizing (14px base, not the generic 16px web default) since the app has dense ledger/report tables.

## Requirements

1. **Migration**: add a `font_scale` decimal(3,2) column to the `users` table, default 1.00, positioned after `email`.

2. **Controller**: create `UserPreferenceController@updateFontScale` that validates `font_scale` is one of [0.85, 1.00, 1.15, 1.30, 1.50] and updates the authenticated user's record. Return JSON `{ status: 'ok', font_scale }`.

3. **Route**: POST `/preferences/font-scale`, auth middleware, wired to the controller above.

4. **Base font size**: set the root font-size to 14px (not the browser default 16px) across the app, since 13–14px is the standard for accounting/finance software (QuickBooks, Xero, Wave) — it fits more columns in ledgers/journals/reports without wrapping or horizontal scroll.

5. **Layout** (`layouts/app.blade.php` or equivalent shared layout): set a `--font-scale` CSS variable on the `<html>` element from the authenticated user's `font_scale` (default 1 if not authenticated). Define:
   ```css
   :root { font-size: 14px; }
   body { font-size: calc(1rem * var(--font-scale)); }
   ```
   Ensure typography across the app uses rem units so it inherits this scale — check `resources/views` for hardcoded px font-sizes (especially ledger/report/grid views) and convert them to rem.

6. **Apply this size scale at each step** (base 14px):
   - 0.85 → ~12px — compact, for power users doing bulk data entry
   - 1.00 → 14px — default, matches accounting software convention
   - 1.15 → ~16px — comfortable reading
   - 1.30 → ~18px — larger, for headings/general accessibility
   - 1.50 → ~21px — largest, for accessibility needs

   Use this differentiated scale where it makes sense:
   - Table/grid data (ledgers, journals): 13–14px at default
   - Body text, form labels, general UI: 14–15px at default
   - Section headings: 18–20px at default
   - Page titles: 22–24px at default

   All should scale proportionally with `--font-scale` so relative hierarchy is preserved at every step.

7. **UI controls**: replace the existing normal/largest buttons with A- / A+ buttons plus a label showing the current level (Small, Normal, Large, Larger, Largest), placed wherever the current toggle currently lives. Clicking steps through the `FONT_STEPS` array `[0.85, 1.00, 1.15, 1.30, 1.50]` one increment at a time, disabled at the array bounds.

8. **JS**: on click, update the `--font-scale` CSS variable immediately (no page reload), update the label, and POST the new value to `/preferences/font-scale` via fetch with the CSRF token from the page's meta tag, only if the user is authenticated.

9. **Persistence**: on page load, initialize `currentStep` from the user's saved `font_scale` so the preference persists across sessions and page loads.

10. **Verification**: after implementing, verify ledger/report tables at the smallest step (0.85) to confirm column headers and numeric alignment don't break, and at the largest step (1.50) to confirm nothing overflows or clips.

## Notes

- Match existing code style and blade structure used elsewhere in the project.
- Do not use `artisan serve`, `artisan schedule`, or queue workers, per project conventions — this feature doesn't need any of them anyway.
