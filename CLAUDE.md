# enhancePostContent

Notes for AI assistants working on the Dotclear plugin enhancePostContent.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

It help to filter post content and extract or show inline more info about words and expressions such as tags, acronyms, abbreviations, definition, citation, link, etc.

Admin page. Public side: widgets, URL handlers.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendList.php`: filters list class.
* `src/Epc.php`: main class.
* `src/EpcFilter.php`: all filter must extends this class.
* `src/EpcFilters.php`: use Epc::getFilters() to get loaded stack.
* `src/EpcRecord.php`: filters records.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/Install.php`: install and update, database structure (a change needs a migration here).
* `src/Manage.php`: admin page of the module.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `src/Widgets.php`: widgets definition and rendering.
* `src/Filter/EpcFilterAbbreviation.php`: abbreviation filter.
* `src/Filter/EpcFilterAcronym.php`: acronym filter.
* `src/Filter/EpcFilterCitation.php`: citaion filter.
* `src/Filter/EpcFilterDefinition.php`: definition filter.
* `src/Filter/EpcFilterLink.php`: link filter.
* `src/Filter/EpcFilterReplace.php`: replacement filter.
* `src/Filter/EpcFilterSearch.php`: search filter.
* `src/Filter/EpcFilterTag.php`: tag filter.
* `src/Filter/EpcFilterTwitter.php`: twitter filter.
* `src/Filter/EpcFilterUpdate.php`: update filter.
* `js/`: `backend.js`.
* Database table `epc`.
* URL handlers: `epccss`.
* Widgets: `epclist`.
* Behaviors used: `enhancePostContentFilters`, `adminDashboardFavoritesV2`, `adminBlogPreferencesFormV2`, `adminBeforeBlogSettingsUpdate`, `adminFiltersListsV3`, `initWidgets`, `publicHeadContent`, `publicBeforeContentFilterV2`, `tplValues`.

## Pitfalls

* Table structure changes need a migration in `src/Install.php` (version compare), and must work on MySQL, PostgreSQL and SQLite.

## Testing

* Check the admin page, the widgets (admin and public), the public URLs, the admin help, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
