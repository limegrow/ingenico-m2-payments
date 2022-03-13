<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Connection;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Provides field with additional information
 */
class Webhook extends Field
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Context      $context
     * @param UrlInterface $frontUrlModel
     * @param RequestInterface $request
     * @param array        $data
     */
    public function __construct(
        Context $context,
        UrlInterface $frontUrlModel,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->urlBuilder = $frontUrlModel;
        $this->request = $request;
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $storeId = $this->request->getParam('store');
        if (!$storeId) {
            $storeId = $this->_storeManager->getStore()->getId();
        }

        $id = $element->getHtmlId();
        $link = $this->urlBuilder->getUrl('ingenico/payment/webhook', ['_nosid' => true, '_scope' => $storeId]);

        return implode('', [
            '<a href="javascript:void(0);" onclick="Ogone.copyLink(\''.$link.'\', \''.$id.'\');">'.$link.'</a>',
            '<div class="copy-response message message-success" data-copy="'.$id.'">'.__('form.connection.label.copied').'</div>',
            '<div class="field-actions">',
            '<a href="javascript:void(0);" onclick="Ogone.copyLink(\''.$link.'\', \''.$id.'\');">'.__("form.connection.button.copy_link").'</a>',
            '&nbsp;&nbsp;&nbsp;',
            '<a href="javascript:void(0);" onclick="Ogone.showModal(\'connection_webhook_content\',\''.__("modal.webhook.howto").'\');">'.__("form.connection.label.howto").'</a>',
            '</div>'
        ]);
    }
}
