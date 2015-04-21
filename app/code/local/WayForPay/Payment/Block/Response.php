<?php
/*
 * WayForPay payment module
 */

class WayForPay_Payment_Block_Response extends Mage_Core_Block_Abstract
{

    /**
     * @return bool
     * @throws Exception
     */
    protected function _toHtml()
    {

        $helper = Mage::helper('wayforpay_payment');
        $model = Mage::getModel('wayforpay_payment/wayForPay');
        $state = $model->getConfigData('after_pay_status');
        $data = json_decode(file_get_contents("php://input"), true);

        $order = Mage::getModel('sales/order')->loadByIncrementId($data['orderReference']);
        if ($order && $order->getId()) {

            $sign = $helper->getResponseSignature($data);
            if (!empty($data["merchantSignature"]) && $data["merchantSignature"] == $sign) {
                if($data['transactionStatus'] == 'Approved') {
                    $order->setStatus($state);
                    $order->save();
                }
                return $helper->getAnswerToGateWay($order);
            }
        }
    }
}