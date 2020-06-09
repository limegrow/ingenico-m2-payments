<?php

namespace Ingenico\Payment\Controller\Payment\Ajax;

class Inline extends \Ingenico\Payment\Controller\Payment\Base
{
    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('ingenico.exception.message2'));
        }

        $params = $this->getRequest()->getParams();
        $result = $this->_connector->finishReturnInline($params['order_id'], $params['card_brand'], $params['alias_id']);
        if (isset($result['message'])) {
            $this->messageManager->addError(__($result['message']));
        }

        return $this->_resultJsonFactory->create()->setData($result);
    }
}
