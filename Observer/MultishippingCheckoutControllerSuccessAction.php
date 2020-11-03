<?php

namespace Ingenico\Payment\Observer;

use Ingenico\Payment\Model\Connector;
use Ingenico\Payment\Model\Config as IngenicoConfig;
use Ingenico\Payment\Helper\Data as IngenicoHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
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
     * @var Connector
     */
    private $connector;

    /**
     * @var IngenicoConfig
     */
    private $cnf;

    /**
     * @var IngenicoHelper
     */
    private $ingenicoHelper;

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
     * @var CheckoutHelper
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
        Connector $connector,
        IngenicoConfig $cnf,
        IngenicoHelper $ingenicoHelper,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        QuoteRepository $quoteRepository,
        CheckoutHelper $checkoutHelper,
        UrlInterface $urlBuilder,
        ResponseFactory $responseFactory,
        ActionFlag $actionFlag,
        RedirectInterface $redirect
    ) {
        $this->connector = $connector;
        $this->cnf = $cnf;
        $this->ingenicoHelper = $ingenicoHelper;
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

        /** @var \Ingenico\Payment\Model\Method\AbstractMethod $paymentMethodInstance */
        $paymentMethodInstance = $order->getPayment()->getMethodInstance();

        /** @var \IngenicoClient\PaymentMethod\PaymentMethod $paymentMethod */
        $paymentMethod = $this->ingenicoHelper->getCoreMethod(
            $paymentMethodInstance::CORE_CODE
        );

        // If alias is defined
        $data = $order->getPayment()->getAdditionalInformation();
        if (!empty($data['alias'])) {
            $redirectionUrl = $this->urlBuilder->getUrl('ingenico/payment/redirect', [
                'alias' => $data['alias']
            ]);
        } elseif ($paymentMethodInstance->getCode() === \Ingenico\Payment\Model\Method\Ingenico::PAYMENT_METHOD_CODE) {
            $redirectionUrl = $this->urlBuilder->getUrl('ingenico/payment/redirect');
        } elseif ($paymentMethodInstance->getCode() === \Ingenico\Payment\Model\Method\Cc::PAYMENT_METHOD_CODE) {
            $redirectionUrl = $this->urlBuilder->getUrl('ingenico/payment/redirect', [
                'payment_id' => null,
                'pm' => 'CreditCard',
                'brand' => 'CreditCard'
            ]);
        } else {
            $redirectionUrl = $this->urlBuilder->getUrl('ingenico/payment/redirect', [
                'payment_id' => $paymentMethod->getId(),
                'pm' => $paymentMethod->getPM(),
                'brand' => $paymentMethod->getBrand()
            ]);
        }

        // Redirect
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $response = $this->responseFactory
            ->create()
            ->setHttpResponseCode(301)
            ->setRedirect($redirectionUrl)
            ->sendResponse();

        $this->redirect->redirect($response, $redirectionUrl);
    }
}
