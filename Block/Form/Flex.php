<?php

namespace Ingenico\Payment\Block\Form;

use Ingenico\Payment\Model\Config as IngenicoConfig;
use Ingenico\Payment\Model\IngenicoConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;

class Flex extends Method
{
    protected $_template = 'form/flex.phtml';

    /**
     * Get Configurable payment methods
     *
     * @return array
     */
    public function getFlexMethods() {
        return $this->cnf->getFlexMethods();
    }
}
