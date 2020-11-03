<?php

namespace Ingenico\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Ingenico\Payment\Model\Connector;
use Ingenico\Payment\Model\Config as IngenicoConfig;
use Ingenico\Payment\Helper\Data as IngenicoHelper;
use Ingenico\Payment\Model\ReminderFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;

abstract class Base extends Action
{
    /**
     * @var CheckoutSession CheckoutSession
     */
    protected $_session;

    /**
     * @var Connector Connector
     */
    protected $_connector;

    /**
     * @var IngenicoHelper
     */
    protected $ingenicoHelper;

    /**
     * @var Registry Registry
     */
    protected $_registry;

    /**
     * @var IngenicoConfig
     */
    protected $_cnf;

    /**
     * @var JsonFactory JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var ReminderFactory
     */
    protected $_reminderFactory;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * Constructor
     * @SuppressWarnings(MEQP2.Classes.ObjectManager.ObjectManagerFound)
     */
    public function __construct(
        Context $context,
        CheckoutSession $session,
        Connector $connector,
        IngenicoHelper $ingenicoHelper,
        Registry $registry,
        IngenicoConfig $cnf,
        JsonFactory $resultJsonFactory,
        UrlInterface $urlBuilder,
        ReminderFactory $reminderFactory,
        PaymentHelper $paymentHelper,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository
    ) {
        parent::__construct($context);

        $this->_session = $session;
        $this->_connector = $connector;
        $this->ingenicoHelper = $ingenicoHelper;
        $this->_registry = $registry;
        $this->_cnf = $cnf;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_urlBuilder = $urlBuilder;
        $this->_reminderFactory = $reminderFactory;
        $this->_paymentHelper = $paymentHelper;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;

        // CsrfAwareAction Magento2.3 compatibility
        if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
            if ($this->_request instanceof HttpRequest &&
                $this->_request->isPost() &&
                empty($this->_request->getParam('form_key'))
            ) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $this->_request->setParam('form_key', $formKey->getFormKey());
            }
        }
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

    /**
     * @deprecated
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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

        $this->_connector->processPayment($this->getAliasId());
    }

    /**
     * Get Alias ID from the request
     * @return mixed|null
     */
    protected function getAliasId()
    {
        $aliasId = $this->getRequest()->getParam('alias', null);

        if (!empty($aliasId) && empty($this->_connector->getAlias($aliasId))) {
            $aliasId = null;
        }

        return $aliasId;
    }

    /**
     * Get order object
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        $incrementId = $this->_session->getLastRealOrderId();
        return $this->orderFactory->create()->loadByIncrementId($incrementId);
    }
}
