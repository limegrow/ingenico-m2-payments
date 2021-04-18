<?php

namespace Ingenico\Payment\Block\Ideal;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Payment\Model\Config;

class Banks extends AbstractBlock implements BlockInterface
{
    /**
     * Options
     *
     * @var array
     */
    private $options = [];

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
        // @codingStandardsIgnoreStart
        /** @var \Magento\Framework\ObjectManagerInterface $om */
        $om = ObjectManager::getInstance();

        /** @var \Ingenico\Payment\Model\Config $config */
        $config = $om->get(Config::class);

        /** @var \Magento\Store\Model\StoreManagerInterface $storeManger */
        $storeManger = $om->get(StoreManagerInterface::class);

        $banks = $config->getIDealBanks($storeManger->getStore()->getId());

        /** @var \Ingenico\Payment\Model\Config\Source\Ideal\Banks $source */
        $source = $om->get(\Ingenico\Payment\Model\Config\Source\Ideal\Banks::class);
        // @codingStandardsIgnoreEnd

        // Get Banks
        $options = $source->toOptionArray();

        $result = [];
        foreach ($options as $option) {
            if (empty($option['value']) || in_array($option['value'], $banks)) {
                $result[] = $option;
            }
        }

        return $result;
    }
}
