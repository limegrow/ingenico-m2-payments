<?php

namespace Ingenico\Payment\Plugin;

class MagentoConfigBlockSystemConfigEdit
{

    protected $_storeManager;
    protected $_adminSession;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Backend\Model\Session $adminSession,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->_storeManager = $storeManager;
        $this->_adminSession = $adminSession;
        $this->request = $request;
    }

    /**
     * Rename button from "Save" to "Submit" on Registration Page
     */
    public function afterSetLayout(\Magento\Config\Block\System\Config\Edit $subject, $result)
    {
        // rename button on registration page
        if ($this->request->getParam('section') === 'ingenico_registration') {
            $subject->getToolbar()->getChildBlock('save_button')->setLabel(__('form.support.submit'));

            // show success message after registration submit
            if ($this->_adminSession->getIngenicoRegistrationResult()) {
                $this->_adminSession->unsIngenicoRegistrationResult();
                $subject->setTemplate('Ingenico_Payment::registration_success.phtml');
            }
        }

        // rename button on support page
        if ($this->request->getParam('section') === 'ingenico_support') {
            $subject->getToolbar()->getChildBlock('save_button')->setLabel(__('form.support.submit'));
        }

        // rename button on settings import page
        if ($this->request->getParam('section') === 'ingenico_import_export') {
            $subject->getToolbar()->getChildBlock('save_button')->addData([
                'label' => __('form.support.import'),
                'on_click' => 'confirm(\'' . __('form.support.upload_confirmation') . '\')'
            ]);
        }

        return $result;
    }
}
