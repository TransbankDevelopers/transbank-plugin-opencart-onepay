#!/usr/bin/env bash

#Script for create the plugin artifact

echo "Travis tag: $TRAVIS_TAG"

if [[ ! -v TRAVIS_TAG ]]; then
    TRAVIS_TAG='1.0.0'
fi

sed -i "s/PLUGIN_VERSION = '1.0.0';/PLUGIN_VERSION = '${TRAVIS_TAG}';/g" src/upload/system/library/TransbankSdkOnepay.php
sed -i "s/<version>1.0.0/<version>${TRAVIS_TAG}/g" src/install.xml

PLUGIN_FILE="plugin-transbank-onepay-opencart3-$TRAVIS_TAG.ocmod.zip"

cp CHANGELOG.md src/
cp LICENSE src/
cd src
zip -FSr ../$PLUGIN_FILE . -x *.git/\* .DS_Store* *.zip
cd ..
rm src/CHANGELOG.md
rm src/LICENSE

echo "Plugin version: $TRAVIS_TAG"
echo "Plugin file: $PLUGIN_FILE"
