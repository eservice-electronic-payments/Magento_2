<?php

namespace EService\Payment\Model\Config\Source;

class DisplayMode implements \Magento\Framework\Option\ArrayInterface
{
    const DISPLAY_MODE_EMBEDDED = 'embedded';
    const DISPLAY_MODE_REDIRECT = 'redirect';
    const DISPLAY_MODE_STANDALONE = 'standalone';
    const DISPLAY_MODE_IFRAME = 'iframe';
    const DISPLAY_MODE_HOSTEDPAY = 'hostedPayPage';

    /**
     * Possible display modes.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $iframe = 1;
        $redirect = 0;
        $hostedpay = 1;
        
        $options = array();
        if($hostedpay){
            array_push($options,[
                'value' => self::DISPLAY_MODE_HOSTEDPAY,
                'label' => 'hostedPayPage',
            ]);
        }
        if($iframe){
            array_push($options,[
                'value' => self::DISPLAY_MODE_IFRAME,
                'label' => 'Iframe',
            ]);
        }
        if($redirect){
            array_push($options,[
                'value' => self::DISPLAY_MODE_REDIRECT,
                'label' => 'Redirect',
            ]);
        }
        
        return $options;
    }
}
