<?php
/*
 * WayForPay payment module
 */

class WayForPay_Payment_RedirectController extends Mage_Core_Controller_Front_Action {

    protected $_order;

    /**
     *
     */
    protected function _expireAjax() {
        if (!Mage::getSingleton('wayforpay_payment/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**  */
    public function indexAction() {
        $this->getResponse()
                ->setHeader('Content-type', 'text/html; charset=utf8')
                ->setBody($this->getLayout()
                ->createBlock('wayforpay_payment/redirect')
                ->toHtml());
    }

    /**
     *
     */
    public function successAction() {
        if($this->getRequest()->isPost()) {
            Mage::getSingleton('ceckout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('ceckout/onepage/success', array('_secure'=>true));
        }
        
    }

}
