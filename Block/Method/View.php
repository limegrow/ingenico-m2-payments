<?php

namespace Ingenico\Payment\Block\Method;

class View extends \Magento\Framework\View\Element\Template
{
    const CARDS = 'Cards';

    protected $_connector;
    protected $_checkoutSession;
    protected $_assetRepo;
    protected $_urlBuilder;
    protected $_registry;
    protected $_cnf;

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
        \Ingenico\Payment\Model\Config $cnf,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_connector = $connector;
        $this->_checkoutSession = $checkoutSession;
        $this->_assetRepo = $assetRepo;
        $this->_urlBuilder = $urlBuilder;
        $this->_registry = $registry;
        $this->_cnf = $cnf;
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

    public function getRedirectPaymentData()
    {
        $result = [];

        if ($this->_cnf->isPaymentPageModeRedirect()) {
            $result = $this->_registry->registry($this->_connector::REGISTRY_KEY_TEMPLATE_VARS_REDIRECT);
        } else {
            $pm = $this->getRequest()->getParam('pm', false);
            $brand = $this->getRequest()->getParam('brand', false);
            $orderId = $this->_connector->requestOrderId();

            if (!$pm || !$brand) {
                throw new \Magento\Framework\Exception\LocalizedException(__('ingenico.exception.message1'));
            }

            // Build Alias with PaymentMethod and Brand
            $alias = new \IngenicoClient\Alias();
            $alias->setIsPreventStoring(true)
                ->setPm($pm)
                ->setBrand($brand);

            $result = $this->_connector->getCoreLibrary()->initiateRedirectPayment($orderId, $alias);
        }

        return $result;
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

    public function getLoaderParam($paramName)
    {
        $params = $this->_registry->registry($this->_connector::REGISTRY_KEY_INLINE_LOADER_PARAMS);
        if (isset($params[$paramName])) {
            return $params[$paramName];
        }

        return null;
    }
}
