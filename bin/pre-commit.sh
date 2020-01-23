#!/bin/sh


PHP_FILES="$(git diff --cached --name-only --diff-filter=ACMR HEAD | grep -E '\.(php)$')"
JS_FILES="$(git diff --cached --name-only --diff-filter=ACMR HEAD | grep -E '\.(js)$')"

if [[ -z "$PHP_FILES" && -z "$JS_FILES" ]]
then
    exit 0
fi

UNSTAGED_FILES="$(git diff --name-only -- ${PHP_FILES} ${JS_FILES})"

if [[ -n "$UNSTAGED_FILES" ]]
then
    echo "Error: There are staged files with unstaged changes. We cannot automatically fix and add those.

Please add or revert the following files:

$UNSTAGED_FILES
"
    exit 1
fi

if [[ -n "$PHP_FILES" ]]
then
    php -l -d display_errors=0 "$PHP_FILES" 1> /dev/null
    ./bin/php_cs-fix.sh
    ./bin/phpstan.sh
fi

if [[ -n "$JS_FILES" && -x ../../../vendor/shopware/platform/src/Administration/Resources/administration/node_modules/.bin/eslint ]]
then
    ./bin/js_cs-fix.sh
fi

git add ${JS_FILES} ${PHP_FILES}
