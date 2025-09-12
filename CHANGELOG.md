# Student Experience Team | OBU Forms ChangeLog

> Keep entries newest → oldest. Use 4-part versions (e.g., 2.1.4.2).  
> Release types: Major Release | Minor Release | Revision | Hotfix

---

## 1.18.1.0 – Minor Change
  **Date:** 2025-09-11  
  **Highlights:**
- Add intro to changelog
- Changed version number to new format
- Adding more campus codes to be denied forms access
- Refactor campus checking code into local lib

## v1.17.10 - Minor Change
**Date:** 2025-06-09  
**Highlights:**
### Added
- Introduced `TISM_FORMS` constant in `locallib.php` to centralize reference to taught student information management form codes: `M200`, `M201`, `M201L`, `M3`.
- Added support for alternate language string `notes_tism` used on TISM forms with lang file

### Changed
- Updated `form_view.php` to conditionally render the notes field label using `notes_tism` if the form is listed in `TISM_FORMS`.
- Updated `form.php` to pass `formref` context through `_customdata` to `form_view`.

### Fixed
- Ensured existing non-TISM forms continue to use the standard `notes` label string with no regressions in label rendering or notes persistence.

### Notes
- This update supports clearer user-facing messaging for TISM-related forms without affecting the behavior of standard forms.
- PHP constants and language string integration verified across form creation, editing, and display.

