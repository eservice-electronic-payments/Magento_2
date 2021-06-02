<?php
namespace EService\Payment\Model\Source;
class Currency implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray() {
		$optionArray = array();
							
		$optionArray[] = array('value' => 'AED', 'label' =>  __('United Arab Emirates dirham (AED)'));
		$optionArray[] = array('value' => 'EUR', 'label' =>  __('Euro(EUR)'));
		$optionArray[] = array('value' => 'USD', 'label' =>  __('United States dollar(USD)'));
		return $optionArray;
    }
}
