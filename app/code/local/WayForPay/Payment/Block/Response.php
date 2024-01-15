<?php
/*
 * WayForPay payment module
 */

class WayForPay_Payment_Block_Response extends Mage_Core_Block_Abstract
{
    /**
     * @return false|mixed
     */
    protected function _toHtml()
    {
        $model = Mage::getModel('wayforpay_payment/wayForPay');
        $data = json_decode(file_get_contents("php://input"), true);
        $orderReference = isset($data['orderReference']) ? $data['orderReference'] : null;
        $order = $this->loadOrderByReference($orderReference);

        if ($order && $order->getId()) {
            return $this->processOrder($order, $data, $model->getConfigData('after_pay_status'));
        }

        return false;
    }

    /**
     * @param $orderReference
     * @return mixed
     */
    protected function loadOrderByReference($orderReference)
    {
        return Mage::getModel('sales/order')->loadByIncrementId($orderReference);
    }

    /**
     * @param $order
     * @param $data
     * @param $state
     * @return false|mixed
     */
    protected function processOrder($order, $data, $state)
    {
        $helper = Mage::helper('wayforpay_payment');
        $sign = $helper->getResponseSignature($data);

        if (!empty($data["merchantSignature"]) && $data["merchantSignature"] == $sign) {
            if ($data['transactionStatus'] == 'Approved') {
                $order->setStatus($state);
                $order->save();
            }
            return $helper->getAnswerToGateWay($order);
        }

        return false;
    }
}
