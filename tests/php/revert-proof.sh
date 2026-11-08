#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DOCKER="${DOCKER:-docker}"
PHP_IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
PHPUNIT_SHA256="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"

mutation="${1:-}"
if [[ -z "$mutation" ]]; then
  echo "Usage: $0 <mutation>" >&2
  exit 2
fi

WORK="$(mktemp -d)"
DOWNLOAD="$(mktemp -d)"
EXPORT="$(mktemp -d)"
chmod 700 "$WORK" "$DOWNLOAD" "$EXPORT"
cleanup() {
  rm -rf "$WORK" "$DOWNLOAD" "$EXPORT"
}
trap cleanup EXIT

git -C "$ROOT" archive HEAD | tar -x -C "$EXPORT"

curl -fsSL -o "$DOWNLOAD/phpunit-4.8.36.phar" "https://phar.phpunit.de/phpunit-4.8.36.phar"
actual_sha="$(sha256sum "$DOWNLOAD/phpunit-4.8.36.phar" | awk '{print $1}')"
if [[ "$actual_sha" != "$PHPUNIT_SHA256" ]]; then
  echo "Unexpected PHPUnit PHAR hash" >&2
  exit 1
fi

case "$mutation" in
  getcontent-falsy)
    perl -0pi -e 's/\$productNbr = Tools::getValue\(.PRODUCTS_VIEWED_NBR.\);\s*if \(\$productNbr === false \|\| \$productNbr === ..\) \{/if (!(\$productNbr = Tools::getValue(\x27PRODUCTS_VIEWED_NBR\x27))) {/s' "$EXPORT/ps_viewedproduct.php"
    test_filter="testZeroShowsInvalidNumber"
    ;;
  getcontent-negative)
    perl -0pi -e 's/\} elseif \(\(int\) \$productNbr <= 0\) \{/} elseif (0 === (int) (\$productNbr)) {/' "$EXPORT/ps_viewedproduct.php"
    test_filter="testNegativeShowsInvalidNumber"
    ;;
  upgrade110)
    perl -0pi -e "s/return \\\$module->registerHook\\('displayProductAdditionalInfo'\\);/\\\$module->registerHook('displayProductAdditionalInfo');\\n\\n    return true;/" "$EXPORT/upgrade/upgrade-1.1.0.php"
    test_filter="testUpgrade110ReturnsFalseWhenHookRegistrationFails"
    ;;
  empty-cookie)
    perl -0pi -e 's/\s*if \(empty\(\$this->context->cookie->viewed\)\) \{\n\s*return array\(\);\n\s*\}\n\s*//' "$EXPORT/ps_viewedproduct.php"
    test_filter="testUnsetCookieDoesNotQueryProducts"
    ;;
  *)
    echo "Unknown mutation: $mutation" >&2
    exit 2
    ;;
esac

case "$mutation" in
  getcontent-falsy|getcontent-negative)
    test_file_path="/export/tests/php/ConfigurationFormTest.php"
    ;;
  upgrade110)
    test_file_path="/export/tests/php/InstallAndUpgradeTest.php"
    ;;
  empty-cookie)
    test_file_path="/export/tests/php/ViewedProductWidgetTest.php"
    ;;
esac

set +e
output="$($DOCKER run --rm \
  -v "$EXPORT:/export:ro" \
  -v "$DOWNLOAD/phpunit-4.8.36.phar:/opt/phpunit-4.8.36.phar:ro" \
  -w /export \
  "$PHP_IMAGE" \
  php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
    /opt/phpunit-4.8.36.phar \
    --bootstrap /export/tests/php/bootstrap.php \
    --filter "$test_filter" \
    "$test_file_path")"
exit_code=$?
set -e

echo "$output"

if echo "$output" | grep -qiE 'Parse error|Fatal error|not found|undefined'; then
  echo "Proof setup failure for $mutation" >&2
  exit 1
fi

if [[ "$exit_code" -eq 0 ]]; then
  echo "Expected failing test did not fail for $mutation" >&2
  exit 1
fi

if ! echo "$output" | grep -q 'FAILURES!'; then
  echo "Expected FAILURES summary for $mutation" >&2
  exit 1
fi

echo "Revert proof passed for $mutation"
