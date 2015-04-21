<?php
/*
 * WayForPay payment module
 */

class WayForPay_Payment_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('WayForPay/form.phtml');

    }
}
