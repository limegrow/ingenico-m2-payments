<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage\Redirect;

use Magento\Backend\Block\AbstractBlock;
use Magento\Backend\Block\Context;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Ingenico\Payment\Model\Connector;

class StepOne extends AbstractBlock implements RendererInterface
{
    /**
     * @var Connector
     */
    private $connector;

    /**
     * @param Context   $context
     * @param Connector $connector
     * @param array     $data
     */
    public function __construct(
        Context $context,
        Connector $connector,
        array $data = []
    ) {
        $this->connector = $connector;

        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        return implode('', [
            '<tr id="row_'.$element->getHtmlId().'">',
            '<td class="label"></td>',
            '<td class="value">',
            '<h4 style="padding:0;">'.$element->getLabel().'</h4>',
            '<p>',
            '<a target="_blank" href="' . $this->getWhiteLabelsData()->getTemplateGuidEcom() . '">'.__('form.payment_page.label.readmore').'</a>',
            '</p>',
            '</td>',
            '<td class=""></td></tr>'
        ]);
    }

    /**
     * @return \IngenicoClient\WhiteLabels
     */
    private function getWhiteLabelsData()
    {
        return $this->connector->getCoreLibrary()->getWhiteLabelsData();
    }
}
