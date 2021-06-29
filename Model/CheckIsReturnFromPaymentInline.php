<?php

declare(strict_types=1);

namespace Ingenico\Payment\Model;

use Magento\Checkout\Model\Session as CheckoutSession;

class CheckIsReturnFromPaymentInline
{
    const PROCESS_PAYMENT_INLINE_FLAG_KEY = 'process_payment_inline_flag';

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * CheckIsReturnFromPaymentInline constructor.
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Check current request is coming from ingenico/payment/inline.
     *
     * @return bool
     */
    public function isRequestFromPaymentInlinePage(): bool
    {
        return (bool)$this->checkoutSession->getData(self::PROCESS_PAYMENT_INLINE_FLAG_KEY, true);
    }
}
