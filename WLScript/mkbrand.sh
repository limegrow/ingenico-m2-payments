#!/bin/bash

BRAND_ID=$1
CURRENT_DIR=$(pwd)
TMPDIR="/tmp"
SOURCE_DIR=$2
BUILD_DIR=$3
MAGENTO_PM_CODES=(e_payments alias afterpay banktransfer cc cb belfius cbc giropay ideal ing kbc klarna twint paypal paysafecard klarna_paynow klarna_paylater klarna_banktransfer klarna_directdebit klarna_financing flex)

case $1 in
     barclays)
          echo "Selected: barclays."
          MODULE_NAME="barclays_payments";
          MODULE_FILE="barclays_payments.php";
          MODULE_CLASS="Barclays_Payments";
          MODULE_BRAND="Barclays";
          MODULE_DESC="Barclays Payments";
          MODULE_AUTHOR="Barclays";
          MAGENTO_PM_PREFIX="barclays_"
          PLATFORM_ID="PLATFORM_BARCLAYS"

          COLOR_MEDIUM_BLUE="#085DA9";
          COLOR_MID_BLUE_TWO="#018FD0";
          COLOR_PINKISH_RED="#E03030";
          COLOR_WHITE_TWO="#F1F1F1";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#848789";
          ;;
     postfinance)
          echo "Selected: Postfinance."
          MODULE_NAME="postfinance";
          MODULE_FILE="postfinance.php";
          MODULE_CLASS="Postfinance";
          MODULE_BRAND="Postfinance";
          MODULE_DESC="PostFinance";
          MODULE_AUTHOR="PostFinance";
          MAGENTO_PM_PREFIX="postfinance_"
          PLATFORM_ID="PLATFORM_POSTFINANCE"

          COLOR_MEDIUM_BLUE="#FFCC00";
          COLOR_MID_BLUE_TWO="#2A6BAA";
          COLOR_PINKISH_RED="#FF0000";
          COLOR_WHITE_TWO="#F7F7F7";
          COLOR_WHITE_FIVE="#E6E6E6";
          COLOR_WHITE_GREY="#999999";
          ;;
     kbc)
          echo "Selected: KBC."
          MODULE_NAME="kbc";
          MODULE_FILE="kbc.php";
          MODULE_CLASS="Kbc";
          MODULE_BRAND="Kbc";
          MODULE_DESC="KBC";
          MODULE_AUTHOR="KBC";
          MAGENTO_PM_PREFIX="kbc_"
          PLATFORM_ID="PLATFORM_KBC"

          COLOR_MEDIUM_BLUE="#00ADEE";
          COLOR_MID_BLUE_TWO="#003768";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";
          ;;
     concardis)
          echo "Selected: Concardis."
          MODULE_NAME="concardis";
          MODULE_FILE="concardis.php";
          MODULE_CLASS="Concardis";
          MODULE_BRAND="Concardis";
          MODULE_DESC="Concardis";
          MODULE_AUTHOR="ConCardis GmbH";
          MAGENTO_PM_PREFIX="concardis_"
          PLATFORM_ID="PLATFORM_CONCARDIS"

          COLOR_MEDIUM_BLUE="#DC4405";
          COLOR_MID_BLUE_TWO="#DC4405";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";
          ;;
     viveum)
          echo "Selected: Viveum."
          MODULE_NAME="viveum";
          MODULE_FILE="viveum.php";
          MODULE_CLASS="Viveum";
          MODULE_BRAND="Viveum";
          MODULE_DESC="Viveum";
          MODULE_AUTHOR="VIVEUM";
          MAGENTO_PM_PREFIX="viveum_"
          PLATFORM_ID="PLATFORM_VIVEUM"

          COLOR_MEDIUM_BLUE="#020D5C";
          COLOR_MID_BLUE_TWO="#353d7d";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";
          ;;
     payglobe)
          echo "Selected: Payglobe."
          MODULE_NAME="payglobe";
          MODULE_FILE="payglobe.php";
          MODULE_CLASS="Payglobe";
          MODULE_BRAND="Payglobe";
          MODULE_DESC="Payglobe";
          MODULE_AUTHOR="Payglobe";
          MAGENTO_PM_PREFIX="payglobe_"
          PLATFORM_ID="PLATFORM_PAYGLOBE"

          COLOR_MEDIUM_BLUE="#173A7E";
          COLOR_MID_BLUE_TWO="#e1a449";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";
          ;;
     santander)
          echo "Selected: Santander."
          MODULE_NAME="santander";
          MODULE_FILE="santander.php";
          MODULE_CLASS="Santander";
          MODULE_BRAND="Santander";
          MODULE_DESC="Santander";
          MODULE_AUTHOR="Santander";
          MAGENTO_PM_PREFIX="santander_"
          PLATFORM_ID="PLATFORM_SANTANDER"

          COLOR_MEDIUM_BLUE="#E82729";
          COLOR_MID_BLUE_TWO="#E82729";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";
          ;;
     *)
          echo "Please select template."
          exit
          ;;
esac


# See https://limegrow.atlassian.net/wiki/spaces/ING/pages/694157313/White+labels+brands+details
# See https://limegrow.atlassian.net/wiki/spaces/ING/pages/249135126/White+labels

# Temporary module dir
MODULE_DIR="$TMPDIR/$MODULE_NAME"

# Copy original module to directory of plugin
cp -r "$SOURCE_DIR/" "$MODULE_DIR/"
cd "$MODULE_DIR/"

# Change the branding in Magento Payment Methods
for code in "${MAGENTO_PM_CODES[@]}"
do
    INGENICO_PM_CODE="ingenico_$code"
    BRANDED_PM_CODE="$MAGENTO_PM_PREFIX$code"

    # config.xml
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/etc/config.xml

	# payment.xml
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/etc/payment.xml

	# layouts
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/view/frontend/layout/checkout_index_index.xml
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/view/frontend/layout/multishipping_checkout_billing.xml

	# JS files
	sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/abstract.js
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer.js
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/abstract.js
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/alias.js
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/ideal.js
	sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/flex.js
	sed -i -e "s/\.ingenico\./\.$MODULE_NAME\./g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer.js
	sed -i -e "s/\.ingenico\./\.$MODULE_NAME\./g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/abstract.js
	sed -i -e "s/\.ingenico\./\.$MODULE_NAME\./g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/alias.js
	sed -i -e "s/\.ingenico\./\.$MODULE_NAME\./g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/ideal.js
	sed -i -e "s/\.ingenico\./\.$MODULE_NAME\./g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/flex.js

	# system/*.xml
	find $MODULE_DIR/etc/adminhtml/system/ -name '*.xml' -type f|while read fname; do
	    sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" "$fname"
    done

    # Models in /Model/Method/*.php
	find $MODULE_DIR/Model/Method/ -name '*.php' -type f|while read fname; do
	    sed -i -e "s/$INGENICO_PM_CODE/$BRANDED_PM_CODE/g" "$fname"
    done
done

# Change branding in configuration XML files
# Replace: Ingenico ePayments
sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" $MODULE_DIR/etc/config.xml
sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" $MODULE_DIR/etc/adminhtml/system.xml
sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" $MODULE_DIR/etc/acl.xml

# Replace: ingenico_e_payments
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/etc/adminhtml/system.xml

# Replace: Ingenico
sed -i -e "s/Ingenico /$MODULE_BRAND/g" $MODULE_DIR/etc/adminhtml/system.xml
sed -i -e "s/Ingenico /$MODULE_BRAND/g" $MODULE_DIR/etc/email_templates.xml

# Change namespace for Models
find $MODULE_DIR/etc/adminhtml/system/ -name '*.xml' -type f|while read fname; do
  sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" "$fname"
  sed -i -e "s/Ingenico /$MODULE_BRAND/g" "$fname"
  sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" "$fname"
done

sed -i -e "s/ingenico_settings/$MODULE_NAME\_settings/g" $MODULE_DIR/etc/config.xml

sed -i -e "s/ingenico_/$MODULE_NAME\_/g" $MODULE_DIR/etc/adminhtml/system.xml
sed -i -e "s/id=\"ingenico\"/id=\"$MODULE_NAME\"/g" $MODULE_DIR/etc/adminhtml/system.xml
sed -i -e "s/>ingenico</>$MODULE_NAME</g" $MODULE_DIR/etc/adminhtml/system.xml

# Change url base part
sed -i -e "s/frontName=\"ingenico\"/frontName=\"$MODULE_NAME\"/g" $MODULE_DIR/etc/adminhtml/routes.xml
sed -i -e "s/frontName=\"ingenico\"/frontName=\"$MODULE_NAME\"/g" $MODULE_DIR/etc/frontend/routes.xml
find $MODULE_DIR/ -name '*.php' -type f|while read fname; do
  sed -i -e "s/ingenico\/payment/$MODULE_NAME\/payment/g" "$fname"
done

# Change branding in Config.php file
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/Model/Config.php
sed -i -e "s/ingenico\_/$MODULE_NAME\_/g" $MODULE_DIR/Model/Config.php
sed -i -e "s/\%ingenico\%/\%$MODULE_NAME\%/g" $MODULE_DIR/Model/Config.php

# Change branding in Connector.php file
sed -i -e "s/PLATFORM_INGENICO/$PLATFORM_ID/g" $MODULE_DIR/Model/Connector.php
sed -i -e "s/ingenico\_payment\_page/$MODULE_NAME\_payment\_page/g" $MODULE_DIR/Model/Connector.php
sed -i -e "s/ingenico\_settings/$MODULE_NAME\_settings/g" $MODULE_DIR/Model/Connector.php

# Change branding in IngenicoConfigProvider.php file
sed -i -e "s/ingenico\_payment\_page/$MODULE_NAME\_payment\_page/g" $MODULE_DIR/Model/IngenicoConfigProvider.php
sed -i -e "s@'ingenico'@'$MODULE_NAME'@g" $MODULE_DIR/Model/IngenicoConfigProvider.php

# Change branding in AbstractMethod.php file
#sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/Model/Method/Ingenico.php

# Change branding in Plugin files
find $MODULE_DIR/Plugin/ -name '*.php' -type f|while read fname; do
  sed -i -e "s/ingenico_/$MODULE_NAME\_/g" "$fname"
done

# Change branding in translation files
find $MODULE_DIR/po/ -iname *.po -type f|while read fname; do
  sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" "$fname"
  sed -i -e "s/Ingenico/$MODULE_BRAND/g" "$fname"
done

# Replace logo
cp "$SOURCE_DIR/WLScript/resources/$BRAND_ID/logo.png" $MODULE_DIR/view/adminhtml/web/images/logo_provider.png
cp "$SOURCE_DIR/WLScript/resources/$BRAND_ID/logo.png" $MODULE_DIR/view/frontend/web/images/logo_provider.png

# Change branding in help files
sed -i -e "s/inline_ingenico.png/inline\_$MODULE_NAME\.png/g" $MODULE_DIR/view/adminhtml/templates/help_content.phtml

# Replace images
cp -f "$SOURCE_DIR/WLScript/resources/$BRAND_ID/inline_off.png" $MODULE_DIR/view/adminhtml/web/images/help_images/inline_off.png

# Replace colors
sed -i -e "s/#306ba8/$COLOR_MEDIUM_BLUE/g" $MODULE_DIR/view/frontend/web/css/ingenico.css
sed -i -e "s/#2a6baa/$COLOR_MID_BLUE_TWO/g" $MODULE_DIR/view/frontend/web/css/ingenico.css
sed -i -e "s/#eb222e/$COLOR_PINKISH_RED/g" $MODULE_DIR/view/frontend/web/css/ingenico.css
sed -i -e "s/#f6f6f6/$COLOR_WHITE_TWO/g" $MODULE_DIR/view/frontend/web/css/ingenico.css
sed -i -e "s/#dddddd/$COLOR_WHITE_FIVE/g" $MODULE_DIR/view/frontend/web/css/ingenico.css
sed -i -e "s/#afafaf/$COLOR_WHITE_GREY/g" $MODULE_DIR/view/frontend/web/css/ingenico.css

# Remove temporary files
rm -rf "$MODULE_DIR/node_modules"
rm -rf "$MODULE_DIR/WLScript"
rm -rf "$MODULE_DIR/.gitignore"
rm -rf "$MODULE_DIR/bitbucket-pipelines.yml"
rm -rf "$MODULE_DIR/package-lock.json"
find $MODULE_DIR/ -name '*.phpg' -type f|while read fname; do
  rm -f "$fname"
done

# Make package
cd $TMPDIR
zip -r "$MODULE_NAME.zip" "./$MODULE_NAME/"
mv "$TMPDIR/$MODULE_NAME.zip" "$BUILD_DIR/"
rm -rf "$MODULE_DIR/"
