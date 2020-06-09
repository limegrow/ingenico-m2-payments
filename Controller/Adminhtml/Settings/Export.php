<?php

namespace Ingenico\Payment\Controller\Adminhtml\Settings;

use Magento\Framework\App\Filesystem\DirectoryList;

class Export extends \Magento\Backend\App\Action
{
    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var array
     */
    protected $_publicActions = ['index'];

    protected $_fileFactory;
    protected $_cnf;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Ingenico\Payment\Model\Config $cnf
    ) {
        $this->_fileFactory = $fileFactory;
        $this->_cnf = $cnf;
        parent::__construct($context);
    }

    public function execute()
    {
        $fileName = sprintf('settings_%s_%s.json', $this->_cnf->getBaseHost(), date('dmY_H_i_s'));
        return $this->_fileFactory->create($fileName, $this->_cnf->exportSettingsJson(), DirectoryList::VAR_DIR);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ingenico_Payment::config_ingenico');
    }
}
