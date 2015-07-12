<?php
/*
 * WayForPay payment module
 */

class WayForPay_Payment_ResponseController extends Mage_Core_Controller_Front_Action {

    public function indexAction()
    {
        $this->getResponse()->setHeader('Content-type', 'text/html; charset=utf8');

        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $this->_getHelper()->log("New response received:\n" . print_r($data, true));

            $responseProcessor = Mage::getModel(
                'wayforpay_payment/responseProcessor',
                array(
                    'data' => $data,
                    'paymentMethod' => Mage::getModel('wayforpay_payment/wayForPay')
                )
            );

            $responseJson = $responseProcessor->getResponseJson();
            $this->_getHelper()->log("Answer:\n" . $responseJson);
            $this->getResponse()->setBody($responseJson);
        } catch (InvalidArgumentException $e) {
            $this->_getHelper()->log('InvalidArgumentException during response handling: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->_getHelper()->log('An exception during response handling: ' . $e->getMessage());
            Mage::logException($e);
        }
    }

    /**
     * @return WayForPay_Payment_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('wayforpay_payment');
    }
}

