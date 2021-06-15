<?php

namespace Ingenico\Payment\Model;

use Ingenico\Payment\Api\ServiceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class Service implements ServiceInterface
{
    const PARAM_NAME_ALIAS = 'alias';

    /**
     * Service constructor.
     *
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param AliasFactory                  $aliasFactory
     */
    public function __construct(
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Ingenico\Payment\Model\AliasFactory $aliasFactory
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->aliasFactory = $aliasFactory;
    }

    /**
     * Remove Alias.
     *
     * @api
     * @param string $alias
     * @return void
     * @throws CouldNotSaveException
     */
    public function removeAlias($alias)
    {
        /** @var \Ingenico\Payment\Model\Alias $alias */
        $aliasObj = $this->aliasFactory->create()->load($alias, self::PARAM_NAME_ALIAS);

        try {
            if ($aliasObj->getId()) {
                if ((int) $aliasObj->getCustomerId() !== $this->checkoutHelper->getQuote()->getCustomerId()) {
                    throw new CouldNotSaveException(__('Access error.'));
                }

                $aliasObj->delete();
            }
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }
}
