<?php

/**
 *
 *
 * @category
 * @package
 * @author      A.Kruzhalin <akruzhalin@divante.pl>
 * Date: 17.04.2015 22:08
 */
class WayForPay_Payment_Model_Source_Language
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'RU', 'label' => Mage::helper('wayforpay_payment')->__('Русский')),
            array('value' => 'UA', 'label' => Mage::helper('wayforpay_payment')->__('Українська')),
            array('value' => 'EN', 'label' => Mage::helper('wayforpay_payment')->__('English')),
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
            'RU' => Mage::helper('wayforpay_payment')->__('Русский'),
            'UA' => Mage::helper('wayforpay_payment')->__('Українська'),
            'EN' => Mage::helper('wayforpay_payment')->__('English')
        );
    }
}
