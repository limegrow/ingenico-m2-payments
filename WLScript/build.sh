#!/bin/bash

CURRENT_DIR=$(pwd)
TMPDIR="/tmp"
BRANDS=(barclays postfinance kbc concardis viveum payglobe santander)
SOURCE_DIR="$TMPDIR/ingenico_epayments"
BUILD_DIR="$CURRENT_DIR/build"

echo "Source dir: $SOURCE_DIR"
echo "Build dir: $BUILD_DIR"

# Prepare temporary source dir
rm -rf "$SOURCE_DIR" > /dev/null
mkdir $SOURCE_DIR > /dev/null
rsync -av --progress "../" "$SOURCE_DIR" --exclude "$CURRENT_DIR" > /dev/null
cd "$SOURCE_DIR" > /dev/null

# Remove unnecessary files

#rm -rf "$SOURCE_DIR/WhiteLabelsScripts"
rm -rf "$SOURCE_DIR/.git" > /dev/null
rm -rf "$SOURCE_DIR/vendor/ingenico/ogone-sdk-php/.git" > /dev/null
rm -rf "$SOURCE_DIR/vendor/ingenico/ogone-client/.git" > /dev/null

# Install composer dependencies
if [ ! -d "$SOURCE_DIR/Vendor" ]; then
    mkdir $SOURCE_DIR/Vendor
    cd $SOURCE_DIR/Vendor

    read -r -d '' COMPOSER_JSON <<"EOF"
{
  "require": {
    "php": ">=7.0",
    "ext-curl": "*",
    "ext-mbstring": "*",
    "ext-bcmath": "*",
    "ingenico/ogone-sdk-php": ">=1.0.0",
    "ingenico/ogone-client": "dev-develop",
    "monolog/monolog": "^1.17"
  }
}

EOF
    echo $COMPOSER_JSON > ./composer.json

    # TODO: repositories should be public
    #composer require
    #composer require ingenico/ogone-sdk-php
    #composer require ingenico/ogone-client
    #composer config repositories.ingenico-sdk-php git git@bitbucket.org:ingenico-limegrow/ogone-sdk-php.git
    #composer config repositories.ingenico-core-library git git@bitbucket.org:ingenico-limegrow/core-library.git
    #composer install
    git clone git@bitbucket.org:ingenico-limegrow/ogone-sdk-php.git ogone-sdk-php
    git clone git@bitbucket.org:ingenico-limegrow/core-library.git ogone-client
    cd ogone-sdk-php
    git checkout develop
    rm -rf ./.git > /dev/null
    cd ../ogone-client
    git checkout develop
    rm -rf ./.git > /dev/null
    cd $SOURCE_DIR
fi

# Install gulp dependencies
npm install

# Prepare directory with build packages
#mkdir $CURRENT_DIR/build > /dev/null

# Start building
for brand in ${BRANDS[*]}
do
    echo "Building $brand..."
    "$CURRENT_DIR/mkbrand.sh" $brand "$SOURCE_DIR" "$BUILD_DIR" 2>&1 > "$TMPDIR/$brand.log"
    echo "done"
done

# Remove temporary files
rm -rf "$SOURCE_DIR" > /dev/null

echo "Finished. Packages are placed in $BUILD_DIR"
