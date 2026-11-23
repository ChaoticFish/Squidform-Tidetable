#!/usr/bin/env bash
# Builds Tide Table into build/:
#   pkg_tidetable-<version>.zip          the package to install (after SquidForm 1.0.3 or later)
#   pkg_tidetable-<version>-source.zip   everything in this repository
# Each part (mod_tidetable, plg_squidform_tidetable, plg_task_tidetable) is zipped into packages/
# inside the package, as Joomla expects. Checks: versions match across manifests, a CHANGELOG
# entry, PHP syntax, and no outside addresses in shipped JavaScript or CSS.
set -euo pipefail
cd "$(dirname "$0")"
VERSION=$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' pkg_tidetable.xml | head -1)
PARTS=(mod_tidetable plg_squidform_tidetable plg_task_tidetable)
fail() { echo "BUILD STOPPED: $*" >&2; exit 1; }
echo "Tide Table $VERSION"
for p in "${PARTS[@]}"; do
  m=$(ls "$p"/*.xml | head -1); v=$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' "$m" | head -1)
  [ "$v" = "$VERSION" ] || fail "$m says $v, pkg_tidetable.xml says $VERSION"
done
grep -q "^## $VERSION\b" CHANGELOG.md || fail "CHANGELOG.md has no \"## $VERSION\" entry"
command -v php >/dev/null && find "${PARTS[@]}" script.php -name '*.php' -print0 | xargs -0 -n1 php -l > /dev/null
if grep -RInE 'https?://[A-Za-z0-9-]+\.[A-Za-z]{2,}' --include='*.js' --include='*.css' "${PARTS[@]}" | grep -vE 'www\.w3\.org/(2000/svg|1999/xlink)'; then
  fail "an outside address in shipped JavaScript or CSS (Tide Table works offline)"
fi
WORK=$(mktemp -d); trap 'rm -rf "$WORK"' EXIT
mkdir -p "$WORK/packages" build
for p in "${PARTS[@]}"; do (cd "$p" && zip -qrX "$WORK/packages/$p.zip" . -x '*.DS_Store'); done
cp pkg_tidetable.xml script.php README.md LICENSE.txt "$WORK/"
rm -f "build/pkg_tidetable-$VERSION.zip" "build/pkg_tidetable-$VERSION-source.zip"
(cd "$WORK" && zip -qrX "$OLDPWD/build/pkg_tidetable-$VERSION.zip" .)
zip -qrX "build/pkg_tidetable-$VERSION-source.zip" . -x '.git/*' -x 'build/*' -x '*.DS_Store'
(cd build && sha256sum "pkg_tidetable-$VERSION.zip" > "pkg_tidetable-$VERSION.zip.sha256")
echo "Built build/pkg_tidetable-$VERSION.zip"; cat "build/pkg_tidetable-$VERSION.zip.sha256"
