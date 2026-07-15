#!/bin/bash
# /usr/bin/php8.3 /usr/local/bin/composer update
/usr/bin/php8.3 /usr/local/bin/composer test
/usr/bin/php8.3 /usr/local/bin/composer analyse
/usr/bin/php8.3 /usr/local/bin/composer format
