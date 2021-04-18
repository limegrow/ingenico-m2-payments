<?php

namespace Ingenico\Payment\Block\Form;

class Flex extends Method
{
    protected $_template = 'form/flex.phtml';

    /**
     * Get Configurable payment methods
     *
     * @return array
     */
    public function getFlexMethods()
    {
        return $this->cnf->getFlexMethods($this->_storeManager->getStore()->getId());
    }
}
