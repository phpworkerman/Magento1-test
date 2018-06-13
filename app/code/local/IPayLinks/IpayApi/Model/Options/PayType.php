<?php

class IPayLinks_IpayApi_Model_Options_PayType
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'EDC', 'label' => Mage::helper('paygate')->__('EDC')),
            array('value' => 'DCC', 'label' => Mage::helper('paygate')->__('DCC')),
        );
    }
}