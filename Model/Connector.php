<?php

namespace Ingenico\Payment\Model;

use Magento\Sales\Model\Order\Payment\Transaction;
use IngenicoClient\Connector as AbstractConnector;
use IngenicoClient\IngenicoCoreLibrary;
use IngenicoClient\OrderItem;
use IngenicoClient\OrderField;

class Connector extends AbstractConnector implements \IngenicoClient\ConnectorInterface
{
    const REGISTRY_KEY_TEMPLATE_VARS_INLINE = 'ingenico_payment_inline_template_vars';
    const REGISTRY_KEY_TEMPLATE_VARS_REDIRECT = 'ingenico_payment_redirect_template_vars';
    const REGISTRY_KEY_TEMPLATE_VARS_ALIAS = 'ingenico_payment_alias_template_vars';
    const REGISTRY_KEY_INLINE_LOADER_PARAMS = 'ingenico_payment_inline_loader_params';
    const REGISTRY_KEY_REDIRECT_TO_REFERER = 'ingenico_payment_redirect_to_referer';

    const REGISTRY_KEY_REDIRECT_URL = 'ingenico_payment_redirect_url';
    const REGISTRY_KEY_ERROR_MESSAGE = 'ingenico_payment_error_message';

    const CNF_SCOPE = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
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

    protected $_logger;
    protected $_cnf;
    protected $_coreLibrary;
    protected $_storeManager;
    protected $_localeResolver;
    protected $_storeConfig;
    protected $_customerFactory;
    protected $_urlBuilder;
    protected $_backendUrlBuilder;
    protected $_checkoutSession;
    protected $_cart;
    protected $_customerSession;
    protected $_processor;
    protected $_transactionFactory;
    protected $_transactionCollectionFactory;
    protected $_aliasFactory;
    protected $_aliasCollectionFactory;
    protected $_reminderFactory;
    protected $_reminderCollectionFactory;
    protected $_transportBuilder;
    protected $_inlineTranslation;
    protected $_registry;
    protected $_productImageHelper;
    protected $_productFactory;
    protected $_priceHelper;
    protected $_appEmulation;
    protected $_messageManager;
    protected $_orderCollectionFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    protected $_orderId = null;
    protected $_customerId = null;

    public function __construct(
        \Ingenico\Payment\Logger\Main $logger,
        \Ingenico\Payment\Model\Config $cnf,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Store\Api\Data\StoreConfigInterface $storeConfig,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Backend\Model\UrlInterface $backendUrlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Customer\Model\Session $customerSession,
        \Ingenico\Payment\Model\Processor $processor,
        \Ingenico\Payment\Model\TransactionFactory $transactionFactory,
        \Ingenico\Payment\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory,
        \Ingenico\Payment\Model\AliasFactory $aliasFactory,
        \Ingenico\Payment\Model\ResourceModel\Alias\CollectionFactory $aliasCollectionFactory,
        \Ingenico\Payment\Model\ReminderFactory $reminderFactory,
        \Ingenico\Payment\Model\ResourceModel\Reminder\CollectionFactory $reminderCollectionFactory,
        \Ingenico\Payment\Model\Email\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Image $productImageHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->_logger = $logger;
        $this->_cnf = $cnf;
        $this->_storeManager = $storeManager;
        $this->_localeResolver = $localeResolver;
        $this->_storeConfig = $storeConfig;
        $this->_customerFactory = $customerFactory;
        $this->_urlBuilder = $urlBuilder;
        $this->_backendUrlBuilder = $backendUrlBuilder;
        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->_customerSession = $customerSession;
        $this->_processor = $processor;
        $this->_transactionFactory = $transactionFactory;
        $this->_transactionCollectionFactory = $transactionCollectionFactory;
        $this->_aliasFactory = $aliasFactory;
        $this->_aliasCollectionFactory = $aliasCollectionFactory;
        $this->_reminderFactory = $reminderFactory;
        $this->_reminderCollectionFactory = $reminderCollectionFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_registry = $registry;
        $this->_productImageHelper = $productImageHelper;
        $this->_productFactory = $productFactory;
        $this->_priceHelper = $priceHelper;
        $this->_appEmulation = $appEmulation;
        $this->_messageManager = $messageManager;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderFactory = $orderFactory;

        $this->_processor->setConnector($this);
        $this->_coreLibrary = new \IngenicoClient\IngenicoCoreLibrary($this);
        $this->_coreLibrary->setLogger($this->_logger);
    }

    public function getCoreLibrary()
    {
        return $this->_coreLibrary;
    }

    public function getProcessor()
    {
        return $this->_processor;
    }

    public function getStoreId()
    {
        if ($orderId = $this->requestOrderId()) {
            $order = $this->_processor->getOrderByIncrementId($orderId);
            return $order->getStoreId();
        }

        return $this->_storeManager->getStore()->getId();
    }

    public function getUrl($path, $params = [])
    {
        $defaultParams = ['_nosid' => true, self::CNF_SCOPE_PARAM_NAME => $this->getStoreId()];
        $params = array_merge($defaultParams, $params);

        if ($params[self::CNF_SCOPE_PARAM_NAME] == 0) {
            unset($params[self::CNF_SCOPE_PARAM_NAME]);
            return $this->_backendUrlBuilder->getUrl($path, $params);
        }

        return $this->_urlBuilder->getUrl($path, $params);
    }

    /**
     * Returns Shopping Cart Extension Id.
     *
     * @return string
     */
    public function requestShoppingCartExtensionId()
    {
        return 'MG2V200';
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
        return $this->_cnf->getMode();
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
        $mode = $this->_cnf->getMode();

        $local = [
            //connection
            'connection_mode' => $this->_cnf->getMode(true),
            'connection_test_pspid' => $this->_cnf->getConnectionPspid('test'),
            'connection_test_signature' => $this->_cnf->getConnectionSignature('test'),
            'connection_test_dl_user' => $this->_cnf->getConnectionUser('test'),
            'connection_test_dl_password' => $this->_cnf->getConnectionPassword('test'),
            'connection_test_dl_timeout' => $this->_cnf->getConnectionTimeout('test'),
            'connection_test_webhook' => $this->getUrl('ingenico/payment/webhook'),

            'connection_live_pspid' => $this->_cnf->getConnectionPspid('live'),
            'connection_live_signature' => $this->_cnf->getConnectionSignature('live'),
            'connection_live_dl_user' => $this->_cnf->getConnectionUser('live'),
            'connection_live_dl_password' => $this->_cnf->getConnectionPassword('live'),
            'connection_live_dl_timeout' => $this->_cnf->getConnectionTimeout('live'),
            'connection_live_webhook' => $this->getUrl('ingenico/payment/webhook'),

            // settings general
            'settings_advanced' => $this->_cnf->getIsAdvancedSettingsMode(),

            // settings tokenisation
            'settings_tokenisation' => $this->_cnf->getValue('ingenico_settings/tokenization/enabled', self::CNF_SCOPE),
            'settings_oneclick' => $this->_cnf->getValue('ingenico_settings/tokenization/stored_cards_enabled', self::CNF_SCOPE),
            'settings_skip3dscvc' => $this->_cnf->getValue('ingenico_settings/tokenization/skip_security_check', self::CNF_SCOPE),
            'settings_skipsecuritycheck' => $this->_cnf->getValue('ingenico_settings/tokenization/skip_security_check', self::CNF_SCOPE),

            'settings_directsales' => $this->_cnf->getValue('ingenico_settings/tokenization/direct_sales', self::CNF_SCOPE),
            'direct_sale_email_option' => $this->_cnf->getValue('ingenico_settings/tokenization/capture_request_notify', self::CNF_SCOPE),
            'direct_sale_email' => $this->_cnf->getValue('ingenico_settings/tokenization/capture_request_email', self::CNF_SCOPE),

            // settings orders
            'settings_reminderemail' => $this->_cnf->getValue('ingenico_settings/orders/payment_reminder_email_send', self::CNF_SCOPE),
            'settings_reminderemail_days' => $this->_cnf->getValue('ingenico_settings/orders/payment_reminder_email_timeout', self::CNF_SCOPE),

            // payment page
            'paymentpage_type' => $this->_cnf->getValue('ingenico_payment_page/presentation/mode', self::CNF_SCOPE),
            'paymentpage_template' => $this->_cnf->getValue('ingenico_payment_page/custom_template/template_source', self::CNF_SCOPE),
            'paymentpage_template_name' => $this->_cnf->getValue('ingenico_payment_page/custom_template/ingenico_template_name', self::CNF_SCOPE),
            'paymentpage_template_externalurl' => $this->_cnf->getValue('ingenico_payment_page/custom_template/remote', self::CNF_SCOPE),
            'paymentpage_template_localfilename' => $this->_cnf->getValue('ingenico_payment_page/custom_template/local', self::CNF_SCOPE),

            // installments
            'instalments_enabled' => $this->_cnf->getValue('ingenico_instalments/general/enabled', self::CNF_SCOPE),
            'instalments_type' => $this->_cnf->getValue('ingenico_instalments/general/rules', self::CNF_SCOPE),
            'instalments_fixed_instalments' => $this->_cnf->getValue('ingenico_instalments/general/count_fixed', self::CNF_SCOPE),
            'instalments_fixed_period' => $this->_cnf->getValue('ingenico_instalments/general/interval_fixed', self::CNF_SCOPE),
            'instalments_fixed_firstpayment' => $this->_cnf->getValue('ingenico_instalments/general/downpayment_fixed', self::CNF_SCOPE),
            'instalments_fixed_minpayment' => $this->_cnf->getValue('ingenico_instalments/general/minpayment', self::CNF_SCOPE),

            'instalments_flex_instalments_min' => $this->_cnf->getMinValue('ingenico_instalments/general/count_flexible', self::CNF_SCOPE),
            'instalments_flex_instalments_max' => $this->_cnf->getMaxValue('ingenico_instalments/general/count_flexible', self::CNF_SCOPE),
            'instalments_flex_period_min' => $this->_cnf->getMinValue('ingenico_instalments/general/interval_flexible', self::CNF_SCOPE),
            'instalments_flex_period_max' => $this->_cnf->getMaxValue('ingenico_instalments/general/interval_flexible', self::CNF_SCOPE),
            'instalments_flex_firstpayment_min' => $this->_cnf->getMinValue('ingenico_instalments/general/downpayment_flexible', self::CNF_SCOPE),
            'instalments_flex_firstpayment_max' => $this->_cnf->getMaxValue('ingenico_instalments/general/downpayment_flexible', self::CNF_SCOPE),

            // payment methods
            'selected_payment_methods' => $this->_cnf->getActivePaymentMethods()
        ];

        foreach ($local as $key => $val) {
            if ($val !== null) {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    public function setOrderId($orderId)
    {
        $this->_orderId = $orderId;
        return $this;
    }

    /**
     * Retrieves orderId from checkout session.
     *
     * @return mixed
     */
    public function requestOrderId()
    {
        if (!$this->_orderId) {
            if ($reminderOrderId = $this->_checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
                $this->_orderId = $reminderOrderId;
            }
            if ($sessOrderId = $this->_checkoutSession->getData('last_real_order_id')) {
                $this->_orderId = $sessOrderId;
            }
        }

        return $this->_orderId;
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
        if (!$this->_customerId) {
            if ($orderId = $this->requestOrderId()) {
                $order = $this->_processor->getOrderByIncrementId($orderId);
                if ($orderCustomerId = $order->getCustomerId()) {
                    $this->_customerId = $orderCustomerId;
                }
            } elseif ($sessCustomerId = $this->_customerSession->getId()) {
                $this->_customerId = $sessCustomerId;
            }
        }

        return $this->_customerId;
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
            case $this->_coreLibrary::CONTROLLER_TYPE_PAYMENT:
                return $this->getUrl('ingenico/payment/redirect', ['_query' => $params]);
            case $this->_coreLibrary::CONTROLLER_TYPE_SUCCESS:
                return $this->getUrl('ingenico/payment/result', ['_query' => $params]);
            case $this->_coreLibrary::CONTROLLER_TYPE_ORDER_SUCCESS:
                return $this->getUrl('checkout/onepage/success');
            case $this->_coreLibrary::CONTROLLER_TYPE_ORDER_CANCELLED:
                return $this->getUrl(self::PARAM_NAME_CHECKOUT_CART);
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Unknown page type.'));
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
            $this->_coreLibrary->processReturnUrls();
        } catch (\IngenicoClient\Exception $e) {
            $this->setOrderErrorPage($e->getMessage());
        }
    }

    /**
     * Executed on the moment when a buyer submits checkout form with an intention to start the payment process.
     * Depending on the payment mode (Inline vs. Redirect) CL will initiate the right processes and render the corresponding GUI.
     *
     * @return void
     */
    public function processPayment($aliasId = null, $forceAliasSave = false)
    {
        $orderId = $this->requestOrderId();

        try {
            $this->_coreLibrary->processPayment($orderId, $aliasId, $forceAliasSave);
        } catch (\IngenicoClient\Exception $e) {
            // Show Error Page
            $this->setOrderErrorPage($e->getMessage());
        }
    }

    /**
     * Matches Ingenico payment statuses to the platform's order statuses.
     *
     * @param mixed $orderId
     * @param string $paymentStatus
     * @param string|null $message
     * @return void
     */
    public function updateOrderStatus($orderId, $paymentStatus, $message = null)
    {
        switch ($paymentStatus) {
            case $this->_coreLibrary::STATUS_PENDING:
                break;
            case $this->_coreLibrary::STATUS_AUTHORIZED:
                $this->_processor->processOrderAuthorization($orderId, $message);
                if (!$this->_cnf->isDirectSalesMode() && $this->_cnf->getMode() == 'test') {
                    $this->_messageManager->addNotice(__('checkout.test_mode_warning').' '.__('checkout.manual_capture_required'));
                }
                break;

            case $this->_coreLibrary::STATUS_CAPTURED:
                $this->_processor->processOrderPayment($orderId, $message);
                break;

            case $this->_coreLibrary::STATUS_CANCELLED:
                $this->_processor->processOrderCancellation($orderId, $message);
                break;

            case $this->_coreLibrary::STATUS_REFUNDED:
                break;
            case $this->_coreLibrary::STATUS_ERROR:
                break;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Unknown Payment Status'));
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
        $order = $this->_processor->getOrderByIncrementId($orderId);
        $orders = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('quote_id', $order->getQuoteId());

        foreach ($orders as $order) {
            /** @var \Magento\Sales\Model\Order $order */
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
        $this->_coreLibrary->submitOnboardingRequest(
            $companyName,
            $email,
            $countryCode,
            'Magento 2',
            $this->requestShoppingCartExtensionId(),
            $this->_cnf->getStoreName(),
            $this->_getStoreEmailLogo(),
            $this->getUrl('/'),
            $this->_cnf->getIngenicoLogo(),
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
        $order = $this->_processor->getOrderByIncrementId($orderId);

        $currentState = $order->getState();

        // Mapping between Magento Order status and Ingenico Payment status
        switch ($currentState) {
            case $order::STATE_NEW:
                $status = $this->_coreLibrary::STATUS_PENDING;
                break;
            case $order::STATE_PENDING_PAYMENT:
                $status = $this->_coreLibrary::STATUS_AUTHORIZED;
                break;
            case $order::STATE_PROCESSING:
                $status = $this->_coreLibrary::STATUS_CAPTURED;
                break;
            case $order::STATE_CANCELED:
                $status = $this->_coreLibrary::STATUS_CANCELLED;
                break;
            case $order::STATE_CLOSED:
                $status = $this->_coreLibrary::STATUS_REFUNDED;
                break;
            default:
                $status = $this->_coreLibrary::STATUS_UNKNOWN;
                break;
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
                'type' => OrderItem::TYPE_PRODUCT,
                'id' => $item->getSku(),
                'name' => $item->getName(),
                'description' => $item->getName(),
                'unit_price' => round($item->getRowTotalInclTax() / $item->getQtyOrdered(), 2),
                'qty' => $item->getQtyOrdered(),
                'unit_vat' => round(($item->getRowTotalInclTax() - $item->getRowTotal()) / $item->getQtyOrdered(), 2),
                'vat_percent' => $taxPercent,
                'vat_included' => 1 // VAT included
            ];
        }

        // Add Discount Order line
        if ((float)$order->getData('discount_amount') > 0 || (float)$order->getData('shipping_discount_amount') > 0) {
            $taxPercent = 0;

            $totalDiscount = $order->getData('discount_amount') + $order->getData('shipping_discount_amount');

            $items[] = [
                'type' => OrderItem::TYPE_DISCOUNT,
                'id' => 'discount',
                'name' => 'Discount',
                'description' => 'Discount',
                'unit_price' => -1 * $totalDiscount,
                'qty' => 1,
                'unit_vat' => 0,
                'vat_percent' => 0,
                'vat_included' => 1 // VAT included
            ];
        }

        // Add Shipping Order Line
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
                'type' => OrderItem::TYPE_SHIPPING,
                'id' => 'shipping',
                'name' => $order->getShippingDescription(),
                'description' => $order->getShippingDescription(),
                'unit_price' => $shippingIncTax,
                'qty' => 1,
                'unit_vat' => $shippingTax,
                'vat_percent' => $shippingTaxRate,
                'vat_included' => 1 // VAT included
            ];
        }

        return [
            self::PARAM_NAME_ORDER_ID => $this->requestOrderId(),
            self::PARAM_NAME_PAY_ID => $this->getIngenicoPayIdByOrderId($this->requestOrderId()),
            'amount' => $totalAmount,
            'total_captured' => $capturedAmount,
            'total_refunded' => $refundedAmount,
            'total_cancelled' => $cancelledAmount,
            'currency' => $order->getData('order_currency_code'),
            'customer_id' => (int) $customerId,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s', strtotime($order->getData('created_at'))), // Y-m-d H:i:s
            'billing_country' => $billingAddress->getCountryId(),
            'billing_country_code' => $billingAddress->getCountryId(),
            'billing_address1' => $billingAddress->getStreet()[0],
            'billing_address2' => count($billingAddress->getStreet()) > 1 ? $billingAddress->getStreet()[1] : '.',
            'billing_address3' => null,
            'billing_city' => $billingAddress->getCity(),
            'billing_state' => $billingAddress->getRegionId(),
            'billing_postcode' => $billingAddress->getPostcode(),
            'billing_phone' => $billingAddress->getTelephone(),
            'billing_email' => $order->getData('customer_email'),
            'billing_first_name' => $billingAddress->getFirstname(),
            'billing_last_name' => $billingAddress->getLastname(),
            'is_shipping_same' => false,
            'shipping_country' => $shippingAddress->getCountryId(),
            'shipping_country_code' => $shippingAddress->getCountryId(),
            'shipping_address1' => $shippingAddress->getStreet()[0],
            'shipping_address2' => count($shippingAddress->getStreet()) > 1 ? $shippingAddress->getStreet()[1] : '.',
            'shipping_address3' => null,
            'shipping_city' => $shippingAddress->getCity(),
            'shipping_state' => $shippingAddress->getRegionId(),
            'shipping_postcode' => $shippingAddress->getPostcode(),
            'shipping_phone' => $shippingAddress->getTelephone(),
            'shipping_email' => $order->getData('customer_email'),
            'shipping_first_name' => $shippingAddress->getFirstname(),
            'shipping_last_name' => $shippingAddress->getLastname(),
            'shipping_company' => $order->getData('shipping_description'),
            'shipping_method' => $order->getData('shipping_description'),
            'shipping_amount' => $order->getData('shipping_amount'),
            'shipping_tax_amount' => $order->getData('shipping_tax_amount'),
            'shipping_tax_code' => 0,
            'shipping_fax' => '-',
            'customer_ip' => $order->getData('remote_ip'),
            'customer_dob' => null,
            'customer_civility' => '',
            'items' => $items,
            'locale' => $this->getLocale($orderId)
        ];
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

    public function setEmailTemplate($templateName)
    {
        $this->_registry->unregister(self::PARAM_NAME_EMAIL_TEMPLATE);
        $this->_registry->register(self::PARAM_NAME_EMAIL_TEMPLATE, $templateName);
    }

    public function getEmailTemplate()
    {
        if ($tpl = $this->_registry->registry(self::PARAM_NAME_EMAIL_TEMPLATE)) {
            $this->_registry->unregister(self::PARAM_NAME_EMAIL_TEMPLATE);
            return $tpl;
        }

        return 'ingenico_formatted';
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
            throw new \Magento\Framework\Exception\LocalizedException(__('Template variable must be instance of MailTemplate'));
        }

        try {
            $this->_inlineTranslation->suspend();
            $emailData = new \Magento\Framework\DataObject();
            $emailData->setData([
                'subject' => $subject,
                'bodyhtml' => $template->getHtml($returnFullTemplate = false),
                'bodyhtmlfull' => $template->getHtml(),
                'bodytext' => $template->getPlainText(),
            ]);

            $sender = $this->_cnf->getValue(
                \Magento\Sales\Model\Order\Email\Container\OrderIdentity::XML_PATH_EMAIL_IDENTITY,
                self::CNF_SCOPE,
                $this->getStoreId()
            );

            $transport = $this->_transportBuilder
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
                $transport->addAttachment($attachedFile['content'], $attachedFile['name'], $attachedFile['mime']);
            }

            $transport->getTransport()->sendMessage();
            $this->_inlineTranslation->resume();
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
        $currentStoreId = $this->_storeManager->getStore()->getId();
        $locale = $this->_localeResolver->getLocale();

        if ($orderId) {
            $order = $this->_processor->getOrderByIncrementId($orderId);
            $orderStoreId = $order->getStoreId();
            if ($currentStoreId !== $orderStoreId) {
                $locale = $this->_localeResolver->emulate($orderStoreId);
                $this->_localeResolver->revert();
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
        $order = $this->_processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);

        try {
            return $this->_coreLibrary->sendMailNotificationPaidOrder(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->_coreLibrary->__('order_paid.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    self::PARAM_NAME_SHOP_NAME => $this->_cnf->getStoreName(),
                    self::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    self::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    self::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    self::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    self::PARAM_NAME_ORDER_URL => $this->getUrl(self::PARAM_NAME_SALES_ORDER_VIEW, [self::PARAM_NAME_ORDER_ID => $order->getId()])
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }
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
        $order = $this->_processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);

        try {
            return $this->_coreLibrary->sendMailNotificationAuthorization(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->_coreLibrary->__('authorization.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    self::PARAM_NAME_SHOP_NAME => $this->_cnf->getStoreName(),
                    self::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    self::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    self::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    self::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    self::PARAM_NAME_ORDER_URL => $this->getUrl(self::PARAM_NAME_SALES_ORDER_VIEW, [self::PARAM_NAME_ORDER_ID => $order->getId()])
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }
    }

    /**
     * Send "Payment Authorized" email to the merchant.
     *
     * @param $orderId
     * @return bool
     */
    public function sendNotificationAdminAuthorization($orderId)
    {
        $order = $this->_processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);
        $recipient = $this->_cnf->getPaymentAuthorisationNotificationEmail();
        if (!$recipient) {
            $recipient = $this->_cnf->getValue('trans_email/ident_general/email', self::CNF_SCOPE);
        }

        $this->_appEmulation->startEnvironmentEmulation($order->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        try {
            $this->setEmailTemplate('ingenico_empty');
            return $this->_coreLibrary->sendMailNotificationAdminAuthorization(
                $recipient,
                null,
                null,
                null,
                $this->_coreLibrary->__('admin_authorization.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    self::PARAM_NAME_SHOP_NAME => $this->_cnf->getStoreName(),
                    self::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    self::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    self::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    self::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    'order_view_url' => $this->getUrl(self::PARAM_NAME_SALES_ORDER_VIEW, [self::PARAM_NAME_ORDER_ID => $order->getId(), self::CNF_SCOPE_PARAM_NAME => 0]),
                    'path_uri' => '',
                    'ingenico_logo' => $this->_cnf->getIngenicoLogo()
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }
        $this->_appEmulation->stopEnvironmentEmulation();
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
            $reminder = $this->_reminderFactory->create()->load($orderId, self::PARAM_NAME_ORDER_ID);
            if (!$reminder->getId() || $reminder->getIsSent()) {
                return null;
            }

            $this->setOrderId($orderId);

            $order = $this->_processor->getOrderByIncrementId($orderId);
            $this->_appEmulation->startEnvironmentEmulation($order->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);

            // Get products
            $products = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $product = $this->_productFactory->create()->load($item->getProductId());
                if (!$product->getId()) {
                    continue;
                }
                $imageUrl = $this->_productImageHelper->init($product, 'product_small_image')->getUrl();
                $products[] = [
                    'image' => $imageUrl,
                    'name' => $item->getData('name') . ' ('.$item->getData('sku').')',
                    'price' => $this->_priceHelper->currency($product->getFinalPrice(), true, false)
                ];
            }
            $this->_appEmulation->stopEnvironmentEmulation();

            // Get Customer's locale
            $locale = $this->getLocale($orderId);

            return $this->_coreLibrary->sendMailNotificationReminder(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->_coreLibrary->__('reminder.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    AbstractConnector::PARAM_NAME_SHOP_NAME => $this->_cnf->getStoreName($this->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_LOGO => '',//$this->_getStoreEmailLogo($order->getStoreId()),
                    AbstractConnector::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    AbstractConnector::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    AbstractConnector::PARAM_NAME_PRODUCTS => $products,
                    AbstractConnector::PARAM_NAME_ORDER_TOTAL => $this->_priceHelper->currency($order->getGrandTotal(), true, false),
                    AbstractConnector::PARAM_NAME_PAYMENT_LINK => $this->getUrl('ingenico/payment/resume', [
                        'token' => $reminder->getSecureToken(),
                        self::CNF_SCOPE_PARAM_NAME => $order->getStoreId()
                    ])
                ],
                $locale
            );
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
        $order = $this->_processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);

        try {
            return $this->coreLibrary->sendMailNotificationRefundFailed(
                $order->getCustomerEmail(),
                null,
                null,
                null,
                $this->coreLibrary->__('refund_failed.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    self::PARAM_NAME_SHOP_NAME => $this->_cnf->getStoreName(),
                    self::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    self::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    self::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    self::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    self::PARAM_NAME_ORDER_URL => $this->getUrl(self::PARAM_NAME_SALES_ORDER_VIEW, [self::PARAM_NAME_ORDER_ID => $order->getId()])
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }
    }

    /**
     * Send "Refund failed" email to the merchant.
     *
     * @param $orderId
     * @return bool
     */
    public function sendRefundFailedAdminEmail($orderId)
    {
        $order = $this->_processor->getOrderByIncrementId($orderId);
        $locale = $this->getLocale($orderId);
        $recipient = $this->_cnf->getValue('trans_email/ident_sales/email', self::CNF_SCOPE);

        try {
            $this->setEmailTemplate('ingenico_empty');
            return $this->coreLibrary->sendMailNotificationAdminRefundFailed(
                $recipient,
                $this->_cnf->getStoreName(),
                null,
                null,
                $this->coreLibrary->__('admin_refund_failed.subject', [], self::PARAM_NAME_EMAIL, $locale),
                [
                    self::PARAM_NAME_SHOP_NAME => $this->_cnf->getStoreName(),
                    self::PARAM_NAME_SHOP_LOGO => '', //$this->_getStoreEmailLogo($order->getStoreId()),
                    self::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                    self::PARAM_NAME_CUSTOMER_NAME => $order->getCustomerName(),
                    self::PARAM_NAME_ORDER_REFERENCE => $orderId,
                    'path_uri' => '',
                    'ingenico_logo' => $this->_cnf->getIngenicoLogo()
                ],
                $locale
            );
        } catch (\Exception $e) {
            $this->log(self::PARAM_NAME_MAIL_SENDING_FAILED . $e->getMessage(), 'crit');
        }

        return true;
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
                'platform' => $this->requestShoppingCartExtensionId(),
                self::PARAM_NAME_SHOP_URL => $this->getUrl(''),
                self::PARAM_NAME_SHOP_NAME => $this->_cnf->getStoreName(),
                'ticket' => '',
                'description' => ''
            ],
            $fields
        );

        // Send E-mail
        return $this->getCoreLibrary()->sendMailSupport(
            'support@ecom.ingenico.com',
            'Ingenico Support',
            $email,
            $this->_cnf->getStoreName(),
            $subject,
            $fields,
            $this->getLocale(),
            $attachedFiles
        );
    }

    protected function _getStoreEmailLogo($storeId = 0)
    {
        if ($storeId) {
            $this->_appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        }

        $logoUrl = $this->_cnf->getStoreEmailLogo($storeId);

        if ($storeId) {
            $this->_appEmulation->stopEnvironmentEmulation();
        }

        return $logoUrl;
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

        $collection = $this->_transactionCollectionFactory
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
                $trx = $this->_transactionFactory->create();
                $trx->setCreatedAt(date('Y-m-d H:i:s', time()))
                    ->setUpdatedAt(date('Y-m-d H:i:s', time()))
                    ->addData($trxData)
                    ->save();
            }
        } catch (\Exception $e) {
            $this->_logger->crit('Failed saving payment transaction: ' . $e->getMessage());
        }

        // Add Magento Transaction
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        if (!$order->getId()) {
            throw new \Exception('Order doesn\'t exists in store');
        }

        // Register Transaction
        $order->getPayment()->setTransactionId($data->getPayId() . '-' . $data->getPayIdSub());

        /** @var \Magento\Sales\Model\Order\Payment\Transaction $transaction */
        $transaction = $order->getPayment()->addTransaction(Transaction::TYPE_PAYMENT, null, true);

        $transaction->setIsClosed(0)
            ->setAdditionalInformation(Transaction::RAW_DETAILS, $data->getData())
            ->save();

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
        $collection = $this->_transactionCollectionFactory
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
        $collection = $this->_transactionCollectionFactory
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
        $collection = $this->_transactionCollectionFactory
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
        $aliasColl = $this->_aliasCollectionFactory->create()
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
        $alias = $this->_aliasFactory->create()->load($aliasId, self::PARAM_NAME_ALIAS);
        if ($alias->getId()) {
            $alias->unsId();
            $alias->setAliasId($alias->getAlias());
            return $alias->getData();
        }

        return [];
    }

    /**
     * Saves the buyer (customer) Alias entity.
     * Important fields that are provided by Ingenico: ALIAS, BRAND, CARDNO, BIN, PM, ED.
     *
     * @param int $customerId
     * @param array $data
     * @return bool
     */
    public function saveAlias($customerId, array $data)
    {
        $data['customer_id'] = $customerId;
        foreach ($data as $key => $val) {
            $newKey = strtolower($key);
            unset($data[$key]);
            $data[$newKey] = $val;
        }
        $alias = $this->_aliasFactory->create()->load($data[self::PARAM_NAME_ALIAS], self::PARAM_NAME_ALIAS);
        try {
            $alias->addData($data)->save();
        } catch (\Exception $e) {
            $this->_logger->crit('Failed saving alias: '.$e->getMessage());
        }
    }

    /**
     * Renders the template of the payment success page.
     *
     * @param array $fields
     * @param Payment $payment
     *
     * @return void
     */
    public function showSuccessTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        $this->emptyShoppingCart();
        $this->_registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl('checkout/onepage/success'));
    }

    /**
     * Renders the template with 3Ds Security Check.
     *
     * @param array $fields
     * @param Payment $payment
     *
     * @return void
     */
    public function showSecurityCheckTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        // Render $fields['html']
        $this->_registry->register(self::REGISTRY_KEY_TEMPLATE_VARS_ALIAS, $fields);
    }

    /**
     * Renders the template with the order cancellation.
     *
     * @param array $fields
     * @param Payment $payment
     *
     * @return void
     */
    public function showCancellationTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        $this->_messageManager->addError(__('checkout.payment_cancelled'));
        if ($this->_checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
            $this->_registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl('/'));
        } else {
            $this->restoreShoppingCart();
            $this->_processor->processOrderCancellation($fields[self::PARAM_NAME_ORDER_ID]);
            $this->_registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl(self::PARAM_NAME_CHECKOUT_CART));
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
        $this->_registry->register(self::REGISTRY_KEY_INLINE_LOADER_PARAMS, $fields);
    }

    public function finishReturnInline($orderId, $cardBrand, $aliasId)
    {
        try {
            $result = $this->_coreLibrary->finishReturnInline($orderId, $cardBrand, $aliasId);
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
        throw new \Magento\Framework\Exception\LocalizedException(__($message));
    }

    /**
     * Renders the template with the payment error.
     *
     * @param array $fields
     * @param Payment $payment
     *
     * @return void
     */
    public function showPaymentErrorTemplate(array $fields, \IngenicoClient\Payment $payment)
    {
        if (!$this->_checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
            $this->_registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl('/'));
        } else {
            $this->restoreShoppingCart();
            $this->_processor->processOrderCancellation($fields[self::PARAM_NAME_ORDER_ID]);
            $this->_registry->register(self::REGISTRY_KEY_REDIRECT_URL, $this->getUrl(self::PARAM_NAME_CHECKOUT_CART));
        }
        $message = 'ingenico.exception.message4';
        if (isset($fields[self::PARAM_NAME_MESSAGE]) && $fields[self::PARAM_NAME_MESSAGE] !== '') {
            $message = $fields[self::PARAM_NAME_MESSAGE];
        }
        throw new \Magento\Framework\Exception\LocalizedException(__($message));
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
        $this->_registry->register(self::REGISTRY_KEY_TEMPLATE_VARS_REDIRECT, $fields);
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
        $this->_registry->register(self::REGISTRY_KEY_TEMPLATE_VARS_INLINE, $fields);
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
        $coll = $this->_reminderCollectionFactory->create()->addFieldToFilter('is_sent', 0);
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
        $this->_reminderFactory->create()->markAsSent($orderId);
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
            $order = $this->_processor->getOrderByIncrementId($orderId);
            $this->_reminderFactory->create()->register($order);
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
        $existingReminderOrderIds = $this->_reminderCollectionFactory->create()->getColumnValues(self::PARAM_NAME_ORDER_ID);
        $orders = $this->_orderCollectionFactory->create()
            ->addFieldToFilter('state', ['in' => [\Magento\Sales\Model\Order::STATE_NEW]]);
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
        return $this->_coreLibrary->getPaymentCategories();
    }

    /**
     * Returns all payment methods with the indicated category.
     *
     * @param $category
     * @return array
     */
    public function getPaymentMethodsByCategory($category)
    {
        return $this->_coreLibrary->getPaymentMethodsByCategory($category);
    }

    /**
     * Returns all supported countries with their popular payment methods mapped
     * Returns array like ['DE' => 'Germany']
     *
     * @return array
     */
    public function getAllCountries()
    {
        return $this->_coreLibrary->getAllCountries();
    }

    /**
     * Returns all payment methods as PaymentMethod objects.
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        return $this->_coreLibrary->getPaymentMethods();
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
        return $this->_coreLibrary->getUnusedPaymentMethods();
    }

    /**
     * Retrieves payment method by Brand value.
     *
     * @param $brand
     * @return PaymentMethod|false
     */
    public function getPaymentMethodByBrand($brand)
    {
        return $this->_coreLibrary->getPaymentMethodByBrand($brand);
    }

    /**
     * Delegates cron jobs handling to the CL.
     *
     * @return void
     */
    public function cronHandler()
    {
        $this->_coreLibrary->cronHandler();
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
        $this->_coreLibrary->webhookListener();
    }

    /**
     * Empty Shopping Cart and reset session.
     *
     * @return void
     */
    public function emptyShoppingCart()
    {
        $this->_checkoutSession->getQuote()->setIsActive(0)->save();
        $this->_checkoutSession->setData('invalidate_cart', 1);
    }

    /**
     * Restore Shopping Cart.
     */
    public function restoreShoppingCart()
    {
        if ($this->_checkoutSession->getData(self::PARAM_NAME_REMINDER_ORDER_ID)) {
            return;
        }
        $this->_checkoutSession->restoreQuote();
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
        $result = $this->_coreLibrary->getMissingOrderFields($orderId, $pm);
        foreach ($result as $key => $field) {
            // @todo Set labels for fields
            /** @var \IngenicoClient\OrderField $field */
            $label = ucfirst(str_replace('_', ' ', $field->getFieldName()));

            $result[$key]->setLabel($label);
        }

        return $result;
    }

    /**
     * Process POST request that came to Openinvoice Controller.
     *
     * @param $request
     */
    public function processOpenInvoiceFields(\Magento\Framework\App\Request\Http $request)
    {
        // Build Alias with PaymentMethod and Brand
        /** @var \IngenicoClient\Alias $alias */
        $alias = (new \IngenicoClient\Alias())
            ->setIsPreventStoring(true)
            ->setPm($request->getParam('pm', null))
            ->setBrand($request->getParam('brand', null))
            ->setPaymentId($request->getParam('payment_id', null))
            ;

        return $this->processOpenInvoicePayment($this->requestOrderId(), $alias, $request->getParams());
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
        $this->_coreLibrary->initiateOpenInvoicePayment($orderId, $alias, $fields);
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
                $this->_messageManager->addError(__('%1: %2', $field->getLabel(), $field->getValidationMessage()));
            }
        }
        $this->_registry->register(self::REGISTRY_KEY_REDIRECT_TO_REFERER, 1);
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
        return $this->processPayment();
    }

    /**
     * Get all Session values in a key => value format
     *
     * @return array
     */
    public function getSessionValues()
    {
        if ($fields = $this->_checkoutSession->getData(self::PARAM_NAME_OPEN_INVOICE_FIELDS)) {
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
        $this->_checkoutSession->setData(self::PARAM_NAME_OPEN_INVOICE_FIELDS, $fields);
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
        $this->_checkoutSession->setData(self::PARAM_NAME_OPEN_INVOICE_FIELDS, $fields);
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
        return __(ucfirst(str_replace('_', ' ', $field)));
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
        if (!$this->_cnf->isLoggingEnabled()) {
            return null;
        }
        if (is_array($str) || is_object($str)) {
            $str = json_encode($str);
        }

        $this->_logger->$mode($str);
    }
}
