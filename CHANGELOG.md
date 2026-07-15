# Changelog

All notable changes to `CliPack` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](https://raw.githubusercontent.com/myForksFiles/clipack/master/CHANGELOG.md) principles.

## Unreleased

### Added
- Registered the full package command set in `CliPackServiceProvider`.
- Unified command names under the `mff:` prefix with legacy aliases.
- Expanded PHPStan analysis to the whole `src` tree.
- Reworked security audit, security checker, free-space, crontab backup and YouTube channel-id commands to drop host-app couplings.
- Implemented `mff:auth:basic` on/off/status handling.

### Removed
- Removed unused facade, commented routes/views/migrations and temporary analysis artifacts.

### Fixed
- Restored and modernized `CreateUser`, `DiskSpace`, `LogRotate`, `ClearCaches`, `CleanAll` and `ExportLang` as package commands without host-app couplings.
- Unified overlapping clean commands into `mff:clear` (Laravel/Artisan) and `mff:clean:files` (filesystem wipe), keeping legacy aliases.

### Added
- Added PHP 8.3 and Laravel 12 package support.
- Added Pest test suite with Orchestra Testbench.
- Added feature tests for service provider registration, command registration, Basic Auth middleware and safe `mff:runphp` execution paths.
- Added unit tests for `CliPackTools`, `TranscriptCleaner` and security audit helper logic.
- Added PHPStan/Larastan configuration for static analysis.
- Added Rector configuration for PHP 8.3 and Laravel 12 modernization.
- Added GitHub Actions workflow for Composer validation, tests, PHPStan, Rector dry-run and Pint style check.
- Added package translations using Laravel translation files under the `clipack::messages` namespace.
- Added English, Polish and German translations for video, transcript and article command messages.
- Added `clipack-lang` vendor publish tag for package translations.
- Added YouTube/X video and transcript helper commands.

### Changed
- Updated package autoloading for `MyForksFiles\\CliPack\\` classes.
- Updated CLI command messages to English defaults with Laravel translation support.
- Updated package service provider to load config and translations using Laravel package conventions.
- Updated command signatures and descriptions to English.
- Updated development tooling scripts for tests, static analysis, Rector and Pint.
- Updated Composer dependencies for PHP 8.3 and Laravel 12 compatibility.

### Security
- Hardened `mff:runphp` by disabling it by default, blocking production execution and requiring an explicit allowlisted path plus `--force`.
- Improved Basic Auth middleware tests around production credentials.
- Reduced unsafe legacy command behavior in the modernization branch by narrowing automated analysis and Rector scope.

### Fixed
- Fixed package service provider autoloading for tests.
- Fixed several PHP 8.3/Rector modernization issues in command classes.
- Fixed missing package translation loading.

## 2018-11-14 update to laravel 5

## 2017-07-10 Added commands
dev:cleanup         Clean tmp files, logs, storage.
dev:db-dump         Call shell mysqldump to dump DB.
dev:db-import       Import sql file to localhost DB.
dev:dbchangeurl     Rewrite url in DB for development/staging
dev:get-langs       Generate csv file with all translations from .
dev:getconfig       Show variable from config
dev:lastlogs        Show last entries from logs
dev:usage

## 2017-06-20 Added command
dev:authbasic       Set up http Auth Basic on/off.

## 2017-05-31 Added commands
dev:log             Lock/unlock system, store and show status log. Message as argument which should be saved, do not forget quotes.
dev:runphp
dev:scheduled       List scheduled commands.

## 2017-05-20 init
First stable release. Everything is brand new!

### Added
- Nothing

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing
