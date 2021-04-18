# Ingenico ePayments for Magento 2

[![Latest Stable Version][version-badge]][packagist]
[![Total Downloads][downloads-badge]][packagist]
[![License][license-badge]][packagist]

[Ingenico ePayments][ingenico] is the online and mobile commerce division of Ingenico Group.
We are a certified payment processor, active worldwide with more than 20 years of experience.
We connect merchants and consumers, enabling businesses everywhere to go further beyond today’s boundaries and
creating the future of global commerce. Benefit from a stable & compliant environment,
a wide range of payment methods and local payment expertise! More than 160,000 merchants trust us.
With advanced data analytics, fraud management solutions and cross-border commerce expertise,
we help merchants optimize their business and grow into new markets around the world.

**Compatible with PSD2 Strong Customer Authentication (3DS v2) requirements,**
Ingenico’s payment extension for Magento enables merchants to accept online payments from customers all over the world.
Whether you are a startup, a medium-sized company or a large corporate business,
our solutions are designed to help you grow in your home markets and beyond.
Our payment extension is built and designed for Magento 2. Get started and download our plugin.
It's free and easy to configure!

## Installation

### Magento Marketplace

The recommended way of installing is through Magento Marketplace, where you can
find [Ingenico ePayments][marketplace].

### Composer

1. Go to Magento2 root folder
2. Enter following commands to install extension:

   ```bash
   composer require ingenico/m2-payment
   ```

   Wait while dependencies are updated.

3. Enter following commands to enable extension:

   ```bash
   php bin/magento module:enable Ingenico_Payment --clear-static-content
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

4. If Magento is running in "production" mode, then also execute:
   ```bash
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```
5. Configure extension as per configuration instructions

### Manual Installation from Downloaded ZIP Archive

1. In Magento root directory create folder named "ingenico_src"
2. Upload extension ZIP archive into that folder. DO NOT extract archive!
3. Log in via SSH, go to Magento root folder and execute:


   ```bash
   composer config repositories.ingenico artifact /full/server/path/to/ingenico_src/
   ```
Where given path is a full server path of the folder containing ZIP archive with module.

   ```bash
   composer require ingenico/m2-payment
   php bin/magento module:enable Ingenico_Payment --clear-static-content
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

If Magento is running in "production" mode, then also execute:
   ```bash
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```

4. Configure extension as per configuration instructions

### Update
Log in via SSH, go to Magento root folder and execute:
   ```bash
   composer require ingenico/m2-payment:VERSION --update-with-dependencies
   php bin/magento setup:upgrade
   ```

### Removal
Log in via SSH, go to Magento root folder and execute:
   ```bash
   php bin/magento module:uninstall Ingenico_Payment --clear-static-content
   composer remove ingenico/m2-payment
   php bin/magento setup:upgrade
   ```

## Configuration
1. Log in to Magento Admin
2. Go to Stores > Configuration > Ingenico ePayments and configure settings
3. Go to Stores > Configuration > Sales > Payment Methods > Ingenico ePayments and configure settings
4. Read more [Installation and Configuration guide][guide]

[ingenico]: https://www.ingenico.com/global-epayments
[marketplace]: https://marketplace.magento.com/ingenico-m2-payment.html
[guide]: https://epayments-support.ingenico.com/en/integration-solutions/plugins/magento-2
[packagist]: https://packagist.org/packages/ingenico/m2-payment
[version-badge]: https://poser.pugx.org/ingenico/m2-payment/v
[downloads-badge]: https://poser.pugx.org/ingenico/m2-payment/downloads
[license-badge]: https://poser.pugx.org/ingenico/m2-payment/license
