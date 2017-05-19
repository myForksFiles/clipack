# clipack
Laravel CLI commands tools pack

## Installation

Via Composer

```bash
$ composer require myforksfiles/clipack dev-master
```

Then add the service provider in `config/app.php`:

```php
MyForksFiles\CliPack\CliPackServiceProvider::class,
```

## Available commands

### New
**Command:**
```bash
#save message
$ php artisan clipack:devlog "message"

#last 10 messages
$ php artisan clipack:devlog

#all messages
$ php artisan clipack:devlog --all
```

**Result:**
cli table view


**Remarks:**

