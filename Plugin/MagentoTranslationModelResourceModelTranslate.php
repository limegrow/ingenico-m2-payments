<?php

namespace Ingenico\Payment\Plugin;

use Magento\Framework\Module\Dir;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Translation\Model\ResourceModel\Translate;
use Symfony\Component\Translation\TranslatorFactory;
use Symfony\Component\Translation\Loader\PoFileLoader;

class MagentoTranslationModelResourceModelTranslate
{
    const LOCALES = ['en_US', 'fr_FR', 'de_DE', 'nl_NL', 'it_IT', 'es_ES', 'pt_PT'];

    /**
     * @var Dir
     */
    private $moduleDir;

    /**
     * @var TranslatorFactory
     */
    private $translatorFactory;

    /**
     * @var PoFileLoader
     */
    private $poFileLoader;

    /**
     * @var File
     */
    private $dirReader;

    /**
     * @var string|null
     */
    private $locale;

    /**
     * Constructor.
     *
     * @param Dir $moduleDir
     * @param TranslatorFactory $translatorFactory
     * @param PoFileLoader $poFileLoader
     * @param File $dirReader
     */
    public function __construct(
        Dir $moduleDir,
        TranslatorFactory $translatorFactory,
        PoFileLoader $poFileLoader,
        File $dirReader
    ) {

        $this->moduleDir = $moduleDir;
        $this->translatorFactory = $translatorFactory;
        $this->poFileLoader = $poFileLoader;
        $this->dirReader = $dirReader;
    }

    /**
     * @param Translate $subject
     * @param mixed $storeId
     * @param mixed $locale
     *
     * @return array
     */
    public function beforeGetTranslationArray(Translate $subject, $storeId = null, $locale = null)
    {
        $this->locale = $locale;
        return [$storeId, $locale];
    }

    /**
     * Interceptor for merging translations from PO files to Magento cached translations
     */
    public function afterGetTranslationArray(Translate $subject, $result)
    {
        $defaultLocale = 'en_US';
        if (!in_array($this->locale, self::LOCALES)) {
            $this->locale = $defaultLocale;
        }

        $translator = $this->translatorFactory->create(['locale' => $this->locale]);
        $translator->addLoader('po', $this->poFileLoader);
        $translator->setFallbackLocales([$defaultLocale]);

        // Load translations
        $moduleBaseDir = $this->moduleDir->getDir('Ingenico_Payment');
        $directory = $moduleBaseDir . '/po';
        $files = $this->dirReader->readDirectoryRecursively($directory);
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

        $allTranslations = $translator->getCatalogue($this->locale)->all();
        foreach ($allTranslations as $pairs) {
            $result = array_merge($pairs, $result);
        }

        return $result;
    }
}
