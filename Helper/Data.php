<?php

namespace Ingenico\Payment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Ingenico\Payment\Model\Config as IngenicoConfig;
use Ingenico\Payment\Model\Connector;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Payment\Model\Method\AbstractMethod;

class Data extends AbstractHelper
{
    /**
     * @var IngenicoConfig
     */
    private $cnf;

    /**
     * @var Connector
     */
    private $connector;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        Connector $connector,
        IngenicoConfig $cnf,
        PaymentHelper $paymentHelper,
        StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);

        $this->connector = $connector;
        $this->cnf = $cnf;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns payment methods codes (Magento).
     *
     * @return string[]
     */
    public function getPaymentMethodCodes()
    {
        $result = [];
        foreach ($this->cnf::getAllPaymentMethods() as $className) {
            $classWithNs = '\\Ingenico\\Payment\\Model\\Method\\' . $className;
            $result[] = $classWithNs::PAYMENT_METHOD_CODE;
        }

        return $result;
    }

    /**
     * Get Magento Payment Code by Ingenico Payment Code.
     *
     * @param string $coreCode Ingenico Payment Code
     *
     * @return string|false
     */
    public function getPaymentMethodCodeByCoreCode($coreCode)
    {
        foreach ($this->cnf::getAllPaymentMethods() as $className) {
            $classWithNs = '\\Ingenico\\Payment\\Model\\Method\\' . $className;
            if ($classWithNs::CORE_CODE === $coreCode) {
                return $classWithNs::PAYMENT_METHOD_CODE;
            }
        }

        return false;
    }

    /**
     * Get Core Method by ID/CODE
     *
     * @param string $methodId Method ID
     * @param array $methods Optional. List of PMs.
     *
     * @return \IngenicoClient\PaymentMethod\PaymentMethod|false
     */
    public function getCoreMethod($methodId, array $methods = [])
    {
        if (count($methods) === 0) {
            $methods = $this->connector->getPaymentMethods();
        }

        // ingenico_e_payments PM nor visa PM can not be used to identify the cc name/class used
        if ((empty($methodId) || $methodId === \IngenicoClient\PaymentMethod\Visa::CODE) && count($methods) === 1) {
            return array_values($methods)[0];
        }

        foreach ($methods as $methodName => $instance) {
            if ($instance->getId() === $methodId) {
                return $instance;
            }
        }

        return false;
    }

    /**
     * Get Magento Payment Method codes that active in Magento.
     *
     * @return AbstractMethod[]
     */
    public function getActiveMagentoPaymentMethods()
    {
        $result = [];

        foreach ($this->cnf::getAllPaymentMethods() as $className) {
            $classWithNs = '\\Ingenico\\Payment\\Model\\Method\\' . $className;
            if (!defined($classWithNs . '::CORE_CODE')) {
                continue;
            }

            // Since this caused circular dependency error - the Instance initiations are commented out.
            // If there will be a need for such usage in the future - use ObjectManager to get
            // Ingenico\Payment\Model\Connector inside Ingenico\Payment\Model\Method\AbstractMethod instead.
            //$instance = $this->paymentHelper->getMethodInstance($method);
            //$instance->isActive($this->_scopeCode)) { }
            if ($this->cnf->isSetFlag(
                'payment/' . $classWithNs::PAYMENT_METHOD_CODE . '/active',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            )) {
                try {
                    $instance = $this->paymentHelper->getMethodInstance(
                        $classWithNs::PAYMENT_METHOD_CODE
                    );

                    if (!defined(get_class($instance) . '::CORE_CODE')) {
                        // It seems Klarna extension conflict. Use di.xml as workaround.
                        continue;
                    }

                    $result[] = $instance;
                } catch (\Exception $e) {
                    // @todo log this
                    // "Payment model name is not provided in config!"
                }
            }
        }

        return $result;
    }
}
