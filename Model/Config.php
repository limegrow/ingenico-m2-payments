<?php

namespace Ingenico\Payment\Model;

use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Config\Model\ResourceModel\Config as ConfigResourceModel;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use IngenicoClient\Configuration;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Payment\Model\Email\Template as EmailTemplate;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class Config extends \Magento\Framework\App\Config
{
    const XML_PATH_GENERAL_MODE = 'ingenico_settings/general/mode';
    const XML_PATH_GENERAL_LOGGING = 'ingenico_settings/general/logging_enabled';
    const XML_PATH_ORDER_STATUS_AUTHORIZED = 'ingenico_settings/general/order_status_authorize';
    const XML_PATH_ORDER_STATUS_CAPTURED = 'ingenico_settings/general/order_status_capture';
    const XML_PATH_TOKENIZATION_ENABLED = 'ingenico_settings/tokenization/enabled';
    const XML_PATH_TOKENIZATION_STORED_CARDS_ENABLED = 'ingenico_settings/tokenization/stored_cards_enabled';
    const XML_PATH_TOKENIZATION_DIRECT_SALES = 'ingenico_settings/tokenization/direct_sales';
    const XML_PATH_TOKENIZATION_SKIP_SECURITY_CHECK = 'ingenico_settings/tokenization/skip_security_check';
    const XML_PATH_TOKENIZATION_CAPTURE_REQUEST_NOTIFY = 'ingenico_settings/tokenization/capture_request_notify';
    const XML_PATH_TOKENIZATION_CAPTURE_REQUEST_EMAIL = 'ingenico_settings/tokenization/capture_request_email';
    const XML_PATH_ORDERS_PAYMENT_REMINDER_SEND = 'ingenico_settings/orders/payment_reminder_email_send';
    const XML_PATH_ORDERS_PAYMENT_REMINDER_TIMEOUT = 'ingenico_settings/orders/payment_reminder_email_timeout';
    const XML_PATH_PAYMENT_PAGE_PRESENTATION_MODE = 'ingenico_payment_page/presentation/mode';
    const XML_PATH_PAYMENT_PAGE_OPTIONS_PMLIST = 'ingenico_payment_page/options/pmlist';
    const XML_PATH_PAYMENT_PAGE_TEMPLATE_SOURCE = 'ingenico_payment_page/custom_template/template_source';
    const XML_PATH_PAYMENT_PAGE_TEMPLATE_NAME = 'ingenico_payment_page/custom_template/ingenico_template_name';
    const XML_PATH_PAYMENT_PAGE_EXTERNAL_URL = 'ingenico_payment_page/custom_template/remote';
    const XML_PATH_PAYMENT_PAGE_LOCAL = 'ingenico_payment_page/custom_template/local';
    const XML_PATH_FLEX_METHODS = 'payment/ingenico_flex/methods';
    const XML_PATH_FLEX_LOGO = 'payment/ingenico_flex/logo';
    const XML_PATH_IDEAL_BANKS = 'payment/ingenico_ideal/banks';
    const XML_PATH_SUPPRESS_ORDER_CONFIRMATION_EMAIL = 'payment/ingenico_e_payments/order_confirmation_email';
    const XML_PATH_ORDER_STATUS_FOR_CONFIRMATION = 'payment/ingenico_e_payments/order_confirmation_status';
    const XML_PATH_CREDIT_CARD_LOGOS = 'payment/ingenico_cc/cc_logos';

    const CONFIG_CONNECTION_KEY = 'ingenico_connection/';
    const MODE_LIVE = 'live';
    const MODE_TEST = 'test';

    /**
     * @var ConfigCollectionFactory;
     */
    private $configCollectionFactory;

    /**
     * @var ConfigResourceModel
     */
    private $configResource;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var OrderStatusCollectionFactory
     */
    private $orderStatusCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EmailTemplate
     */
    private $template;

    /**
     * @var AssetRepository
     */
    private $assert;

    /**
     * Config constructor.
     *
     * @param ScopeCodeResolver            $scopeCodeResolver
     * @param ConfigResourceModel          $configResource
     * @param ConfigCollectionFactory      $configCollectionFactory
     * @param CacheManager                 $cacheManager
     * @param StoreManagerInterface        $storeManager
     * @param OrderStatusCollectionFactory $orderStatusCollectionFactory
     * @param EmailTemplate                $template
     * @param AssetRepository              $assert
     * @param array                        $types
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        ScopeCodeResolver $scopeCodeResolver,
        ConfigResourceModel $configResource,
        ConfigCollectionFactory $configCollectionFactory,
        CacheManager $cacheManager,
        StoreManagerInterface $storeManager,
        OrderStatusCollectionFactory $orderStatusCollectionFactory,
        EmailTemplate $template,
        AssetRepository $assert,
        array $types = []
    ) {
        $this->configResource = $configResource;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->cacheManager = $cacheManager;
        $this->storeManager = $storeManager;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
        $this->template = $template;
        $this->assert = $assert;

        return parent::__construct($scopeCodeResolver, $types);
    }

    /**
     * Save config value
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     *
     * @return \Magento\Config\Model\ResourceModel\Config
     */
    public function saveConfig($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        return $this->configResource->saveConfig(
            $path,
            $value,
            $scope,
            $scopeId
        );
    }

    /**
     * Delete config value
     *
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     *
     * @return \Magento\Config\Model\ResourceModel\Config
     */
    public function deleteConfig($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        return $this->configResource->deleteConfig(
            $path,
            $scope,
            $scopeId
        );
    }

    /**
     * Check if the extension is configured.
     *
     * @return bool
     */
    public function isExtensionConfigured($scopeId = null)
    {
        $mode = $this->getMode();

        $settings = [
            'user' => $user = $this->getConnectionUser($mode, $scopeId),
            'password' => $this->getConnectionPassword($mode, $scopeId),
            'psid' => $this->getConnectionPspid($mode, $scopeId),
            'signature' => $this->getConnectionSignature($mode, $scopeId)
        ];

        foreach ($settings as $key => $value) {
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is Logging Enabled.
     *
     * @return bool
     * @SuppressWarnings(Generic.Files.LineLength.TooLong)
     */
    public function isLoggingEnabled()
    {
        return $this->isSetFlag(self::XML_PATH_GENERAL_LOGGING, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }

    /**
     * Suppress Order Confirmation Email Mode.
     *
     * @return int
     */
    public function getOrderConfirmationEmailMode($scopeId = null)
    {
        return (int) $this->getValue(
            self::XML_PATH_SUPPRESS_ORDER_CONFIRMATION_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Suppress Order Confirmation Email "Order Status".
     *
     * @return string
     */
    public function getOrderStatusForConfirmationEmail($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_ORDER_STATUS_FOR_CONFIRMATION,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Credit Card Logos
     *
     * @return string
     */
    public function getCCLogos($scopeId = null)
    {
        $logos = $this->getValue(
            self::XML_PATH_CREDIT_CARD_LOGOS,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        return explode(',', $logos);
    }

    /**
     * Get Configurable payment methods
     *
     * @return array
     */
    public function getFlexMethods($scopeId = null)
    {
        $methods = $this->getValue(
            self::XML_PATH_FLEX_METHODS,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        return is_string($methods) ? (array) json_decode($methods, true) : $methods;
    }

    /**
     * Get Flex Logo
     *
     * @return string
     */
    public function getFlexLogo($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_FLEX_LOGO,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Gateway mode.
     *
     * @param bool $asBool
     *
     * @return string|bool
     */
    public function getMode($asBool = false, $scopeId = null)
    {
        if ($asBool) {
            return $this->getValue(
                self::CONFIG_CONNECTION_KEY . 'mode/mode',
                ScopeInterface::SCOPE_STORE,
                $scopeId
            ) === self::MODE_TEST ? false : true;
        }

        return $this->getValue(
            self::CONFIG_CONNECTION_KEY . 'mode/mode',
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Connection User.
     *
     * @param      $mode
     * @param mxied $scopeId
     *
     * @return mixed
     */
    public function getConnectionUser($mode, $scopeId = null)
    {
        return $this->getValue(
            self::CONFIG_CONNECTION_KEY . $mode . '/user',
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Connection Password.
     *
     * @param      $mode
     * @param mxied $scopeId
     *
     * @return mixed
     */
    public function getConnectionPassword($mode, $scopeId = null)
    {
        return $this->getValue(
            self::CONFIG_CONNECTION_KEY . $mode . '/password',
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Connection Pspid.
     *
     * @param      $mode
     * @param mxied $scopeId
     *
     * @return mixed
     */
    public function getConnectionPspid($mode, $scopeId = null)
    {
        return $this->getValue(
            self::CONFIG_CONNECTION_KEY . $mode . '/pspid',
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Connection Signature.
     *
     * @param      $mode
     * @param mxied $scopeId
     *
     * @return mixed
     */
    public function getConnectionSignature($mode, $scopeId = null)
    {
        return $this->getValue(
            self::CONFIG_CONNECTION_KEY . $mode . '/signature',
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Is Advanced Settings Mode.
     *
     * @return bool
     */
    public function getIsAdvancedSettingsMode($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_GENERAL_MODE,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        ) === 'advanced';
    }

    /**
     * Check if saved cards feature is active.
     *
     * @return bool
     * @SuppressWarnings(Generic.Files.LineLength.TooLong)
     */
    public function canUseSavedCards($scopeId = null)
    {
        return $this->isTokenizationEnabled($scopeId) && $this->isStoredCardsEnabled($scopeId);
    }

    /**
     * Is Tokenization Enabled.
     *
     * @param mixed $scopeId
     *
     * @return bool
     */
    public function isTokenizationEnabled($scopeId = null)
    {
        return $this->isSetFlag(
            self::XML_PATH_TOKENIZATION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Is Stored Cards Enabled.
     *
     * @param mixed $scopeId
     *
     * @return bool
     */
    public function isStoredCardsEnabled($scopeId = null)
    {
        return $this->isSetFlag(
            self::XML_PATH_TOKENIZATION_STORED_CARDS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get "Skip security check" option.
     *
     * @param null $scopeId
     *
     * @return bool
     */
    public function getSkipSecurityCheck($scopeId = null)
    {
        return $this->isSetFlag(
            self::XML_PATH_TOKENIZATION_SKIP_SECURITY_CHECK,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Send an e-mail for any new capture requests
     *
     * @param null $scopeId
     *
     * @return bool
     */
    public function getSendEmailCaptureRequests($scopeId = null)
    {
        return $this->isSetFlag(
            self::XML_PATH_TOKENIZATION_CAPTURE_REQUEST_NOTIFY,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Send a reminder e-mail.
     *
     * @param null $scopeId
     *
     * @return bool
     */
    public function getPaymentReminderEmailSend($scopeId = null)
    {
        return $this->isSetFlag(
            self::XML_PATH_ORDERS_PAYMENT_REMINDER_SEND,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Payment reminder: Send After.
     *
     * @param null $scopeId
     *
     * @return mixed
     */
    public function getPaymentReminderEmailTimeout($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_ORDERS_PAYMENT_REMINDER_TIMEOUT,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get List of Magento Payment Classes.
     *
     * @return string[]
     */
    public static function getAllPaymentMethods()
    {
        $methods = [];
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'Method';
        $files = scandir($directory);
        foreach ($files as $file) {
            $file = $directory . DIRECTORY_SEPARATOR . $file;

            $info = pathinfo($file);
            if (!isset($info['extension']) || $info['extension'] !== 'php') {
                continue;
            }

            $class_name = basename($info['filename'], '.php');
            if (in_array($class_name, ['AbstractMethod'])) {
                continue;
            }

            $methods[] = $class_name;
        }

        return $methods;
    }

    /**
     * Get Ingenico Payment Code codes that active in Magento.
     *
     * @return array
     */
    public function getActivePaymentMethods($scopeId = null)
    {
        if (!$this->isExtensionConfigured($scopeId)) {
            return [];
        }

        $result = [];
        foreach (self::getAllPaymentMethods() as $className) {
            $classWithNs = '\\Ingenico\\Payment\\Model\\Method\\' . $className;
            if (!defined($classWithNs . '::CORE_CODE')) {
                continue;
            }

            $methodCode = $classWithNs::PAYMENT_METHOD_CODE;
            $coreCode = $classWithNs::CORE_CODE;

            try {
                if ($this->isSetFlag(
                    'payment/' . $methodCode . '/active',
                    ScopeInterface::SCOPE_STORE,
                    $scopeId
                )) {
                    $result[] = $coreCode;

                    if ($coreCode === 'visa') {
                        $result[] = 'mastercard';
                        $result[] = 'amex';
                        $result[] = 'diners_club';
                        $result[] = 'discover';
                        $result[] = 'jcb';
                        $result[] = 'maestro';
                        $result[] = 'dankort';
                    }
                }
            } catch (\Exception $e) {
                //
            }
        }

        return $result;
    }

    /**
     * Get Payment Page Mode.
     *
     * @return string
     */
    public function getPaymentPageMode($scopeId = null)
    {
        $mode = $this->getValue(
            self::XML_PATH_PAYMENT_PAGE_PRESENTATION_MODE,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        if (empty($mode)) {
            return Configuration::PAYMENT_TYPE_REDIRECT;
        }

        return $mode;
    }

    /**
     * Is Payment Page Mode Redirect.
     *
     * @return bool
     */
    public function isPaymentPageModeRedirect($scopeId = null)
    {
        return $this->getPaymentPageMode($scopeId) === Configuration::PAYMENT_TYPE_REDIRECT;
    }

    /**
     * Is Payment Page Mode Inline.
     *
     * @return bool
     */
    public function isPaymentPageModeInline($scopeId = null)
    {
        return $this->getPaymentPageMode($scopeId) === Configuration::PAYMENT_TYPE_INLINE;
    }

    /**
     * Get Payment Page Template Source.
     *
     * @param mixed $scopeId
     *
     * @return mixed
     */
    public function getPaymentPageTemplateSource($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_PAYMENT_PAGE_TEMPLATE_SOURCE,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Payment Page Template Name.
     *
     * @param mixed $scopeId
     *
     * @return mixed
     */
    public function getPaymentPageTemplateName($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_PAYMENT_PAGE_TEMPLATE_NAME,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Payment Page Template External url.
     *
     * @param mixed $scopeId
     *
     * @return mixed
     */
    public function getPaymentPageExternalUrl($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_PAYMENT_PAGE_EXTERNAL_URL,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Payment Page Local.
     *
     * @param mixed $scopeId
     *
     * @return mixed
     */
    public function getPaymentPageLocal($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_PAYMENT_PAGE_LOCAL,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Layout of Payment Methods.
     *
     * @param mixed $scopeId
     *
     * @return int
     */
    public function getPaymentPageListType($scopeId = null)
    {
        return (int) $this->getValue(
            self::XML_PATH_PAYMENT_PAGE_OPTIONS_PMLIST,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Is Direct Sales Mode activated.
     *
     * @return bool
     */
    public function isDirectSalesMode($scopeId = null)
    {
        return $this->isSetFlag(
            self::XML_PATH_TOKENIZATION_DIRECT_SALES,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * @return string
     * @SuppressWarnings(Generic.Files.LineLength.TooLong)
     */
    public function getTemplateManagerUrl($scopeId = null)
    {
        $out = [
            'live' => 'https://secure.ogone.com/Ncol/Prod/BackOffice/Template/defaulttemplate?MenuId=43&CSRFSP=%2fncol%2ftest%2fbackoffice%2fmenu%2findex&CSRFKEY=9AAD1230DF4EDF2C1ABBEAFBB016022D26D58041&CSRFTS=20190108114345&branding=OGONE&MigrationMode=DOTNET',
            'test' => 'https://secure.ogone.com/Ncol/Test/BackOffice/login/index?branding=OGONE&CSRFSP=%2fncol%2ftest%2fbackoffice%2ftemplate%2fdefaulttemplate&CSRFKEY=0E7EEC3B1111D27F3F21B10729EE6ADF37112190&CSRFTS=20190213163650'
        ];

        $mode = $this->getMode(false, $scopeId);
        if ($mode && isset($out[$mode])) {
            return $out[$mode];
        }

        return $out['test'];
    }

    /**
     * Get email for sending captureing request.
     *
     * @param null $scopeId
     *
     * @return array|mixed
     */
    public function getPaymentAuthorisationNotificationEmail($scopeId = null)
    {
        return $this->getValue(
            self::XML_PATH_TOKENIZATION_CAPTURE_REQUEST_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );
    }

    /**
     * Get Order Status of Authorized payments.
     *
     * @return string
     */
    public function getOrderStatusAuth(OrderInterface $order)
    {
        $scopeId = $order->getStoreId();

        // Get value per payment method
        if ($order->getPayment() && $order->getPayment()->getMethod()) {
            $method_id = $order->getPayment()->getMethod();

            $value = $this->getValue(
                'payment/' . $method_id . '/order_status_authorize',
                ScopeInterface::SCOPE_STORE,
                $scopeId
            );

            if (!empty($value)) {
                return $value;
            }
        }

        // Get global value
        $value = $this->getValue(
            self::XML_PATH_ORDER_STATUS_AUTHORIZED,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        if (empty($value)) {
            $value = $order->getStatus();
        }

        return $value;
    }

    /**
     * Get Order Status of Sale/Captured payments.
     *
     * @return string
     */
    public function getOrderStatusSale(OrderInterface $order)
    {
        $scopeId = $order->getStoreId();

        // Get value per payment method
        if ($order->getPayment() && $order->getPayment()->getMethod()) {
            $method_id = $order->getPayment()->getMethod();

            $value = $this->getValue(
                'payment/' . $method_id . '/order_status_capture',
                ScopeInterface::SCOPE_STORE,
                $scopeId
            );

            if (!empty($value)) {
                return $value;
            }
        }

        // Get global value
        $value = $this->getValue(
            self::XML_PATH_ORDER_STATUS_CAPTURED,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        if (empty($value)) {
            $value = $order->getStatus();
        }

        return $value;
    }

    /**
     * Get Store Name.
     *
     * @param $storeId
     *
     * @return array|\Magento\Framework\Phrase|mixed
     */
    public function getStoreName($storeId = null)
    {
        if ($this->getValue(
            Information::XML_PATH_STORE_INFO_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            return $this->getValue(
                Information::XML_PATH_STORE_INFO_NAME,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return __('[Please set Store Name in configuration]');
    }

    /**
     * @param mixed $storeId
     *
     * @return string
     */
    public function getStoreEmailLogo($storeId = null)
    {
        return $this->template->getLogoUrlCustom($storeId);
    }

    /**
     * @param mixed $storeId
     *
     * @return mixed
     */
    public function getIngenicoLogo($storeId = null)
    {
        return $this->assert->getUrl('Ingenico_Payment::images/logo_provider.png');
    }

    public function getBaseHost()
    {
        $baseUrl = $this->getValue(\Magento\Store\Model\Store::XML_PATH_UNSECURE_BASE_URL, 'default', 0);

        // phpcs:ignore
        return parse_url($baseUrl)['host'];
    }

    public function exportSettingsJson()
    {
        $out = [];
        $coll = $this->configCollectionFactory->create()
                                              ->addFieldToFilter('path', ['like' => '%ingenico%'])
                                              ->addFieldToFilter('scope', 'default')
            ;

        foreach ($coll as $rec) {
            $rec->unsConfigId();
            $data = $rec->getData();

            // Remove sensitive data
            if (in_array($data['path'], [
                'ingenico_connection/test/pspid',
                'ingenico_connection/test/signature',
                'ingenico_connection/test/user',
                'ingenico_connection/test/password',
                'ingenico_connection/live/pspid',
                'ingenico_connection/live/signature',
                'ingenico_connection/live/user',
                'ingenico_connection/live/password'
            ])) {
                $data['value'] = '';
            }

            $out[] = $data;
        }

        return json_encode($out, JSON_PRETTY_PRINT);
    }

    public function importSettingsJson($json)
    {
        $data = json_decode($json);
        if (!$data || !is_array($data)) {
            // phpcs:ignore
            throw new \Exception('File does not contain settings in correct format. Please make sure you selected correct file!');
        }

        foreach ($data as $row) {
            $this->configResource->saveConfig($row->path, $row->value, $row->scope, $row->scope_id);
        }
        $this->cacheManager->flush(['config']);
    }

    /**
     * Get Assigned State
     * @param $status
     * @return DataObject
     */
    public function getAssignedState($status)
    {
        $collection = $this->orderStatusCollectionFactory->create()->joinStates();
        $status = $collection->addAttributeToFilter('main_table.status', $status)
                             ->addAttributeToSort('state_table.is_default', 'desc')
                             ->getFirstItem();
        return $status;
    }

    /**
     * Get Assigned Status.
     *
     * @param string $state
     * @return DataObject
     */
    public function getAssignedStatus($state)
    {
        $collection = $this->orderStatusCollectionFactory->create()->addStateFilter($state);
        return $collection->getFirstItem();
    }

    /**
     * Get iDeal Banks
     *
     * @return array
     */
    public function getIDealBanks($scopeId = null)
    {
        $banks = $this->getValue(
            self::XML_PATH_IDEAL_BANKS,
            ScopeInterface::SCOPE_STORE,
            $scopeId
        );

        return explode(',', $banks);
    }
}
