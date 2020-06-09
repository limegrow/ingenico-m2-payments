<?php

namespace Ingenico\Payment\Plugin;

class MagentoTranslationModelResourceModelTranslate
{
    protected $_storeManager;
    protected $_connector;
    protected $_cnf;
    protected $_moduleDir;
    protected $_translatorFactory;
    protected $_poFileLoader;
    protected $_dirReader;

    protected $_locale;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ingenico\Payment\Model\Config $cnf,
        \Ingenico\Payment\Model\Connector $connector,
        \Magento\Framework\Module\Dir $moduleDir,
        \Symfony\Component\Translation\TranslatorFactory $translatorFactory,
        \Symfony\Component\Translation\Loader\PoFileLoader $poFileLoader,
        \Magento\Framework\Filesystem\Driver\File $dirReader
    ) {
        $this->_storeManager = $storeManager;
        $this->_connector = $connector;
        $this->_cnf = $cnf;
        $this->_moduleDir = $moduleDir;
        $this->_translatorFactory = $translatorFactory;
        $this->_poFileLoader = $poFileLoader;
        $this->_dirReader = $dirReader;
    }

    public function beforeGetTranslationArray(\Magento\Translation\Model\ResourceModel\Translate $subject, $storeId = null, $locale = null)
    {
        $this->_locale = $locale;
        return [$storeId, $locale];
    }

    /**
     * Interceptor for merging translations from PO files to Magento cached translations
     */
    public function afterGetTranslationArray(\Magento\Translation\Model\ResourceModel\Translate $subject, $result)
    {
        $defaultLocale = 'en_US';
        if (!in_array($this->_locale, $this->_cnf->getAvailableLocalisations())) {
            $this->_locale = $defaultLocale;
        }

        $translator = $this->_translatorFactory->create(['locale' => $this->_locale]);
        $translator->addLoader('po', $this->_poFileLoader);
        $translator->setFallbackLocales([$defaultLocale]);

        // Load translations
        $moduleBaseDir = $this->_moduleDir->getDir('Ingenico_Payment');
        $directory = $moduleBaseDir . '/po';
        $files = $this->_dirReader->readDirectoryRecursively($directory);
        foreach ($files as $file) {
            $file = $directory . DIRECTORY_SEPARATOR . $file;
            $info = pathinfo($file);
            if ($info['extension'] !== 'po') {
                continue;
            }

            $filename = $info['filename'];
            list($domain, $locale) = explode('.', $filename);
            $translator->addResource('po', $directory . DIRECTORY_SEPARATOR . $info['basename'], $locale, $domain);
        }

        $allTranslations = $translator->getCatalogue($this->_locale)->all();

        foreach ($allTranslations as $pairs) {
            $result = array_merge($pairs, $result);
        }

        return $result;
    }
}
