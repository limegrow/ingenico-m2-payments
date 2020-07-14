#!/bin/bash

BRAND_ID=$1
CURRENT_DIR=$(pwd)
TMPDIR="/tmp"
SOURCE_DIR=$2
BUILD_DIR=$3

case $1 in
     barclays)
          echo "Selected: barclays."
          MODULE_NAME="barclays_payments";
          MODULE_FILE="barclays_payments.php";
          MODULE_CLASS="Barclays_Payments";
          MODULE_BRAND="Barclays";
          MODULE_DESC="Barclays Payments";
          MODULE_AUTHOR="Barclays";

          LOGO_URL="https://barclays.co.uk";

          TEMPLATE_GUIDE_ECOM="https://support.epdq.co.uk/en/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template/";
          TEMPLATE_GUIDE_FLEX="https://support.epdq.co.uk/en/integration/all-sales-channels/flexcheckout/guide#customization"
          TEMPLATE_GUIDE_PAYPAL="https://support.epdq.co.uk/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration"

          SUPPORT_EMAIL="epdqsupport@barclaycard.co.uk";
          SUPPORT_NAME="Barclays Support";
          SUPPORT_PHONE="0844 8240230";
          SUPPORT_URL="08448240230";
          SUPPORT_TICKET_PLACEHOLDER="XXX-XXX";

          COLOR_MEDIUM_BLUE="#085DA9";
          COLOR_MID_BLUE_TWO="#018FD0";
          COLOR_PINKISH_RED="#E03030";
          COLOR_WHITE_TWO="#F1F1F1";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#848789";

          API_ECOMMERCE_TEST="https://mdepayments.epdq.co.uk/ncol/test/orderstandard_utf8.asp";
          API_ECOMMERCE_PROD="https://payments.epdq.co.uk/ncol/prod/orderstandard_utf8.asp";
          API_FLEXCHECKOUT_TEST="https://mdepayments.epdq.co.uk/Tokenization/HostedPage";
          API_FLEXCHECKOUT_PROD="https://payments.epdq.co.uk/Tokenization/HostedPage";
          API_DIRECTLINK_TEST="https://mdepayments.epdq.co.uk/ncol/test/querydirect_utf8.asp";
          API_DIRECTLINK_PROD="https://payments.epdq.co.uk/ncol/prod/querydirect_utf8.asp";
          API_DIRECTLINK_ORDER_TEST="https://mdepayments.epdq.co.uk/ncol/test/orderdirect_utf8.asp";
          API_DIRECTLINK_ORDER_PROD="https://payments.epdq.co.uk/ncol/prod/orderdirect_utf8.asp";
          API_MAINTENANCE_TEST="https://mdepayments.epdq.co.uk/ncol/test/maintenancedirect_utf8.asp";
          API_MAINTENANCE_PROD="https://payments.epdq.co.uk/ncol/prod/maintenancedirect_utf8.asp";
          API_ALIAS_REQUEST_TEST="https://mdepayments.epdq.co.uk/ncol/test/alias_gateway_utf8.asp";
          API_ALIAS_REQUEST_PROD="https://payments.epdq.co.uk/ncol/prod/alias_gateway_utf8.asp";

          COMPOSER_SDK="ingenico/ogone-sdk-php-barclays"
          COMPOSER_CLIENT="ingenico/ogone-client-barclays"
          ;;
     postfinance)
          echo "Selected: Postfinance."
          MODULE_NAME="postfinance";
          MODULE_FILE="postfinance.php";
          MODULE_CLASS="Postfinance";
          MODULE_BRAND="Postfinance";
          MODULE_DESC="PostFinance";
          MODULE_AUTHOR="PostFinance";

          LOGO_URL="https://www.postfinance.ch";

          TEMPLATE_GUIDE_ECOM="https://e-payment-postfinance.v-psp.com/en/en/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template/";
          TEMPLATE_GUIDE_FLEX="https://e-payment-postfinance.ecom-psp.com/en/integration/all-sales-channels/flexcheckout/guide#customization"
          TEMPLATE_GUIDE_PAYPAL="https://e-payment-postfinance.ecom-psp.com/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration"

          SUPPORT_EMAIL="merchanthelp@postfinance.ch";
          SUPPORT_NAME="Postfinance Support";
          SUPPORT_PHONE="+41 848 382 423";
          SUPPORT_URL="+41848382423";
          SUPPORT_TICKET_PLACEHOLDER="XXX-XXX";

          COLOR_MEDIUM_BLUE="#FFCC00";
          COLOR_MID_BLUE_TWO="#2A6BAA";
          COLOR_PINKISH_RED="#FF0000";
          COLOR_WHITE_TWO="#F7F7F7";
          COLOR_WHITE_FIVE="#E6E6E6";
          COLOR_WHITE_GREY="#999999";

          API_ECOMMERCE_TEST="https://e-payment.postfinance.ch/ncol/test/orderstandard_utf8.asp";
          API_ECOMMERCE_PROD="https://e-payment.postfinance.ch/ncol/prod/orderstandard_utf8.asp";
          API_FLEXCHECKOUT_TEST="https://postfinance.test.v-psp.com/Tokenization/HostedPage";
          API_FLEXCHECKOUT_PROD="https://e-payment.postfinance.ch/Tokenization/HostedPage";
          API_DIRECTLINK_TEST="https://e-payment.postfinance.ch/ncol/test/querydirect_utf8.asp";
          API_DIRECTLINK_PROD="https://e-payment.postfinance.ch/ncol/prod/querydirect_utf8.asp";
          API_DIRECTLINK_ORDER_TEST="https://e-payment.postfinance.ch/ncol/test/orderdirect_utf8.asp";
          API_DIRECTLINK_ORDER_PROD="https://e-payment.postfinance.ch/ncol/prod/orderdirect_utf8.asp";
          API_MAINTENANCE_TEST="https://e-payment.postfinance.ch/ncol/test/maintenancedirect_utf8.asp";
          API_MAINTENANCE_PROD="https://e-payment.postfinance.ch/ncol/prod/maintenancedirect_utf8.asp";
          API_ALIAS_REQUEST_TEST="https://e-payment.postfinance.ch/ncol/test/alias_gateway_utf8.asp";
          API_ALIAS_REQUEST_PROD="https://e-payment.postfinance.ch/ncol/prod/alias_gateway_utf8.asp";

          COMPOSER_SDK="ingenico/ogone-sdk-php-postfinance"
          COMPOSER_CLIENT="ingenico/ogone-client-postfinance"
          ;;
     kbc)
          echo "Selected: KBC."
          MODULE_NAME="kbc";
          MODULE_FILE="kbc.php";
          MODULE_CLASS="Kbc";
          MODULE_BRAND="Kbc";
          MODULE_DESC="KBC";
          MODULE_AUTHOR="KBC";

          LOGO_URL="https://www.kbc.com";

          TEMPLATE_GUIDE_ECOM="https://support-paypage.v-psp.com/en/en/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template/";
          TEMPLATE_GUIDE_FLEX="https://support-paypage.ecom-psp.com/en/integration/all-sales-channels/flexcheckout/guide#customization"
          TEMPLATE_GUIDE_PAYPAL="https://support-paypage.ecom-psp.com/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration"

          SUPPORT_EMAIL="epay@kbc.be";
          SUPPORT_NAME="KBC Support";
          SUPPORT_PHONE="+32 78 15 21 53";
          SUPPORT_URL="+3278152153";
          SUPPORT_TICKET_PLACEHOLDER="XXX-XXX";

          COLOR_MEDIUM_BLUE="#00ADEE";
          COLOR_MID_BLUE_TWO="#003768";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";

          API_ECOMMERCE_TEST="https://secure.paypage.be/ncol/test/orderstandard_utf8.asp";
          API_ECOMMERCE_PROD="https://secure.paypage.be/ncol/prod/orderstandard_utf8.asp";
          API_FLEXCHECKOUT_TEST="https://paypage.test.v-psp.com/Tokenization/HostedPage";
          API_FLEXCHECKOUT_PROD="https://secure.paypage.be/Tokenization/HostedPage";
          API_DIRECTLINK_TEST="https://secure.paypage.be/ncol/test/querydirect_utf8.asp";
          API_DIRECTLINK_PROD="https://secure.paypage.be/ncol/prod/querydirect_utf8.asp";
          API_DIRECTLINK_ORDER_TEST="https://secure.paypage.be/ncol/test/orderdirect_utf8.asp";
          API_DIRECTLINK_ORDER_PROD="https://secure.paypage.be/ncol/prod/orderdirect_utf8.asp";
          API_MAINTENANCE_TEST="https://secure.paypage.be/ncol/test/maintenancedirect_utf8.asp";
          API_MAINTENANCE_PROD="https://secure.paypage.be/ncol/prod/maintenancedirect_utf8.asp";
          API_ALIAS_REQUEST_TEST="https://secure.paypage.be/ncol/test/alias_gateway_utf8.asp";
          API_ALIAS_REQUEST_PROD="https://secure.paypage.be/ncol/prod/alias_gateway_utf8.asp";

          COMPOSER_SDK="ingenico/ogone-sdk-php-kbc"
          COMPOSER_CLIENT="ingenico/ogone-client-kbc"
          ;;
     concardis)
          echo "Selected: Concardis."
          MODULE_NAME="concardis";
          MODULE_FILE="concardis.php";
          MODULE_CLASS="Concardis";
          MODULE_BRAND="Concardis";
          MODULE_DESC="Concardis";
          MODULE_AUTHOR="ConCardis GmbH";

          LOGO_URL="https://www.concardis.com/";

          TEMPLATE_GUIDE_ECOM="https://support-payengine.v-psp.com/en/en/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template/";
          TEMPLATE_GUIDE_FLEX="https://support-payengine.ecom-psp.com/en/integration/all-sales-channels/flexcheckout/guide#customization"
          TEMPLATE_GUIDE_PAYPAL="https://support-payengine.ecom-psp.com/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration"

          SUPPORT_EMAIL="support@payengine.com";
          SUPPORT_NAME="Concardis Support";
          SUPPORT_PHONE="https://payengine.com/support/phone";
          SUPPORT_URL="https://payengine.com/support/phone";
          SUPPORT_TICKET_PLACEHOLDER="XXX-XXX";

          COLOR_MEDIUM_BLUE="#DC4405";
          COLOR_MID_BLUE_TWO="#DC4405";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";

          API_ECOMMERCE_TEST="https://secure.payengine.de/ncol/test/orderstandard_utf8.asp";
          API_ECOMMERCE_PROD="https://secure.payengine.de/ncol/prod/orderstandard_utf8.asp";
          API_FLEXCHECKOUT_TEST="https://payengine.test.v-psp.com/Tokenization/Hostedpage";
          API_FLEXCHECKOUT_PROD="https://secure.payengine.de/Tokenization/HostedPage";
          API_DIRECTLINK_TEST="https://secure.payengine.de/ncol/test/querydirect_utf8.asp";
          API_DIRECTLINK_PROD="https://secure.payengine.de/ncol/prod/querydirect_utf8.asp";
          API_DIRECTLINK_ORDER_TEST="https://secure.payengine.de/ncol/test/orderdirect_utf8.asp";
          API_DIRECTLINK_ORDER_PROD="https://secure.payengine.de/ncol/prod/orderdirect_utf8.asp";
          API_MAINTENANCE_TEST="https://secure.payengine.de/ncol/test/maintenancedirect_utf8.asp";
          API_MAINTENANCE_PROD="https://secure.payengine.de/ncol/prod/maintenancedirect_utf8.asp";
          API_ALIAS_REQUEST_TEST="https://secure.payengine.de/ncol/test/alias_gateway_utf8.asp";
          API_ALIAS_REQUEST_PROD="https://secure.payengine.de/ncol/prod/alias_gateway_utf8.asp";

          COMPOSER_SDK="ingenico/ogone-sdk-php-concardis"
          COMPOSER_CLIENT="ingenico/ogone-client-concardis"
          ;;
     viveum)
          echo "Selected: Viveum."
          MODULE_NAME="viveum";
          MODULE_FILE="viveum.php";
          MODULE_CLASS="Viveum";
          MODULE_BRAND="Viveum";
          MODULE_DESC="Viveum";
          MODULE_AUTHOR="VIVEUM";

          LOGO_URL="https://www.viveum.com/";

          TEMPLATE_GUIDE_ECOM="https://support-viveum.v-psp.com/en/en/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template/";
          TEMPLATE_GUIDE_FLEX="https://support-viveum.ecom-psp.com/en/integration/all-sales-channels/flexcheckout/guide#customization"
          TEMPLATE_GUIDE_PAYPAL="https://support-viveum.ecom-psp.com/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration"

          SUPPORT_EMAIL="support@viveum.com";
          SUPPORT_NAME="Viveum Support";
          SUPPORT_PHONE="https://www.viveum.com/support/";
          SUPPORT_URL="https://www.viveum.com/support/";
          SUPPORT_TICKET_PLACEHOLDER="XXX-XXX";

          COLOR_MEDIUM_BLUE="#020D5C";
          COLOR_MID_BLUE_TWO="#353d7d";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";

          API_ECOMMERCE_TEST="https://viveum.v-psp.com/ncol/test/orderstandard_utf8.asp";
          API_ECOMMERCE_PROD="https://viveum.v-psp.com/ncol/prod/orderstandard_utf8.asp";
          API_FLEXCHECKOUT_TEST="https://viveum.test.v-psp.com/Tokenization/HostedPage";
          API_FLEXCHECKOUT_PROD="https://viveum.v-psp.com/Tokenization/HostedPage";
          API_DIRECTLINK_TEST="https://viveum.v-psp.com/ncol/test/querydirect_utf8.asp";
          API_DIRECTLINK_PROD="https://viveum.v-psp.com/ncol/prod/querydirect_utf8.asp";
          API_DIRECTLINK_ORDER_TEST="https://viveum.v-psp.com/ncol/test/orderdirect_utf8.asp";
          API_DIRECTLINK_ORDER_PROD="https://viveum.v-psp.com/ncol/prod/orderdirect_utf8.asp";
          API_MAINTENANCE_TEST="https://viveum.v-psp.com/ncol/test/maintenancedirect_utf8.asp";
          API_MAINTENANCE_PROD="https://viveum.v-psp.com/ncol/prod/maintenancedirect_utf8.asp";
          API_ALIAS_REQUEST_TEST="https://viveum.v-psp.com/ncol/test/alias_gateway_utf8.asp";
          API_ALIAS_REQUEST_PROD="https://viveum.v-psp.com/ncol/prod/alias_gateway_utf8.asp";

          COMPOSER_SDK="ingenico/ogone-sdk-php-viveum"
          COMPOSER_CLIENT="ingenico/ogone-client-viveum"
          ;;
     payglobe)
          echo "Selected: Payglobe."
          MODULE_NAME="payglobe";
          MODULE_FILE="payglobe.php";
          MODULE_CLASS="Payglobe";
          MODULE_BRAND="Payglobe";
          MODULE_DESC="Payglobe";
          MODULE_AUTHOR="Payglobe";

          LOGO_URL="https://eupayglobe.com";

          TEMPLATE_GUIDE_ECOM="https://support.eupayglobe.com/en/en/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template/";
          TEMPLATE_GUIDE_FLEX="https://support.eupayglobe.com/en/integration/all-sales-channels/flexcheckout/guide#customization"
          TEMPLATE_GUIDE_PAYPAL="https://support.eupayglobe.com/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration"

          SUPPORT_EMAIL="support@eupayglobe.com";
          SUPPORT_NAME="Payglobe Support";
          SUPPORT_PHONE="+39 0259914268";
          SUPPORT_URL="+390259914268";
          SUPPORT_TICKET_PLACEHOLDER="XXX-XXX";

          COLOR_MEDIUM_BLUE="#173A7E";
          COLOR_MID_BLUE_TWO="#e1a449";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";

          API_ECOMMERCE_TEST="https://test-securepay.eupayglobe.com/ncol/test/orderstandard_utf8.asp";
          API_ECOMMERCE_PROD="https://securepay.eupayglobe.com/ncol/prod/orderstandard_utf8.asp";
          API_FLEXCHECKOUT_TEST="https://test-securepay.eupayglobe.com/Tokenization/HostedPage";
          API_FLEXCHECKOUT_PROD="https://securepay.eupayglobe.com/Tokenization/HostedPage";
          API_DIRECTLINK_TEST="https://test-securepay.eupayglobe.com/ncol/test/querydirect_utf8.asp";
          API_DIRECTLINK_PROD="https://securepay.eupayglobe.com/ncol/prod/querydirect_utf8.asp";
          API_DIRECTLINK_ORDER_TEST="https://test-securepay.eupayglobe.com/ncol/test/orderdirect_utf8.asp";
          API_DIRECTLINK_ORDER_PROD="https://securepay.eupayglobe.com/ncol/prod/orderdirect_utf8.asp";
          API_MAINTENANCE_TEST="https://test-securepay.eupayglobe.com/ncol/test/maintenancedirect_utf8.asp";
          API_MAINTENANCE_PROD="https://securepay.eupayglobe.com/ncol/prod/maintenancedirect_utf8.asp";
          API_ALIAS_REQUEST_TEST="https://test-securepay.eupayglobe.com/ncol/test/alias_gateway_utf8.asp";
          API_ALIAS_REQUEST_PROD="https://securepay.eupayglobe.com/ncol/prod/alias_gateway_utf8.asp";

          COMPOSER_SDK="ingenico/ogone-sdk-php-payglobe"
          COMPOSER_CLIENT="ingenico/ogone-client-payglobe"
          ;;
     santander)
          echo "Selected: Santander."
          MODULE_NAME="santander";
          MODULE_FILE="santander.php";
          MODULE_CLASS="Santander";
          MODULE_BRAND="Santander";
          MODULE_DESC="Santander";
          MODULE_AUTHOR="Santander";

          LOGO_URL="https://www.santanderbank.com";

          TEMPLATE_GUIDE_ECOM="https://support.tpvecommerce.es/en/en/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template/";
          TEMPLATE_GUIDE_FLEX="https://support.tpvecommerce.es/en/integration/all-sales-channels/flexcheckout/guide#customization"
          TEMPLATE_GUIDE_PAYPAL="https://support.tpvecommerce.es/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration"

          SUPPORT_EMAIL="premier@gruposantander.es";
          SUPPORT_NAME="Santander Support";
          SUPPORT_PHONE="+34 91 175 03 45";
          SUPPORT_URL="+34911750345";
          SUPPORT_TICKET_PLACEHOLDER="XXX-XXX";

          COLOR_MEDIUM_BLUE="#E82729";
          COLOR_MID_BLUE_TWO="#E82729";
          COLOR_PINKISH_RED="#EB222E";
          COLOR_WHITE_TWO="#F6F6F6";
          COLOR_WHITE_FIVE="#DDDDDD";
          COLOR_WHITE_GREY="#AFAFAF";

          API_ECOMMERCE_TEST="https://test-secure.tpvecommerce.es/ncol/test/orderstandard_utf8.asp";
          API_ECOMMERCE_PROD="https://secure.tpvecommerce.es/ncol/prod/orderstandard_utf8.asp";
          API_FLEXCHECKOUT_TEST="https://test-secure.tpvecommerce.es/Tokenization/HostedPage";
          API_FLEXCHECKOUT_PROD="https://secure.tpvecommerce.es/Tokenization/HostedPage";
          API_DIRECTLINK_TEST="https://test-secure.tpvecommerce.es/ncol/test/querydirect_utf8.asp";
          API_DIRECTLINK_PROD="https://secure.tpvecommerce.es/ncol/prod/querydirect_utf8.asp";
          API_DIRECTLINK_ORDER_TEST="https://test-secure.tpvecommerce.es/ncol/test/orderdirect_utf8.asp";
          API_DIRECTLINK_ORDER_PROD="https://secure.tpvecommerce.es/ncol/prod/orderdirect_utf8.asp";
          API_MAINTENANCE_TEST="https://test-secure.tpvecommerce.es/ncol/test/maintenancedirect_utf8.asp";
          API_MAINTENANCE_PROD="https://secure.tpvecommerce.es/ncol/prod/maintenancedirect_utf8.asp";
          API_ALIAS_REQUEST_TEST="https://test-secure.tpvecommerce.es/ncol/test/alias_gateway_utf8.asp";
          API_ALIAS_REQUEST_PROD="https://secure.tpvecommerce.es/ncol/prod/alias_gateway_utf8.asp";

          COMPOSER_SDK="ingenico/ogone-sdk-php-santander"
          COMPOSER_CLIENT="ingenico/ogone-client-santander"
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

# Change branding in Block files
sed -i -e "s@https://payment-services.ingenico.com/int/en/ogone/support/guides/integration%20guides/e-commerce/payment-page-look-and-feel#adapt-upload-customized-template@$TEMPLATE_GUIDE_ECOM@g" $MODULE_DIR/Block/Adminhtml/System/Config/PaymentPage/Redirect/StepOne.php
sed -i -e "s@https://epayments-support.ingenico.com/en/integration/all-sales-channels/flexcheckout/guide#customization@$TEMPLATE_GUIDE_FLEX@g" $MODULE_DIR/Block/Adminhtml/System/Config/PaymentPage/Inline/StepOne.php

# Change branding in configuration XML files
sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" $MODULE_DIR/etc/config.xml
sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" $MODULE_DIR/etc/adminhtml/system.xml
sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" $MODULE_DIR/etc/acl.xml

sed -i -e "s/Ingenico /$MODULE_BRAND/g" $MODULE_DIR/etc/adminhtml/system.xml
sed -i -e "s/Ingenico /$MODULE_BRAND/g" $MODULE_DIR/etc/email_templates.xml

sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/etc/config.xml
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/etc/payment.xml
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/etc/adminhtml/system.xml

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

# Change branding in Config file
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/Model/Config.php
sed -i -e "s/ingenico\_/$MODULE_NAME\_/g" $MODULE_DIR/Model/Config.php

# Change branding in Connector file
sed -i -e "s/Ingenico Support/$MODULE_BRAND Support/g" $MODULE_DIR/Model/Connector.php
sed -i -e "s/ingenico\_payment\_page/$MODULE_NAME\_payment\_page/g" $MODULE_DIR/Model/Connector.php
sed -i -e "s/ingenico\_settings/$MODULE_NAME\_settings/g" $MODULE_DIR/Model/Connector.php

# Change branding in ConfigProvider file
sed -i -e "s/ingenico\_payment\_page/$MODULE_NAME\_payment\_page/g" $MODULE_DIR/Model/IngenicoConfigProvider.php
sed -i -e "s@'ingenico'@'$MODULE_NAME'@g" $MODULE_DIR/Model/IngenicoConfigProvider.php

# Change branding in Payment Method file
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/Model/Method/Ingenico.php
sed -i -e "s/support@ecom.ingenico.сom/$SUPPORT_EMAIL/g" $MODULE_DIR/Model/Method/Ingenico.php
sed -i -e "s@https://www.ingenico.com/support/phone@$SUPPORT_URL@g" $MODULE_DIR/Model/Method/Ingenico.php

# Change branding in Plugin files
find $MODULE_DIR/Plugin/ -name '*.php' -type f|while read fname; do
  sed -i -e "s/ingenico_/$MODULE_NAME\_/g" "$fname"
done

# Change branding in translation files
find $MODULE_DIR/po/ -iname *.po -type f|while read fname; do
  sed -i -e "s/Ingenico ePayments/$MODULE_DESC/g" "$fname"
  sed -i -e "s/Ingenico/$MODULE_BRAND/g" "$fname"
done

# Change branding in payment method JS files
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer.js
sed -i -e "s/\.ingenico\./\.$MODULE_NAME\./g" $MODULE_DIR/view/frontend/web/js/view/payment/method-renderer/ingenico-e-payments.js

# Change branding layout files
sed -i -e "s/ingenico_e_payments/$MODULE_NAME/g" $MODULE_DIR/view/frontend/layout/checkout_index_index.xml

# Change APIs in core library
if [ -d "$MODULE_DIR/Vendor" ]; then
    find $MODULE_DIR/Vendor/ -name '*.php' -type f|while read fname; do
        sed -i -e "s@https://ogone.test.v-psp.com/ncol/test/orderstandard_utf8.asp@$API_ECOMMERCE_TEST@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/prod/orderstandard_utf8.asp@$API_ECOMMERCE_PROD@g" "$fname"
        sed -i -e "s@https://ogone.test.v-psp.com/Tokenization/HostedPage@$API_FLEXCHECKOUT_TEST@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/Tokenization/HostedPage@$API_FLEXCHECKOUT_PROD@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/test/querydirect_utf8.asp@$API_DIRECTLINK_TEST@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/prod/querydirect_utf8.asp@$API_DIRECTLINK_PROD@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/test/orderdirect_utf8.asp@$API_DIRECTLINK_ORDER_TEST@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/prod/orderdirect_utf8.asp@$API_DIRECTLINK_ORDER_PROD@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/test/alias_gateway_utf8.asp@$API_ALIAS_REQUEST_TEST@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/prod/alias_gateway_utf8.asp@$API_ALIAS_REQUEST_PROD@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/test/maintenancedirect_utf8.asp@$API_MAINTENANCE_TEST@g" "$fname"
        sed -i -e "s@https://secure.ogone.com/ncol/prod/maintenancedirect_utf8.asp@$API_MAINTENANCE_PROD@g" "$fname"
    done

    # Update composer names of Vendor
    sed -i -e "s@ingenico/ogone-sdk-php@$COMPOSER_SDK@g" "$MODULE_DIR/Vendor/ogone-sdk-php/composer.json"
    sed -i -e "s@ingenico/ogone-client@$COMPOSER_CLIENT@g" "$MODULE_DIR/Vendor/ogone-sdk-php/composer.json"
    sed -i -e "s@ingenico/ogone-sdk-php@$COMPOSER_SDK@g" "$MODULE_DIR/Vendor/ogone-client/composer.json"
    sed -i -e "s@ingenico/ogone-client@$COMPOSER_CLIENT@g" "$MODULE_DIR/Vendor/ogone-client/composer.json"
fi

# Update composer names
sed -i -e "s@ingenico/ogone-sdk-php@$COMPOSER_SDK@g" "$MODULE_DIR/composer.json"
sed -i -e "s@ingenico/ogone-client@$COMPOSER_CLIENT@g" "$MODULE_DIR/composer.json"

# Change Support data
if [[ -f "$MODULE_DIR/Vendor/ogone-client/onboarding/emails.ini" ]]; then
    cp -f "$SOURCE_DIR/WLScript/resources/$BRAND_ID/emails.ini" "$MODULE_DIR/Vendor/ogone-client/onboarding/emails.ini"
fi

# Replace logo
cp "$SOURCE_DIR/WLScript/resources/$BRAND_ID/logo.png" $MODULE_DIR/view/adminhtml/web/images/logo_provider.png
cp "$SOURCE_DIR/WLScript/resources/$BRAND_ID/logo.png" $MODULE_DIR/view/frontend/web/images/logo_provider.png

# Change branding in help files
sed -i -e "s/inline_ingenico.png/inline\_$MODULE_NAME\.png/g" $MODULE_DIR/view/adminhtml/templates/help_content.phtml
sed -i -e "s@https://epayments-support.ingenico.com/en/payment-methods/wallets/paypal-express-checkout/guide#paypal-account-configuration@$TEMPLATE_GUIDE_PAYPAL@g" $MODULE_DIR/view/adminhtml/templates/help_content.phtml

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