<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config;

class Countries extends \Magento\Backend\Block\Template
{
    protected $_connector;
    protected $_request;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Ingenico\Payment\Model\Connector $connector,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        $this->_connector = $connector;
        $this->_request = $request;
        parent::__construct($context, $data);
    }


    public function getCountries()
    {
        return $this->_connector->getAllCountries();
    }
}
