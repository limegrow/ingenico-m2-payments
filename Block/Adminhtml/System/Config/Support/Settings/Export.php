<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config\Support\Settings;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\UrlInterface;

/**
 * Provides field with additional information
 */
class Export extends Field
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var BackendUrlInterface
     */
    private $backendUrlBuilder;

    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        BackendUrlInterface $backendUrlBuilder,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->backendUrlBuilder = $backendUrlBuilder;

        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $id = $element->getHtmlId();
        $link = $this->getUrlPath('ingenico/settings/export', ['_scope' => 0]);
        return implode('', [
            '<a href="' . $link . '">' . __('form.support.download_settings') . '</a>',
        ]);
    }

    private function getUrlPath($path, $params = [])
    {
        $defaultParams = ['_nosid' => true, '_scope' => $this->getStoreId()];
        $params = array_merge($defaultParams, $params);

        if ($params['_scope'] == 0) {
            unset($params['_scope']);
            return $this->backendUrlBuilder->getUrl($path, $params);
        }

        return $this->urlBuilder->getUrl($path, $params);
    }
}
