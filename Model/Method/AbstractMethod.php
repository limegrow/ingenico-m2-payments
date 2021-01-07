<?php

namespace Ingenico\Payment\Model\Method;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment\Transaction;

class AbstractMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_isGateway = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canCancelInvoice = true;
    protected $_canFetchTransactionInfo = true;
    protected $_isInitializeNeeded = true;
    protected $_canUseForMultishipping = true;

    /**
     * @var string
     */
    protected $_formBlockType = \Ingenico\Payment\Block\Form\Method::class;

    /**
     * @var string
     */
    protected $_infoBlockType = \Ingenico\Payment\Block\Info\Method::class;

    /**
     * @var \Ingenico\Payment\Model\Config
     */
    protected $cnf;

    /**
     * @var \Ingenico\Payment\Model\Connector
     */
    protected $connector;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Ingenico\Payment\Model\Connector $connector,
        \Ingenico\Payment\Model\Config $cnf,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );

        $this->connector = $connector;
        $this->cnf = $cnf;
        $this->registry = $registry;
    }

    /**
     * Is active
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isActive($storeId = null)
    {
        if (!$this->cnf->isExtensionConfigured()) {
            return false;
        }

        return parent::isActive($storeId);
    }

    /**
     * @return mixed|false
     */
    public function getCoreLibraryMethodInstance()
    {
        // Payment method with filled data is in saved registry
        $inlineData = $this->_registry->registry($this->connector::REGISTRY_KEY_TEMPLATE_VARS_INLINE);
        if (!empty($inlineData) && isset($inlineData[$this->connector::PARAM_NAME_METHODS][static::CORE_CODE])) {
            return $inlineData[$this->connector::PARAM_NAME_METHODS][static::CORE_CODE];
        }

        // Default payment method
        if (isset($this->connector->getPaymentMethods()[static::CORE_CODE])) {
            return $this->connector->getPaymentMethods()[static::CORE_CODE];
        }

        return false;
    }

    /**
     * Capture
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('ingenico.exception.message6'));
        }

        $payment->setAmount($amount);

        // only execute payment capture if invoice created from Admin with "online capture" mode
        if (!$this->_registry->registry('current_invoice')) {
            return $this;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // Convert amount if currency is different
        if ($order->getBaseCurrencyCode() !== $order->getOrderCurrencyCode()) {
            $amount = round($amount * $order->getBaseToOrderRate(), 2);
        }

        try {
            $trxId = $this->connector->getIngenicoPayIdByOrderId($order->getIncrementId());
            $this->connector->setOrderId($order->getIncrementId());
            $result = $this->connector->getCoreLibrary()->capture($order->getIncrementId(), $trxId, $amount);
            if ($result->getPaymentStatus() == $this->connector->getCoreLibrary()::STATUS_CAPTURE_PROCESSING) {
                $payment->setIsTransactionPending(true);
            }
            $payment
                ->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($result->getPayId() . '-' . $result->getPayIdSub())
                ->setIsTransactionClosed(0)
                ->setAdditionalInformation(Transaction::RAW_DETAILS, $result->getData());
        } catch (\Exception $e) {
            $this->connector->log($e->getMessage(), 'crit');
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('ingenico.exception.message7'));
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // Convert amount if currency is different
        if ($order->getBaseCurrencyCode() !== $order->getOrderCurrencyCode()) {
            $amount = round($amount * $order->getBaseToOrderRate(), 2);
        }

        try {
            $payId = $this->connector->getIngenicoPayIdByOrderId($order->getIncrementId());
            $this->connector->setOrderId($order->getIncrementId());

            // Has it been paid with "Bank transfer"?
            $result = $this->connector->getCoreLibrary()->getPaymentInfo($order->getIncrementId(), $payId);
            if (mb_strpos($result->getPm(), 'Bank transfer', null, 'UTF-8') !== false) {
                throw new LocalizedException(__('modal.refund_failed.not_refundable', $result->getPm()));
            }

            $result = $this->connector->getCoreLibrary()->refund($order->getIncrementId(), $payId, $amount);
            $trxId = $result->getPayId() . '-' . $result->getPayIdSub();

            // Add Credit Transaction
            $payment->setAnetTransType(Transaction::TYPE_REFUND)
                    ->setAmount($amount)
                    ->setTransactionId($trxId)
                    ->setIsTransactionClosed(0)
                    ->setAdditionalInformation(Transaction::RAW_DETAILS, $result->getData());

            switch ($result->getPaymentStatus()) {
                case $this->connector->getCoreLibrary()::STATUS_REFUND_PROCESSING:
                    $payment->setIsTransactionPending(true);

                    break;
                case $this->connector->getCoreLibrary()::STATUS_REFUNDED:
                    $payment->setStatus(self::STATUS_APPROVED);

                    break;
            }
        } catch (LocalizedException $e) {
            throw new LocalizedException(__($e->getMessage()));
        } catch (\IngenicoClient\Exception $e) {
            $this->connector->log($e->getMessage(), 'crit');

            $msg = __('modal.refund_failed.label1');
            $msg .= ' '.__('modal.refund_failed.label2') . ' ' . $this->connector->getCoreLibrary()->getWhiteLabelsData()->getSupportEmail();
            $msg .= ' '.__('modal.refund_failed.label3') . ' ' . $this->connector->getCoreLibrary()->getWhiteLabelsData()->getSupportUrl();

            throw new LocalizedException(__($msg));
        }

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     * @throws LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        try {
            $trxId = $this->connector->getIngenicoPayIdByOrderId($order->getIncrementId());
            $this->connector->setOrderId($order->getIncrementId());
            $result = $this->connector->getCoreLibrary()->cancel($order->getIncrementId(), $trxId, null);

            // Add Cancel Transaction
            $payment->setStatus(self::STATUS_DECLINED)
                    ->setTransactionId($result->getPayId() . '-' . $result->getPayIdSub())
                    ->setIsTransactionClosed(1)
                    ->setAdditionalInformation(Transaction::RAW_DETAILS, $result);
        } catch (\Exception $e) {
            $this->connector->log($e->getMessage(), 'crit');

            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Fetch transaction info
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param string $transactionId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
    {
        $trxData = $this->connector->getIngenicoPaymentById($transactionId);
        if (!$trxData) {
            throw new LocalizedException(__('Unable to retrieve data.'));
        }

        $data = $this->connector->getCoreLibrary()->getPaymentInfo($trxData['order_id'], $transactionId);
        if (!$data->isTransactionSuccessful()) {
            throw new LocalizedException(__('Unable to retrieve online data.'));
        }

        return $data->getData();
    }
}
