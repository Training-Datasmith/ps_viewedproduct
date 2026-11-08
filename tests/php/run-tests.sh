#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
DOCKER="${DOCKER:-docker}"
PHP_IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
PHPUNIT_SHA256="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
PHPUNIT_FPR="D8406D0D82947747293778314AA394086372C20A"
EXPECTED_FILES=(
  "ConfigurationFormTest.php"
  "DirectoryGuardTest.php"
  "InstallAndUpgradeTest.php"
  "ModuleMetadataTest.php"
  "ViewedProductWidgetTest.php"
)
EXPECTED_TEST_COUNT=45

WORK="$(mktemp -d)"
DOWNLOAD="$(mktemp -d)"
chmod 700 "$WORK" "$DOWNLOAD"
cleanup() {
  rm -rf "$WORK" "$DOWNLOAD"
}
trap cleanup EXIT

actual_sorted="$(find "$ROOT/tests/php" -maxdepth 1 -name '*Test.php' -printf '%f\n' | LC_ALL=C sort)"
expected_sorted="$(printf '%s\n' "${EXPECTED_FILES[@]}" | LC_ALL=C sort)"
if [[ "$actual_sorted" != "$expected_sorted" ]]; then
  echo "Test file drift detected." >&2
  echo "Expected:" >&2
  echo "$expected_sorted" >&2
  echo "Found:" >&2
  echo "$actual_sorted" >&2
  exit 1
fi

curl -fsSL -o "$DOWNLOAD/phpunit-4.8.36.phar" "https://phar.phpunit.de/phpunit-4.8.36.phar"
curl -fsSL -o "$DOWNLOAD/phpunit-4.8.36.phar.asc" "https://phar.phpunit.de/phpunit-4.8.36.phar.asc"
actual_sha="$(sha256sum "$DOWNLOAD/phpunit-4.8.36.phar" | awk '{print $1}')"
if [[ "$actual_sha" != "$PHPUNIT_SHA256" ]]; then
  echo "Unexpected PHPUnit PHAR hash: $actual_sha" >&2
  exit 1
fi

GNUPGHOME="$(mktemp -d)"
chmod 700 "$GNUPGHOME"
export GNUPGHOME
gpg --batch --keyserver hkps://keyserver.ubuntu.com --recv-keys 4AA394086372C20A >/dev/null
verify_out="$(gpg --batch --status-fd 1 --verify "$DOWNLOAD/phpunit-4.8.36.phar.asc" "$DOWNLOAD/phpunit-4.8.36.phar" 2>&1)"
echo "$verify_out" | grep -q "VALIDSIG"
echo "$verify_out" | grep -q "$PHPUNIT_FPR"
rm -rf "$GNUPGHOME"
unset GNUPGHOME

$DOCKER pull "$PHP_IMAGE" >/dev/null

php_version="$($DOCKER run --rm "$PHP_IMAGE" php -r 'echo PHP_VERSION;')"
if [[ "$php_version" != "5.6.40" ]]; then
  echo "Unexpected PHP version: $php_version" >&2
  exit 1
fi

run_phpunit() {
  local config_name="$1"
  local junit_name="$2"
  $DOCKER run --rm \
    -v "$ROOT:/src:ro" \
    -v "$DOWNLOAD/phpunit-4.8.36.phar:/opt/phpunit-4.8.36.phar:ro" \
    -v "$WORK:/work" \
    -e PHP_BINARY=/usr/local/bin/php \
    -w /src \
    "$PHP_IMAGE" \
    php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
      /opt/phpunit-4.8.36.phar \
      --bootstrap /src/tests/php/bootstrap.php \
      --configuration "/work/$config_name" \
      --log-junit "/work/$junit_name"
}

sorted_config="$WORK/phpunit-sorted.xml"
shuffled_config="$WORK/phpunit-shuffled.xml"
cat >"$sorted_config" <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="/src/tests/php/bootstrap.php" colors="true">
  <testsuites>
    <testsuite name="module-tests">
      <file>/src/tests/php/ConfigurationFormTest.php</file>
      <file>/src/tests/php/DirectoryGuardTest.php</file>
      <file>/src/tests/php/InstallAndUpgradeTest.php</file>
      <file>/src/tests/php/ModuleMetadataTest.php</file>
      <file>/src/tests/php/ViewedProductWidgetTest.php</file>
    </testsuite>
  </testsuites>
</phpunit>
EOF

$DOCKER run --rm \
  -v "$ROOT:/src:ro" \
  -v "$WORK:/work" \
  -w /work \
  "$PHP_IMAGE" \
  php -r '
$files = array(
  "/src/tests/php/ConfigurationFormTest.php",
  "/src/tests/php/DirectoryGuardTest.php",
  "/src/tests/php/InstallAndUpgradeTest.php",
  "/src/tests/php/ModuleMetadataTest.php",
  "/src/tests/php/ViewedProductWidgetTest.php",
);
srand(20261108);
for ($i = count($files) - 1; $i > 0; $i--) {
  $j = rand(0, $i);
  $tmp = $files[$i];
  $files[$i] = $files[$j];
  $files[$j] = $tmp;
}
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<phpunit bootstrap=\"/src/tests/php/bootstrap.php\" colors=\"true\">\n";
$xml .= "  <testsuites>\n    <testsuite name=\"module-tests\">\n";
foreach ($files as $file) {
  $xml .= "      <file>" . $file . "</file>\n";
}
$xml .= "    </testsuite>\n  </testsuites>\n</phpunit>\n";
file_put_contents("/work/phpunit-shuffled.xml", $xml);
'

sorted_out="$WORK/sorted.out"
shuffled_out="$WORK/shuffled.out"
run_phpunit "phpunit-sorted.xml" "junit-sorted.xml" | tee "$sorted_out"
run_phpunit "phpunit-shuffled.xml" "junit-shuffled.xml" | tee "$shuffled_out"

for out in "$sorted_out" "$shuffled_out"; do
  if ! grep -q "^OK ($EXPECTED_TEST_COUNT tests," "$out"; then
    echo "Missing clean OK ($EXPECTED_TEST_COUNT tests, ...) summary in $out" >&2
    cat "$out" >&2
    exit 1
  fi
  if grep -qiE '^(There were|WARN|RISKY|INCOMPLETE|Skipped|Tests:.*Skipped)' "$out"; then
    if grep -qiE 'Skipped|Risky|Incomplete|WARN' "$out"; then
      echo "Non-clean PHPUnit summary in $out" >&2
      cat "$out" >&2
      exit 1
    fi
  fi
  if grep -qiE 'Notice:|Warning:|Deprecated:|Strict Standards:|Parse error|Fatal error' "$out"; then
    echo "Diagnostic output detected in $out" >&2
    cat "$out" >&2
    exit 1
  fi
done

echo "Suite passed on PHP $php_version with sorted and shuffled orders."
