<?php

namespace Ingenico\Payment\Model\ResourceModel\Transaction;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\Ingenico\Payment\Model\Transaction::class, \Ingenico\Payment\Model\ResourceModel\Transaction::class);
    }
}
