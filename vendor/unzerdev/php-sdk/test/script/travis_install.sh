#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

INSTALL_COMMAND="composer install --no-interaction --prefer-dist"
UPDATE_COMMAND="composer update --no-interaction --prefer-source"

if [ "$DEPS" == "NO" ]; then
    ${INSTALL_COMMAND}
fi

if [ "$DEPS" == "HIGH" ]; then
    ${UPDATE_COMMAND}
fi
