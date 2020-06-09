<?php

namespace Ingenico\Payment\Model\ResourceModel;

class Alias extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('ingenico_payment_alias', 'id');
    }
}
