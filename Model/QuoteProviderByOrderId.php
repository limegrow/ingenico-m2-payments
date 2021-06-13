<?php

declare(strict_types=1);

namespace Ingenico\Payment\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote;

class QuoteProviderByOrderId
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var Quote
     */
    private $quoteResourceModel;

    /**
     * QuoteProviderByOrderId constructor.
     *
     * @param QuoteFactory $quoteFactory
     * @param Quote        $quoteResourceModel
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        Quote $quoteResourceModel
    )
    {
        $this->quoteFactory = $quoteFactory;
        $this->quoteResourceModel = $quoteResourceModel;
    }

    /**
     * Load Quote by reserved_order_id field.
     *
     * @param mixed $reservedOrderId
     *
     * @return CartInterface
     */
    public function execute($reservedOrderId) : CartInterface
    {
        $quote = $this->quoteFactory->create();
        $this->quoteResourceModel->load(
            $quote, $reservedOrderId,'reserved_order_id'
        );

        return $quote;
    }

}
