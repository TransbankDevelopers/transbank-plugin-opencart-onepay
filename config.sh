#!/usr/bin/env bash

#Script for configure the plugin project

PHP_SDK_VERSION='1.3.2'
REPO_SDK='https://github.com/TransbankDevelopers/transbank-sdk-php.git'
DIR_DEST_SDK='upload/system/library/transbank-sdk-php'

echo "Removing the older SDK"
rm -rf upload/system/library/transbank-sdk-php

echo "Cloning SDK version: $PHP_SDK_VERSION"
git clone https://github.com/TransbankDevelopers/transbank-sdk-php.git $DIR_DEST_SDK

echo "Changing to SDK version: $PHP_SDK_VERSION"
cd $DIR_DEST_SDK
git fetch && git fetch --tags
git checkout $PHP_SDK_VERSION
git status
echo "SDK version: $PHP_SDK_VERSION"
cd ../../../../
