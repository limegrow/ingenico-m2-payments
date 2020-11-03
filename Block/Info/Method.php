<?php

namespace Ingenico\Payment\Block\Info;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order\Payment\Transaction;

class Method extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Ingenico_Payment::info/method.phtml';

    /**
     * @var array
     */
    protected $transactionFields = [
        'Payment Method' => ['pm'],
        'Brand' => ['brand'],
        'Card number' => ['card_no'],
        'Payment ID' => ['pay_id'],
        'Status' => ['status'],
        'Customer Name' => ['cn'],
    ];

    /**
     * @var \Ingenico\Payment\Model\Connector
     */
    private $connector;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Ingenico\Payment\Model\Connector $connector,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        Template\Context $context,
        array $data = []
    ) {

        parent::__construct($context, $data);
        $this->connector = $connector;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Get some specific information in format of array($label => $value)
     *
     * @return array
     */
    public function getSpecificInformation()
    {
        // Get Payment Info
        /** @var \Magento\Payment\Model\Info $info */
        $info = $this->getInfo();
        if ($info) {
            $paymentId = $this->connector->getIngenicoPayIdByOrderId($info->getOrder()->getIncrementId());

            if ($paymentId) {
                $payment = $info->getOrder()->getPayment();

                try {
                    $transactionData = $payment->getMethodInstance()->fetchTransactionInfo($payment, $paymentId);
                } catch (\Exception $e) {
                    return $this->_prepareSpecificInformation()->getData();
                }

                // Filter empty values
                $transactionData = array_filter($transactionData, 'strlen');

                $result = [];
                foreach ($this->transactionFields as $description => $list) {
                    foreach ($list as $key => $value) {
                        if (isset($transactionData[$value])) {
                            $result[$description] = $transactionData[$value];
                        }
                    }
                }

                return $result;
            }
        }

        return $this->_prepareSpecificInformation()->getData();
    }

    /**
     * Render as PDF
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Ingenico_Payment::info/pdf/method.phtml');
        return $this->toHtml();
    }
}
