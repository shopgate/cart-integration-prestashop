#!/bin/sh

ZIP_FILE_NAME=shopgate-prestashop-integration-${TAG_NAME}.zip

rm -rf vendor release/package $ZIP_FILE_NAME

mkdir release/package && mkdir release/package/shopgate
composer install -vvv --no-dev
rsync -av --exclude-from './release/exclude-filelist.txt' ./src/ release/package/shopgate
rsync -av ./vendor release/package/shopgate
rsync -av ./modman release/package/shopgate
rsync -av ./README.md release/package/shopgate
rsync -av ./LICENSE.md release/package/shopgate
rsync -av ./CONTRIBUTING.md release/package/shopgate
rsync -av ./CHANGELOG.md release/package/shopgate

cd release/package
zip -r ../$ZIP_FILE_NAME .
cd ..
rm -rf package
cd ..
rm -rf vendor
