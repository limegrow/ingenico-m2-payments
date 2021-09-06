<?php

namespace Ingenico\Payment\Model;

use Ingenico\Payment\Model\Config as IngenicoConfig;
use IngenicoClient\ConnectorInterface;
use IngenicoClient\Connector as AbstractConnector;
use IngenicoClient\IngenicoCoreLibrary;
use IngenicoClient\Configuration as IngenicoConf;
use IngenicoClient\OrderItem;
use IngenicoClient\OrderField;
use IngenicoClient\PaymentMethod\PaymentMethod;
use IngenicoClient\Data;
use IngenicoClient\Exception;
use Ingenico\Payment\Logger\Main as IngenicoLogger;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Ingenico\Payment\Model\Processor;
use Ingenico\Payment\Model\TransactionFactory;
use Ingenico\Payment\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Ingenico\Payment\Model\AliasFactory;
use Ingenico\Payment\Model\ResourceModel\Alias\CollectionFactory as AliasCollectionFactory;
use Ingenico\Payment\Model\ReminderFactory;
use Ingenico\Payment\Model\ResourceModel\Reminder\CollectionFactory as ReminderCollectionFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Connector
 *
 * @package Ingenico\Payment\Model
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Connector extends AbstractConnector implements ConnectorInterface
{
    const REGISTRY_KEY_TEMPLATE_VARS_OPENINVOICE = 'ingenico_payment_openinvoice_template_vars';
    const REGISTRY_KEY_TEMPLATE_VARS_INLINE = 'ingenico_payment_inline_template_vars';
    const REGISTRY_KEY_TEMPLATE_VARS_REDIRECT = 'ingenico_payment_redirect_template_vars';
    const REGISTRY_KEY_TEMPLATE_VARS_ALIAS = 'ingenico_payment_alias_template_vars';
    const REGISTRY_KEY_INLINE_LOADER_PARAMS = 'ingenico_payment_inline_loader_params';
    const REGISTRY_KEY_REDIRECT_TO_REFERER = 'ingenico_payment_redirect_to_referer';

    const REGISTRY_KEY_REDIRECT_URL = 'ingenico_payment_redirect_url';
    const REGISTRY_KEY_ERROR_MESSAGE = 'ingenico_payment_error_message';
    const REGISTRY_KEY_CAN_SEND_AUTH_EMAIL = 'can_send_auth_email';

    const CNF_SCOPE_PARAM_NAME = '_scope';
    const PARAM_NAME_REMINDER_ORDER_ID = 'reminder_order_id';
    const PARAM_NAME_CHECKOUT_CART = 'checkout/cart';
    const PARAM_NAME_ORDER_ID = 'order_id';
    const PARAM_NAME_ORDER_URL = 'order_url';
    const PARAM_NAME_PAY_ID = 'pay_id';
    const PARAM_NAME_EMAIL = 'email';
    const PARAM_NAME_SHOP_NAME = 'shop_name';
    const PARAM_NAME_SHOP_LOGO = 'shop_logo';
    const PARAM_NAME_SHOP_URL = 'shop_url';
    const PARAM_NAME_CUSTOMER_NAME = 'customer_name';
    const PARAM_NAME_ORDER_REFERENCE = 'order_reference';
    const PARAM_NAME_SALES_ORDER_VIEW = 'sales/order/view';
    const PARAM_NAME_ALIAS = 'alias';
    const PARAM_NAME_MAIL_SENDING_FAILED = 'Mail sending failed: ';
    const PARAM_NAME_MESSAGE = 'message';
    const PARAM_NAME_EMAIL_TEMPLATE = 'email_template';
    const PARAM_NAME_OPEN_INVOICE_FIELDS = 'open_invoice_fields';

    /**
     * @var IngenicoLogger
     */
    private $logger;

    /**
     * @var Config
     */
    private $cnf;

    /**
     * @var IngenicoCoreLibrary
     */
    private $coreLibrary;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var BackendUrlInterface
     */
    private $backendUrlBuilder;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var ResourceModel\Transaction\CollectionFactory
     */
    private $transactionCollectionFactory;

    /**
     * @var AliasFactory
     */
    private $aliasFactory;

    /**
     * @var ResourceModel\Alias\CollectionFactory
     */
    private $aliasCollectionFactory;

    /**
     * @var ReminderFactory
     */
    private $reminderFactory;

    /**
     * @var ResourceModel\Reminder\CollectionFactory
     */
    private $reminderCollectionFactory;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ImageHelper
     */
    private $productImageHelper;

    /**
     * @var ProductRepositoryInterfaceFactory
     */
    private $productRepositoryFactory;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @var AppEmulation
     */
    private $appEmulation;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    private $productMetadata;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileDriver;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var ActionFlag
     */
    private $actionFlag;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var UserCollectionFactory
     */
    private $userCollectionFactory;

    /**
     * @var mixed
     */
    private $orderId = null;

    /**
     * @var mixed
     */
    private $customerId = null;
    /**
     * @var QuoteProviderByOrderId
     */
    private $quoteProviderByOrderId;

    public function __construct(
        IngenicoLogger $logger,
        Config $cnf,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        CustomerRepositoryInterface $customerRepository,
        UrlInterface $urlBuilder,
        BackendUrlInterface $backendUrlBuilder,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Processor $processor,
        TransactionFactory $transactionFactory,
        TransactionCollectionFactory $transactionCollectionFactory,
        AliasFactory $aliasFactory,
        AliasCollectionFactory $aliasCollectionFactory,
        ReminderFactory $reminderFactory,
        ReminderCollectionFactory $reminderCollectionFactory,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        Registry $registry,
        ImageHelper $productImageHelper,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        PriceHelper $priceHelper,
        AppEmulation $appEmulation,
        ManagerInterface $messageManager,
        OrderCollectionFactory $orderCollectionFactory,
        OrderFactory $orderFactory,
        QuoteRepository $quoteRepository,
        Http $request,
        ProductMetadata $productMetadata,
        FileDriver $fileDriver,
        Json $json,
        ResponseFactory $responseFactory,
        ActionFlag $actionFlag,
        RedirectInterface $redirect,
        UserCollectionFactory $userCollectionFactory,
        QuoteProviderByOrderId $quoteProviderByOrderId
    ) {
        $this->logger = $logger;
        $this->cnf = $cnf;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->customerRepository = $customerRepository;
        $this->urlBuilder = $urlBuilder;
        $this->backendUrlBuilder = $backendUrlBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->processor = $processor;
        $this->transactionFactory = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->aliasFactory = $aliasFactory;
        $this->aliasCollectionFactory = $aliasCollectionFactory;
        $this->reminderFactory = $reminderFactory;
        $this->reminderCollectionFactory = $reminderCollectionFactory;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->registry = $registry;
        $this->productImageHelper = $productImageHelper;
        $this->productRepositoryFactory = $productRepositoryFactory;
        $this->priceHelper = $priceHelper;
        $this->appEmulation = $appEmulation;
        $this->messageManager = $messageManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->request = $request;
        $this->productMetadata = $productMetadata;
        $this->fileDriver = $fileDriver;
        $this->json = $json;
        $this->responseFactory = $responseFactory;
        $this->actionFlag = $actionFlag;
        $this->redirect = $redirect;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->quoteProviderByOrderId = $quoteProviderByOrderId;

        $this->processor->setConnector($this);
        $this->coreLibrary = new IngenicoCoreLibrary($this);
        $this->coreLibrary->setLogger($this->logger);
    }

    /**
     * @return IngenicoCoreLibrary
     */
    public function getCoreLibrary()
    {
        return $this->coreLibrary;
    }

    /**
     * Get Processor.
     *
     * @return Processor
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Returns Shopping Cart Extension Id.
     *
     * @return string
     */
    public function requestShoppingCartExtensionId()
    {
        $composerData = $this->json->unserialize(
            $this->fileDriver->fileGetContents(__DIR__ . '../../composer.json')
        );

        return sprintf(
            'MG%sV%s',
            str_replace('.', '', $this->productMetadata->getVersion()),
            str_replace('.', '', $composerData['version'])
        );
    }

    /**
     * Returns activated Ingenico environment mode.
     * False for Test (transactions will go through the Ingenico sandbox).
     * True for Live (transactions will be real).
     *
     * @return bool
     */
    public function requestSettingsMode()
    {
        return $this->cnf->getMode();
    }

    /**
     * Returns the complete list of all settings as an array.
     *
     * @param bool $mode False for Test. True for Live.
     *
     * @return array
     */
    public function requestSettings($mode)
    {
        $result = \IngenicoClient\Configuration::getDefault();
        $storeId = $this->getStoreId();

        $local = [
            //connection
            IngenicoConf::CONF_CONNECTION_MODE => $this->cnf->getMode(true),
            IngenicoConf::CONF_CONNECTION_TEST_PSPID => $this->cnf->getConnectionPspid(
                Config::MODE_TEST,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_TEST_SIGNATURE => $this->cnf->getConnectionSignature(
                Config::MODE_TEST,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_TEST_DL_USER => $this->cnf->getConnectionUser(
                Config::MODE_TEST,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_TEST_DL_PASSWORD => $this->cnf->getConnectionPassword(
                Config::MODE_TEST,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_TEST_WEBHOOK => $this->getUrl('ingenico/payment/webhook'),
            IngenicoConf::CONF_CONNECTION_LIVE_PSPID => $this->cnf->getConnectionPspid(
                Config::MODE_LIVE,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_LIVE_SIGNATURE => $this->cnf->getConnectionSignature(
                Config::MODE_LIVE,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_LIVE_DL_USER => $this->cnf->getConnectionUser(
                Config::MODE_LIVE,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_LIVE_DL_PASSWORD => $this->cnf->getConnectionPassword(
                Config::MODE_LIVE,
                $storeId
            ),
            IngenicoConf::CONF_CONNECTION_LIVE_WEBHOOK => $this->getUrl('ingenico/payment/webhook'),

            // settings general
            IngenicoConf::CONF_SETTINGS_ADVANCED => $this->cnf->getIsAdvancedSettingsMode(),

            // settings tokenisation
            IngenicoConf::CONF_SETTINGS_TOKENIZATION => $this->cnf->isTokenizationEnabled($storeId),
            IngenicoConf::CONF_SETTINGS_ONECLICK => $this->cnf->isStoredCardsEnabled($storeId),
            IngenicoConf::CONF_SETTINGS_SKIP3DSCVC => $this->cnf->getSkipSecurityCheck($storeId),
            IngenicoConf::CONF_SETTINGS_SKIPSECURITYCHECK => $this->cnf->getSkipSecurityCheck($storeId),

            IngenicoConf::CONF_SETTINGS_DIRECTSALES => $this->cnf->isDirectSalesMode($storeId),
            IngenicoConf::CONF_DIRECT_SALE_EMAIL_OPTION => $this->cnf->getSendEmailCaptureRequests($storeId),
            IngenicoConf::CONF_DIRECT_SALE_EMAIL => $this->cnf->getPaymentAuthorisationNotificationEmail($storeId),

            // settings orders
            IngenicoConf::CONF_SETTINGS_REMINDEREMAIL => $this->cnf->getPaymentReminderEmailSend($storeId),
            IngenicoConf::CONF_SETTINGS_REMINDEREMAIL_DAYS => $this->cnf->getPaymentReminderEmailTimeout($storeId),

            // payment page
            IngenicoConf::CONF_PAYMENTPAGE_TYPE => $this->cnf->getPaymentPageMode($storeId),
            IngenicoConf::CONF_PAYMENTPAGE_TEMPLATE => $this->cnf->getPaymentPageTemplateSource($storeId),
            IngenicoConf::CONF_PAYMENTPAGE_TEMPLATE_NAME => $this->cnf->getPaymentPageTemplateName($storeId),
            IngenicoConf::CONF_PAYMENTPAGE_TEMPLATE_EXTERNALURL => $this->cnf->getPaymentPageExternalUrl($storeId),
            IngenicoConf::CONF_PAYMENTPAGE_TEMPLATE_LOCALFILENAME => $this->cnf->getPaymentPageLocal($storeId),
            IngenicoConf::CONF_PAYMENTPAGE_LIST_TYPE => $this->cnf->getPaymentPageListType($storeId),

            // payment methods
            IngenicoConf::CONF_SELECTED_PAYMENT_METHODS => $this->cnf->getActivePaymentMethods($storeId)
        ];

        foreach ($local as $key => $val) {
            if ($val !== null) {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    /**
     * Retrieves orderId from checkout session.
     *
     * @return mixed
     */
    public function requestOrderId()
    {
        if (!$this->orderId) {
            if ($reminderOrderId = $this->checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
                $this->orderId = $reminderOrderId;
            }
            if ($sessOrderId = $this->checkoutSession->getData('last_real_order_id')) {
                $this->orderId = $sessOrderId;
            }
        }

        return $this->orderId;
    }

    /**
     * Retrieves Customer (buyer) ID on the platform side.
     * Zero for guests.
     * Needed for retrieving customer aliases (if saved any).
     *
     * @return int
     */
    public function requestCustomerId()
    {
        if (!$this->customerId) {
            if ($orderId = $this->requestOrderId()) {
                $order = $this->processor->getOrderByIncrementId($orderId);
                if ($orderCustomerId = $order->getCustomerId()) {
                    $this->customerId = $orderCustomerId;
                } else {
                    $this->customerId = 0;
                }
            } elseif ($sessCustomerId = $this->customerSession->getId()) {
                $this->customerId = $sessCustomerId;
            }
        }

        return $this->customerId;
    }

    /**
     * Returns callback URLs where Ingenico must call after the payment processing. Depends on the context of the callback.
     * Following cases are required:
     *  CONTROLLER_TYPE_PAYMENT
     *  CONTROLLER_TYPE_SUCCESS
     *  CONTROLLER_TYPE_ORDER_SUCCESS
     *  CONTROLLER_TYPE_ORDER_CANCELLED
     *
     * @param $type
     * @param array $params
     * @return string
     */
    public function buildPlatformUrl($type, array $params = [])
    {
        switch ($type) {
            case $this->coreLibrary::CONTROLLER_TYPE_PAYMENT:
                return $this->getUrl('ingenico/payment/redirect', ['_query' => $params]);
            case $this->coreLibrary::CONTROLLER_TYPE_SUCCESS:
                return $this->getUrl('ingenico/payment/result', ['_query' => $params]);
            case $this->coreLibrary::CONTROLLER_TYPE_ORDER_SUCCESS:
                return $this->getUrl('checkout/onepage/success');
            case $this->coreLibrary::CONTROLLER_TYPE_ORDER_CANCELLED:
                return $this->getUrl(self::PARAM_NAME_CHECKOUT_CART);
            default:
                throw new LocalizedException(__('Unknown page type.'));
        }
    }

    /**
     * This method is a generic callback gate.
     * Depending on the URI it redirects to the corresponding action which is done already on the CL level.
     * CL takes responsibility for the data processing and initiates rendering of the matching GUI (template, page etc.).
     *
     * @return void
     */
    public function processSuccessUrls()
    {
        try {
            $this->coreLibrary->processReturnUrls();
        } catch (\IngenicoClient\Exception $e) {
            $this->setOrderErrorPage($e->getMessage());
        }
    }

    /**
     * Executed on the moment when a buyer submits checkout form with an intention to start the payment process.
     * Depending on the payment mode (Inline vs. Redirect) CL will initiate the right processes and render the corresponding GUI.
     *
     * @deprecated
     * @param mixed|null  $aliasId
     * @param bool $forceAliasSave
     *
     * @return void
     * @throws LocalizedException
     */
    public function processPayment($aliasId = null, $forceAliasSave = false)
    {
        $orderId = $this->requestOrderId();

        try {
            $this->coreLibrary->processPayment($orderId, $aliasId, $forceAliasSave);
            // @see self::showPaymentListRedirectTemplate()
            // @see self::showPaymentListInlineTemplate()
        } catch (\IngenicoClient\Exception $e) {
            // Show Error Page
            $this->setOrderErrorPage($e->getMessage());
        }
    }

    /**
     * Executed on the moment when a buyer submits checkout form with an intention to start the payment process.
     * @param mixed|null $aliasId
     *showPaymentListRedirectTemplate
     * @throws LocalizedException
     */
    public function processPaymentRedirect($aliasId)
    {
        $orderId = $this->requestOrderId();

        try {
            $this->coreLibrary->processPaymentRedirect($orderId, $aliasId);
            // @see self::showPaymentListRedirectTemplate()
        } catch (\IngenicoClient\Exception $e) {
            // Show Error Page
            $this->setOrderErrorPage($e->getMessage());
        }
    }

    /**
     * Executed on the moment when a buyer submits checkout form with an intention to start the payment process.
     * @param mixed|null $aliasId
     * @param string $paymentMethod
     * @param string $brand
     *
     * @throws LocalizedException
     */
    public function processPaymentRedirectSpecified($aliasId, $paymentMethod, $brand)
    {
        $orderId = $this->requestOrderId();

        try {
            $this->coreLibrary->processPaymentRedirectSpecified($orderId, $aliasId, $paymentMethod, $brand);
            // @see self::showPaymentListRedirectTemplate()
        } catch (\IngenicoClient\Exception $e) {
            // Show Error Page
            $this->setOrderErrorPage($e->getMessage());
        }
    }

    /**
     * Executed on the moment when a buyer submits checkout form with an intention to start the payment process.
     * @param mixed|null $aliasId
     *
     * @throws LocalizedException
     */
    public function processPaymentInline($aliasId = null)
    {
        $orderId = $this->requestOrderId();

        try {
            $this->checkoutSession->setData(CheckIsReturnFromPaymentInline::PROCESS_PAYMENT_INLINE_FLAG_KEY, true);
            $this->coreLibrary->processPaymentInline($orderId, $aliasId);
            // @see self::showPaymentListInlineTemplate()
        } catch (Exception $e) {
            // Show Error Page
            $this->setOrderErrorPage($e->getMessage());
        }
    }

    /**
     * Matches Ingenico payment statuses to the platform's order statuses.
     *
     * @param mixed $orderId
     * @param \IngenicoClient\Payment $paymentResult
     * @param string|null $message
     * @return void
     */
    public function updateOrderStatus($orderId, $paymentResult, $message = null)
    {
        $storeId = $this->getStoreId($orderId);

        switch ($paymentResult->getPaymentStatus()) {
            case $this->coreLibrary::STATUS_PENDING:
                break;
            case $this->coreLibrary::STATUS_AUTHORIZED:
                $this->processor->processOrderAuthorization($orderId, $paymentResult, $message);
                if (!$this->cnf->isDirectSalesMode($storeId) && $this->cnf->getMode(false, $storeId) == 'test') {
                    $this->messageManager->addNotice(__('checkout.test_mode_warning') . ' ' . __('checkout.manual_capture_required'));
                }
                break;
            case $this->coreLibrary::STATUS_CAPTURE_PROCESSING:
                $this->processor->processOrderCaptureProcessing($orderId, $paymentResult, $message);
                break;
            case $this->coreLibrary::STATUS_CAPTURED:
                $this->processor->processOrderPayment($orderId, $paymentResult, $message);
                break;
            case $this->coreLibrary::STATUS_CANCELLED:
                $this->processor->processOrderCancellation($orderId, $paymentResult, $message);
                break;
            case $this->coreLibrary::STATUS_REFUND_PROCESSING:
                $this->processor->processOrderRefundProcessing($orderId, $paymentResult, $message);
                break;
            case $this->coreLibrary::STATUS_REFUND_REFUSED:
                $this->processor->processOrderDefault($orderId, $paymentResult, $message);
                break;
            case $this->coreLibrary::STATUS_REFUNDED:
                $this->processor->processOrderRefund($orderId, $paymentResult, $message);
                break;
            case $this->coreLibrary::STATUS_ERROR:
                break;
            default:
                throw new LocalizedException(__('Unknown Payment Status'));
        }
    }

    /**
     * Check if Shopping Cart has orders that were paid (via other payment integrations, i.e. PayPal module)
     * It's to cover the case where payment was initiated through Ingenico but at the end, user went back and paid by other
     * payment provider. In this case we know not to send order reminders etc.
     *
     * @param $orderId
     * @return bool
     */
    public function isCartPaid($orderId)
    {
        $order = $this->processor->getOrderByIncrementId($orderId);
        $orders = $this->orderCollectionFactory->create()
                                               ->addFieldToSelect('*')
                                               ->addFieldToFilter('quote_id', $order->getQuoteId());

        foreach ($orders as $order) {
            /** @var Order $order */
            if ($order->hasInvoices()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delegates to the CL the complete processing of the onboarding data and dispatching email to the corresponding
     *  Ingenico sales representative.
     *
     * @param string $companyName
     * @param string $email
     * @param string $countryCode
     *
     * @throws \IngenicoClient\Exception
     */
    public function submitOnboardingRequest($companyName, $email, $countryCode)
    {
        $this->coreLibrary->submitOnboardingRequest(
            $companyName,
            $email,
            $countryCode,
            'Magento 2',
            $this->requestShoppingCartExtensionId(),
            $this->cnf->getStoreName($this->getStoreId()),
            $this->_getStoreEmailLogo(),
            $this->getUrl('/'),
            $this->cnf->getIngenicoLogo(),
            $this->getLocale()
        );
    }

    /**
     * Returns an array with the order details in a standardised way for all connectors.
     * Matches platform specific fields to the fields that are understood by the CL.
     *
     * @param mixed $orderId
     * @return array
     */
    public function requestOrderInfo($orderId = null)
    {
        $order = $this->processor->getOrderByIncrementId($orderId);

        // Ingenico statuses
        $authorizedStatus = $this->cnf->getValue(
            IngenicoConfig::XML_PATH_ORDER_STATUS_AUTHORIZED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        $capturedStatus = $this->cnf->getValue(
            IngenicoConfig::XML_PATH_ORDER_STATUS_CAPTURED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        // Mapping between Magento Order status and Ingenico Payment status
        if ($order->getStatus() === $authorizedStatus) {
            $status = $this->coreLibrary::STATUS_AUTHORIZED;
        } elseif ($order->getStatus() === $capturedStatus) {
            $status = $this->coreLibrary::STATUS_CAPTURED;
        } else {
            switch ($order->getState()) {
                case $order::STATE_NEW:
                case $order::STATE_PENDING_PAYMENT:
                    $status = $this->coreLibrary::STATUS_PENDING;
                    break;
                case $order::STATE_CANCELED:
                    $status = $this->coreLibrary::STATUS_CANCELLED;
                    break;
                case $order::STATE_CLOSED:
                    $status = $this->coreLibrary::STATUS_REFUNDED;
                    break;
                default:
                    $status = $this->coreLibrary::STATUS_UNKNOWN;
                    break;
            }
        }

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            $shippingAddress = $billingAddress;
        }
        $customerId = 0;
        if ($order->getCustomerId()) {
            $customerId = $order->getCustomerId();
        }

        $customerDob = null;
        $customerGender = null;

        // Get customer's Date of Birth
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerDob = $customer->getDob();

            // Get customer's gender
            switch ($customer->getGender()) {
                case 1:
                    $customerGender = 'M';
                    break;
                case 2:
                    $customerGender = 'F';
            }
        } catch (NoSuchEntityException $exception) {
            $customerDob = null;
            $customerGender = null;
        }

        // Calculate refunded, cancelled, and captured totals
        $totalAmount = round($order->getGrandTotal(), 2);
        $refundedAmount = $order->getTotalRefunded();
        $cancelledAmount = 0.00;
        $capturedAmount = $order->getGrandTotal() - $order->getTotalDue();

        // Get order items
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $taxPercent = (int) $item->getTaxPercent();

            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_PRODUCT,
                OrderItem::ITEM_ID => $item->getSku(),
                OrderItem::ITEM_NAME => $item->getName(),
                OrderItem::ITEM_DESCRIPTION => $item->getName(),
                OrderItem::ITEM_UNIT_PRICE => round($item->getRowTotalInclTax() / $item->getQtyOrdered(), 2),
                OrderItem::ITEM_QTY => $item->getQtyOrdered(),
                OrderItem::ITEM_UNIT_VAT => round(($item->getRowTotalInclTax() - $item->getRowTotal()) / $item->getQtyOrdered(), 2),
                OrderItem::ITEM_VATCODE => $taxPercent,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add Discount Order line
        if (abs($order->getDiscountAmount()) > 0 || abs($order->getShippingDiscountAmount()) > 0) {
            $taxPercent = 0;

            $totalDiscount = abs($order->getDiscountAmount() + $order->getShippingDiscountAmount());

            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'discount',
                OrderItem::ITEM_NAME => 'Discount',
                OrderItem::ITEM_DESCRIPTION => 'Discount',
                OrderItem::ITEM_UNIT_PRICE => -1 * $totalDiscount,
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add "Store Credit" discount
        if ($order->getCustomerBalanceAmount() > 0) {
            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'store_credit',
                OrderItem::ITEM_NAME => __('Store Credit'),
                OrderItem::ITEM_DESCRIPTION =>__('Store Credit'),
                OrderItem::ITEM_UNIT_PRICE => -1 * $order->getCustomerBalanceAmount(),
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add "Reward Points" discount
        if ($order->getRewardCurrencyAmount() > 0) {
            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'reward_pints',
                OrderItem::ITEM_NAME => __('Reward Points'),
                OrderItem::ITEM_DESCRIPTION =>__('Reward Points'),
                OrderItem::ITEM_UNIT_PRICE => -1 * $order->getRewardCurrencyAmount(),
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add "Gift Cards" discount
        if ($order->getGiftCardsAmount() > 0) {
            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'gift_cards',
                OrderItem::ITEM_NAME => __('Gift Card'),
                OrderItem::ITEM_DESCRIPTION =>__('Gift Card'),
                OrderItem::ITEM_UNIT_PRICE => -1 * $order->getGiftCardsAmount(),
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add Shipping Order Line
        $shippingIncTax = 0;
        $shippingTax = 0;
        $shippingTaxRate = 0;
        if (!$order->getIsVirtual()) {
            $shippingExclTax = $order->getShippingAmount();
            $shippingIncTax = $order->getShippingInclTax();
            $shippingTax = $shippingIncTax - $shippingExclTax;

            // find out tax-rate for the shipping
            if ((float) $shippingIncTax && (float) $shippingExclTax) {
                $shippingTaxRate = (($shippingIncTax / $shippingExclTax) - 1) * 100;
            } else {
                $shippingTaxRate = 0;
            }

            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_SHIPPING,
                OrderItem::ITEM_ID => 'shipping',
                OrderItem::ITEM_NAME => $order->getShippingDescription(),
                OrderItem::ITEM_DESCRIPTION => $order->getShippingDescription(),
                OrderItem::ITEM_UNIT_PRICE => $shippingIncTax,
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => $shippingTax,
                OrderItem::ITEM_VATCODE => round($shippingTaxRate),
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // @codingStandardsIgnoreStart
        return [
            OrderField::ORDER_ID => $this->requestOrderId(),
            OrderField::PAY_ID => $this->getIngenicoPayIdByOrderId($this->requestOrderId()),
            OrderField::AMOUNT => $totalAmount,
            OrderField::TOTAL_CAPTURED => $capturedAmount,
            OrderField::TOTAL_REFUNDED => $refundedAmount,
            OrderField::TOTAL_CANCELLED => $cancelledAmount,
            OrderField::CURRENCY => $order->getOrderCurrencyCode(),
            OrderField::STATUS => $status,
            OrderField::CREATED_AT => (new \DateTime($order->getData('created_at')))->format('Y-m-d H:i:s'), // Y-m-d H:i:s
            OrderField::BILLING_CUSTOMER_TITLE => $billingAddress->getPrefix(),
            OrderField::BILLING_COUNTRY => $billingAddress->getCountryId(),
            OrderField::BILLING_COUNTRY_CODE => $billingAddress->getCountryId(),
            OrderField::BILLING_ADDRESS1 => $billingAddress->getStreet()[0],
            OrderField::BILLING_ADDRESS2 => count($billingAddress->getStreet()) > 1 ? $billingAddress->getStreet()[1] : null,
            OrderField::BILLING_ADDRESS3 => null,
            OrderField::BILLING_STREET_NUMBER => null,
            OrderField::BILLING_CITY => $billingAddress->getCity(),
            OrderField::BILLING_STATE => $billingAddress->getRegionId(),
            OrderField::BILLING_POSTCODE => $billingAddress->getPostcode(),
            OrderField::BILLING_PHONE => $billingAddress->getTelephone(),
            OrderField::BILLING_EMAIL => $order->getData('customer_email'),
            OrderField::BILLING_FIRST_NAME => $billingAddress->getFirstname(),
            OrderField::BILLING_LAST_NAME => $billingAddress->getLastname(),
            OrderField::BILLING_FAX => $billingAddress->getFax(),
            OrderField::IS_SHIPPING_SAME => false,
            OrderField::SHIPPING_CUSTOMER_TITLE => $shippingAddress->getPrefix(),
            OrderField::SHIPPING_COUNTRY => $shippingAddress->getCountryId(),
            OrderField::SHIPPING_COUNTRY_CODE => $shippingAddress->getCountryId(),
            OrderField::SHIPPING_ADDRESS1 => $shippingAddress->getStreet()[0],
            OrderField::SHIPPING_ADDRESS2 => count($shippingAddress->getStreet()) > 1 ? $shippingAddress->getStreet()[1] : null,
            OrderField::SHIPPING_ADDRESS3 => null,
            OrderField::SHIPPING_STREET_NUMBER => null,
            OrderField::SHIPPING_CITY => $shippingAddress->getCity(),
            OrderField::SHIPPING_STATE => $shippingAddress->getRegionId(),
            OrderField::SHIPPING_POSTCODE => $shippingAddress->getPostcode(),
            OrderField::SHIPPING_PHONE => $shippingAddress->getTelephone(),
            OrderField::SHIPPING_EMAIL => $order->getData('customer_email'),
            OrderField::SHIPPING_FIRST_NAME => $shippingAddress->getFirstname(),
            OrderField::SHIPPING_LAST_NAME => $shippingAddress->getLastname(),
            OrderField::SHIPPING_FAX => $shippingAddress->getFax(),
            OrderField::CUSTOMER_ID => (int) $customerId,
            OrderField::CUSTOMER_IP => $order->getData('remote_ip'),
            OrderField::CUSTOMER_DOB => $customerDob ? (new \DateTime($customerDob))->getTimestamp() : null, //null or timestamp
            OrderField::IS_VIRTUAL => $order->getIsVirtual(),
            OrderField::ITEMS => $items,
            OrderField::LOCALE => $this->getLocale($orderId),
            OrderField::SHIPPING_METHOD => $order->getShippingDescription(),
            OrderField::SHIPPING_AMOUNT => $shippingIncTax,
            OrderField::SHIPPING_TAX_AMOUNT => $shippingTax,
            OrderField::SHIPPING_TAX_CODE => round($shippingTaxRate),
            OrderField::COMPANY_NAME => $billingAddress->getCompany(),
            OrderField::COMPANY_VAT => null,
            OrderField::CHECKOUT_TYPE => \IngenicoClient\Checkout::TYPE_B2C,
            OrderField::SHIPPING_COMPANY => $order->getData('shipping_description'),
            OrderField::CUSTOMER_CIVILITY => null,
            OrderField::CUSTOMER_GENDER => $customerGender, // M or F or null
            OrderField::ADDITIONAL_DATA => $order->getPayment()->getAdditionalInformation()
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Same As requestOrderInfo()
     * But Order Object Cannot Be Used To Fetch The Required Info
     *
     * @param mixed $reservedOrderId
     * @return array
     */
    public function requestOrderInfoBeforePlaceOrder($reservedOrderId)
    {
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->getId()) {
            $quote = $this->quoteProviderByOrderId->execute($reservedOrderId);
        }

        $status = $this->coreLibrary::STATUS_UNKNOWN;

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress) {
            $shippingAddress = $billingAddress;
        }
        $customerId = 0;
        if ($quote->getCustomerId()) {
            $customerId = $quote->getCustomerId();
        }

        // Calculate refunded, cancelled, and captured totals
        $totalAmount = round($quote->getGrandTotal(), 2);
        $refundedAmount = 0.00;
        $cancelledAmount = 0.00;
        $capturedAmount = 0.00;

        // Get quote items
        $items = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $taxPercent = (int) $item->getTaxPercent();

            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_PRODUCT,
                OrderItem::ITEM_ID => $item->getSku(),
                OrderItem::ITEM_NAME => $item->getName(),
                OrderItem::ITEM_DESCRIPTION => $item->getName(),
                OrderItem::ITEM_UNIT_PRICE => round($item->getRowTotalInclTax() / $item->getQty(), 2),
                OrderItem::ITEM_QTY => $item->getQtyOrdered(),
                OrderItem::ITEM_UNIT_VAT => round(($item->getRowTotalInclTax() - $item->getRowTotal()) / $item->getQty(), 2),
                OrderItem::ITEM_VATCODE => $taxPercent,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add Discount Order line
        if ((float)$quote->getData('discount_amount') > 0 || (float)$quote->getData('shipping_discount_amount') > 0) {
            $taxPercent = 0;

            $totalDiscount = $quote->getData('discount_amount') + $quote->getData('shipping_discount_amount');

            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'discount',
                OrderItem::ITEM_NAME => 'Discount',
                OrderItem::ITEM_DESCRIPTION => 'Discount',
                OrderItem::ITEM_UNIT_PRICE => -1 * $totalDiscount,
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add "Store Credit" discount
        if ($quote->getCustomerBalanceAmountUsed() > 0) {
            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'store_credit',
                OrderItem::ITEM_NAME => __('Store Credit'),
                OrderItem::ITEM_DESCRIPTION =>__('Store Credit'),
                OrderItem::ITEM_UNIT_PRICE => -1 * $quote->getCustomerBalanceAmountUsed(),
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add "Reward Points" discount
        if ($quote->getRewardCurrencyAmount() > 0) {
            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'reward_pints',
                OrderItem::ITEM_NAME => __('Reward Points'),
                OrderItem::ITEM_DESCRIPTION =>__('Reward Points'),
                OrderItem::ITEM_UNIT_PRICE => -1 * $quote->getRewardCurrencyAmount(),
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add "Gift Cards" discount
        if ($quote->getGiftCardsAmount() > 0) {
            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_DISCOUNT,
                OrderItem::ITEM_ID => 'gift_cards',
                OrderItem::ITEM_NAME => __('Gift Card'),
                OrderItem::ITEM_DESCRIPTION =>__('Gift Card'),
                OrderItem::ITEM_UNIT_PRICE => -1 * $quote->getGiftCardsAmount(),
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => 0,
                OrderItem::ITEM_VATCODE => 0,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // Add Shipping Quote Line
        if (!$quote->getIsVirtual()) {
            $shippingExclTax = $shippingAddress->getShippingAmount();
            $shippingIncTax = $shippingAddress->getShippingInclTax();
            $shippingTax = $shippingIncTax - $shippingExclTax;

            // find out tax-rate for the shipping
            if ((float) $shippingIncTax && (float) $shippingExclTax) {
                $shippingTaxRate = (($shippingIncTax / $shippingExclTax) - 1) * 100;
            } else {
                $shippingTaxRate = 0;
            }

            $items[] = [
                OrderItem::ITEM_TYPE => OrderItem::TYPE_SHIPPING,
                OrderItem::ITEM_ID => 'shipping',
                OrderItem::ITEM_NAME => $shippingAddress->getShippingDescription(),
                OrderItem::ITEM_DESCRIPTION => $shippingAddress->getShippingDescription(),
                OrderItem::ITEM_UNIT_PRICE => $shippingIncTax,
                OrderItem::ITEM_QTY => 1,
                OrderItem::ITEM_UNIT_VAT => $shippingTax,
                OrderItem::ITEM_VATCODE => $shippingTaxRate,
                OrderItem::ITEM_VAT_INCLUDED => 1 // VAT included
            ];
        }

        // @codingStandardsIgnoreStart
        return [
            OrderField::ORDER_ID => $reservedOrderId,
            OrderField::PAY_ID => $this->getIngenicoPayIdByOrderId($reservedOrderId),
            OrderField::AMOUNT => $totalAmount,
            OrderField::TOTAL_CAPTURED => $capturedAmount,
            OrderField::TOTAL_REFUNDED => $refundedAmount,
            OrderField::TOTAL_CANCELLED => $cancelledAmount,
            OrderField::CURRENCY => $quote->getQuoteCurrencyCode(),
            OrderField::CUSTOMER_ID => (int) $customerId,
            OrderField::STATUS => $status,
            OrderField::CREATED_AT => date('Y-m-d H:i:s', strtotime($quote->getCreatedAt())), // Y-m-d H:i:s
            OrderField::BILLING_CUSTOMER_TITLE => $billingAddress->getPrefix(),
            OrderField::BILLING_COUNTRY => $billingAddress->getCountryId(),
            OrderField::BILLING_COUNTRY_CODE => $billingAddress->getCountryId(),
            OrderField::BILLING_ADDRESS1 => $billingAddress->getStreet()[0],
            OrderField::BILLING_ADDRESS2 => count($billingAddress->getStreet()) > 1 ? $billingAddress->getStreet()[1] : null,
            OrderField::BILLING_ADDRESS3 => null,
            OrderField::BILLING_CITY => $billingAddress->getCity(),
            OrderField::BILLING_STATE => $billingAddress->getRegionId(),
            OrderField::BILLING_POSTCODE => $billingAddress->getPostcode(),
            OrderField::BILLING_PHONE => $billingAddress->getTelephone(),
            OrderField::BILLING_EMAIL => $billingAddress->getEmail(),
            OrderField::BILLING_FIRST_NAME => $billingAddress->getFirstname(),
            OrderField::BILLING_LAST_NAME => $billingAddress->getLastname(),
            OrderField::BILLING_FAX => $billingAddress->getFax(),
            OrderField::IS_SHIPPING_SAME => false,
            OrderField::SHIPPING_CUSTOMER_TITLE => $shippingAddress->getPrefix(),
            OrderField::SHIPPING_COUNTRY => $shippingAddress->getCountryId(),
            OrderField::SHIPPING_COUNTRY_CODE => $shippingAddress->getCountryId(),
            OrderField::SHIPPING_ADDRESS1 => $shippingAddress->getStreet()[0],
            OrderField::SHIPPING_ADDRESS2 => count($shippingAddress->getStreet()) > 1 ? $shippingAddress->getStreet()[1] : null,
            OrderField::SHIPPING_ADDRESS3 => null,
            OrderField::SHIPPING_CITY => $shippingAddress->getCity(),
            OrderField::SHIPPING_STATE => $shippingAddress->getRegionId(),
            OrderField::SHIPPING_POSTCODE => $shippingAddress->getPostcode(),
            OrderField::SHIPPING_PHONE => $shippingAddress->getTelephone(),
            OrderField::SHIPPING_EMAIL => $billingAddress->getEmail(),
            OrderField::SHIPPING_FIRST_NAME => $shippingAddress->getFirstname(),
            OrderField::SHIPPING_LAST_NAME => $shippingAddress->getLastname(),
            OrderField::SHIPPING_FAX => $shippingAddress->getFax(),
            OrderField::SHIPPING_COMPANY => $shippingAddress->getShippingDescription(),
            OrderField::SHIPPING_METHOD => $shippingAddress->getShippingDescription(),
            OrderField::SHIPPING_AMOUNT => $shippingAddress->getShippingAmount(),
            OrderField::SHIPPING_TAX_AMOUNT => $shippingAddress->getShippingTaxAmount(),
            OrderField::SHIPPING_TAX_CODE => 0,
            OrderField::CUSTOMER_IP => $quote->getRemoteIp(),
            OrderField::CUSTOMER_DOB => null,
            OrderField::CUSTOMER_CIVILITY => null,
            OrderField::CUSTOMER_GENDER => null, // M or F or null
            OrderField::IS_VIRTUAL => $quote->getIsVirtual(),
            OrderField::ITEMS => $items,
            OrderField::LOCALE => $this->getLocale(),
            OrderField::CHECKOUT_TYPE => \IngenicoClient\Checkout::TYPE_B2C,
            OrderField::ADDITIONAL_DATA => $quote->getPayment()->getAdditionalInformation()
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * Get Payment Method Code of Order.
     *
     * @param mixed $orderId
     *
     * @return string|false
     */
    public function getOrderPaymentMethod($orderId)
    {
        try {
            $order = $this->processor->getOrderByIncrementId($orderId);
            $method = $order->getPayment()->getMethodInstance();
            if ($method instanceof AbstractMethod) {
                return $method::CORE_CODE;
            }
            return $method->getCode();
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Get Payment Method Code of Quote/Cart.
     *
     * @param mixed $quoteId
     *
     * @return string|false
     */
    public function getQuotePaymentMethod($quoteId = null)
    {
        try {
            if (!$quoteId) {
                $quote = $this->checkoutSession->getQuote();
            } else {
                $quote = $this->quoteRepository->get($quoteId);
            }

            $method = $quote->getPayment()->getMethodInstance();

            if ($method instanceof AbstractMethod) {
                return $method::CORE_CODE;
            }
            return $method->getCode();
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Save Platform's setting (key-value couple depending on the mode).
     *
     * @param bool $mode
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function saveSetting($mode, $key, $value)
    {
        // do nothing
    }

    /**
     * Sends an e-mail using platform's email engine.
     *
     * @param \IngenicoClient\MailTemplate $template
     * @param string $to
     * @param string $toName
     * @param string $from
     * @param string $fromName
     * @param string $subject
     * @param array $attachedFiles Array like [['name' => 'attached.txt', 'mime' => 'plain/text', 'content' => 'Body']]
     * @return bool|int
     * @SuppressWarnings(MEQP2.Classes.ObjectManager.ObjectManagerFound)
     */
    public function sendMail(
        $template,
        $to,
        $toName,
        $from,
        $fromName,
        $subject,
        array $attachedFiles = []
    ) {
        if (!$template instanceof \IngenicoClient\MailTemplate) {
            throw new LocalizedException(__('Template variable must be instance of MailTemplate'));
        }

        try {
            $this->inlineTranslation->suspend();
            $emailData = new \Magento\Framework\DataObject();
            $emailData->setData([
                'subject' => $subject,
                'bodyhtml' => $template->getHtml($returnFullTemplate = false),
                'bodyhtmlfull' => $template->getHtml(),
                'bodytext' => $template->getPlainText(),
            ]);

            $sender = $this->cnf->getValue(
                \Magento\Sales\Model\Order\Email\Container\OrderIdentity::XML_PATH_EMAIL_IDENTITY,
                ScopeInterface::SCOPE_STORE,
                $this->getStoreId()
            );

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->getEmailTemplate())
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->getStoreId(),
                ])
                ->setTemplateVars(['data' => $emailData])
                ->addTo($to)
                ;

            if (method_exists($transport, 'setFromByScope')) {
                // since 102.0.1
                $transport->setFromByScope($sender, $this->getStoreId());
            } else {
                $transport->setFrom($sender);
            }

            $transport = $transport->getTransport();

            if (count($attachedFiles)) {
                $message = $transport->getMessage();

                // Check if $message has setBody method
                if (method_exists($message, 'setBody')) {
                    $parts = $message->getBody()->getParts();
                    foreach ($attachedFiles as $attachedFile) {
                        $parts[] = (new \Zend\Mime\Part())
                            ->setContent($attachedFile['content'])
                            ->setType($attachedFile['mime'])
                            ->setFileName($attachedFile['name'])
                            ->setDisposition(\Zend\Mime\Mime::DISPOSITION_ATTACHMENT)
                            ->setEncoding(\Zend\Mime\Mime::ENCODING_BASE64)
                        ;
                    }

                    $mimeMessage = (new \Zend\Mime\Message())->setParts($parts);
                    $message->setBody($mimeMessage);
                } else {
                    // Magento 2.3.3 release introduces a new, immutable EmailMessageInterface
                    // that supports the sending of multi-part MIME-type content in email.
                    // The Magento\Framework\Mail\Template\TransportBuilder
                    // structures were refactored to use this new EmailMessageInterface instead of MessageInterface,
                    // which was previously used.
                    $transportBuilder = ObjectManager::getInstance()->create(\Ingenico\Payment\Model\Email\Template\TransportBuilder::class);
                    $transport = $transportBuilder
                        ->setTemplateIdentifier($this->getEmailTemplate())
                        ->setTemplateOptions([
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $this->getStoreId(),
                        ])
                        ->setTemplateVars(['data' => $emailData])
                        ->setFrom($sender)
                        ->addTo($to)
                    ;

                    // add attachments to email
                    foreach ($attachedFiles as $attachedFile) {
                        /** @var \Ingenico\Payment\Model\Email\Template\TransportBuilder $transport */
                        $transport->addAttachment($attachedFile['content'], $attachedFile['name'], $attachedFile['mime']);
                    }

                    $transport = $transport->getTransport();
                }
            }

            $transport->sendMessage();
            $this->inlineTranslation->resume();

            return true;
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'crit');
        }

        return false;
    }

    /**
     * Get the platform's actual locale code.
     * Returns code in a format: en_US.
     *
     * @param int|null $orderId
     * @return string
     */
    public function getLocale($orderId = null)
    {
        $currentStoreId = $this->storeManager->getStore()->getId();
        $locale = $this->localeResolver->getLocale();

        if ($orderId) {
            $order = $this->processor->getOrderByIncrementId($orderId);
            $orderStoreId = $order->getStoreId();
            if ($currentStoreId !== $orderStoreId) {
                $locale = $this->localeResolver->emulate($orderStoreId);
                $this->localeResolver->revert();
            }
        }

        return $locale;
    }

    /**
     * Adds cancelled amount to the order which is used for identifying full or partial operation.
     *
     * @param $orderId
     * @param $canceledAmount
     * @return void
     */
    public function addCancelledAmount($orderId, $canceledAmount)
    {
        // do nothing
    }

    /**
     * Adds captured amount to the order which is used for identifying full or partial operation.
     *
     * @param $orderId
     * @param $capturedAmount
     * @return void
     */
    public function addCapturedAmount($orderId, $capturedAmount)
    {
        // do nothing
    }

    /**
     * Adds refunded amount to the order which is used for identifying full or partial operation.
     *
     * @param $orderId
     * @param $refundedAmount
     * @return void
     */
    public function addRefundedAmount($orderId, $refundedAmount)
    {
        // do nothing
    }

    /**
     * Send "Order paid" email to the buyer (customer).
     *
     * @param $orderId
     * @return bool
     */
    public function sendOrderPaidCustomerEmail($orderId)
    {
        $order = $this->processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);

        try {
            return $this->coreLibrary->sendMailNotificationPaidOrder(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->coreLibrary->__('order_paid.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    AbstractConnector::PARAM_NAME_SHOP_NAME => $this->cnf->getStoreName($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    AbstractConnector::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    AbstractConnector::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    AbstractConnector::PARAM_NAME_ORDER_URL => $this->getUrl(
                        self::PARAM_NAME_SALES_ORDER_VIEW,
                        [
                            self::PARAM_NAME_ORDER_ID => $order->getId()
                        ]
                    )
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }

        return false;
    }

    /**
     * Send "Order paid" email to the merchant.
     *
     * @param $orderId
     * @return bool
     */
    public function sendOrderPaidAdminEmail($orderId)
    {
        // already implemented in Magento
    }

    /**
     * Send "Payment Authorized" email to the buyer (customer).
     *
     * @param $orderId
     * @return bool
     */
    public function sendNotificationAuthorization($orderId)
    {
        $order = $this->processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);

        if (!$this->registry->registry(self::REGISTRY_KEY_CAN_SEND_AUTH_EMAIL)) {
            return null;
        }

        try {
            return $this->coreLibrary->sendMailNotificationAuthorization(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->coreLibrary->__('authorization.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    AbstractConnector::PARAM_NAME_SHOP_NAME => $this->cnf->getStoreName($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    AbstractConnector::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    AbstractConnector::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    AbstractConnector::PARAM_NAME_ORDER_URL => $this->getUrl(
                        self::PARAM_NAME_SALES_ORDER_VIEW,
                        [
                            self::PARAM_NAME_ORDER_ID => $order->getId()
                        ]
                    )
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }

        return false;
    }

    /**
     * Send "Payment Authorized" email to the merchant.
     *
     * @param $orderId
     * @return bool
     */
    public function sendNotificationAdminAuthorization($orderId)
    {
        if (!$this->registry->registry(self::REGISTRY_KEY_CAN_SEND_AUTH_EMAIL)) {
            return null;
        }

        $order = $this->processor->getOrderByIncrementId($orderId);

        // Get recipient's email
        $recipient = $this->cnf->getPaymentAuthorisationNotificationEmail($order->getStoreId());
        if (!$recipient) {
            $recipient = $this->cnf->getValue(
                'trans_email/ident_general/email',
                ScopeInterface::SCOPE_STORE,
                $order->getStoreId()
            );
        }

        // Get locale of admin if possible
        $locale = $this->getAdminUserLocale($recipient);
        if (!$locale) {
            $locale = $this->localeResolver->getDefaultLocale();
        }

        $this->appEmulation->startEnvironmentEmulation(
            $order->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );

        try {
            $this->setEmailTemplate('ingenico_empty');

            return $this->coreLibrary->sendMailNotificationAdminAuthorization(
                $recipient,
                null,
                null,
                null,
                $this->coreLibrary->__('admin_authorization.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    AbstractConnector::PARAM_NAME_SHOP_NAME => $this->cnf->getStoreName($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    AbstractConnector::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    AbstractConnector::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    AbstractConnector::PARAM_NAME_ORDER_VIEW_URL => $this->getUrl(
                        self::PARAM_NAME_SALES_ORDER_VIEW,
                        [
                            self::PARAM_NAME_ORDER_ID => $order->getId(),
                            self::CNF_SCOPE_PARAM_NAME => 0
                        ]
                    ),
                    'path_uri' => '',
                    AbstractConnector::PARAM_NAME_INGENICO_LOGO => $this->cnf->getIngenicoLogo()
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }

        $this->appEmulation->stopEnvironmentEmulation();

        return false;
    }

    /**
     * Sends payment reminder email to the buyer (customer).
     *
     * @param $orderId
     * @return bool
     */
    public function sendReminderNotificationEmail($orderId)
    {
        try {
            /** @var \Ingenico\Payment\Model\Reminder $reminder */
            $reminder = $this->reminderFactory->create()->load($orderId, self::PARAM_NAME_ORDER_ID);
            if (!$reminder->getId() || $reminder->getIsSent()) {
                return null;
            }

            $this->setOrderId($orderId);

            $order = $this->processor->getOrderByIncrementId($orderId);

            $this->appEmulation->startEnvironmentEmulation(
                $order->getStoreId(),
                \Magento\Framework\App\Area::AREA_FRONTEND,
                true
            );

            // Get products
            $products = [];
            foreach ($order->getAllVisibleItems() as $item) {
                /** @var \Magento\Catalog\Model\Product $product */
                $product = $this->productRepositoryFactory->create()->getById($item->getProductId());
                if (!$product->getId()) {
                    continue;
                }

                $imageUrl = $this->productImageHelper->init($product, 'product_small_image')->getUrl();
                $products[] = [
                    'image' => $imageUrl,
                    'name' => $item->getData('name') . ' ('.$item->getData('sku').')',
                    'price' => $this->priceHelper->currency($product->getFinalPrice(), true, false)
                ];
            }

            // Get Customer's locale
            $locale = $this->getLocale($orderId);

            $result = $this->coreLibrary->sendMailNotificationReminder(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->coreLibrary->__('reminder.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    AbstractConnector::PARAM_NAME_SHOP_NAME => $this->cnf->getStoreName($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_LOGO => '',//$this->_getStoreEmailLogo($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    AbstractConnector::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    AbstractConnector::PARAM_NAME_PRODUCTS => $products,
                    AbstractConnector::PARAM_NAME_ORDER_TOTAL => $this->priceHelper->currency($order->getGrandTotal(), true, false),
                    AbstractConnector::PARAM_NAME_PAYMENT_LINK => $this->getUrl('ingenico/payment/resume', [
                        'token' => $reminder->getSecureToken(),
                        self::CNF_SCOPE_PARAM_NAME => $order->getStoreId()
                    ])
                ],
                $locale
            );

            $this->appEmulation->stopEnvironmentEmulation();

            return $result;
        } catch (\Exception $e) {
            $this->log('sendReminderNotificationEmail is failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Send "Refund failed" email to the buyer (customer).
     *
     * @param $orderId
     * @return bool
     */
    public function sendRefundFailedCustomerEmail($orderId)
    {
        $order = $this->processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);

        try {
            return $this->coreLibrary->sendMailNotificationRefundFailed(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->coreLibrary->__('refund_failed.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    AbstractConnector::PARAM_NAME_SHOP_NAME => $this->cnf->getStoreName($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    AbstractConnector::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    AbstractConnector::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    AbstractConnector::PARAM_NAME_ORDER_URL => $this->getUrl(
                        self::PARAM_NAME_SALES_ORDER_VIEW,
                        [
                            self::PARAM_NAME_ORDER_ID => $order->getId()
                        ]
                    )
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }

        return false;
    }

    /**
     * Send "Refund failed" email to the merchant.
     *
     * @param $orderId
     * @return bool
     */
    public function sendRefundFailedAdminEmail($orderId)
    {
        $order = $this->processor->getOrderByIncrementId($orderId);
        $recipient = $this->cnf->getValue(
            'trans_email/ident_sales/email',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        // Get locale of admin if possible
        $locale = $this->getAdminUserLocale($recipient);
        if (!$locale) {
            $locale = $this->localeResolver->getDefaultLocale();
        }

        try {
            $this->setEmailTemplate('ingenico_empty');

            return $this->coreLibrary->sendMailNotificationAdminRefundFailed(
                $recipient,
                $this->cnf->getStoreName($order->getStoreId()),
                null,
                null,
                $this->coreLibrary->__('admin_refund_failed.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    AbstractConnector::PARAM_NAME_SHOP_NAME => $this->cnf->getStoreName($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    AbstractConnector::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    AbstractConnector::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    AbstractConnector::PARAM_NAME_ORDER_URL => $this->getUrl(
                        self::PARAM_NAME_SALES_ORDER_VIEW,
                        [
                            self::PARAM_NAME_ORDER_ID => $order->getId()
                        ]
                    ),
                    'path_uri' => '',
                    AbstractConnector::PARAM_NAME_INGENICO_LOGO => $this->cnf->getIngenicoLogo()
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }

        return false;
    }

    /**
     * Send "Request Support" email to Ingenico Support
     * @param $email
     * @param $subject
     * @param array $fields
     * @param null $file
     * @return bool
     */
    public function sendSupportEmail(
        $email,
        $subject,
        array $fields = [],
        $file = null
    ) {
        // Attached files
        $attachedFiles = [];
        // phpcs:ignore
        if ($file && file_exists($file)) {
            $attachedFiles = [
                // phpcs:ignore
                ['name' => basename($file), 'mime' => 'plain/text', 'content' => file_get_contents($file)]
            ];
        }

        // Default Mail template fields
        $fields = array_merge(
            [
                AbstractConnector::PARAM_NAME_PLATFORM => $this->requestShoppingCartExtensionId(),
                AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                AbstractConnector::PARAM_NAME_SHOP_NAME => $this->cnf->getStoreName($this->getStoreId()),
                AbstractConnector::PARAM_NAME_TICKET => '',
                AbstractConnector::PARAM_NAME_DESCRIPTION => ''
            ],
            $fields
        );

        // Send E-mail
        return $this->getCoreLibrary()->sendMailSupport(
            $this->getCoreLibrary()->getWhiteLabelsData()->getSupportEmail(),
            $this->getCoreLibrary()->getWhiteLabelsData()->getSupportName(),
            $email,
            $this->cnf->getStoreName($this->getStoreId()),
            $subject,
            $fields,
            $this->getLocale(),
            $attachedFiles
        );
    }

    /**
     * Save Payment data.
     * This data helps to avoid constant pinging of Ingenico to get PAYID and other information
     *
     * @param $orderId
     * @param \IngenicoClient\Payment $data
     *
     * @return bool
     */
    public function logIngenicoPayment($orderId, \IngenicoClient\Payment $data)
    {
        $trxData = $data->getData();
        $trxData['order_id'] = $orderId;
        $trxData['transaction_data'] = json_encode($data->getData());

        $collection = $this->transactionCollectionFactory
            ->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('pay_id', $data->getPayId())
            ->addFieldToFilter('pay_id_sub', $data->getPayIdSub());

        try {
            if ($collection->getSize() > 0) {
                // Update
                /** @var \Ingenico\Payment\Model\Transaction $trx */
                $trx = $collection->getFirstItem();
                $trx->setUpdatedAt(date('Y-m-d H:i:s', time()))
                    ->addData($trxData)
                    ->save();
            } else {
                $trx = $this->transactionFactory->create();
                $trx->setCreatedAt(date('Y-m-d H:i:s', time()))
                    ->setUpdatedAt(date('Y-m-d H:i:s', time()))
                    ->addData($trxData)
                    ->save();
            }
        } catch (\Exception $e) {
            $this->logger->crit('Failed saving payment transaction: ' . $e->getMessage());
        }

        // process Magento Transaction if needed
        $transactionStatus = $this->coreLibrary->getStatusByCode($data->getStatus());
        $transactionTypeMap = [
            $this->coreLibrary::STATUS_AUTHORIZED => Transaction::TYPE_AUTH,
            $this->coreLibrary::STATUS_CAPTURED   => Transaction::TYPE_CAPTURE,
            $this->coreLibrary::STATUS_CANCELLED  => Transaction::TYPE_VOID,
            $this->coreLibrary::STATUS_REFUNDED   => Transaction::TYPE_REFUND
        ];

        // only create relevant Magento Transactions
        if (isset($transactionTypeMap[$transactionStatus])) {
            $trxType = $transactionTypeMap[$transactionStatus];

            /** @var Order $order */
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            if (!$order->getId()) {
                throw new \Exception('Order doesn\'t exists in store');
            }

            // Register Magento Transaction
            $order->getPayment()->setTransactionId($data->getPayId() . '-' . $data->getPayIdSub());

            /** @var \Magento\Sales\Model\Order\Payment\Transaction $transaction */
            $transaction = $order->getPayment()->addTransaction($trxType, null, true);
            $transaction
                ->setIsClosed(0)
                ->setAdditionalInformation(Transaction::RAW_DETAILS, $data->getData())
                ->save();
        }

        return true;
    }

    /**
     * Retrieves payment log for the specified order ID.
     *
     * @param $orderId
     *
     * @return \IngenicoClient\Payment
     */
    public function getIngenicoPaymentLog($orderId)
    {
        $collection = $this->transactionCollectionFactory
            ->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('order_id', $orderId)
            ->setOrder('pay_id_sub', 'DESC')
            ->setPageSize(1)
            ->setCurPage(1);

        if ($collection->getSize() > 0) {
            /** @var \Ingenico\Payment\Model\Transaction $trx */
            $trx = $collection->getFirstItem();
            return new \IngenicoClient\Payment($trx->unsId()->unsTransactionData()->unsTrxdate()->getData());
        }

        return new \IngenicoClient\Payment([]);
    }

    /**
     * Retrieves payment log entry by the specified Pay ID (PAYID).
     *
     * @param $payId
     *
     * @return \IngenicoClient\Payment
     */
    public function getIngenicoPaymentById($payId)
    {
        $collection = $this->transactionCollectionFactory
            ->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('pay_id', $payId)
            ->setOrder('pay_id_sub', 'DESC')
            ->setPageSize(1)
            ->setCurPage(1);

        if ($collection->getSize() > 0) {
            /** @var \Ingenico\Payment\Model\Transaction $trx */
            $trx = $collection->getFirstItem();
            return new \IngenicoClient\Payment($trx->unsId()->unsTransactionData()->unsTrxdate()->getData());
        }

        return new \IngenicoClient\Payment([]);
    }

    /**
     * Retrieves Ingenico Pay ID by the specified platform order ID.
     *
     * @param $orderId
     * @return string|false
     */
    public function getIngenicoPayIdByOrderId($orderId)
    {
        $collection = $this->transactionCollectionFactory
            ->create()
            ->addFieldToSelect('pay_id')
            ->addFieldToFilter('order_id', $orderId)
            ->setPageSize(1)
            ->setCurPage(1);

        if ($collection->getSize() > 0) {
            return $collection->getFirstItem()->getPayId();
        }

        return null;
    }

    /**
     * Retrieves buyer (customer) aliases by the platform's customer ID.
     *
     * @param $customerId
     * @return array
     */
    public function getCustomerAliases($customerId)
    {
        $aliasColl = $this->aliasCollectionFactory->create()
                                                  ->addFieldToFilter('customer_id', $customerId);

        return $aliasColl->getData();
    }

    /**
     * Retrieves an Alias object with the fields as an array by the Alias ID (platform's entity identifier).
     * Fields list: alias_id, customer_id, ALIAS, ED, BRAND, CARDNO, BIN, PM.
     *
     * @param $aliasId
     * @return array|false
     */
    public function getAlias($aliasId)
    {
        $alias = $this->aliasFactory->create()->load($aliasId, self::PARAM_NAME_ALIAS);
        if ($alias->getId()) {
            $alias->unsId();
            $alias->setAliasId($alias->getAlias());
            return $alias->getData();
        }

        return [];
    }

    /**
     * Saves the buyer (customer) Alias entity.
     * Important fields that are provided by Ingenico: ALIAS, BRAND, CARDNO, BIN, PM, ED, CN.
     *
     * @param int $customerId
     * @param array $data
     * @return bool
     */
    public function saveAlias($customerId, array $data)
    {
        $data['customer_id'] = $customerId;
        $data = array_change_key_case($data, CASE_LOWER);

        $collection = $this->aliasCollectionFactory
            ->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('alias', $data['alias']);

        try {
            if ($collection->getSize() > 0) {
                // Update
                /** @var \Ingenico\Payment\Model\Alias $trx */
                $alias = $collection->getFirstItem();
                $alias->setUpdatedAt(date('Y-m-d H:i:s', time()))
                    ->addData($data)
                    ->save();
            } else {
                $trx = $this->aliasFactory->create();
                $trx->setCreatedAt(date('Y-m-d H:i:s', time()))
                    ->setUpdatedAt(date('Y-m-d H:i:s', time()))
                    ->addData($data)
                    ->save();
            }
        } catch (\Exception $e) {
            $this->logger->crit('Failed saving alias: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Renders the template of the payment success page.
     *
     * @param array $fields
     * @param \IngenicoClient\Payment $payment
     *
     * @return void
     */
    public function showSuccessTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        $this->emptyShoppingCart();
        $this->registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl('checkout/onepage/success'));
    }

    /**
     * Renders the template with 3Ds Security Check.
     *
     * @param array $fields
     * @param \IngenicoClient\Payment $payment
     *
     * @return void
     */
    public function showSecurityCheckTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        // Render $fields['html']
        $this->registry->register(self::REGISTRY_KEY_TEMPLATE_VARS_ALIAS, $fields);
    }

    /**
     * Restore Customer Session.
     * Some Payment Methods ( like Paysafecard or CBC/KBC) Logged out customer after order cancellation
     * @param ?string $orderId
     */
    private function restoreCustomerSession($orderId = null): void
    {
        $sessionCustomerId = $this->customerSession->getCustomerId();
        if ($sessionCustomerId) {
            return ;
        }

        $this->restoreCheckoutSessionLastRealOrderId($orderId);
        $orderCustomerId = $this->checkoutSession->getLastRealOrder()->getCustomerId();
        if ($orderCustomerId) {
            $this->customerSession->loginById($orderCustomerId);
        }
    }

    /**
     * Renders the template with the order cancellation.
     *
     * @param array $fields
     * @param \IngenicoClient\Payment $payment
     *
     * @return void
     */
    public function showCancellationTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        $message = $fields[self::PARAM_NAME_MESSAGE] ?? __('checkout.payment_cancelled');

        $this->messageManager->addError($message);
        if ($this->checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
            $this->registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl('/'));
        } else {
            $this->restoreShoppingCart($payment->getOrderId());
            $this->restoreCustomerSession($payment->getOrderId());

            $incrementId = $fields[self::PARAM_NAME_ORDER_ID];
            if ($incrementId) {
                $order = $this->processor->getOrderByIncrementId($incrementId);

                // Commerce: Cancel order if the store customer balance wasn't set only to prevent double refund
                if (class_exists('\Magento\CustomerBalance\Helper\Data', false)) {
                    /** @var \Magento\CustomerBalance\Helper\Data $helper */
                    $helper = ObjectManager::getInstance()->create('Magento\CustomerBalance\Helper\Data');
                    if ($helper->isEnabled() &&
                        !$helper->isAutoRefundEnabled() &&
                        abs($order->getBaseCustomerBalanceAmount()) === 0
                    ) {
                        $this->processor->processOrderCancellation(
                            $fields[self::PARAM_NAME_ORDER_ID],
                            $payment,
                            $message
                        );
                    }
                }
            }

            $this->registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl(self::PARAM_NAME_CHECKOUT_CART));
        }
    }

    /**
     * Renders page with Inline's Loader template.
     * This template should include code that allow charge payment asynchronous.
     *
     * @param array $fields
     * @return void
     */
    public function showInlineLoaderTemplate(array $fields)
    {
        $this->registry->register(self::REGISTRY_KEY_INLINE_LOADER_PARAMS, $fields);
    }

    public function finishReturnInline($orderId, $cardBrand, $aliasId)
    {
        try {
            $result = $this->coreLibrary->finishReturnInline($orderId, $cardBrand, $aliasId);
            if (isset($result[self::PARAM_NAME_MESSAGE])) {
                $this->restoreShoppingCart();
            }
        } catch (\Exception $e) {
            $this->restoreShoppingCart();
            $result = [
                'status' => 'error',
                self::PARAM_NAME_MESSAGE => $e->getMessage(),
                'redirect' => $this->getUrl(self::PARAM_NAME_CHECKOUT_CART)
            ];
        }

        return $result;
    }

    /**
     * In case of error, display error page.
     *
     * @param $message
     * @return void
     */
    public function setOrderErrorPage($message)
    {
        $this->restoreShoppingCart();
        throw new LocalizedException(__($message));
    }

    /**
     * Renders the template with the payment error.
     *
     * @param array $fields
     * @param \IngenicoClient\Payment $payment
     *
     * @return void
     */
    public function showPaymentErrorTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        $message = $fields[self::PARAM_NAME_MESSAGE] ?? __('ingenico.exception.message4');

        if ($this->checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
            $this->registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl('/'));
        } else {
            $this->restoreShoppingCart($payment->getOrderId());
            $this->processor->processOrderCancellation(
                $fields[self::PARAM_NAME_ORDER_ID],
                $payment,
                $message
            );

            $this->registry->register(
                self::REGISTRY_KEY_REDIRECT_URL,
                $this->getUrl(self::PARAM_NAME_CHECKOUT_CART)
            );
        }

        throw new LocalizedException(__($message));
    }

    /**
     * Renders the template of payment methods list for the redirect mode.
     *
     * @param array $fields
     *
     * @return void
     */
    public function showPaymentListRedirectTemplate(array $fields)
    {
        $this->registry->register(self::REGISTRY_KEY_TEMPLATE_VARS_REDIRECT, $fields);
    }

    /**
     * Renders the template with the payment methods list for the inline mode.
     *
     * @param array $fields
     *
     * @return void
     */
    public function showPaymentListInlineTemplate(array $fields)
    {
        $this->registry->register(self::REGISTRY_KEY_TEMPLATE_VARS_INLINE, $fields);
    }

    /**
     * Renders the template with the payment methods list for the alias selection.
     * It does require by CoreLibrary.
     *
     * @param array $fields
     *
     * @return void
     */
    public function showPaymentListAliasTemplate(array $fields)
    {
        // do nothing
    }

    /**
     * Retrieves the list of orders that have no payment status at all or have an error payment status.
     * Used for the cron job that is proactively updating orders statuses.
     * Returns an array with order IDs.
     *
     * @return array
     */
    public function getNonactualisedOrdersPaidWithIngenico()
    {
        // do nothing
    }

    /**
     * Sets PaymentStatus.Actualised Flag.
     * Used for the cron job that is proactively updating orders statuses.
     *
     * @param $orderId
     * @param bool $value
     * @return bool
     */
    public function setIsPaymentStatusActualised($orderId, $value)
    {
        // do nothing
    }

    /**
     * Retrieves the list of orders for the reminder email.
     *
     * @return array
     */
    public function getPendingReminders()
    {
        $result = [];
        $coll = $this->reminderCollectionFactory->create()->addFieldToFilter('is_sent', 0);
        foreach ($coll as $reminder) {
            $result[] = $reminder->getOrderId();
        }

        return $result;
    }

    /**
     * Sets order reminder flag as "Sent".
     *
     * @param $orderId
     *
     * @return void
     */
    public function setReminderSent($orderId)
    {
        $this->reminderFactory->create()->markAsSent($orderId);
    }

    /**
     * Enqueues the reminder for the specified order.
     * Used for the cron job that is sending payment reminders.
     *
     * @param mixed $orderId
     * @return void
     */
    public function enqueueReminder($orderId)
    {
        try {
            $order = $this->processor->getOrderByIncrementId($orderId);
            $this->reminderFactory->create()->register($order);
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'crit');
        }
    }

    /**
     * Initiates payment page from the reminder email link.
     *
     * @return void
     */
    public function showReminderPayOrderPage()
    {
        // do nothing
    }

    /**
     * Retrieves the list of orders that are candidates for the reminder email.
     * Returns an array with orders IDs.
     *
     * @return array
     */
    public function getOrdersForReminding()
    {
        $existingReminderOrderIds = $this->reminderCollectionFactory->create()
            ->getColumnValues(self::PARAM_NAME_ORDER_ID);

        $orders = $this->orderCollectionFactory->create()
                                               ->addFieldToFilter('state', ['in' => [Order::STATE_PENDING_PAYMENT]]);

        if (count($existingReminderOrderIds) > 0) {
            $orders->addFieldToFilter('increment_id', ['nin' => $existingReminderOrderIds]);
        }

        return $orders->getColumnValues('increment_id');
    }

    /**
     * Returns categories of the payment methods.
     *
     * @return array
     */
    public function getPaymentCategories()
    {
        return $this->coreLibrary->getPaymentCategories();
    }

    /**
     * Returns all payment methods with the indicated category.
     *
     * @param $category
     * @return array
     */
    public function getPaymentMethodsByCategory($category)
    {
        return $this->coreLibrary->getPaymentMethodsByCategory($category);
    }

    /**
     * Returns all supported countries with their popular payment methods mapped
     * Returns array like ['DE' => 'Germany']
     *
     * @return array
     */
    public function getAllCountries()
    {
        return $this->coreLibrary->getAllCountries();
    }

    /**
     * Returns all payment methods as PaymentMethod objects.
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        return $this->coreLibrary->getPaymentMethods();
    }

    /**
     * Get Unused Payment Methods (not selected ones).
     * Returns an array with PaymentMethod objects.
     * Used in the modal window in the plugin Settings in order to list Payment methods that are not yet added.
     *
     * @return array
     */
    public function getUnusedPaymentMethods()
    {
        return $this->coreLibrary->getUnusedPaymentMethods();
    }

    /**
     * Retrieves payment method by Brand value.
     *
     * @param $brand
     * @return PaymentMethod|false
     */
    public function getPaymentMethodByBrand($brand)
    {
        return $this->coreLibrary->getPaymentMethodByBrand($brand);
    }

    /**
     * Check whether an order with given ID is created in Magento
     *
     * @param $orderId
     * @return bool
     */
    public function isOrderCreated($orderId)
    {
        try {
            return (bool) $this->orderFactory->create()->loadByIncrementId($orderId)->getId();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retrieves iFrame URL.
     *
     * @param $reservedOrderId
     * @return string
     */
    public function getCcIframeUrlBeforePlaceOrder()
    {
        if ($this->request->getFullActionName() !== 'checkout_index_index') {
            return false;
        }

        $this->reserveOrderId();

        return $this->coreLibrary->getCcIFrameUrlBeforePlaceOrder(
            $this->checkoutSession->getQuote()->getReservedOrderId()
        );
    }

    /**
     * Delegates cron jobs handling to the CL.
     *
     * @return void
     */
    public function cronHandler()
    {
        $this->coreLibrary->cronHandler();
    }

    /**
     * Handles incoming requests from Ingenico.
     * Passes execution to CL.
     * From there it updates order's statuses.
     * This method must return HTTP status 200/400.
     *
     * @return void
     */
    public function webhookListener()
    {
        $this->coreLibrary->webhookListener();
    }

    /**
     * Empty Shopping Cart and reset session.
     *
     * @return void
     */
    public function emptyShoppingCart()
    {
        $this->checkoutSession->getQuote()->setIsActive(0)->save();
        $this->checkoutSession->setData('invalidate_cart', 1);
    }

    private function restoreCheckoutSessionLastRealOrderId($orderId = null)
    {
        if ($orderId && !$this->checkoutSession->getLastRealOrderId()) {
            $this->checkoutSession->setLastRealOrderId($orderId);
        }
    }
    /**
     * Restore Shopping Cart.
     */
    public function restoreShoppingCart($orderId = null)
    {
        if ($this->checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
            return;
        }

        $this->restoreCheckoutSessionLastRealOrderId($orderId);
        $this->checkoutSession->restoreQuote();
    }

    /**
     * Filters countries based on the search string.
     *
     * @param $query
     * @param $selected_countries array of selected countries iso codes
     * @return array
     */
    public function filterCountries($query, $selected_countries)
    {
        // do nothing
    }

    /**
     * Filters payment methods based on the search string.
     *
     * @param $query
     * @return array
     */
    public function filterPaymentMethods($query)
    {
        // do nothing
    }

    /**
     * Retrieve Missing or Invalid Order's fields.
     *
     * @param mixed $orderId
     * @param PaymentMethod $pm
     * @return array
     */
    public function retrieveMissingFields($orderId, \IngenicoClient\PaymentMethod\PaymentMethod $pm)
    {
        $result = $this->coreLibrary->getMissingOrderFields($orderId, $pm);

        return $result;
    }

    /**
     * Process POST request that came to Openinvoice Controller.
     *
     * @param $request
     */
    public function processOpenInvoiceFields(\Magento\Framework\App\RequestInterface $request)
    {
        // Build Alias with PaymentMethod and Brand
        /** @var \IngenicoClient\Alias $alias */
        $alias = (new \IngenicoClient\Alias())
            ->setIsPreventStoring(true)
            ->setPm($request->getParam('pm', null))
            ->setBrand($request->getParam('brand', null))
            ->setPaymentId($request->getParam('payment_id', null))
            ;

        $this->processOpenInvoicePayment($this->requestOrderId(), $alias, $request->getParams());
    }

    /**
     * Process OpenInvoice Payment.
     *
     * @param mixed $orderId
     * @param \IngenicoClient\Alias $alias
     * @param array $fields Form fields
     * @return void
     */
    public function processOpenInvoicePayment($orderId, \IngenicoClient\Alias $alias, array $fields = [])
    {
        // @see Connector::showPaymentListRedirectTemplate()
        // @see Connector::clarifyOpenInvoiceAdditionalFields()

        if (isset($fields[OrderField::CUSTOMER_DOB])) {
            $fields[OrderField::CUSTOMER_DOB] = (new \DateTime($fields[OrderField::CUSTOMER_DOB]))->getTimestamp();
        }

        $this->coreLibrary->initiateOpenInvoicePayment($orderId, $alias, $fields);
    }

    /**
     * Process if have invalid fields of OpenInvoice.
     *
     * @param $orderId
     * @param \IngenicoClient\Alias $alias
     * @param array $fields
     */
    public function processOpenInvoiceInvalidFields($orderId, \IngenicoClient\Alias $alias, array $fields)
    {
        foreach ($fields as $field) {
            if (!$field->getIsValid()) {
                $this->messageManager->addError(__('%1: %2', $field->getLabel(), $field->getValidationMessage()));
            }
        }
        $this->registry->register(self::REGISTRY_KEY_REDIRECT_TO_REFERER, 1);
    }

    /**
     * Process if have invalid fields of OpenInvoice.
     *
     * @param $orderId
     * @param \IngenicoClient\Alias $alias
     * @param array $fields
     */
    public function clarifyOpenInvoiceAdditionalFields($orderId, \IngenicoClient\Alias $alias, array $fields)
    {
        $this->log(sprintf('%s %s', __METHOD__, var_export($fields, true)), 'debug');

        foreach ($fields as $field) {
            /** @var \IngenicoClient\OrderField $field */
            if (!$field->getIsValid()) {
                $this->messageManager->addErrorMessage(__($field->getValidationMessage()));
            }
        }

        // Redirect
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $response = $this->responseFactory
            ->create()
            ->setHttpResponseCode(301)
            ->setRedirect($this->getUrl('ingenico/payment/inline'))
            ->sendResponse();

        $this->redirect->redirect($response, $this->getUrl('ingenico/payment/inline'));
    }

    /**
     * Get all Session values in a key => value format
     *
     * @return array
     */
    public function getSessionValues()
    {
        if ($fields = $this->customerSession->getCoreSessionStorage()) {
            return $fields;
        }

        return [];
    }

    /**
     * Get value from Session.
     *
     * @param string $key
     * @return mixed
     */
    public function getSessionValue($key)
    {
        $fields = $this->getSessionValues();
        return isset($fields[$key]) ? $fields[$key] : null;
    }

    /**
     * Store value in Session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSessionValue($key, $value)
    {
        $fields = $this->getSessionValues();
        $fields[$key] = $value;
        $this->customerSession->setCoreSessionStorage($fields);
    }

    /**
     * Remove value from Session.
     *
     * @param $key
     * @return void
     */
    public function unsetSessionValue($key)
    {
        $fields = $this->getSessionValues();
        unset($fields[$key]);
        $this->customerSession->setCoreSessionStorage($fields);
    }

    /**
     * Get Field Label
     *
     * @param string $field
     * @return string
     */
    public function getOrderFieldLabel($field)
    {
        // @todo Set labels for fields
        switch ($field) {
            case OrderField::CUSTOMER_DOB:
                return __('Date of Birth');
            default:
                return __(ucfirst(str_replace('_', ' ', $field)));
        }
    }

    /**
     * Renders the Ingenico Payment Page template that hosted on the Merchant.
     * This methods outputs HTML code.
     *
     * @param array $fields Additional variables used for dynamic rendering
     * @return void
     */
    public function showRedirectPaymentPageTemplate(array $fields = [])
    {
        // do nothing
    }

    /**
     * Returns URL of the Ingenico Payment Page template that hosted on the Merchant.
     *
     * @return string
     */
    public function getRedirectPaymentPageTemplateUrl()
    {
        // do nothing
    }

    /**
     * Class Logger.
     */
    public function log($str, $mode = 'info')
    {
        if (!$this->cnf->isLoggingEnabled()) {
            return null;
        }
        if (is_array($str) || is_object($str)) {
            $str = json_encode($str);
        }

        $this->logger->$mode($str);
    }

    /**
     * Get "Redirect" Payment Request with specified PaymentMethod and Brand.
     * @see \IngenicoClient\PaymentMethod\PaymentMethod
     *
     * @param mixed|null $aliasId
     * @param string $paymentMethod
     * @param string $brand
     * @param string|null $paymentId
     *
     * @return Data Data with url and fields keys
     * @throws Exception
     */
    public function getSpecifiedRedirectPaymentRequest($aliasId, $paymentMethod, $brand, $paymentId = null)
    {
        $orderId = $this->requestOrderId();

        if (!$paymentMethod || !$brand) {
            throw new LocalizedException(__('ingenico.exception.message1'));
        }

        return $this->getCoreLibrary()->getSpecifiedRedirectPaymentRequest(
            $orderId,
            $aliasId,
            $paymentMethod,
            $brand,
            $paymentId
        );
    }

    /**
     * Get Platform Environment.
     *
     * @return string
     */
    public function getPlatformEnvironment()
    {
        return \IngenicoClient\IngenicoCoreLibrary::PLATFORM_INGENICO;
    }


    /**
     * Get Store ID
     * @param null $orderId
     *
     * @return false|float|int|null
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreId($orderId = null)
    {
        if ($orderId) {
            $order = $this->processor->getOrderByIncrementId($orderId);
            return $order->getStoreId();
        } elseif ($orderId = $this->requestOrderId()) {
            if ($this->isOrderCreated($orderId)) {
                $order = $this->processor->getOrderByIncrementId($orderId);
                return $order->getStoreId();
            } else {
                return $this->getStoreIdBeforePlaceOrder();
            }
        }

        return $this->storeManager->getStore()->getId();
    }

    private function getUrl($path, $params = [])
    {
        $defaultParams = ['_nosid' => true, self::CNF_SCOPE_PARAM_NAME => $this->getStoreId()];
        $params = array_merge($defaultParams, $params);

        if ($params[self::CNF_SCOPE_PARAM_NAME] == 0) {
            unset($params[self::CNF_SCOPE_PARAM_NAME]);
            return $this->backendUrlBuilder->getUrl($path, $params);
        }

        return $this->urlBuilder->getUrl($path, $params);
    }


    private function setEmailTemplate($templateName)
    {
        $this->registry->unregister(self::PARAM_NAME_EMAIL_TEMPLATE);
        $this->registry->register(self::PARAM_NAME_EMAIL_TEMPLATE, $templateName);
    }

    private function getEmailTemplate()
    {
        if ($tpl = $this->registry->registry(self::PARAM_NAME_EMAIL_TEMPLATE)) {
            $this->registry->unregister(self::PARAM_NAME_EMAIL_TEMPLATE);
            return $tpl;
        }

        return 'ingenico_formatted';
    }


    private function _getStoreEmailLogo($storeId = 0)
    {
        if ($storeId) {
            $this->appEmulation->startEnvironmentEmulation(
                $storeId,
                \Magento\Framework\App\Area::AREA_FRONTEND,
                true
            );
        }

        $logoUrl = $this->cnf->getStoreEmailLogo($storeId);

        if ($storeId) {
            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $logoUrl;
    }

    private function reserveOrderId()
    {
        if ($this->checkoutSession->getQuote()->getReservedOrderId() === null || $this->isOrderCreated($this->checkoutSession->getQuote()->getReservedOrderId())) {
            $quote = $this->checkoutSession->getQuote()->reserveOrderId();
            $this->quoteRepository->save($quote);
        }
    }

    /**
     * Get Admin Locale by email.
     *
     * @param string $email
     *
     * @return string|false
     */
    private function getAdminUserLocale($email)
    {
        //UserCollectionFactory
        $collection = $this->userCollectionFactory->create();
        $collection->addFieldToFilter('main_table.email', $email);
        if ($collection->getSize() > 0) {
            $userData = $collection->getFirstItem();
            return $userData->getInterfaceLocale();
        }

        return false;
    }

    private function getStoreIdBeforePlaceOrder()
    {
        if ($this->request->getFullActionName() !== 'checkout_index_index') {
            return false;
        }

        $this->reserveOrderId();

        return $this->checkoutSession->getQuote()->getStoreId();
    }
}
