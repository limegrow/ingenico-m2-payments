<?php

namespace Ingenico\Payment\Model\ResourceModel\Alias;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\Ingenico\Payment\Model\Alias::class, \Ingenico\Payment\Model\ResourceModel\Alias::class);
    }
}
