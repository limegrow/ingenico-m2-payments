<?php

declare(strict_types=1);

namespace Ingenico\Payment\Plugin;

use Ingenico\Payment\Model\CheckIsReturnFromPaymentInline;
use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

class RestoreQuoteAfterIngenicoPaymentInlinePage extends CheckIsReturnFromPaymentInline
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * RestoreQuoteAfterIngenicoPaymentInlinePage constructor.
     * @param UrlInterface $url
     * @param RequestInterface $request
     * @param Session $checkoutSession
     */
    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        Session $checkoutSession
    ) {
        $this->url = $url;
        $this->request = $request;
        parent::__construct($checkoutSession);
    }

    /**
     * Restore Shopping Cart when click to the logo on the inline payment page.
     *
     * @param Cart $subject
     * @return array
     */
    public function beforeGetSectionData(Cart $subject): array
    {
        $refererUri = (string)$this->request->getServer('HTTP_REFERER');

        if ($refererUri === $this->url->getUrl('') && $this->isRequestFromPaymentInlinePage()) {
             $this->checkoutSession->restoreQuote();
        }

        return [$subject];
    }
}
