#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

# run static php-cs-fixer code analysis
./vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

## enable xdebug again
# shellcheck disable=SC2046
mv ~/.phpenv/versions/$(phpenv version-name)/xdebug.ini.bak ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini

## run the unit tests
if [ "$LEVEL" == "UNIT" ]; then
    echo "Perform unit tests only";
    ./vendor/bin/phpunit test/unit --coverage-clover build/coverage/xml
fi

## run the integration tests
if [ "$LEVEL" == "INTEGRATION" ]; then
    echo "Perform integration tests only";
    ./vendor/bin/phpunit test/integration
fi
