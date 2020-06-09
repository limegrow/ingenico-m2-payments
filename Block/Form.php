<?php

namespace Ingenico\Payment\Block;

use Magento\Framework\View\Element\Template;
use Ingenico\Payment\Model\Config as IngenicoConfig;
use \Ingenico\Payment\Model\IngenicoConfigProvider;

class Form extends \Magento\Payment\Block\Form
{
    protected $_template = 'form/form.phtml';

    /**
     * @var IngenicoConfig
     */
    protected $cnf;

    /**
     * @var IngenicoConfigProvider
     */
    private $configProvider;

    /**
     * Form constructor.
     *
     * @param Template\Context $context
     * @param IngenicoConfig   $cnf
     * @param IngenicoConfigProvider $configProvider
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        IngenicoConfig $cnf,
        IngenicoConfigProvider $configProvider,
        array $data = []
    ) {
        $this->cnf = $cnf;
        $this->configProvider = $configProvider;

        parent::__construct($context, $data);
    }

    /**
     * Is Redirect Payment Page Mode
     *
     * @return bool
     */
    public function isPaymentPageModeRedirect()
    {
        return $this->cnf->isPaymentPageModeRedirect();
    }

    /**
     * Get Saved Cards
     *
     * @return array
     */
    public function getSavedCards()
    {
        return $this->configProvider->getSavedCards();
    }
}
