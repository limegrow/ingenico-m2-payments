<?php

namespace Ingenico\Payment\Model;

class IngenicoConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const PARAM_NAME_TITLE_KEY = 'title';

    protected $_assetRepo;
    protected $_cnf;
    protected $_urlBuilder;
    protected $_escaper;
    protected $_customerSession;
    protected $_connector;

    /**
     * Constructor
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ingenico\Payment\Model\Config $cnf,
        \Magento\Framework\Escaper $escaper,
        \Magento\Customer\Model\Session $customerSession,
        \Ingenico\Payment\Model\Connector $connector
    ) {
        $this->_assetRepo = $assetRepo;
        $this->_urlBuilder = $urlBuilder;
        $this->_cnf = $cnf;
        $this->_escaper = $escaper;
        $this->_customerSession = $customerSession;
        $this->_connector = $connector;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $paymentMode = strtolower($this->_cnf->getPaymentPageMode());

        return [
            'payment' => [
                'ingenico' => [
//                    'paymentAcceptanceMarkSrc' => $this->_assetRepo->getUrl('Ingenico_Payment::images/logo.gif'),
                    'redirectUrl' => $this->_urlBuilder->getUrl('ingenico/payment/'.$paymentMode),
                    'redirectUri' => 'ingenico/payment/'.$paymentMode,
                    'titleMode' => $this->_cnf->getTitleMode(),
                    'paymentMode' => $paymentMode,
                    'methodLogos' => $this->getPaymentLogos(),
                    'savedCards' => $this->getSavedCards()
                ],
            ],
        ];
    }

    public function getPaymentLogos()
    {
        $methods = $this->_connector->getCoreLibrary()->getSelectedPaymentMethods();
        $sorted = [];
        foreach ($methods as $code => $method) {
            if (!isset($sorted[$method->getCategoryName()])) {
                $sorted[$method->getCategoryName()] = [];
            }

            $sorted[$method->getCategoryName()][$code] = $method;
        }

        $imgs = [];
        foreach ($sorted as $methods) {
            foreach ($methods as $code => $method) {
                $imgs[] = (object) [
                    'src' => $method->getEmbeddedLogo(),
                    self::PARAM_NAME_TITLE_KEY => $method->getName(),
                ];
            }
        }

        return $imgs;
    }

    public function getSavedCards()
    {
        $out = [];
        if ($this->_cnf->canUseSavedCards() && $customerId = $this->_customerSession->getId()) {
            $savedCards = $this->_connector->getCoreLibrary()->getCustomerAliases($customerId);
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
                $out[] = (object) [
                    'code' => $savedCard->getAlias(),
                    self::PARAM_NAME_TITLE_KEY => $savedCard->getName(),
                    'brand' => $savedCard->getBrand(),
                    'imgSrc' => $savedCard->getEmbeddedLogo(),
                    'isChecked' => false
                ];
            }
        }

        return $out;
    }
}
