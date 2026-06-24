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
```

`mff:runphp` is disabled by default and should only be enabled in local/development environments.

## Translations

The package uses Laravel translations under the `clipack::messages` namespace.

Included languages:

- `en` — English
- `pl` — Polish
- `de` — German

Example usage inside the package:

```php
__('clipack::messages.done')
__('clipack::messages.directory', ['path' => $path])
```

After publishing translations, files are copied to:

```text
lang/vendor/clipack
```

## Available commands

### Dev log

```bash
# save message
php artisan dev:log message "your message"

# last 10 messages
php artisan dev:log

# all messages
php artisan dev:log --all
```

### Clear helpers

```bash
php artisan cleanup
php artisan mff:clean:up
```

### Scheduled commands

```bash
php artisan mff:scheduled
```

### Database dump

```bash
php artisan mff:db:dump
```

### Run PHP helper file

Disabled by default. Enable only in local/development environments:

```env
CLIPACK_RUN_PHP_ENABLED=true
```

Place scripts in the configured allowlisted path and run with explicit confirmation:

```bash
php artisan mff:runphp storage/app/clipack-scripts/example.php --force
```

### X/Twitter video download

```bash
php artisan video:download:x "https://x.com/user/status/123"
php artisan video:download:x "https://x.com/user/status/123" --browser=chrome
php artisan video:download:x "https://x.com/user/status/123" --cookies=/path/to/cookies.txt
```

### YouTube video download

```bash
php artisan video:download:yt "https://www.youtube.com/watch?v=VIDEO_ID"
php artisan video:download:yt "https://www.youtube.com/watch?v=VIDEO_ID" --browser=chrome
```

### YouTube transcript download

```bash
php artisan youtube:transcript "https://www.youtube.com/watch?v=VIDEO_ID"
php artisan youtube:transcript "https://www.youtube.com/watch?v=VIDEO_ID" --lang=pl,en,de
php artisan youtube:transcript "https://www.youtube.com/watch?v=VIDEO_ID" --browser=chrome
```

### Article from transcript

```bash
php artisan article:from-transcript storage/app/transcripts/youtube/example.txt
php artisan article:from-transcript storage/app/transcripts/youtube/example.txt --title="My article" --lang=en
```

## Development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Run Rector dry-run:

```bash
composer rector:test
```

Run code style check:

```bash
composer format:test
```

Fix code style:

```bash
composer format
```

Full local check:

```bash
composer validate --strict
composer update -W
composer dump-autoload
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
