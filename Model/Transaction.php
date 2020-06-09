<?php

namespace Ingenico\Payment\Model;

class Transaction extends \Magento\Framework\Model\AbstractModel
{
    const CACHE_TAG = 'ingenico_payment_transaction';

    protected function _construct()
    {
        $this->_init(\Ingenico\Payment\Model\ResourceModel\Transaction::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
