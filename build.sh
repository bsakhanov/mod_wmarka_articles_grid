#!/usr/bin/env bash
# Builds the release into dist/ and refreshes updates.xml in the repository root:
#   pkg_wmarka_articles_grid_v<version>.zip        — module + JUImage (installed only when the site has none); updates the package
#   mod_wmarka_articles_grid_v<version>.zip         — the module alone, with its own update server
#   mod_wmarka_articles_grid_v<version>_files.zip   — module files laid out from the site root (modules/, language/) for unpacking over an installed module
#   updates.xml, updates_module.xml — what Joomla reads to offer the update (package / standalone module):
#                                 version, download links, SHA-256. Joomla keeps one entry per file, hence two files.
# Needs bash, zip, sha256sum; checks PHP syntax when php is available.
set -euo pipefail
cd "$(dirname "$0")"

OWNER=bsakhanov
EL=mod_wmarka_articles_grid
PKG=pkg_wmarka_articles_grid
NAME="Webmarka Articles Grid"
REPO="https://github.com/$OWNER/$EL"
RAW="https://raw.githubusercontent.com/$OWNER/$EL/main"

version() { sed -n 's:.*<version>\(.*\)</version>.*:\1:p' "$1" | head -1; }
VER=$(version "module/$EL.xml")
[ "$VER" = "$(version "package/$PKG.xml")" ] || { echo "Module and package versions differ" >&2; exit 1; }
DL="$REPO/releases/download/v$VER"

JUIMAGE=$(ls third_party/juimage/lib_juimage_v*.zip | sort -V | tail -1)
(cd third_party/juimage && sha256sum -c --quiet SHA256SUMS)

if command -v php >/dev/null; then
    find module package -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
fi

rm -rf dist build && mkdir -p dist build/package/packages build/standalone build/files/modules

# 1. Module for the package: no update server of its own (the package announces updates, no duplicates).
(cd module && zip -qrX "../build/package/packages/$EL.zip" . -x '.*')

# 2. Standalone module: same files, its manifest gets the update server and the changelog.
cp -r module/. build/standalone/
sed -i "s#</extension>#    <changelogurl>$RAW/changelog.xml</changelogurl>\n    <updateservers>\n        <server type=\"extension\" priority=\"1\" name=\"$EL\">$RAW/updates_module.xml</server>\n    </updateservers>\n</extension>#" "build/standalone/$EL.xml"
(cd build/standalone && zip -qrX "../../dist/${EL}_v${VER}.zip" . -x '.*')

# 3. Package: module + JUImage archive (installed by script.php only when missing).
cp -r package/. build/package/
cp "$JUIMAGE" build/package/packages/lib_juimage.zip
(cd build/package && zip -qrX "../../dist/${PKG}_v${VER}.zip" .)

# 4. Files from the site root: modules/<module>/ and language/<tag>/<module>.ini.
cp -r module "build/files/modules/$EL"   # clean manifest: unpacking over a package install must not add an update server
rm -rf "build/files/modules/$EL/language"
for tag in module/language/*/; do
    tag=$(basename "$tag"); mkdir -p "build/files/language/$tag"
    cp "module/language/$tag/"*.ini "build/files/language/$tag/"
done
(cd build/files && zip -qrX "../../dist/${EL}_v${VER}_files.zip" .)
rm -rf build

(cd dist && sha256sum ./*.zip > SHA256SUMS)
sha() { sha256sum "dist/$1" | cut -d' ' -f1; }

# 5. Update files: Joomla keeps only one entry per update file, so the package and the standalone module get one each.
cat > updates.xml <<XML
<?xml version="1.0" encoding="utf-8"?>
<updates>
    <update>
        <name>$NAME (package)</name>
        <description>$NAME $VER</description>
        <element>$PKG</element>
        <type>package</type>
        <client>site</client>
        <version>$VER</version>
        <infourl title="$NAME $VER">$REPO/releases/tag/v$VER</infourl>
        <downloads>
            <downloadurl type="full" format="zip">$DL/${PKG}_v${VER}.zip</downloadurl>
        </downloads>
        <sha256>$(sha "${PKG}_v${VER}.zip")</sha256>
        <changelogurl>$RAW/changelog.xml</changelogurl>
        <maintainer>Beibit Sakhanov</maintainer>
        <maintainerurl>https://webmarka.kz</maintainerurl>
        <targetplatform name="joomla" version="[56]\.[0-9]+" />
        <php_minimum>8.1</php_minimum>
    </update>
</updates>
XML

cat > updates_module.xml <<XML
<?xml version="1.0" encoding="utf-8"?>
<updates>
    <update>
        <name>$NAME</name>
        <description>$NAME $VER</description>
        <element>$EL</element>
        <type>module</type>
        <client>site</client>
        <version>$VER</version>
        <infourl title="$NAME $VER">$REPO/releases/tag/v$VER</infourl>
        <downloads>
            <downloadurl type="full" format="zip">$DL/${EL}_v${VER}.zip</downloadurl>
        </downloads>
        <sha256>$(sha "${EL}_v${VER}.zip")</sha256>
        <changelogurl>$RAW/changelog.xml</changelogurl>
        <maintainer>Beibit Sakhanov</maintainer>
        <maintainerurl>https://webmarka.kz</maintainerurl>
        <targetplatform name="joomla" version="[56]\.[0-9]+" />
        <php_minimum>8.1</php_minimum>
    </update>
</updates>
XML

ls -1 dist
echo "updates.xml, updates_module.xml → $VER"
