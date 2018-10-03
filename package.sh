#!/usr/bin/env bash

#Script for create the plugin artifact

if [[ ! -v TRAVIS_TAG ]]; then
    TRAVIS_TAG='1.0.0'
fi

sed -i "s/PLUGIN_VERSION = '1.0.0';/PLUGIN_VERSION = '${TRAVIS_TAG}';/g" upload/system/library/TransbankSdkOnepay.php
sed -i "s/<version>1.0.0/<version>${TRAVIS_TAG}/g" install.xml

PLUGIN_FILE="plugin-transbank-onepay-$TRAVIS_TAG.ocmod.zip"

zip -FSr $PLUGIN_FILE . -x docs/\* *.git/\* .DS_Store* .editorconfig* .gitignore* .vscode/\* *.sh .travis* README.md *.zip docker-opencart3/\*

echo "Plugin version: $TRAVIS_TAG"
echo "Plugin file: $PLUGIN_FILE"
