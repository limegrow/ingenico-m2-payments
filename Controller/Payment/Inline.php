<?php

namespace Ingenico\Payment\Controller\Payment;

use IngenicoClient\Connector;

class Inline extends Base
{
    public function execute()
    {
        try {
            // @see self::showPaymentListInlineTemplate()
            $this->_connector->processPaymentInline($this->getAliasId());
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        // redirect to success page if payment processing is final
        if ($redirectUrl = $this->_registry->registry($this->_connector::REGISTRY_KEY_REDIRECT_URL)) {
            return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
        }

        $inlineData = $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_INLINE);
        if ($inlineData) {
            $orderModel = $this->orderFactory->create();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $orderModel->loadByIncrementId($inlineData['order_id']);
            $paymentMethodInstance = $order->getPayment()->getMethodInstance();

            /** @var \IngenicoClient\PaymentMethod\PaymentMethod $paymentMethod */
            $paymentMethod = $this->ingenicoHelper->getCoreMethod(
                $paymentMethodInstance::CORE_CODE,
                $inlineData[Connector::PARAM_NAME_METHODS]
            );

            // Process the payment depends on type
            if ($paymentMethod->getAdditionalDataRequired()) {
                // Payment Methods which require additional data, i.e. OpenInvoice
                // Filter the methods to limit one for list.phtml
                foreach ($inlineData[Connector::PARAM_NAME_METHODS] as $key => $method) {
                    /** @var \IngenicoClient\PaymentMethod\PaymentMethod $method */
                    if ($method->getId() !== $paymentMethodInstance::CORE_CODE) {
                        unset($inlineData[Connector::PARAM_NAME_METHODS][$key]);
                    }
                }

                // Update results for the View block
                $this->_registry->unregister($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_INLINE);
                $this->_registry->register($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_INLINE, $inlineData);

                return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
            } elseif ($paymentMethod->isRedirectOnly()) {
                // redirect when is_redirect_only equals true - mainly all PMs except cc
                $iFrameUrl = $paymentMethod->getIFrameUrl();
                if (empty($iFrameUrl)) {
                    throw new \Exception('Unable to get iframe url.');
                }

                return $this->resultRedirectFactory->create()->setUrl($iFrameUrl);
            } else {
                // Credit Card time
                $orderId = $this->_session->getData('last_real_order_id');
                if ($this->_cnf->isPaymentPageModeInline() && $this->_connector->isOrderCreated($orderId)) {
                    $response = $this->_connector->finishReturnInline(
                        $orderId,
                        $this->getRequest()->getParam('cardbrand'),
                        $this->getRequest()->getParam('alias')
                    );

                    // Show error
                    if ('error' === $response['status']) {
                        $this->messageManager->addError($response['message']);

                        if (isset($response['redirect'])) {
                            return $this->resultRedirectFactory->create()->setUrl($response['redirect']);
                        }

                        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
                    }

                    if (isset($response['html'])) {
                        return $this->getResponse()->setBody($response['html']);
                    }

                    if (isset($response['redirect'])) {
                        return $this->resultRedirectFactory->create()->setUrl($response['redirect']);
                    }
                }
                // throw new \Exception('Credit cards aren\'t supported here');
            }
        }
        
        // @todo use redirect as default and remove the possibility to show this custom inline page
        return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
    }
}
