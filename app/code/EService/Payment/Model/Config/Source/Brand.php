<?php

namespace EService\Payment\Model\Config\Source;

class Brand implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray() {
        $optionArray = array();

        $optionArray[] = array('value' => 'evo_pl', 'label' =>  __('EVO PL'));
        $optionArray[] = array('value' => 'moneta', 'label' =>  __('MONETA'));
        $optionArray[] = array('value' => 'evo_usa', 'label' =>  __('EVO USA'));
        $optionArray[] = array('value' => 'universal_pay', 'label' =>  __('Universal Pay'));
        $optionArray[] = array('value' => 'eservice', 'label' =>  __('ESERVICE'));
        return $optionArray;
    }
}
