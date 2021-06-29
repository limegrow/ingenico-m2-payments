<?php

namespace Ingenico\Payment\Block\Method;

use IngenicoClient\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Ingenico\Payment\Model\Config as IngenicoConfig;

class View extends \Magento\Framework\View\Element\Template
{
    const CARDS = 'Cards';

    protected $_connector;
    protected $_checkoutSession;
    protected $_assetRepo;
    protected $_urlBuilder;
    protected $_registry;

    /**
     * @var IngenicoConfig
     */
    private $cnf;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Ingenico\Payment\Model\Connector $connector,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Registry $registry,
        IngenicoConfig $cnf,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_connector = $connector;
        $this->_checkoutSession = $checkoutSession;
        $this->_assetRepo = $assetRepo;
        $this->_urlBuilder = $urlBuilder;
        $this->_registry = $registry;
        $this->cnf = $cnf;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
    }

    public function getPaymentMethods()
    {
        $result = $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_INLINE);
        if (isset($result['methods'])) {
            $sorted = [];
            foreach ($result['methods'] as $code => $method) {
                $categoryName = $method->getCategoryName();
                if (!isset($sorted[$categoryName])) {
                    $sorted[$categoryName] = [];
                }

                $sorted[$categoryName][$code] = $method;
            }
            ksort($sorted);

            if (isset($sorted[self::CARDS]) && isset($result['credit_card_url'])) {
                $combinedMethod = current($sorted[self::CARDS]);
                $subMethodLogos = [];
                foreach ($sorted[self::CARDS] as $code => $method) {
                    $subMethodLogos[$method->getName()] = $method->getEmbeddedLogo();
                }
                $combinedMethod->setSubmethodLogos($subMethodLogos);
                $combinedMethod->setIFrameUrl($result['credit_card_url']);
                $sorted[self::CARDS] = [$combinedMethod];
            }

            foreach ($sorted as $catName => $methods) {
                unset($sorted[$catName]);
                $catNameLocalized = $this->_connector->getCoreLibrary()->__($catName, [], null, $this->_connector->getLocale());
                $sorted[$catNameLocalized] = $methods;
            }

            return $sorted;
        }

        return [];
    }

    /**
     * Get Specified Redirect payment data.
     *
     * @return false|Data Array like ['url' => '', 'fields' => []]
     */
    public function getSpecifiedRedirectPaymentData()
    {
        $paymentId = $this->getRequest()->getParam('payment_id', false);
        $paymentMethod = $this->getRequest()->getParam('pm', false);
        $brand = $this->getRequest()->getParam('brand', false);

        if (!$paymentMethod || !$brand) {
            return false;
        }

        return $this->_connector->getSpecifiedRedirectPaymentRequest(null, $paymentMethod, $brand, $paymentId);
    }

    /**
     * Get Redirect payment data.
     *
     * @return array Array like ['url' => '', 'fields' => []]
     */
    public function getRedirectPaymentData()
    {
        // There's result of $this->_connector->getCoreLibrary()->initiateRedirectPayment($orderId, $alias);
        return $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_REDIRECT);
    }

    /**
     * Get HTML Answer of 3DSec
     *
     * @return string|false
     */
    public function getSecurityHTMLAnswer()
    {
        $result = $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_ALIAS);
        if ($result && isset($result['html'])) {
            return $result['html'];
        }

        return false;
    }

    /**
     * Set Order redirected.
     *
     * @return void
     */
    public function setOrderRedirected()
    {
        $order = $this->getOrder();

        if ($order) {
            $status = $this->cnf->getAssignedStatus(Order::STATE_PENDING_PAYMENT);

            $order->setData('state', $status->getState());
            $order->setStatus($status->getStatus());

            $order->addStatusToHistory(
                $status->getStatus(),
                __('The customer was redirected to the payment gateway')
            );

            $this->orderRepository->save($order);
        }
    }

    public function getLoaderUrl()
    {
        return $this->_assetRepo->getUrl('Ingenico_Payment::images/loader.svg');
    }

    public function getResultRedirectUrl()
    {
        return $this->_registry->registry($this->_connector::REGISTRY_KEY_REDIRECT_URL);
    }

    public function getAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('ingenico/payment_ajax/inline');
    }

    public function getOpenInvoicePostUrl()
    {
        return $this->_urlBuilder->getUrl('ingenico/payment/openinvoice');
    }

    /**
     * Returns OpenInvoice payment data: the url and fields.
     *
     * @return array Like ['url' => '', 'fields' = []]
     */
    public function getOpenInvoicePaymentData()
    {
        return $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_OPENINVOICE);
    }

    public function getLoaderParam($paramName)
    {
        $params = $this->_registry->registry($this->_connector::REGISTRY_KEY_INLINE_LOADER_PARAMS);
        if (isset($params[$paramName])) {
            return $params[$paramName];
        }

        return null;
    }

    /**
     * @return \Magento\Sales\Model\Order|false
     */
    public function getOrder()
    {
        $incrementId = $this->_checkoutSession->getLastRealOrderId();
        if ($incrementId) {
            return $this->orderFactory->create()->loadByIncrementId($incrementId);
        }

        return false;
    }
}
