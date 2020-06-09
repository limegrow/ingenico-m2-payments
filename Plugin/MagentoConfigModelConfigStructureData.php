<?php

namespace Ingenico\Payment\Plugin;

class MagentoConfigModelConfigStructureData
{
    const PARAM_NAME_CHILDREN = 'children';
    const PARAM_NAME_SYSTEM = 'system';
    const PARAM_NAME_INGENICO_PAYMENT_METHODS = 'ingenico_payment_methods';
    const PARAM_NAME_CONFIG = 'config';
    const PARAM_NAME_SECTIONS = 'sections';
    const PARAM_NAME_ELEMENT_TYPE = '_elementType';
    const PARAM_NAME_LABEL = 'label';
    const PARAM_NAME_TRANSLATE = 'translate';
    const PARAM_NAME_SHOW_IN_DEFAULT = 'showInDefault';
    const PARAM_NAME_SHOW_IN_WEBSITE = 'showInWebsite';
    const PARAM_NAME_SHOW_IN_STORE = 'showInStore';
    const PARAM_NAME_SORT_ORDER = 'sortOrder';
    const PARAM_NAME_CONFIGKEY = 'ingenico_payment_methods/business_geography/countries';

    protected $_storeManager;
    protected $_connector;
    protected $_cnf;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ingenico\Payment\Model\Config $cnf,
        \Ingenico\Payment\Model\Connector $connector
    ) {
        $this->_storeManager = $storeManager;
        $this->_connector = $connector;
        $this->_cnf = $cnf;
    }

    /**
     * Interceptor Function for Adding Payment Methods to Config
     */
    public function aroundMerge(\Magento\Config\Model\Config\Structure\Data $subject, callable $proceed, $config)
    {
        if (!isset($config[self::PARAM_NAME_CONFIG][self::PARAM_NAME_SYSTEM][self::PARAM_NAME_SECTIONS][self::PARAM_NAME_INGENICO_PAYMENT_METHODS][self::PARAM_NAME_CHILDREN])) {
            return $proceed($config);
        }

        $path = 'ingenico_payment_methods/methods';

        $allMethods = $this->_connector->getPaymentMethods();
        $allMethodsByType = [];

        foreach ($allMethods as $methodData) {
            /** @var \IngenicoClient\PaymentMethod\PaymentMethod $methodData */
            $methodCat = $methodData->getCategory();
            if (!isset($allMethodsByType[$methodCat])) {
                $allMethodsByType[$methodCat] = [];
            }

            $allMethodsByType[$methodCat][] = $methodData;
        }

        ksort($allMethodsByType);
        $additionalConfig = [];
        $typeSortIndex = 1;

        foreach ($allMethodsByType as $categoryId => $typeMethods) {
            $methodsConfData = [];
            $methodSortIndex = 1;

            // create config data for individual payment methods
            $categoryName = '';
            foreach ($typeMethods as $ingenicoMethodData) {
                $methodId = $ingenicoMethodData->getId();

                $label = '';
                $label .= '<span class="ingenico-method-title">';
                $label .= '<img width="50" src="'.$ingenicoMethodData->getEmbeddedLogo().'" >';
                $label .= $ingenicoMethodData->getName();
                $label .= '</span>';

                if ($methodId === 'pay_pal') {
                    $label .= '<span class="paypal-warn"><span class="icon-span modal-link" data-modal-id="paypal_warning_content"></span></span>';
                }

                // Get translated category name
                if (empty($categoryName)) {
                    $categoryName = $this->_connector->getCoreLibrary()->__(
                        $ingenicoMethodData->getCategoryName(),
                        [],
                        null,
                        $this->_connector->getLocale()
                    );
                }

                $methodsConfData[$methodId] = [
                    'id' => $methodId,
                    'type' => 'text',
                    self::PARAM_NAME_ELEMENT_TYPE => 'group',
                    self::PARAM_NAME_LABEL => $label,
                    self::PARAM_NAME_TRANSLATE => self::PARAM_NAME_LABEL,
                    self::PARAM_NAME_SHOW_IN_DEFAULT => 1,
                    self::PARAM_NAME_SHOW_IN_WEBSITE => 1,
                    self::PARAM_NAME_SHOW_IN_STORE => 0,
                    self::PARAM_NAME_SORT_ORDER => $methodSortIndex,
                    'expanded' => 1,
                    self::PARAM_NAME_CHILDREN => $this->_getPaymentMethodConfigFieldsData($path.'/'.$categoryId.'/'.$ingenicoMethodData['id']),
                    'path' => $path.'/'.$categoryId,
                    'depends' => [
                        'fields' => [
                            self::PARAM_NAME_CONFIGKEY => [
                                'id' => self::PARAM_NAME_CONFIGKEY,
                                'separator' => ',',
                                'value' => implode(',', array_keys($ingenicoMethodData['countries'])),
                                self::PARAM_NAME_ELEMENT_TYPE => 'field',
                                'dependPath' => explode('/', self::PARAM_NAME_CONFIGKEY)
                            ]
                        ]
                    ]
                ];
            }

            // create config data for payment method groups
            $additionalConfig[$categoryId] = [
                'id' => $categoryId,
                'type' => 'text',
                self::PARAM_NAME_ELEMENT_TYPE => 'group',
                self::PARAM_NAME_LABEL => $categoryName,
                self::PARAM_NAME_TRANSLATE => self::PARAM_NAME_LABEL,
                self::PARAM_NAME_SHOW_IN_DEFAULT => 1,
                self::PARAM_NAME_SHOW_IN_WEBSITE => 1,
                self::PARAM_NAME_SHOW_IN_STORE => 0,
                self::PARAM_NAME_SORT_ORDER => $typeSortIndex,
                'expanded' => 1,
                self::PARAM_NAME_CHILDREN => $methodsConfData,
                'path' => $path
            ];
        }

        if (count($additionalConfig)
            && isset($config[self::PARAM_NAME_CONFIG][self::PARAM_NAME_SYSTEM][self::PARAM_NAME_SECTIONS][self::PARAM_NAME_INGENICO_PAYMENT_METHODS][self::PARAM_NAME_CHILDREN]['methods'])
        ) {
            $config[self::PARAM_NAME_CONFIG][self::PARAM_NAME_SYSTEM][self::PARAM_NAME_SECTIONS][self::PARAM_NAME_INGENICO_PAYMENT_METHODS][self::PARAM_NAME_CHILDREN]['methods'][self::PARAM_NAME_CHILDREN] = $additionalConfig;
        }

        return $proceed($config);
    }

    private function _getPaymentMethodConfigFieldsData($path)
    {
        return [
            'enabled' => [
                'id' => 'enabled',
                'type' => 'select',
                'source_model' => \Magento\Config\Model\Config\Source\Yesno::class,
                self::PARAM_NAME_ELEMENT_TYPE => 'field',
                self::PARAM_NAME_SORT_ORDER => 10,
                self::PARAM_NAME_LABEL => __('toggle.enabled'),
                self::PARAM_NAME_TRANSLATE => self::PARAM_NAME_LABEL,
                self::PARAM_NAME_SHOW_IN_DEFAULT => 1,
                self::PARAM_NAME_SHOW_IN_WEBSITE => 1,
                self::PARAM_NAME_SHOW_IN_STORE => 0,
                'path' => $path
            ]
        ];
    }
}
