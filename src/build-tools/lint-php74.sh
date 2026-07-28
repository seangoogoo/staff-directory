#!/bin/sh
# Guards the PHP 7.4 target: the local runtime is PHP 8.x, whose `php -l` happily
# accepts 8-only syntax (match, ?->, enums, promoted constructors, readonly...)
# that would be a fatal error on a 7.4 host. Lint every application file with a
# real 7.4 binary instead.
#
# Override the interpreter with PHP74=/path/to/php7.4 npm run lint:php74
set -u

PHP74="${PHP74:-}"
if [ -z "$PHP74" ]; then
    if command -v php7.4 > /dev/null 2>&1; then
        PHP74="php7.4"
    else
        PHP74="/opt/homebrew/opt/php@7.4/bin/php"
    fi
fi

if ! command -v "$PHP74" > /dev/null 2>&1; then
    echo "PHP 7.4 not found at '$PHP74'."
    echo "Install it (brew install shivammathur/php/php@7.4) or set PHP74=/path/to/php7.4"
    exit 1
fi

echo "Linting against $("$PHP74" -r 'echo PHP_VERSION;')"

failed=0
for file in $(find public config database tools -name '*.php' -not -path '*/vendor/*'); do
    if ! output=$("$PHP74" -l "$file" 2>&1); then
        echo "$output"
        failed=1
    fi
done

if [ "$failed" -ne 0 ]; then
    echo "PHP 7.4 compatibility check failed."
    exit 1
fi

echo "PHP 7.4 syntax OK."
