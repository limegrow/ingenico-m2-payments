<?php

namespace Ingenico\Payment\Block\Form;

class Cc extends Method
{
    protected $_template = 'form/cc_form.phtml';

    /**
     * Get Saved Cards
     *
     * @return array
     */
    public function getSavedCards()
    {
        return $this->configProvider->getSavedCards();
    }
}
