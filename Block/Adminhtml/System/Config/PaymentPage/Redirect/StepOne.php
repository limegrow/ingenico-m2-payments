<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\PaymentPage\Redirect;

use Magento\Backend\Block\AbstractBlock;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Ingenico\Payment\Model\Connector;

class StepOne extends AbstractBlock implements RendererInterface
{
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
     * @SuppressWarnings(MEQP2.Classes.ObjectManager.ObjectManagerFound)
     */
    private function getWhiteLabelsData()
    {
        $connector = ObjectManager::getInstance()->create(Connector::class);
        return $connector->getCoreLibrary()->getWhiteLabelsData();
    }
}
