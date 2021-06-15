#!/bin/bash
CURRENT_DIR=$(pwd)
TMPDIR="/tmp"

mkdir $TMPDIR/m2-payment/
cp -R -f $CURRENT_DIR/* $TMPDIR/m2-payment/

cd $TMPDIR/m2-payment/
rm -rf ./.git
rm -rf ./.github
rm -rf ./WLScript
rm -rf ./.gitignore
rm -rf ./bitbucket-pipelines.yml
rm -rf ./build.sh

zip -r ./m2-payment.zip ./
mv ./m2-payment.zip $CURRENT_DIR/m2-payment.zip
rm -rf $TMPDIR/m2-payment

echo "Finished. Package located in $CURRENT_DIR/m2-payment.zip"
