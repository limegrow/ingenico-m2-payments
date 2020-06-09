<?php

if (!class_exists('\\IngenicoClient\\IngenicoCoreLibrary')) {
    spl_autoload_register(function ($className) {
        $autoLoadPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Vendor' . DIRECTORY_SEPARATOR;

        if (substr($className, 0, 14) === 'IngenicoClient') {
            $path = $autoLoadPath . 'ogone-client' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

            $classFile = $path . str_replace('\\', DIRECTORY_SEPARATOR, str_replace('IngenicoClient\\', '', $className)) . '.php';
            if (file_exists($classFile)) {
                // phpcs:ignore Magento2.Security.IncludeFile
                require_once $classFile;
                return true;
            }
        }

        return false;
    });
}

if (!class_exists('\\Ogone\\Ecommerce\\EcommercePaymentRequest')) {
    spl_autoload_register(function ($className) {
        $autoLoadPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Vendor' . DIRECTORY_SEPARATOR;

        if (substr($className, 0, 5) === 'Ogone') {
            $path = $autoLoadPath . 'ogone-sdk-php' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
            $classFile = $path . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
            if (file_exists($classFile)) {
                // phpcs:ignore Magento2.Security.IncludeFile
                require_once $classFile;
                return true;
            }
        }

        return false;
    });
}

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Ingenico_Payment',
    __DIR__
);
