# v1.3.0
## 06/11/2026

1. [](#new)
    * Added **Admin2** integration: a `Grav Views` report and an optional top pages dashboard widget via the API plugin events
    * Added support for **Database plugin named connections** (e.g. PostgreSQL) alongside the default file-based SQLite database
    * Added `tracking.humans_only` option to skip automatic tracking of bots / non-trackable requests
2. [](#improved)
    * Made tracking and listing queries portable across SQLite and PostgreSQL (upsert parameter binding, no negative `LIMIT`)
    * The Admin2 dashboard widget now reads from a dedicated lightweight `/views/top-pages` API endpoint instead of the heavyweight `/reports` endpoint, which ran a full-site XSS scan on every dashboard load

# v1.2.0
## 05/01/2026

1. [](#improved)
    * Added 1.7|2.0 compatibility flags
    * Folded in pending working-tree changes (vendor refresh / minor PHP 8 modernization)

# v1.1.0
## 12/02/2020

1. [](#new)
    * Require Grav 1.7
    * Code cleanup
    * Pass `phpstan` tests
    * Added `select` method that allows you to perform custom SELECT queries
    * Remove `admin.css` as it's no longer needed
1. [](#bugfix)
    * Fixed default `limit` for `getAll` from `0` to `-1` in order to actually return all items
    * Fix CLI commands to use new format

# v1.0.1
## 12/09/2018

1. [](#new)
    * Added a new `type` column to support multiple view types concurrently
    * Updated README.md

# v1.0.0
## 12/08/2018

1. [](#new)
    * Added support for CLI
    * Allow `{{ track_views(object) }}` format if object can be casted to string containing a key
    * Make `{{ track_views(object) }}` HTML safe, allowing it to be used without `|raw` filter
    * Use new `user-data://` stream
    * Added support for `autotrack` to track any page based on `onPageInitialized()` event

# v0.1.0
## 11/01/2018

1. [](#new)
    * ChangeLog started...
