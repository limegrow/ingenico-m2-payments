<?php

namespace Ingenico\Payment\Plugin;

class MagentoConfigControllerAdminhtmlSystemConfigSave
{
    const PARAM_NAME_VALUE = 'value';

    protected $_storeManager;
    protected $_resultRedirectFactory;
    protected $_adminSession;
    protected $_messageManager;
    protected $_connector;
    protected $_cnf;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\Request
     */
    protected $request;

    protected $_redirect = true;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Backend\Model\Session $adminSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Ingenico\Payment\Model\Connector $connector,
        \Ingenico\Payment\Model\Config $cnf,
        \Magento\Framework\HTTP\PhpEnvironment\Request $request
    ) {
        $this->_storeManager = $storeManager;
        $this->_resultRedirectFactory = $resultRedirectFactory;
        $this->_adminSession = $adminSession;
        $this->_messageManager = $messageManager;
        $this->_connector = $connector;
        $this->_cnf = $cnf;
        $this->request = $request;
    }

    /**
     * Intercept request, skip config save and execute custom logic
     */
    public function aroundExecute(\Magento\Config\Controller\Adminhtml\System\Config\Save $subject, callable $proceed)
    {
        $section = $subject->getRequest()->getParam('section');
        if (!in_array($section, [
            'ingenico_registration',
            'ingenico_connection',
            'ingenico_payment_methods',
            'ingenico_support',
            'ingenico_import_export'
        ])) {
            return $proceed();
        }

        switch ($section) {
            case 'ingenico_registration':
                $this->_processRegistrationRequest($subject);
                break;
            case 'ingenico_connection':
                $this->_processConnectionRequest($subject);
                break;
            case 'ingenico_payment_methods':
                $this->_processPaymentMethodsRequest($subject);
                break;
            case 'ingenico_support':
                $this->_processSupportRequest($subject);
                break;
            case 'ingenico_import_export':
                $this->_processSettingsImport($subject);
                break;
        }

        if ($this->_redirect) {
            return $this->_resultRedirectFactory->create()->setRefererUrl();
        }

        return $proceed();
    }

    /**
     * Send registration request to Ingenico
     *
     * @param \Magento\Config\Controller\Adminhtml\System\Config\Save $subject
     */
    protected function _processRegistrationRequest($subject)
    {
        $data = $subject->getRequest()->getParam('groups');
        $data = $data['register']['fields'];

        $company = $data['company_name'][self::PARAM_NAME_VALUE];
        $country = $data['country'][self::PARAM_NAME_VALUE];
        $email = $data['email'][self::PARAM_NAME_VALUE];

        try {
            // send data to Ingenico here
            $this->_connector->submitOnboardingRequest($company, $email, $country);

            // record parameter in session to replace template
            $this->_adminSession->setIngenicoRegistrationResult(true);

            // Clean up
            $this->_cnf->deleteConfig('ingenico_registration/register/company_name');
            $this->_cnf->deleteConfig('ingenico_registration/register/company_country');
            $this->_cnf->deleteConfig('ingenico_registration/register/email');
        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage(__($e->getMessage()));
        }
    }

    /**
     * Prevent empty credentials on Connection section
     *
     * @param \Magento\Config\Controller\Adminhtml\System\Config\Save $subject
     */
    protected function _processConnectionRequest($subject)
    {
        $this->_redirect = false;
        $data = $subject->getRequest()->getParam('groups');
        $mode = $data['mode']['fields']['mode'][self::PARAM_NAME_VALUE];
        $pspid = $data[$mode]['fields']['pspid'][self::PARAM_NAME_VALUE];
        $signature = $data[$mode]['fields']['signature'][self::PARAM_NAME_VALUE];
        $user = $data[$mode]['fields']['user'][self::PARAM_NAME_VALUE];
        $password = $data[$mode]['fields']['password'][self::PARAM_NAME_VALUE];

        if (empty($pspid)) {
            $this->_messageManager->addErrorMessage(__('ingenico.notification.message11'));
            $this->_redirect = true;
        }

        if (empty($signature)) {
            $this->_messageManager->addErrorMessage(__('ingenico.notification.message12'));
            $this->_redirect = true;
        }

        if (empty($user)) {
            $this->_messageManager->addErrorMessage(__('ingenico.notification.message13'));
            $this->_redirect = true;
        }

        if (empty($password)) {
            $this->_messageManager->addErrorMessage(__('ingenico.notification.message14'));
            $this->_redirect = true;
        }
    }

    /**
     * Prevent more than one Open Invoice provider being enabled
     *
     * @param \Magento\Config\Controller\Adminhtml\System\Config\Save $subject
     */
    protected function _processPaymentMethodsRequest($subject)
    {
        $this->_redirect = false;
        $data = $subject->getRequest()->getParam('groups');
        $data = $data['methods']['groups'];
        $count = 0;

        if (isset($data['open_invoice'])) {
            foreach ($data['open_invoice']['groups'] as $openInvoiceMethod) {
                $count += $openInvoiceMethod['fields']['enabled']['value'];
            }
        }

        if ($count > 1) {
            $this->_messageManager->addErrorMessage(__('ingenico.notification.message9'));
            $this->_messageManager->addErrorMessage(__('ingenico.notification.message10'));
            $this->_redirect = true;
        }
    }

    /**
     * Send support request to Ingenico
     *
     * @param \Magento\Config\Controller\Adminhtml\System\Config\Save $subject
     */
    protected function _processSupportRequest($subject)
    {
        $data = $subject->getRequest()->getParam('groups');
        $data = $data['config_assistance']['fields'];

        $ticket = $data['ticket'][self::PARAM_NAME_VALUE];
        $email = $data['email'][self::PARAM_NAME_VALUE];
        $description = $data['message'][self::PARAM_NAME_VALUE];

        // prepare file with settings
        $fileName = sprintf('settings_%s_%s.json', $this->_cnf->getBaseHost(), date('dmY_H_i_s'));
        if ($ticket) {
            $fileName = sprintf('settings_%s_%s_%s.json', $this->_cnf->getBaseHost(), $ticket, date('dmY_H_i_s'));
        }
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
        file_put_contents($filePath, $this->_cnf->exportSettingsJson());

        // prepare subject
        $subject = sprintf('%s: Issues configuring the site %s', $this->_connector->requestShoppingCartExtensionId(), $this->_cnf->getBaseHost());
        if ($ticket) {
            $subject = sprintf('Exported settings related to the ticket [%s]', $ticket);
        }

        // send email
        $result = $this->_connector->sendSupportEmail(
            $email,
            $subject,
            [
                'ticket' => $ticket,
                'description' => $description
            ],
            $filePath
        );

        // remove temporary file
        unlink($filePath);

        if ($result) {
            $this->_messageManager->addSuccessMessage(__('form.support.validation.mail_sent'));
        } else {
            $this->_messageManager->addErrorMessage(__('form.support.validation.mail_failed'));
        }
    }

    /**
     * Import settings from file
     *
     * @param \Magento\Config\Controller\Adminhtml\System\Config\Save $subject
     */
    protected function _processSettingsImport($subject)
    {
        try {
            $files = $this->request->getFiles()->toArray();

            if (!isset($files['groups']['tmp_name']['import']['fields']['import_settings']['value'])) { //@codingStandardsIgnoreLine
                throw new \Exception('Failed uploading file');
            }

            $content = file_get_contents($files['groups']['tmp_name']['import']['fields']['import_settings']['value']); //@codingStandardsIgnoreLine
            $this->_cnf->importSettingsJson($content);

            $this->_messageManager->addSuccessMessage('Settings successfully imported!');

        } catch (\Exception $e) {
            $this->_messageManager->addErrorMessage(__($e->getMessage()));
        }
    }
}
