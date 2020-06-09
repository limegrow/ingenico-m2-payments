<?php

namespace Ingenico\Payment\Block\Adminhtml\System\Config;

class HelpContent extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Ingenico\Payment\Model\Config
     */
    protected $cnf;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Ingenico\Payment\Model\Config $cnf,
        array $data = []
    ) {
        $this->_assetRepo = $assetRepo;
        $this->cnf = $cnf;

        parent::__construct($context, $data);
    }

    public function getImageUrl($imgName)
    {
        return $this->_assetRepo->getUrl('Ingenico_Payment::images/help_images/'.$imgName);
    }

    /**
     * Check if there's test mode
     *
     * @return bool
     */
    public function isTestMode()
    {
        return $this->cnf->getMode(true) === false;
    }
}
