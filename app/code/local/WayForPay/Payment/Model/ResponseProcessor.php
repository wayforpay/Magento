<?php

/**
 * Class WayForPay_Payment_Model_ResponseProcessor
 *
 * Represents a response from WayForPay gateway. Processes the data and make all changes needed.
 * Generates an answer to gateway.
 */
class WayForPay_Payment_Model_ResponseProcessor
{
    const TRANSACTIONSTATUS_APPROVED = 'approved';
    const TRANSACTIONSTATUS_DECLINED = 'declined';

    /** @var array of input data */
    protected $_data = array();

    /** @var Mage_Sales_Model_Order */
    protected $_order;

    /** @var Mage_Payment_Model_Method_Abstract */
    protected $_paymentMethod;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        if (isset($parameters['data'])) {
            $this->_data = $parameters['data'];
        }
        if (isset($parameters['paymentMethod'])) {
            $this->_paymentMethod = $parameters['paymentMethod'];
        }

        $this->_validateInputData();

        $this->_order = $this->_getOrder($this->_data['orderReference']);
    }

    /**
     * Process a response and return an answer to the WayForPay gateway
     * @return string
     * @throws InvalidArgumentException
     */
    public function getResponseJson()
    {
        switch (strtolower($this->_data['transactionStatus'])) {
            case self::TRANSACTIONSTATUS_APPROVED:
                $this->_processApproved();
                break;
            case self::TRANSACTIONSTATUS_DECLINED:
                $this->_processDeclined();
                break;
            default:
                throw new InvalidArgumentException("Unknown transactionStatus: {$this->_data['transactionStatus']}");
        }

        return $this->_getAnswerToGateWay();
    }

    /**
     * @return string JSON encoded answer to the WayForPay gateway
     */
    protected function _getAnswerToGateWay()
    {
        $responseToGateway = array(
            'orderReference' => $this->_order->getIncrementId(),
            'status' => 'accept',
            'time' => time()
        );
        $sign = array();
        foreach ($responseToGateway as $dataKey => $dataValue) {
            $sign[] = $dataValue;
        }
        $sign = implode(WayForPay_Payment_Helper_Data::SIGNATURE_SEPARATOR, $sign);
        $sign = hash_hmac('md5', $sign, $this->_paymentMethod->getConfigData('secret_key'));
        $responseToGateway['signature'] = $sign;

        return json_encode($responseToGateway);
    }

    /**
     * Handle Approved answer
     *
     * @throws Exception
     */
    protected function _processApproved()
    {
        $state = $this->_paymentMethod->getConfigData('after_pay_status');
        $this->_order->setStatus($state)
            ->addStatusHistoryComment('WayForPay returned an Approved status.');
        $this->_order->save();
    }

    /**
     * Handle Declined answer
     *
     * @throws Exception
     */
    protected function _processDeclined()
    {
        $this->_order->cancel()
            ->addStatusHistoryComment(
                "WayForPay returned a Declined status. Code: #{$this->_data['reasonCode']}. Reason: {$this->_data['reason']}",
                Mage_Sales_Model_Order::STATE_CANCELED
            );
        $this->_order->save();
    }

    /**
     * Validate an input data
     *
     * @throws InvalidArgumentException
     */
    protected function _validateInputData()
    {
        $sign = $this->_getHelper()->getResponseSignature($this->_data);
        if (empty($this->_data['merchantSignature']) || $this->_data['merchantSignature'] != $sign) {
            throw new InvalidArgumentException("Wrong merchantSignature ({$this->_data['merchantSignature']} != $sign)");
        }

        if (!isset($this->_data['orderReference']) || empty($this->_data['orderReference'])) {
            throw new InvalidArgumentException('Missing orderReference parameter');
        }

        if (!isset($this->_data['transactionStatus']) || empty($this->_data['transactionStatus'])) {
            throw new InvalidArgumentException('Missing transactionStatus parameter');
        }
    }

    /**
     * Get an order object by order increment ID
     *
     * @param $orderReference
     * @return Mage_Sales_Model_Order
     * @throws InvalidArgumentException
     */
    protected function _getOrder($orderReference)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderReference);
        if (!$order || !$order->getId()) {
            throw new InvalidArgumentException('No order found');
        }

        return $order;
    }

    /**
     * @return WayForPay_Payment_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('wayforpay_payment');
    }
}

