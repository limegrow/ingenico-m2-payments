<?php

namespace Ingenico\Payment\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

abstract class Base extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    protected $_session;
    protected $_connector;
    protected $_registry;
    protected $_cnf;
    protected $_resultJsonFactory;
    protected $_urlBuilder;
    protected $_reminderFactory;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $session,
        \Ingenico\Payment\Model\Connector $connector,
        \Magento\Framework\Registry $registry,
        \Ingenico\Payment\Model\Config $cnf,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ingenico\Payment\Model\ReminderFactory $reminderFactory
    ) {
        parent::__construct($context);

        $this->_session = $session;
        $this->_connector = $connector;
        $this->_registry = $registry;
        $this->_cnf = $cnf;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_urlBuilder = $urlBuilder;
        $this->_reminderFactory = $reminderFactory;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    protected function _processPayment()
    {
        // process only if we are dealing with checkout, not reminder
        if (!$this->_session->getData('reminder_order_id')) {
            
            // get and set last_real_order_id so it is not lost after restoreShoppingCart;
            $orderIncId = $this->_session->getData('last_real_order_id');
            $this->_connector->restoreShoppingCart();
            if ($orderIncId) {
                $this->_session->setData('last_real_order_id', $orderIncId);
            }
        }
        
        $aliasId = $this->getRequest()->getParam('alias', null);
        $this->_connector->processPayment($aliasId);
    }
}
