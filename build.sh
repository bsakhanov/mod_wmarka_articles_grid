#!/usr/bin/env bash
# Builds two archives in dist/:
#   mod_wmarka_articles_grid_v<version>.zip  — the module only
#   pkg_wmarka_articles_grid_v<version>.zip — the module + JUImage (installed only when the site has none)
# Needs bash and zip; checks PHP syntax when php is available.
set -euo pipefail
cd "$(dirname "$0")"

EL=mod_wmarka_articles_grid
PKG=pkg_wmarka_articles_grid
version() { sed -n 's:.*<version>\(.*\)</version>.*:\1:p' "$1" | head -1; }
VER=$(version "module/$EL.xml")
[ "$VER" = "$(version "package/$PKG.xml")" ] || { echo "Module and package versions differ" >&2; exit 1; }

JUIMAGE=$(ls third_party/juimage/lib_juimage_v*.zip | sort -V | tail -1)
(cd third_party/juimage && sha256sum -c --quiet SHA256SUMS)

if command -v php >/dev/null; then
    find module package -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
fi

rm -rf dist build && mkdir -p dist build/package/packages
(cd module && zip -qrX "../dist/${EL}_v${VER}.zip" . -x '.*')
cp -r package/. build/package/
cp "dist/${EL}_v${VER}.zip" "build/package/packages/${EL}.zip"
cp "$JUIMAGE" build/package/packages/lib_juimage.zip
(cd build/package && zip -qrX "../../dist/${PKG}_v${VER}.zip" .)
rm -rf build
(cd dist && sha256sum ./*.zip > SHA256SUMS)
echo "dist/${EL}_v${VER}.zip"
echo "dist/${PKG}_v${VER}.zip"
