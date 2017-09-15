<?php

class Due_Payments_Model_Source_RailType
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => 'us',
				'label' => Mage::helper('due')->__('United States')
			),
			array(
				'value' => 'us_int',
				'label' => Mage::helper('due')->__('US + International')
			),
		);
	}
}
