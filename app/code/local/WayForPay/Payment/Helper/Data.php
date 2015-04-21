<?php

/*
 * WayForPay payment module
 */
class WayForPay_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**  */
    const SIGNATURE_SEPARATOR = ';';
    /** @var array */
    protected $keysForResponseSignature = array(
        'merchantAccount',
        'orderReference',
        'amount',
        'currency',
        'authCode',
        'cardPan',
        'transactionStatus',
        'reasonCode'
    );

    /** @var array */
    protected $keysForSignature = array(
        'merchantAccount',
        'merchantDomainName',
        'orderReference',
        'orderDate',
        'amount',
        'currency',
        'productName',
        'productCount',
        'productPrice'
    );


    /**
     * @param $option
     * @param $keys
     * @return string
     */
    public function getSignature($option, $keys)
    {
        $hash = array();
        foreach ($keys as $dataKey) {
            if (!isset($option[$dataKey])) {
                continue;
            }
            if (is_array($option[$dataKey])) {
                foreach ($option[$dataKey] as $v) {
                    $hash[] = $v;
                }
            } else {
                $hash [] = $option[$dataKey];
            }
        }
        $hash = implode(self::SIGNATURE_SEPARATOR, $hash);

        $secret = Mage::getModel('wayforpay_payment/wayforpay')->getConfigData('secret_key');
        return hash_hmac('md5', $hash, $secret);
    }

    /**
     * @param $options
     * @return string
     */
    public function getRequestSignature($options)
    {
        return $this->getSignature($options, $this->keysForSignature);
    }

    /**
     * @param $options
     * @return string
     */
    public function getResponseSignature($options)
    {
        return $this->getSignature($options, $this->keysForResponseSignature);
    }

    /**
     * @param $order
     * @return string
     */
    public function getAnswerToGateWay($order)
    {
        $time = time();
        $responseToGateway = array(
            'orderReference' => $order->getId(),
            'status' => 'accept',
            'time' => $time
        );
        $sign = array();
        foreach ($responseToGateway as $dataKey => $dataValue) {
            $sign [] = $dataValue;
        }
        $sign = implode(self::SIGNATURE_SEPARATOR, $sign);
        $sign = hash_hmac('md5', $sign, Mage::getModel('wayforpay_payment/wayforpay')->getConfigData('secret_key'));
        $responseToGateway['signature'] = $sign;

        return json_encode($responseToGateway);
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return Mage::getStoreConfig('payment/wayforpay_payment/url');
    }
}
