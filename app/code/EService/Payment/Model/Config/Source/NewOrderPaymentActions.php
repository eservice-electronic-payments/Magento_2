<?php

namespace EService\Payment\Model\Config\Source;

class NewOrderPaymentActions implements \Magento\Framework\Option\ArrayInterface
{
    const PAYMENT_ACTION_SALE = 'sale';
    const PAYMENT_ACTION_AUTH = 'authorization';
    /**
     * Possible payment actions
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::PAYMENT_ACTION_AUTH,
                'label' => 'Authorization',
            ],
            [
                'value' => self::PAYMENT_ACTION_SALE,
                'label' => 'Sale',
            ]
        ];
    }
}
