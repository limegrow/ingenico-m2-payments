## Installation

```bash
php composer require ingenico/m2-payment
php bin/magento module:enable Ingenico_Payment
php bin/magento setup:upgrade
```

If Magento is running in "production" mode, then also execute:
```bash
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

4. Configure module as per configuration instructions

## Removal

Log in via SSH, go to Magento root folder and execute:

```bash
php bin/magento module:uninstall Ingenico_Payment  --clear-static-content
php composer remove ingenico/m2-payment
php bin/magento setup:upgrade
```

# Module Configuration

1. Log in to Magento Admin

2. Go to Stores > Configuration > Ingenico ePayments and configure settings

3. Go to Stores > Configuration > Sales > Payment Methods > Ingenico ePayments and configure settings
