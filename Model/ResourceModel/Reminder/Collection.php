<?php

namespace Ingenico\Payment\Model\ResourceModel\Reminder;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\Ingenico\Payment\Model\Reminder::class, \Ingenico\Payment\Model\ResourceModel\Reminder::class);
    }
}
