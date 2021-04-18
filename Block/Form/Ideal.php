<?php

namespace Ingenico\Payment\Block\Form;

use Magento\Framework\App\ObjectManager;

class Ideal extends Method
{
    protected $_template = 'form/ideal.phtml';

    /**
     * Get Available Banks.
     *
     * @return array
     */
    public function getAvailableBanks()
    {
        return ObjectManager::getInstance()->get('Ingenico\Payment\Block\Ideal\Banks')->getAvailableBanks();
    }
}
