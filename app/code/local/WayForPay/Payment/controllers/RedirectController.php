<?php
/*
 * WayForPay payment module
 */

class WayForPay_Payment_RedirectController extends Mage_Core_Controller_Front_Action
{
    protected $_order;

    /**
     *
     */
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('wayforpay_payment/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**  */
    public function indexAction()
    {
        $this->getResponse()
                ->setHeader('Content-type', 'text/html; charset=utf8')
                ->setBody($this->getLayout()
                ->createBlock('wayforpay_payment/redirect')
                ->toHtml());
    }

    /**
     * User is redirected to this action from WayForPay payment page, no matter if payment is successful or not
     */
    public function returnAction()
    {
        if (!$this->getRequest()->isPost() || !$this->getRequest()->getPost('merchantSignature', false)) {
            return $this->_redirectForFailedPayment("The request is not POST or doesn't contain merchantSignature");
        }

        $postData = $this->getRequest()->getPost();
        $this->_getHelper()->log("Customer comes to the returnAction:\n" . print_r($postData, true));

        $sign = $this->_getHelper()->getResponseSignature($postData);
        if ($postData['merchantSignature'] != $sign) {
            return $this->_redirectForFailedPayment("Wrong merchantSignature ({$postData['merchantSignature']} != $sign)");
        }

        $transactionStatus = $this->getRequest()->getPost('transactionStatus', false);
        switch (strtolower($transactionStatus)) {
            case WayForPay_Payment_Model_ResponseProcessor::TRANSACTIONSTATUS_APPROVED:
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
                break;
            case WayForPay_Payment_Model_ResponseProcessor::TRANSACTIONSTATUS_DECLINED:
                return $this->_redirectForFailedPayment(
                    "Declined transactionStatus received",
                    $this->_getHelper()->__('Your payment was declined by the payment system.')
                );
                break;
            default:
                return $this->_redirectForFailedPayment("Unknown transactionStatus: $transactionStatus");
        }
    }

    /**
     * Redirect to the cart page and restore cart items. Show and log error messages.
     *
     * @param string $logMessage
     */
    protected function _redirectForFailedPayment($logMessage = '', $customerMessage = '')
    {
        $this->_restoreCart();
        $this->_getHelper()->log("Error in returnAction: $logMessage");
        Mage::getSingleton('checkout/session')->addError(
            empty($customerMessage) ? $this->_getHelper()->__('An error with your payment occurred.') : $customerMessage
        );
        $this->_redirect('checkout/cart');
    }

    /**
     * active cart back
     */
    protected function _restoreCart()
    {
        $session = $this->_getCheckoutSession();
        if ($quoteId = $session->getLastQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        return $this;
    }

    /**
     * @return WayForPay_Payment_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('wayforpay_payment');
    }

    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}