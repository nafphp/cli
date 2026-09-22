#!/bin/sh
set -eu
NAF_CLI_SOURCE=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
export NAF_CLI_SOURCE
root=$(mktemp -d /tmp/naf-console-install.XXXXXX)
trap 'rm -rf "$root"' EXIT
cd "$root"
php <<'PHP'
<?php
file_put_contents('composer.json', json_encode([
    'name' => 'test/console-host',
    'version' => '1.0.0',
    'require' => ['naf/framework' => '^0.2', 'naf/cli' => '@dev'],
    'repositories' => [['type' => 'path', 'url' => getenv('NAF_CLI_SOURCE'), 'options' => ['versions' => ['naf/cli' => 'dev-test']]]],
    'config' => ['allow-plugins' => false, 'vendor-dir' => 'packages/vendor', 'bin-dir' => 'tools/bin'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
PHP
composer install --no-interaction --no-progress --prefer-dist
# A deliberately broken bootstrap proves installation never starts the application.
printf '%s\n' '<?php throw new RuntimeException("must not boot during setup");' > bootstrap.php
php tools/bin/naf-install
test -x bin/naf
before=$(sha256sum composer.json)
composer dump-autoload --no-interaction
test "$before" = "$(sha256sum composer.json)"
rm bin/naf
composer install --no-interaction --no-progress
test -x bin/naf
cat > bootstrap.php <<'PHP'
<?php
require_once __DIR__.'/packages/vendor/autoload.php';
define('BASE_PATH', __DIR__);
\Naf\app()->run();
PHP
bin/naf command:list > commands.txt
grep -q 'Registered commands' commands.txt
composer remove naf/cli --no-interaction --no-progress
composer dump-autoload --no-interaction
if bin/naf command:list >out.txt 2>err.txt; then
    echo 'Removed console unexpectedly succeeded' >&2
    exit 1
fi
grep -q 'composer install' err.txt
printf '%s\n' 'PASS: fresh install, custom directories, setup without boot, hook idempotence, regeneration, command dispatch and package removal.'
