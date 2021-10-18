<?php
// phpcs:ignoreFile An error occurred during processing; checking has been aborted.

namespace Ingenico\Payment\Block\Ideal;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Widget\Block\BlockInterface;
use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Payment\Model\Config;
use Magento\Framework\View\Element\Context;
use Ingenico\Payment\Model\Config\Source\Ideal\Banks as BanksSource;

class Banks extends AbstractBlock implements BlockInterface
{
    /**
     * Options
     *
     * @var array
     */
    private $options = [];

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var BanksSource
     */
    private $banksSource;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        BanksSource $banksSource,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->banksSource = $banksSource;

        parent::__construct($context, $data);
    }

    /**
     * Render block HTML
     * @return string
     */
    protected function _toHtml()
    {
        $options = $this->getOptions();

        /** @var \Magento\Framework\View\Element\Html\Select $select */
        $select = $this->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select',
            'issuerid'
        );

        $select->setName('issuerid')
               ->setId('issuerid')
               ->setValue(null)
               ->setExtraParams(null)
               ->setOptions($options);

        $html = $select->getHtml();
        return $html;
    }

    /**
     * Set Options
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Get Options
     * @return array|null
     */
    public function getOptions()
    {
        if (count($this->options) === 0) {
            return $this->getAvailableBanks();
        }

        return $this->options;
    }

    /**
     * Get Available Banks.
     *
     * @return array
     */
    public function getAvailableBanks()
    {
        $banks = $this->config->getIDealBanks($this->storeManager->getStore()->getId());

        // Get Banks
        $options = $this->banksSource->toOptionArray();

        $result = [];
        foreach ($options as $option) {
            if (empty($option['value']) || in_array($option['value'], $banks)) {
                $result[] = $option;
            }
        }

        return $result;
    }
}
