<?php

/*
 * WayForPay payment module
 */

class WayForPay_Payment_Model_WayForPay extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'wayforpay_payment';

    protected $_canOrder = true;

    protected $_supportedCurrencies = array('RUB', 'EUR', 'USD', 'UAH');

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('wayforpay_payment/redirect', array('_secure' => true));
    }

    protected function _getCurrentCurrencyCode()
    {
        $currentCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        if (in_array($currentCode, $this->_supportedCurrencies)) {
            return $currentCode;
        } else {
            throw new Zend_Currency_Exception(Mage::helper('wayforpay_payment')->__("Currency $currentCode is not supported by WayForPay"));
        }
    }

    public function getFormFields()
    {
        $order_id = $this->getCheckout()->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

        try {
            // First try to use an order currency, if it's not supported - use base order currency
            $currentCurrency = $this->_getCurrentCurrencyCode();
            $grandTotal = round($order->getGrandTotal(), 2);
            $productPriceField = 'price';
        } catch (Zend_Currency_Exception $e) {
            // Try to use an order base currency
            $currentCurrency = $order->getBaseCurrencyCode();
            if (!in_array($currentCurrency, $this->_supportedCurrencies)) {
                // base currency is not supported as well
                throw new Zend_Currency_Exception(Mage::helper('wayforpay_payment')->__("Base currency $currentCurrency is not supported by WayForPay"));
            }
            $grandTotal = round($order->getBaseGrandTotal(), 2);
            $productPriceField = 'base_price';
        }

        $fields = array(
            'merchantAccount' => $this->getConfigData('merchant'),
            'orderReference' => $order_id,
            'orderDate' => strtotime($order->getCreatedAt()),
            'merchantDomainName' => Mage::app()->getRequest()->getHttpHost(),
            'merchantTransactionSecureType' => 'AUTO',
            'order_desc' => 'Order description',
            'currency' => $currentCurrency,
            'amount' => $grandTotal,
            'serviceUrl' => $this->_getServiceUrl(),
            'returnUrl' => $this->_getReturnUrl(),
            'language' => $this->getConfigData('language'),
        );

        $cartItems = $order->getAllVisibleItems();

        $productNames = array();
        $productQty = array();
        $productPrices = array();
        /** @var Mage_Sales_Model_Order_Item $_item */
        foreach ($cartItems as $_item) {
            $productNames[] = $_item->getName();
            $productPrices[] = round($_item->getData($productPriceField), 2);
            $productQty[] = (int)$_item->getQtyOrdered();
        }
        $fields['productName'] = $productNames;
        $fields['productPrice'] = $productPrices;
        $fields['productCount'] = $productQty;

        /**
         * Check phone
         */
        $phone = str_replace(array('+', ' ', '(', ')'), '', $order->getBillingAddress()->getTelephone());
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

    protected function _getReturnUrl()
    {
        return Mage::getUrl('WayForPay/redirect/return');
    }

    protected function _getServiceUrl()
    {
        return $this->getConfigData('serviceUrl')
            ? $this->getConfigData('serviceUrl')
            : Mage::getUrl('WayForPay/response');
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