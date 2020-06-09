<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage\Inline;

class StepThree extends \Magento\Backend\Block\AbstractBlock implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    protected $_config;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Ingenico\Payment\Model\Config $config,
        $data = []
    ) {
        $this->_config = $config;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<h4 style="padding:0;">'.$element->getLabel().'</h4>',
            '<p>',
            __('form.payment_page.label.upload_template'),
            ' <a target="_blank" href="'.$this->_config->getTemplateManagerUrl().'">',
            __('form.payment_page.label.template_manager'),
            '</a>',
            '<span class="hint modal-link" data-modal-id="payment_templatemanagerinfo_inline_content" data-modal-title="'.__('modal.template.inline.howto').'"></span>',
            '</p>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }
}
