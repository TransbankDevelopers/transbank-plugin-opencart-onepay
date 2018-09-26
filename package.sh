#!/usr/bin/env bash

zip -FSr transbank-onepay-1.0.0.ocmod.zip . -x docs/\* *.git/\* .DS_Store* .editorconfig* .gitignore* .vscode/\* package.sh .travis* README.md README_EN.md *.zip
