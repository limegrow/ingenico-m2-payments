<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Settings;

class StoredCardsEnabled extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Ingenico\Payment\Model\Config
     */
    protected $config;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ingenico\Payment\Model\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if (!$this->config->isPaymentPageModeRedirect()) {
            // Inject custom hint
            $hint = '<span class="warning" onclick="Ogone.showModal(\'settings_inline_store_cards_content\',\''.__("modal.inline_store_cards.howto").'\');"></span>';
            return str_replace('<td class="">', '<td class="">' . $hint, parent::render($element));
        }

        return parent::render($element);
    }
}
