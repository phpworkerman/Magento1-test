<?php

class IPayLinks_IpayApi_Model_Options_PaType
{
    public function toOptionArray()
    {
        return array(
        		array('value' => 'authorize', 'label' => Mage::helper('paygate')->__('Authorize Only')),
        );
    }
}
