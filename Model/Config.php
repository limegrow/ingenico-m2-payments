<?php

namespace Ingenico\Payment\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config extends \Magento\Framework\App\Config
{
    const CONFIG_CONNECTION_KEY = 'ingenico_connection/';

    protected $_scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $_scopeCode = null;

    protected $_configCollectionFactory;
    protected $_configResource;
    protected $_cacheManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeCodeResolver $scopeCodeResolver,
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configCollectionFactory,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $types = []
    ) {
        $this->_configResource = $configResource;
        $this->_configCollectionFactory = $configCollectionFactory;
        $this->_cacheManager = $cacheManager;
        $this->storeManager = $storeManager;

        $this->setStoreId($this->storeManager->getStore()->getId());

        return parent::__construct($scopeCodeResolver, $types);
    }

    public function setStoreId($id)
    {
        if (is_numeric($id)) {
            $this->_scopeCode = $id;
        }

        return $this;
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
        if (!$scopeId) {
            $scopeId = $this->_scopeCode;
        }

        return $this->_configResource->saveConfig(
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
        if (!$scopeId) {
            $scopeId = $this->_scopeCode;
        }

        return $this->_configResource->deleteConfig(
            $path,
            $scope,
            $scopeId
        );
    }

    public function isEnabled()
    {
        return $this->isSetFlag('payment/ingenico_e_payments/active', $this->_scope, $this->_scopeCode);
    }

    public function isLoggingEnabled()
    {
        return $this->isSetFlag('ingenico_settings/general/logging_enabled', $this->_scope, $this->_scopeCode);
    }

    public function getTitleMode()
    {
        return $this->getValue('payment/ingenico_e_payments/title_mode', $this->_scope, $this->_scopeCode);
    }

    public function isOrderConfirmationEmailSuppressed()
    {
        return $this->isSetFlag('payment/ingenico_e_payments/supress_order_email', $this->_scope, $this->_scopeCode);
    }

    public function getMode($asBool = false)
    {
        if ($asBool) {
            return $this->getValue(self::CONFIG_CONNECTION_KEY.'mode/mode', $this->_scope, $this->_scopeCode) == 'test' ? false : true;
        }
        return $this->getValue(self::CONFIG_CONNECTION_KEY.'mode/mode', $this->_scope, $this->_scopeCode);
    }

    public function getConnectionUser($mode)
    {
        return $this->getValue(self::CONFIG_CONNECTION_KEY.$mode.'/user', $this->_scope, $this->_scopeCode);
    }

    public function getConnectionPassword($mode)
    {
        return $this->getValue(self::CONFIG_CONNECTION_KEY.$mode.'/password', $this->_scope, $this->_scopeCode);
    }

    public function getConnectionPspid($mode)
    {
        return $this->getValue(self::CONFIG_CONNECTION_KEY.$mode.'/pspid', $this->_scope, $this->_scopeCode);
    }

    public function getConnectionSignature($mode)
    {
        return $this->getValue(self::CONFIG_CONNECTION_KEY.$mode.'/signature', $this->_scope, $this->_scopeCode);
    }

    public function getConnectionTimeout($mode)
    {
        return $this->getValue(self::CONFIG_CONNECTION_KEY.$mode.'/timeout', $this->_scope, $this->_scopeCode);
    }

    public function getIsAdvancedSettingsMode()
    {
        return $this->getValue('ingenico_settings/general/mode', $this->_scope, $this->_scopeCode) == 'advanced' ? true : false;
    }

    public function canUseSavedCards()
    {
        return $this->isSetFlag('ingenico_settings/tokenization/stored_cards_enabled', $this->_scope, $this->_scopeCode);
    }

    public function getActivePaymentMethods()
    {
        $result = [];

        if ($data = $this->getValue('ingenico_payment_methods/methods', $this->_scope, $this->_scopeCode)) {
            foreach ($data as $methods) {
                foreach ($methods as $code => $methData) {
                    if (isset($methData['enabled']) && $methData['enabled']) {
                        $result[] = $code;
                    }
                }
            }
        }

        return $result;
    }

    public function getPaymentPageMode()
    {
        return $this->getValue('ingenico_payment_page/presentation/mode', $this->_scope, $this->_scopeCode);
    }

    public function isPaymentPageModeRedirect()
    {
        return $this->getPaymentPageMode() == \IngenicoClient\Configuration::PAYMENT_TYPE_REDIRECT;
    }

    public function isDirectSalesMode()
    {
        return $this->isSetFlag('ingenico_settings/tokenization/direct_sales', $this->_scope, $this->_scopeCode);
    }

    public function getTemplateManagerUrl()
    {
        $out = [
            'live' => 'https://secure.ogone.com/Ncol/Prod/BackOffice/Template/defaulttemplate?MenuId=43&CSRFSP=%2fncol%2ftest%2fbackoffice%2fmenu%2findex&CSRFKEY=9AAD1230DF4EDF2C1ABBEAFBB016022D26D58041&CSRFTS=20190108114345&branding=OGONE&MigrationMode=DOTNET',
            'test' => 'https://secure.ogone.com/Ncol/Test/BackOffice/login/index?branding=OGONE&CSRFSP=%2fncol%2ftest%2fbackoffice%2ftemplate%2fdefaulttemplate&CSRFKEY=0E7EEC3B1111D27F3F21B10729EE6ADF37112190&CSRFTS=20190213163650'
        ];

        $mode = $this->getMode();
        if ($mode && isset($out[$mode])) {
            return $out[$mode];
        }

        return $out['test'];
    }

    public function getPaymentAuthorisationNotificationEmail()
    {
        return $this->getValue('ingenico_settings/tokenization/capture_request_email', $this->_scope, $this->_scopeCode);
    }

    public function getMinValue($path)
    {
        $val = $this->getValue($path);
        if (stripos($val, '-') !== false) {
            $val = explode('-', $val);
            return $val[0];
        }

        return '';
    }

    public function getMaxValue($path)
    {
        $val = $this->getValue($path);
        if (stripos($val, '-') !== false) {
            $val = explode('-', $val);
            return $val[1];
        }

        return '';
    }

    public function getStoreName()
    {
        if ($this->getValue('general/store_information/name', $this->_scope, $this->_scopeCode)) {
            return $this->getValue('general/store_information/name', $this->_scope, $this->_scopeCode);
        }

        return '[Please set Store Name in configuration]';
    }

    public function getStoreEmailLogo($storeId)
    {
        $template = ObjectManager::getInstance()->create(\Ingenico\Payment\Model\Email\Template::class);
        return $template->getLogoUrlCustom($storeId);
    }

    public function getIngenicoLogo($storeId = null)
    {
        $assetRepo = ObjectManager::getInstance()->create(\Magento\Framework\View\Asset\Repository::class);
        return $assetRepo->getUrl('Ingenico_Payment::images/logo_provider.png');
    }

    public function getAvailableLocalisations()
    {
        return ['en_US','fr_FR','de_DE','nl_NL','it_IT','es_ES','pt_PT'];
    }

    public function getBaseHost()
    {
        $baseUrl = $this->getValue('web/unsecure/base_url', 'default', 0);
        // phpcs:ignore
        return parse_url($baseUrl)['host'];
    }

    public function exportSettingsJson()
    {
        $out = [];
        $coll = $this->_configCollectionFactory->create()
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
            $this->_configResource->saveConfig($row->path, $row->value, $row->scope, $row->scope_id);
        }
        $this->_cacheManager->flush(['config']);
    }
}
