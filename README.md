# clipack

Laravel CLI commands tools pack.

## Requirements

- PHP 8.3+
- Laravel 12.x
- Composer 2.x

## Installation

```bash
composer require myforksfiles/clipack
```

The service provider is auto-discovered by Laravel.

To publish the configuration file:

```bash
php artisan vendor:publish --tag=clipack-config
```

To publish package translations:

```bash
php artisan vendor:publish --tag=clipack-lang
```

## Configuration

Published configuration is available as `config/clipack.php`.

Important options:

```env
CLIPACK_AUTH_BASIC_ENABLED=false
CLIPACK_AUTH_USER=user
CLIPACK_AUTH_PASSWORD=secretPassword
CLIPACK_RUN_PHP_ENABLED=false
CLIPACK_ALLOWED_HOSTS=
CLIPACK_MYSQL_BINARY=mysql
CLIPACK_USER_MODEL=App\Models\User
CLIPACK_EXTRA_LOGS_DIR=
CLIPACK_LOG_ROTATE_PATH=
CLIPACK_LOG_ROTATE_ARCHIVE=
CLIPACK_LOG_ROTATE_DAYS=60
```

`mff:runphp` is disabled by default and should only be enabled in local/development environments.

## Command naming

All package commands use the `mff:` prefix. Legacy names remain available as aliases where noted.

| Command | Alias(es) | Description |
|---------|-----------|-------------|
| `mff:clear` | `clean`, `cleanup`, `mff:cleanup`, `mff:clear:all`, `mff:clean:up`, `mff:cache:clear`, `mff:cached`, `mff:dev:clear`, `dev:clear` | Clear Laravel caches via Artisan/Cache APIs |
| `mff:clean:files` | `mff:files:clear` | Delete storage cache/session/view files and truncate logs |
| `mff:logs:clear` | `mff:apache:logs` | Truncate Laravel and web server logs |
| `mff:logs:rotate` | `knx:qs:logs:rotate` | Archive log files and prune old archives |
| `mff:db:dump` | | MySQL dump to `storage/sqlDumps` |
| `mff:db:import` | | Import a SQL dump |
| `mff:dev:log` | `dev:log` | Dev status lock/log helper |
| `mff:runphp` | | Run allowlisted local PHP helper scripts |
| `mff:schedule:list` | `mff:scheduled` | List scheduled commands |
| `mff:auth:basic` | | Toggle/inspect basic-auth flag file |
| `mff:disk:free` | `mff:space` | Disk free/used report |
| `mff:disk:check` | `knx:qs:disk` | Disk check with optional log file |
| `mff:create:user` | | Create/update user via configured model |
| `mff:lang:export` | | Export language keys to CSV |
| `mff:crontab:backup` | | Backup current crontab |
| `mff:schema:check` | `dev:check:schema` | Schema/migrations/models check |
| `mff:security:audit` | `security:audit` | PHP/server security audit report |
| `mff:security:check` | `mmf:security:check` | Run `local-php-security-checker` |
| `mff:video:download:x` | `video:download:x` | Download X/Twitter video via yt-dlp |
| `mff:video:download:yt` | `video:download:yt` | Download YouTube video via yt-dlp |
| `mff:youtube:transcript` | `youtube:transcript` | Download YouTube transcript |
| `mff:article:from-transcript` | `article:from-transcript` | Build article from transcript |
| `mff:youtube:channel-id` | `youtube:channel-id` | Resolve YouTube channel ID |

## Usage examples

### Dev log

```bash
php artisan mff:dev:log message "your message"
php artisan mff:dev:log
php artisan mff:dev:log --all
```

### Clear helpers

```bash
# 1) Laravel APIs (Artisan + Cache::flush)
php artisan mff:clear
php artisan mff:clear --rebuild-config

# 2) Delete files on disk (framework cache/views/sessions + logs)
php artisan mff:clean:files
php artisan mff:clean:files --report --skip-logs
php artisan mff:clean:files --extra-logs-dir=../.ddev/logs

# legacy aliases still work: cleanup, clean, mff:cache:clear, dev:clear, ...
php artisan mff:logs:rotate --create-missing
php artisan mff:lang:export
php artisan mff:create:user --email=admin@example.test --name=Admin
php artisan mff:disk:check --log
```

### Database dump

```bash
php artisan mff:db:dump
php artisan mff:db:import storage/sqlDumps/example.sql --force
```

### Run PHP helper file

Disabled by default. Enable only in local/development environments:

```env
CLIPACK_RUN_PHP_ENABLED=true
```

```bash
php artisan mff:runphp storage/app/clipack-scripts/example.php --force
```

### Media helpers

```bash
php artisan mff:video:download:x "https://x.com/user/status/123"
php artisan mff:video:download:yt "https://www.youtube.com/watch?v=VIDEO_ID"
php artisan mff:youtube:transcript "https://www.youtube.com/watch?v=VIDEO_ID" --lang=pl,en,de
php artisan mff:article:from-transcript storage/app/transcripts/youtube/example.txt
php artisan mff:youtube:channel-id @GoogleDevelopers
```

### Security

```bash
php artisan mff:security:audit
php artisan mff:security:audit --json
php artisan mff:security:check
```

## Translations

The package uses Laravel translations under the `clipack::messages` namespace.

Included languages: `en`, `pl`, `de`.

## Development

```bash
composer install
composer test
composer analyse
composer rector:test
composer format:test
```

## Testing stack

- Pest
- Orchestra Testbench
- PHPStan/Larastan
- Rector
- Laravel Pint

## License

MIT
