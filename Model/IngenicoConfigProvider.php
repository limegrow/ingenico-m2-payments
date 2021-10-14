<?php

namespace Ingenico\Payment\Model;

use Ingenico\Payment\Model\Config as IngenicoConfig;
use Ingenico\Payment\Helper\Data as IngenicoHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use IngenicoClient\PaymentMethod\PaymentMethodInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Payment\Block\Ideal\Banks;

class IngenicoConfigProvider implements ConfigProviderInterface
{
    const PARAM_NAME_TITLE_KEY = 'title';

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
     * @var AssetRepository
     */
    private $assetRepo;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Banks
     */
    private $banks;

    /**
     * Constructor
     */
    public function __construct(
        Connector $connector,
        IngenicoConfig $cnf,
        IngenicoHelper $ingenicoHelper,
        AssetRepository $assetRepo,
        UrlInterface $urlBuilder,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        Banks $banks
    ) {
        $this->connector = $connector;
        $this->cnf = $cnf;
        $this->ingenicoHelper = $ingenicoHelper;
        $this->assetRepo = $assetRepo;
        $this->urlBuilder = $urlBuilder;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->banks = $banks;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $paymentMode = strtolower($this->cnf->getPaymentPageMode($storeId));

        return [
            'payment' => [
                'ingenico' => [
                    //'paymentAcceptanceMarkSrc' => $this->assetRepo->getUrl('Ingenico_Payment::images/logo.gif'),
                    'redirectUrl' => $this->urlBuilder->getUrl('ingenico/payment/redirect'),
                    'inlineUrl' => $this->urlBuilder->getUrl('ingenico/payment/inline'),
                    'redirectUri' => 'ingenico/payment/' . $paymentMode,
                    'openInvoiceUrl' => $this->urlBuilder->getUrl('ingenico/payment/inline'),
                    'paymentMode' => $paymentMode,
                    'methodLogos' => $this->getPaymentLogos(),
                    'ccLogos' => $this->getCCPaymentLogos(),
                    'methods' => $this->getMethodsData(),
                ],
                \Ingenico\Payment\Model\Method\Alias::PAYMENT_METHOD_CODE => [
                    'use_saved_cards' => $this->cnf->canUseSavedCards(),
                    'savedCards' => $this->getSavedCards(),
                    'default' => $this->getCustomerDefaultCard(),
                    'aliasPayUrl' => $this->urlBuilder->getUrl('ingenico/payment/redirect', [
                        '_secure' => true,
                        'alias' => 'aliasID'
                    ])
                ],
                \Ingenico\Payment\Model\Method\Flex::PAYMENT_METHOD_CODE => [
                    'methods' => $this->cnf->getFlexMethods($storeId)
                ],
                \Ingenico\Payment\Model\Method\Ideal::PAYMENT_METHOD_CODE => [
                    'banks' => $this->banks->getAvailableBanks()
                ]
            ],
        ];
    }

    /**
     * Get Payment Logos.
     * @return array Returns array like ['ingenico_cc' => [0 => ['title' => '', 'src' => '']]]
     */
    public function getPaymentLogos()
    {
        $storeId = $this->storeManager->getStore()->getId();

        $result = [];
        foreach ($this->cnf::getAllPaymentMethods() as $className) {
            $classWithNs = '\\Ingenico\\Payment\\Model\\Method\\' . $className;
            if (!defined($classWithNs . '::CORE_CODE')) {
                continue;
            }

            $methodCode = $classWithNs::PAYMENT_METHOD_CODE;

            /** @var PaymentMethodInterface $method */
            $method = $this->ingenicoHelper->getCoreMethod($classWithNs::CORE_CODE);
            if (!$method) {
                continue;
            }

            $result[$methodCode] = [];

            if ($methodCode === \Ingenico\Payment\Model\Method\Cc::PAYMENT_METHOD_CODE) {
                // Get configured CC logos
                $logos = $this->cnf->getCCLogos($storeId);
                foreach ($logos as $logo) {
                    if (\Ingenico\Payment\Model\Config\Source\CcLogos::LOGO_GENERIC === $logo) {
                        // Generic card logo
                        $result[$methodCode][] = [
                            'src' => $this->assetRepo->getUrl('Ingenico_Payment::images/card.svg'),
                            'title' => __('Credit Card'),
                        ];
                    } else {
                        // Get logo from the core library
                        /** @var PaymentMethodInterface $method */
                        $method = $this->ingenicoHelper->getCoreMethod($logo);
                        if (!$method) {
                            continue;
                        }

                        $result[$methodCode][] = [
                            'src' => $method->getEmbeddedLogo(),
                            'title' => $method->getName(),
                        ];
                    }
                }
            } elseif ($methodCode === \Ingenico\Payment\Model\Method\Ingenico::PAYMENT_METHOD_CODE) {
                $methods = $this->connector->getPaymentMethods();
                foreach ($methods as $subMethod) {
                    /** @var PaymentMethodInterface $subMethod */

                    // Prevent duplicate
                    if (in_array($subMethod->getEmbeddedLogo(), array_column($result[$methodCode], 'src'))) {
                        continue;
                    }

                    //$result[$methodCode][] = [
                        //'src' => $subMethod->getEmbeddedLogo(),
                        //'title' => $subMethod->getName(),
                    //];
                }
            } elseif ($methodCode === \Ingenico\Payment\Model\Method\Flex::PAYMENT_METHOD_CODE) {
                if ($this->cnf->getFlexLogo($storeId)) {
                    $base = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

                    $result[$methodCode][] = [
                        'src' => $base . 'ingenico/logo/' . $this->cnf->getFlexLogo($storeId),
                        'title' => $method->getName(),
                    ];
                }
            } else {
                $result[$methodCode][] = [
                    'src' => $method->getEmbeddedLogo(),
                    'title' => $method->getName(),
                ];
            }
        }

        return $result;
    }

    /**
     * Get logos of CreditCards
     *
     * @return array
     */
    public function getCCPaymentLogos()
    {
        $methods = $this->getPaymentLogos();

        return $methods[\Ingenico\Payment\Model\Method\Cc::PAYMENT_METHOD_CODE];
    }

    /**
     * Get Saved cards.
     *
     * @return array
     */
    public function getSavedCards()
    {
        $out = [];
        if ($this->cnf->canUseSavedCards() && $customerId = $this->customerSession->getId()) {
            $savedCards = $this->connector->getCoreLibrary()->getCustomerAliases($customerId);
            if (count($savedCards)) {
                $out[] = (object) [
                    'code' => '',
                    self::PARAM_NAME_TITLE_KEY => __('checkout.use_new_payment_method'),
                    'brand' => '',
                    'imgSrc' => false,
                    'isChecked' => true
                ];
            }
            foreach ($savedCards as $savedCard) {
                $brand = $savedCard->getBrand() === 'CB' ? 'Carte Bancaire' : $savedCard->getBrand();

                $out[] = (object) [
                    'code' => $savedCard->getAlias(),
                    self::PARAM_NAME_TITLE_KEY => (string) __(
                        '%1 ends with %2, expires on %3/%4',
                        $brand,
                        substr($savedCard->getCardno(), -4, 4),
                        substr($savedCard->getEd(), 0, 2),
                        substr($savedCard->getEd(), 2, 4)
                    ),
                    'brand' => $savedCard->getBrand(),
                    'imgSrc' => $savedCard->getEmbeddedLogo(),
                    'isChecked' => false
                ];
            }
        }

        return $out;
    }

    /**
     * Get Customer's default card.
     *
     * @return null|string
     */
    private function getCustomerDefaultCard()
    {
        if ($this->cnf->canUseSavedCards() && $customerId = $this->customerSession->getId()) {
            $savedCards = (array) $this->connector->getCoreLibrary()->getCustomerAliases($customerId);
            if (count($savedCards) > 0) {
                $savedCard = array_shift($savedCards);

                return $savedCard->getAlias();
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getMethodsData()
    {
        $libraryPaymentObjects = $this->connector->getCoreLibrary()->getSelectedPaymentMethods();
        $activeM2PaymentObjects = $this->ingenicoHelper->getActiveMagentoPaymentMethods();
        $result = [];

        /** @var MethodInterface $paymentMethod */
        foreach ($activeM2PaymentObjects as $paymentMethod) {
            if (!defined(get_class($paymentMethod) . '::CORE_CODE')) {
                continue;
            }

            $coreCode = $paymentMethod::CORE_CODE;
            if (!isset($libraryPaymentObjects[$coreCode])) {
                $this->connector->log('No such payment method exists in CL: "' . $coreCode . '"', 'notice');
                continue;
            }

            /** @var PaymentMethodInterface $corePaymentMethod */
            $corePaymentMethod = $libraryPaymentObjects[$coreCode];

            $result[$paymentMethod::PAYMENT_METHOD_CODE] = [
                'code' => $paymentMethod::PAYMENT_METHOD_CODE,
                'category' => $corePaymentMethod->getCategory(),
            ];

            // Url in inline mode and for cc only at the moment
            if ($this->cnf->isPaymentPageModeInline() && $corePaymentMethod->getCategory() === 'card') {
                $result[$paymentMethod::PAYMENT_METHOD_CODE]['url'] = $this->connector->getCcIframeUrlBeforePlaceOrder();
            }
        }

        return $result;
    }
}
