<?php

namespace Ingenico\Payment\Block\Cart;

class Invalidate extends \Magento\Framework\View\Element\Template
{
    protected $_checkoutSession;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        
        $this->_checkoutSession = $checkoutSession;
    }
    
    public function shouldInvalidateCart()
    {
        if ($this->_checkoutSession->getData('invalidate_cart')) {
            $this->_checkoutSession->unsetData('invalidate_cart');
            return true;
        }
    }
}
