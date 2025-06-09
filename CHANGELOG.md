# Changelog

## [v1.17.10] - 2025-06-09

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

