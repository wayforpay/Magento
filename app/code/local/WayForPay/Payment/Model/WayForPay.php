<?php

/*
 * WayForPay payment module
 */

class WayForPay_Payment_Model_Wayforpay extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'wayforpay_payment';
    protected $_formBlockType = 'wayforpay_payment/form';

    protected $_canOrder = true;

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getFormFields()
    {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $amount = round($order->getGrandTotal(), 2);

        $fields = array(
            'merchantAccount' => $this->getConfigData('merchant'),
            'orderReference' => $order_id,
            'orderDate' => strtotime($order->getCreatedAt()),
            'merchantAuthType' => 'simpleSignature',
            'merchantDomainName' => $_SERVER['HTTP_HOST'],
            'merchantTransactionSecureType' => 'AUTO',
            'order_desc' => 'Order description',
            'amount' => $amount,
            'currency' => 'UAH',
            'serviceUrl' => $this->getConfigData('serviceUrl') ? $this->getConfigData('serviceUrl') : 'http://' . $_SERVER['HTTP_HOST'] . '/WayForPay/response/',
            'returnUrl' => $this->getConfigData('returnUrl'),
            'language' => $this->getConfigData('language'),
        );

        //TODO
        if ($this->getConfigData('currency') != 'UAH') {
//            $fields['alternativeCurrency'] = $this->getConfigData('currency');
//            $fields['alternativeAmount'] = $this->getConfigData('currency');
        }

        $cartItems = $order->getAllVisibleItems();

        $productNames = array();
        $productQty = array();
        $productPrices = array();
        foreach ($cartItems as $_item) {
            $productNames[] = $_item->getName();
            $productPrices[] = round($_item->getPrice(), 2);
            $productQty[] = (int)$_item->getQtyOrdered();
        }
        $fields['productName'] = $productNames;
        $fields['productPrice'] = $productPrices;
        $fields['productCount'] = $productQty;

        /**
         * Check phone
         */
        $phone = str_replace(array('+', ' ', '(', ')'), array('', '', '', ''), $order->getBillingAddress()->getTelephone());
        if (strlen($phone) == 10) {
            $phone = '38' . $phone;
        } elseif (strlen($phone) == 11) {
            $phone = '3' . $phone;
        }

        $fields['clientFirstName'] = $order->getCustomerFirstname();
        $fields['clientLastName'] = $order->getCustomerLastname();;
        $fields['clientEmail'] = $order->getCustomerEmail();
        $fields['clientPhone'] = $phone;
        $fields['clientCity'] = $order->getBillingAddress()->getCity();

        $fields['merchantSignature'] = Mage::helper('wayforpay_payment')->getRequestSignature($fields);

        $params = array(
            'button' => $this->getButton(),
            'fields' => $fields,
        );

        return $params;
    }

    /**
     * @return string
     */
    public function getButton()
    {
        $button = "<div style='position:absolute; top:50%; left:50%; margin:-40px 0px 0px -60px; '>" .
            "</div>" .
            "<script type=\"text/javascript\">
            setTimeout( subform, 100 );
            function subform(){ document.getElementById('WayForPayForm').submit(); }
            </script>";

        return $button;
    }

}

