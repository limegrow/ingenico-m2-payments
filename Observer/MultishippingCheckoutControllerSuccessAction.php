<?php

namespace Ingenico\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;

class MultishippingCheckoutControllerSuccessAction implements ObserverInterface
{
    /**
     * @var \Ingenico\Payment\Model\Connector
     */
    private $connector;

    /**
     * @var \Ingenico\Payment\Model\Config
     */
    private $cnf;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var ActionFlag
     */
    protected $actionFlag;

    /**
     * @var RedirectInterface
     */
    protected $redirect;

    public function __construct(
        \Ingenico\Payment\Model\Connector $connector,
        \Ingenico\Payment\Model\Config $cnf,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        UrlInterface $urlBuilder,
        ResponseFactory $responseFactory,
        ActionFlag $actionFlag,
        RedirectInterface $redirect
    ) {
        $this->connector = $connector;
        $this->cnf = $cnf;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutHelper = $checkoutHelper;
        $this->urlBuilder = $urlBuilder;
        $this->responseFactory = $responseFactory;
        $this->actionFlag = $actionFlag;
        $this->redirect = $redirect;
    }

    public function execute(Observer $observer)
    {
        $orderId = $this->checkoutHelper->getCheckout()->getMultishippingMainOrderId();
        if (!$orderId) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        if (!$order->getId()) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($order->getQuoteId());

        // Initiate Checkout session
        $this->checkoutHelper->getCheckout()
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        $paymentMode = strtolower($this->cnf->getPaymentPageMode());
        $redirectionUrl = $this->urlBuilder->getUrl('ingenico/payment/' . $paymentMode);

        // If alias is defined
        $data = $order->getPayment()->getAdditionalInformation();
        if (!empty($data['alias'])) {
            $redirectionUrl = $this->urlBuilder->getUrl('ingenico/payment/' . $paymentMode . '/alias/' . $data['alias']);
        }

        // Redirect
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $this->responseFactory->create()->setHttpResponseCode(301)->setRedirect($redirectionUrl)->sendResponse();
        $this->redirect->redirect(null, $redirectionUrl);
    }
}
