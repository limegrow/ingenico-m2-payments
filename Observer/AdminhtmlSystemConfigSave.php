<?php

namespace Ingenico\Payment\Observer;

use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AdminhtmlSystemConfigSave implements ObserverInterface
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * Constructor.
     *
     * @param CacheManager $cacheManager
     */
    public function __construct(
        CacheManager $cacheManager
    ) {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $request = $observer->getData('request');

        if ($request && 'payment' === $request->getParam('section')) {
            // Clean the cache automatically
            $this->cacheManager->clean(['config', 'full_page']);
        }
    }
}
