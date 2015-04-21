<?php

/**
 *
 *
 * @category
 * @package
 * @author      A.Kruzhalin <akruzhalin@divante.pl>
 * Date: 17.04.2015 22:08
 */
class WayForPay_Payment_Model_Source_Currency
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'UAH', 'label' => Mage::helper('wayforpay_payment')->__('Украинская Гривна')),
            array('value' => 'RUB', 'label' => Mage::helper('wayforpay_payment')->__('Росийский рубль')),
            array('value' => 'USD', 'label' => Mage::helper('wayforpay_payment')->__('Доллар США')),
            array('value' => 'EUR', 'label' => Mage::helper('wayforpay_payment')->__('Евро')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'UAH' => Mage::helper('wayforpay_payment')->__('Украинская Гривна'),
            'RUB' => Mage::helper('wayforpay_payment')->__('Росийский рубль'),
            'USD' => Mage::helper('wayforpay_payment')->__('Доллар США'),
            'EUR' => Mage::helper('wayforpay_payment')->__('Евро'),
        );
    }
}
