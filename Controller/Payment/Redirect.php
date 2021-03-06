<?php

namespace Ingenico\Payment\Controller\Payment;

class Redirect extends Base
{
    public function execute()
    {
        $order = $this->getOrder();
        if (!$order->getId()) {
            $this->_session->restoreQuote();
            $this->messageManager->addError(__('No order for processing found'));
            return $this->_redirect('checkout/cart');
        }

        $aliasId = $this->getAliasId();

        // Get the core library PaymentMethod
        $paymentMethodInstance = $order->getPayment()->getMethodInstance();
        $corePaymentMethod = $this->ingenicoHelper->getCoreMethod($paymentMethodInstance::CORE_CODE);

        try {
            if (\Ingenico\Payment\Model\Method\Ingenico::PAYMENT_METHOD_CODE === $paymentMethodInstance->getCode()) {
                // @see self::showPaymentListRedirectTemplate()
                $this->_connector->processPaymentRedirect($aliasId);
            } elseif (\Ingenico\Payment\Model\Method\Cc::PAYMENT_METHOD_CODE === $paymentMethodInstance->getCode()) {
                // @see self::showPaymentListRedirectTemplate()
                if ($aliasId) {
                    // There's specified card brand
                    $alias = $this->_connector->getAlias($aliasId);
                    $this->_connector->processPaymentRedirectSpecified(
                        $aliasId,
                        'CreditCard',
                        $alias['brand']
                    );
                } else {
                    // Use "CreditCard" brands
                    $this->_connector->processPaymentRedirectSpecified(
                        $aliasId,
                        'CreditCard',
                        'CreditCard'
                    );
                }
            } elseif (\Ingenico\Payment\Model\Method\Alias::PAYMENT_METHOD_CODE === $paymentMethodInstance->getCode()) {
                // @see self::showPaymentListRedirectTemplate()
                $alias = $this->_connector->getAlias($aliasId);
                $this->_connector->processPaymentRedirectSpecified(
                    $aliasId,
                    'CreditCard',
                    $alias['brand']
                );
            } elseif ($corePaymentMethod) {
                // @see self::showPaymentListRedirectTemplate()
                $this->_connector->processPaymentRedirectSpecified(
                    $aliasId,
                    $corePaymentMethod->getPM(),
                    $corePaymentMethod->getBrand()
                );
            } else {
                throw new \Exception('Unable to initialize the payment process.');
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        // redirect to success page if payment processing is final
        if ($redirectUrl = $this->_registry->registry($this->_connector::REGISTRY_KEY_REDIRECT_URL)) {
            return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
        }

        return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
    }
}
