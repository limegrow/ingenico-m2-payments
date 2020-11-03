<?php

namespace Ingenico\Payment\Controller\Payment;

class Result extends \Ingenico\Payment\Controller\Payment\Base
{
    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        $paymentModeInRequest = $this->getRequest()->getParam('payment_mode');
        $coreLibrary = $this->_connector->getCoreLibrary();
        $redirectUrl = $this->_urlBuilder->getUrl('checkout/cart');

        try {
            $this->_connector->setOrderId($this->getRequest()->getParam('order_id'));
            $this->_connector->processSuccessUrls();
            if ($url = $this->_registry->registry($this->_connector::REGISTRY_KEY_REDIRECT_URL)) {
                $redirectUrl = $url;
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        if ($paymentModeInRequest == $coreLibrary::PAYMENT_MODE_INLINE) {
            if (!$this->_connector->isOrderCreated($this->getRequest()->getParam('order_id'))) {
                $returnState = $this->getRequest()->getParam('return_state', null);
                $triggerName = ($returnState == $coreLibrary::RETURN_STATE_ACCEPT) ? 'success' : 'failure';
                $aliasId = $this->getRequest()->getParam('Alias_AliasId');
                $cardBrand = $this->getRequest()->getParam('Card_Brand');

                return $this->getResponse()->setBody(
                    '<script type="application/javascript">
                        window.onload = function() {
                            top.jQuery("body").trigger(
                                "ingenico:inline:' . $triggerName . '",
                                ["' . $aliasId . '", "' . $cardBrand . '"]
                            );
                        };
                    </script>'
                );
            }

            return $this->resultFactory->create($this->resultFactory::TYPE_PAGE);
        } elseif ($paymentModeInRequest == $coreLibrary::PAYMENT_MODE_REDIRECT) {
            return $this->resultRedirectFactory->create()->setUrl($redirectUrl);
        }
    }
}
