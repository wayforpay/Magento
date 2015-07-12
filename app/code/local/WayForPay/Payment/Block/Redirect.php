<?php
/*
 * WayForPay payment module
 */

class WayForPay_Payment_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $model = Mage::getModel('wayforpay_payment/wayForPay');
        $data = $model->getFormFields();
        $state = $model->getConfigData('order_status');

        $order = $model->getQuote();
        if($order){
            $order->setStatus($state);
            $order->save();
        }

        Mage::helper('wayforpay_payment')->log(
            "{$data['fields']['orderReference']}: Redirecting to WayForPay payment page:\n" . print_r($data['fields'], true)
        );

        $html = '<form name="WayForPay" id="WayForPayForm" method="post" action="' . $model->getConfigData('url') . '">';
        foreach ($data['fields'] as $name => $value) {
            if (!is_array($value)) {
                $html .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
            } elseif (is_array($value)) {
                foreach ($value as $avalue) {
                    $html .= '<input type="hidden" name="' . $name . '[]" value="' . htmlspecialchars($avalue) . '">';
                }
            }
        }
        $html .= $data['button'];
        $html .= '</form>';
        return $html;
    }
}
